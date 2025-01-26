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
			if ($job_id === ALL_ID) {
				continue;
			}
			$job_name = get_job_name($job_id);
			if (DEBUG_ASSIGNMENTS) {
				error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ .
					" job id:{$job_id} {$job_name}");
			}

			$this->schedule->setJobId($job_id);
			$this->schedule->initPlaceholderCount($job_id);
			$this->roster->setJobId($job_id);
			$this->schedule->sortPossibleRatios();
			$work_avail_ratio = $this->schedule->getPossibleRatios();

			// keep assigning until all the meals have been assigned
			$success = TRUE;
			while (!$this->schedule->isFinished() && $success) {
				$worker_freedom = $this->roster->sortAvailable();
				$success = $this->schedule->fillMeal($worker_freedom);
			}

			if (DEBUG_FIND_CANCEL_MEALS) {
				$count = $this->schedule->getPlaceholderCount($job_id);
				error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ .
					" Placeholder count {$count} for {$job_name}");
				if (($count != 0) && !empty($work_avail_ratio)) {
					error_log(__FILE__ . ' ' . __LINE__ .
						" ratios for {$job_name} (<1 is bad): " .
						var_export($work_avail_ratio, TRUE));
				}
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

	/**
	 * Find the ratio of available labor to full number of shifts being sought,
	 * and uncover the least available meal dates for cancellation.
	 */
	public function findCancelDates() {
		# look up the full calendar of potential dates
		$shifts_needed = $this->calendar->getAssignmentsNeededForCurrentSeason();

		# look at the available labor
		$this->roster->loadNumShiftsAssigned();
		$labor_available = $this->roster->getTotalLaborAvailable();

		$this->run();

		$labor_shortfall = [];
		$shifts_per_meal = [];
		// figure out how much labor is available and needed for each job
		foreach($shifts_needed as $job_id => $shifts_possible) {
			if ($labor_available[$job_id] < 1) {
				continue;
			}
			echo "job id:{$job_id} shifts poss:{$shifts_possible} labor:{$labor_available[$job_id]}\n";

			$jobs = get_weekday_jobs();
			$type = get_meal_type_by_job_id($job_id);
			if (is_a_brunch_job($job_id)) {
				$jobs = get_brunch_jobs();
			}
			else if (is_a_mtg_night_job($job_id)) {
				$jobs = get_mtg_jobs();
			}
			$labor_shortfall[$type][$job_id] = ($shifts_possible -
				$labor_available[$job_id]);
			$shifts_per_meal[$type][$job_id] =
				get_num_workers_per_job_per_meal($job_id);
		}
		echo <<<EOM

Labor Shortfall:
* A positive number shows a deficit of labor, counting the number of shifts
  that do not have enough labor to fill.
* A negative number means that there is a surplus of labor
------------------------------------------------------------

EOM;
		echo print_r($labor_shortfall, TRUE);
		# echo "shifts per meal: " . print_r($shifts_per_meal, TRUE);

		/*
		#!# Figure out how many meals to cancel here... based on
		number of "unavailable" meals and the number of istances each shift
		type can support.
		*/

		$date_points = [];
		foreach($shifts_needed as $job_id => $shifts_possible) {
			$type = get_meal_type_by_job_id($job_id);

			$this->schedule->setJobId($job_id);
			$this->schedule->initPlaceholderCount($job_id);
			$this->schedule->sortPossibleRatios();
			$work_avail_ratio = $this->schedule->getPossibleRatios();
			if (empty($work_avail_ratio)) {
				continue;
			}

			# echo "least possible {$job_id}: " . print_r( $work_avail_ratio, true );
			foreach($work_avail_ratio as $date => $ratio) {
				if (!isset($date_points[$type][$date])) {
					// initialize
					$date_points[$type][$date] = $ratio;
				}
				else {
					// use the smallest ratio
					$date_points[$type][$date] =
						min($date_points[$type][$date], $ratio);
				}
			}
		}

		// sort component arrays
		$sorted_date_points = [];
		foreach($date_points as $key => $sub) {
			asort($sub);
			$sorted_date_points[$key] = $sub;
		}
		echo <<<EOM

Labor & calendar availability per date:
For each date, display the ratio of the amount of available labor for that meal
for the number of shifts which need to be filled for it. This combines the
ratio for each of the shift types into one sortable number. If we need to
cancel 1 or more meals, this can help pick which ones. Anything which is <1.0
does not have enough labor.
-------------------------------------------------------------

EOM;
		echo print_r( $sorted_date_points, true );

		return NULL;
	}
}
?>
