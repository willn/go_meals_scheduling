<?php
global $relative_dir;
$relative_dir = '../public/';

require_once $relative_dir . 'globals.php';
require_once $relative_dir . 'classes/calendar.php';
require_once $relative_dir . 'classes/worker.php';
require_once $relative_dir . 'classes/roster.php';
require_once $relative_dir . 'classes/meal.php';
require_once 'schedule.php';

global $job_key_clause;

class Assignments {
	public $roster;
	public $schedule;
	public $calendar;

	/**
	 * Construct an Assignments object.
	 */
	public function __construct() {
		$this->roster = new Roster();
		$this->initialize();
	}

	/**
	 * Set up pieces before an assignment run takes place.
	 *
	 * @param array $season_months array listing strings of each month to use.
	 */
	public function initialize($season_months=[]) {
		if (empty($season_months)) {
			$season_months = get_current_season_months();
		}

		$this->schedule = new Schedule();
		$this->schedule->setRoster($this->roster);

		$this->roster->setSchedule($this->schedule);

		$this->calendar = new Calendar($season_months);
	}

	/**
	 * Run the assignments
	 */
	public function run() {
		// load the dates and shifts needed

		$this->calendar->disableWebDisplay();
		$dates = $this->calendar->evalDates();
		$this->schedule->initializeShifts($dates);
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
		$mysql_api = get_mysql_api();

		// load worker preferences per shift / date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT s.date_shift_string as date, s.job_id, a.username, p.pref
				FROM {$auth_user_table} as a, {$prefs_table} as p,
					{$shifts_table} as s
				WHERE p.pref>0
					AND a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY date ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;

		$count = 0;
		foreach($mysql_api->get($sql) as $row) {
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

		$slackers = $this->roster->getNonResponderNames();
		$this->initNonResponderPrefs($slackers);
	}

	/**
	 * This examines the overrides for those who have not taken the survey -
	 * not just those folks who were in the database tobegin with.
	 *
	 * @param array $slackers list of usernames of people who have not
	 *     submitted their survey.
	 */
	public function initNonResponderPrefs($slackers) {
		sort($slackers);

		$this->schedule->addNonResponderPrefs($slackers);
		$this->roster->addNonResponderPrefs($slackers);
	}


	/**
	 * Sort the dates and workers' availabilities then make assignments.
	 */
	public function makeAssignments() {
		$all_jobs = get_all_jobs();

		// iterate through each of the job types and make assignments for each
		foreach(array_keys($all_jobs) as $job_id) {
			$this->schedule->setJobId($job_id);
			$this->roster->setJobId($job_id);
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
		file_put_contents('../public/' . JSON_ASSIGNMENTS_FILE, $json);
	}


	/**
	 *
	 */
	public function getNumPlaceholders() {
		return $this->schedule->getNumPlaceholders();
	}

	/**
	 * XXX Get the results of the run
	public function getResults() {
		// return $this->schedule
	}
	 */


	/**
	 * Display the assigned schedule.
	 */
	public function printSchedule($options) {
		$this->schedule->printResults();
	}

	/**
	 * Display the list of workers and how they're allocated.
	 */
	public function printWorkers($options) {
		$only_unfilled_workers = array_key_exists('u', $options);
		$this->roster->printResults($only_unfilled_workers);
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

	/**
	 * Output the schedule as a series of SQL insert statements
	 */
	public function outputGatherImports() {
		$this->schedule->printResults('gather_csv');
	}
}
?>
