<?php
/*
 * Automated meals scheduling assignments
 * $Id: assignments.php,v 1.38 2014/11/03 02:16:29 willn Exp $
 */

$start = microtime(TRUE);

$options = getopt('cijsxwqu');
if (empty($options)) {
	echo <<<EOTXT
Usage:
	-c	output as CSV
	-i	output as SQL insert statements
	-j	output to json format
	-s	display schedule
	-u	only unfulfilled workers
	-w	display workers
	-x	run many combinations looking for a full allocation
	-q  quiet mode: don't display the results (used for debugging)

EOTXT;
	exit;
}

global $relative_dir;
$relative_dir = '../public/scheduling/';

require_once $relative_dir . 'globals.php';
require_once $relative_dir . 'classes/calendar.php';
error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " ");
require_once $relative_dir . 'classes/worker.php';
require_once $relative_dir . 'classes/roster.php';
require_once 'schedule.php';
require_once 'meal.php';
error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " ");

global $dbh;
global $job_key_clause;

// remove special case...
unset($all_jobs['all']);

$job_ids_clause = get_job_ids_clause();

error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " ");
// run many combinations of numbers looking for a full allocation
if (array_key_exists('x', $options)) {
	foreach(range(1, 10, 2) as $a) {
		foreach(range(1, 10, 2) as $b) {
			foreach(range(1, 10, 2) as $c) {
				$assignments = new Assignments($a, $b, $c);
				$assignments->run();

				$found = $assignments->getNumPlaceholders();
				echo "FOUND: $found A:$a, B:$b, C:$c\n";
				if ($found == 0) {
					exit;
				}
			}
		}
	}
}
else {
	$assignments = new Assignments();
	$assignments->run();
}

// output to json for integration with the report
if (array_key_exists('j', $options)) {
	$assignments->saveResults();
}

// output as SQL insert statements
if (array_key_exists('i', $options)) {
	$assignments->outputSqlInserts();
}

// output as CSV
if (array_key_exists('c', $options)) {
	$assignments->outputCSV();
}

if (!empty($options)) {
	$assignments->printResults($options);
}

$end = microtime(TRUE);
echo "elapsed time: " . ($end - $start) . "\n";

class Assignments {
	public $roster;
	public $schedule;

	/**
	 * Construct a new Assignments object.
	 *
	 * @param[in] a int the hobart_factor weight.
	 * @param[in] b int avail_factor weight.
	 * @param[in] c avoids_factor weight.
	 */
	public function __construct($a=NULL, $b=NULL, $c=NULL) {
		$this->roster = new Roster();

		$this->schedule = new Schedule();
		$this->schedule->setPointFactors($a, $b, $c);
		$this->schedule->setRoster($this->roster);

		$this->roster->setSchedule($this->schedule);
	}

	/**
	 * Run the assignments
	 */
	public function run() {
		// load the dates and shifts needed
		$this->schedule->initializeShifts();
		$this->roster->loadNumShiftsAssigned();
		$this->roster->loadRequests();
		$this->loadPrefs();

		$this->makeAssignments();
	}

	/**
	 * Load the shift-based survey preferences for each worker, and add their
	 * scheduling preferences.
	 * XXX: maybe this method could be moved to Roster?
	 */
	public function loadPrefs() {
		global $dbh;

		// load worker preferences per shift / date
		$sql = <<<EOSQL
			SELECT s.string as date, s.job_id, a.username, p.pref
				FROM auth_user as a, schedule_prefs as p, schedule_shifts as s
				WHERE p.pref>0
					AND a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY date ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;

		$count = 0;
		foreach($dbh->query($sql) as $row) {
			$u = $row['username'];
			$d = $row['date'];
			$ji = $row['job_id'];
			$p = $row['pref'];

			// only add jobs which appear in the schedule
			if ($this->schedule->addPrefs($u, $ji, $d, $p)) {
				$this->roster->addPrefs($u, $ji, $d, $p);
			}

			$count++;
		}

		$this->initNonResponerPrefs();
	}

	/**
	 * This examines the overrides for those who have not taken the survey -
	 * not just those folks who were in the database tobegin with.
	 */
	public function initNonResponerPrefs() {
		$slackers = $this->roster->getNonResponderNames();
		sort($slackers);

		$this->schedule->addNonResponderPrefs($slackers);
		$this->roster->addNonResponderPrefs($slackers);
	}


	/**
	 * Sort the dates and workers' availabilities then make assignments.
	 */
	public function makeAssignments() {
		global $all_jobs;

		foreach(array_keys($all_jobs) as $j) {
			$this->schedule->setJobId($j);
			$this->roster->setJobId($j);
			$this->schedule->sortPossibleRatios();

			// keep assigning until all the meals have been assigned
			$success = TRUE;
			while (!$this->schedule->isFinished() && $success) {
				$worker_freedom = $this->roster->sortAvailable();
				$success = $this->schedule->fillMeal($worker_freedom);
			}
		}
	}

	/**
	 * Save the results to a json file which can be used to...
	 */
	public function saveResults() {
		$assn = $this->schedule->getAssigned();
		$json = json_encode($assn);
		global $json_assignments_file;
		file_put_contents('../public/' . $json_assignments_file, $json);
	}


	/**
	 *
	 */
	public function getNumPlaceholders() {
		return $this->schedule->getNumPlaceholders();
	}


	/**
	 * Display the results on the screen
	 */
	public function printResults($options) {
		$display_schedule = array_key_exists('s', $options);
		if ($display_schedule) {
			$this->schedule->printResults();
		}

		$display_workers = array_key_exists('w', $options);
		if ($display_workers) {
			$only_unfilled_workers = array_key_exists('u', $options);
			$this->roster->printResults($only_unfilled_workers);
		}
	}


	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputSqlInserts() {
		$this->schedule->printResults('sql');
	}


	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputCSV() {
		$this->schedule->printResults('csv');
	}
}
?>
