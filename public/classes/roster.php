<?php
global $relative_dir;
require_once $relative_dir . 'utils.php';
require_once $relative_dir . 'classes/worker.php';

global $dbh;

// -----------------------------------
class Roster {
	protected $workers = [];
	protected $gather_ids = [];

	protected $job_id;
	protected $dbh;
	protected $num_shifts_per_season = 0;

	// job_id => username => counts
	protected $least_available = [];

	protected $schedule;

	protected $total_labor_avail = [];

	protected $requests = [];


	public function __construct() {
		global $dbh;
		$this->dbh = $dbh;
		if (is_null($dbh)) {
			$this->dbh = create_sqlite_connection();
		}

		$this->initLaborCount();

		$this->setShifts(SEASON_NAME);
	}

	/**
	 * Initialize the total_labor_avail array with zeroes for each job ID.
	 */
	public function initLaborCount() {
		$this->total_labor_avail = [];

		$all_jobs = get_all_jobs();
		// initialize all jobs to zeroes
		foreach(array_keys($all_jobs) as $job_id) {
			$this->total_labor_avail[$job_id] = 0;
		}
	}

	/**
	 * Get how many "shifts" per season there should be for the current season.
	 */
	public function setShifts($season_name=NULL) {
		$months = is_valid_season_name($season_name) ?
			get_current_season_months($season_name) :
			get_current_season_months();

		if (empty($months)) {
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ .
				" no months assigned to current season: " . SEASON_NAME);
		}
		$this->num_shifts_per_season = count($months);
	}


	public function setSchedule($schedule) {
		$this->schedule = $schedule;
	}


	/**
	 * Load the special requests made for each worker.
	 */
	public function loadRequests() {
		$comments_table = SCHEDULE_COMMENTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.username, c.avoids as avoid_workers, c.prefers,
				c.clean_after_self, c.bunch_shifts, c.bundle_shifts
			FROM {$auth_user_table} as a, {$comments_table} as c
			WHERE c.worker_id=a.id
			ORDER BY a.username, c.timestamp
EOSQL;
		foreach($this->dbh->query($sql) as $row) {
			$w = $this->getWorker($row['username']);
			if (!empty($row['avoid_workers'])) {
				$w->setAvoids(explode(',', $row['avoid_workers']));
			}
			if (!empty($row['prefers'])) {
				$w->setPrefers(explode(',', $row['prefers']));
			}

			$req = array(
				'clean_after_self' => $row['clean_after_self'],
				'bunch_shifts' => $row['bunch_shifts'],
				'bundle_shifts' => ($row['bundle_shifts'] == 'on'),
			);
			$w->setRequests($req);
		}
	}


	/**
	 * Set the job ID and cut down on the number of parameters passed.
	 * @param int $job_id the ID for the current job being processed.
	 */
	public function setJobId($job_id) {
		$this->job_id = $job_id;
	}


	/**
	 * Add shift preferences for a worker. If the worker doesn't exist yet,
	 * create an entry, then add their availability preferences.
	 *
	 * @param string $username - the username.
	 * @param int $job_id the ID of the shift/job.
	 * @param string $date the date of the preference.
	 * @param int $pref the preference rating for this shift.
	 */
	public function addPrefs($username, $job_id, $date, $pref=NULL) {
		if (!array_key_exists($username, $this->workers)) {
			// echo "Worker {$username} doesn't have any shifts assigned\n";
			return;
		}

		$worker = $this->getWorker($username);
		$worker->addAvailability($job_id, $date, $pref);
	}


	/**
	 * Add default preferences for those who haven't responded to the survey.
	 *
	 * @param array $slackers list of usernames who haven't responded.
	 */
	public function addNonResponderPrefs($slackers) {
		$dates_by_shift = $this->schedule->getDatesByShift();

		foreach($slackers as $username) {
			$worker = $this->getWorker($username);
			$worker->addNonResponsePrefs($dates_by_shift);
		}
	}


	/**
	 * Sort the available workers to see who has the tightest availabilty, and
	 * schedule them first.
	 *
	 * @return array list of workers sorted by schedule availability
	 */
	public function sortAvailable() {
		$this->least_available = [];

		foreach($this->workers as $username=>$worker) {
			$avail = $worker->getNumAvailableShiftsRatio($this->job_id);
			if ($avail == 0) {
				continue;
			}

			$this->least_available[$username] = $avail;
		}

		// need to assign a placeholder for manual fixing later
		if (empty($this->least_available)) {
			$name = get_job_name($this->job_id);
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . 
				" Ran out of workers, using a placeholder for {$name}");
		}

		// Sort the list of workers by username (key)
		asort($this->least_available);
		return $this->least_available;
	}


	/**
	 * Find out how many shifts each worker is assigned, and set that value for
	 * each Worker object.
	 */
	public function loadNumShiftsAssigned($username=NULL) {
		$this->initLaborCount();
		$this->loadNumMealsFromDatabase($username);
		$this->loadNumMealsFromOverrides($username);
	}

	/**
	 * Load the number of meals from the database.
	 * This converts the "assignment bundles" to the number of actual
	 * meals per person and applies the SUB_SEASON_FACTOR (to cut long
	 * seasons in half) if needed.
	 *
	 * @param string $username (optional defaults to NULL). The name
	 *     of the worker to filter by. If not set, then get all.
	 */
	public function loadNumMealsFromDatabase($username=NULL) {
		$meals_per_job = get_num_meals_per_assignment(
			get_current_season_months(), NULL, SUB_SEASON_FACTOR);

		$job_ids_clause = get_job_ids_clause();
		$user_clause = is_null($username) ? '' :
			"AND u.username='{$username}'";

		// set the number of shifts per assigned worker
		$sid = get_season_id();
		$assn_table = ASSIGN_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
		SELECT u.username, a.job_id, a.instances
			FROM {$assn_table} as a, {$auth_user_table} as u
			WHERE a.season_id={$sid}
				AND a.type="a"
				AND a.worker_id = u.id
				AND ({$job_ids_clause})
				{$user_clause}
			ORDER BY u.username
EOSQL;

		$results = $this->dbh->query($sql);
		foreach($results as $row) {
			$username = $row['username'];
			$job_id = $row['job_id'];

			// XXX ought to add a unit test for when the username doesn't appear in db
			$worker = $this->getWorker($username);

			// determine the number of shifts across the season
			if (!isset($meals_per_job[$job_id])) {
				error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " unable to find job id :{$job_id} in meals_per_job:" . var_export($meals_per_job, TRUE));
				exit;
			}
			$num_instances = isset($meals_per_job[$job_id]) ?
				($row['instances'] * $meals_per_job[$job_id]) :
				($row['instances'] * $this->num_shifts_per_season);
			$worker->addNumShiftsAssigned($job_id, $num_instances);
			$this->total_labor_avail[$job_id] += $num_instances;
		}
	}

	/**
	 * Load the Gather IDs per each username.
	 *
	 * @return array of gather IDs, otherwise an empty array.
	 */
	public function loadGatherIDs() {
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
		SELECT username, gather_id
			FROM {$auth_user_table}
			WHERE gather_id != ''
			ORDER BY username
EOSQL;

		$results = $this->dbh->query($sql);
		if (empty($results)) {
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " Can't load gather IDs");
			return [];
		}
		foreach($results as $row) {
			$username = $row['username'];
			$this->gather_ids[$username] = $row['gather_id'];
		}

		return $this->gather_ids;
	}

	/**
	 * Get the number of meals a worker has been assigned from the
	 * overrides list, and add those to the list of shifts assigned.
	 *
	 * @param string $username the name of the user viewing the survey.
	 * @param array $num_shift_overrides username => array(job_id => num_meals)
	 */
	public function loadNumMealsFromOverrides($username=NULL,
		$num_shift_overrides=[]) {

		$all_jobs = get_all_jobs();
		if (empty($num_shift_overrides)) {
			$num_shift_overrides = get_num_shift_overrides();
		}

		// set the shifts in overrides - additional shift volunteers
		$shift_overrides = $num_shift_overrides;

		// if request is for a single user, instead of all
		if ($username) {
			// filter results for this specific user
			$shift_overrides = [];
			if (isset($num_shift_overrides[$username])) {
				$shift_overrides = [$username => $num_shift_overrides[$username]];
			}
		}

		foreach($shift_overrides as $or_username => $jobs) {
			$worker = $this->getWorker($or_username);

			foreach($jobs as $job_id=>$num_instances) {
				if (!isset($all_jobs[$job_id])) {
					echo "Could not find job ID: $job_id\n";
					continue;
				}
				$worker->addNumShiftsAssigned($job_id, $num_instances);
				$this->total_labor_avail[$job_id] += $num_instances;
			}
		}
	}

	/**
	 * Get the total labor available.
	 * These numbers reflect the total number of meal events, i.e. shifts.
	 *
	 * @return array associative where the keys are the job IDs
	 *    (including 'all') and the values are the number of times
	 *    that the workers have cumulatively been assigned to work for
	 *    these jobs.
	 */
	public function getTotalLaborAvailable() {
		return $this->total_labor_avail;
	}

	/**
	 * Get the list of workers and their number of shifts to fill.
	 *
	 * @return array associative where keys are usernames and values
	 *     are an associative array of job ids -> number of shifts to fill.
	 *     Example:
     *     'someone' => [
	 *         4596 => 7,
	 *         4594 => 1,
	 *     ],
	 */
	public function getWorkerShiftsToFill() {
		return array_map(
			function($worker) { return $worker->getNumShiftsToFill(); },
			$this->workers);
	}


	/**
	 * Get the worker object based on username.
	 *
	 * @return Worker object.
	 */
	public function getWorker($username) {
		$worker = array_get($this->workers, $username);
		if (is_null($worker)) {
			$worker = $this->addWorker($username);
		}
		return $worker;
	}

	/**
	 * Given a username, instantiate a new Worker object and add it to the list.
	 * The workers list is an associative array, where the keys are
	 * usernames and the values are Worker objects.
	 *
	 * @param string $username the username for the worker.
	 * @return Worker object, the corresponding Worker object.
	 */
	public function addWorker($username) {
		$worker = new Worker($username);
		$this->workers[$username] = $worker;
		return $worker;
	}

	/**
	 * Get the list of workers who exist in the Roster.
	 * NOTE: Currently exists for unit testing.
	 */
	public function getWorkers() {
		return $this->workers;
	}

	/**
	 * Get a list of names of the people who did not respond to the survey.
	 * @return array of usernames.
	 */
	public function getNonResponderNames() {
		$list = [];
		foreach($this->workers as $u=>$w) {
			if (!$w->hasResponded()) {
				$list[] = $u;
			}
		}

		return $list;
	}

	/**
	 * Get the list of all worker avoids.
	 *
	 * @return array username -> array of names they want to avoid.
	 */
	public function getAllAvoids() {
		$out = [];
		foreach($this->workers as $w) {
			$avoid_workers = $w->getAvoids();
			if (empty($avoid_workers)) {
				continue;
			}

			$out[$w->getUsername()] = $avoid_workers;
		}

		return $out;
	}

	/**
	 * Accessor for number of shifts per season.
	 * Mainly for unit testing.
	 */
	public function getNumShiftsPerSeason() {
		return $this->num_shifts_per_season;
	}

	/**
	 * Display the assignments for each worker
     * @param bool $only_unfilled_workers if true, then only display the
     *     workers and their jobs which have unfilled shifts.
	 */
	public function printResults($only_unfilled_workers=FALSE) {
		$all_jobs = get_all_jobs();
		$num_jobs_assigned = [];
		foreach(array_keys($all_jobs) as $job_id) {
			$num_jobs_assigned[$job_id] = 0;
		}

		ksort($this->workers);
		foreach($this->workers as $username=>$w) {
			$job_counts = $w->printResults($only_unfilled_workers);

			// only give a summary of unfilled workers if all are displayed
			if (!$only_unfilled_workers) {
				foreach($job_counts as $job_id=>$count) {
					$num_jobs_assigned[$job_id] += $count;
				}
			}
		}

		foreach($num_jobs_assigned as $job_id => $num_assn) {
			$diff = ($this->total_labor_avail[$job_id] - $num_assn);
			if ($diff != 0) {
				echo <<<EOTXT
REMAINING AVAILABLE SHIFTS FOR {$job_id} ({$all_jobs[$job_id]}): {$diff}

EOTXT;
			}
		}
	}
}

?>
