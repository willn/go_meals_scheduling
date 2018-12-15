<?php
global $relative_dir;
require_once $relative_dir . 'utils.php';

global $dbh;

// -----------------------------------
class Roster {
	protected $workers = [];
	protected $job_id;
	protected $dbh;
	protected $num_shifts_per_season = 0;

	// job_id => username => counts
	protected $least_available = array();

	protected $schedule;

	protected $total_labor_avail = array();

	protected $requests = array();


	public function __construct() {
		global $dbh;
		$this->dbh = $dbh;

		global $all_jobs;
		foreach(array_keys($all_jobs) as $job_id) {
			$this->total_labor_avail[$job_id] = 0;
		}

		$this->setShifts(SEASON_NAME);
	}

	/**
	 * Get how many "shifts" per season there should be for the current season.
	 */
	public function setShifts($season_name=NULL) {
		$current_season = NULL;
		if (is_null($season_name)) {
			$current_season = get_current_season_months();
		}
		else {
			$current_season = get_current_season_months($season_name);
		}

		if (empty($current_season)) {
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ .
				" no months assigned to current season: " . SEASON_NAME);
		}
		$this->num_shifts_per_season = count($current_season);
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
			SELECT a.username, c.avoids, c.prefers, c.clean_after_self,
				c.bunch_shifts, c.bundle_shifts
			FROM {$auth_user_table} as a, {$comments_table} as c
			WHERE c.worker_id=a.id
			ORDER BY a.username, c.timestamp
EOSQL;
		foreach($this->dbh->query($sql) as $row) {
			$w = $this->getWorker($row['username']);
			if (is_null($w)) {
				echo "null worker {$row['username']} when loading requests\n";
				continue;
			}

			if (!empty($row['avoids'])) {
				$w->setAvoids(explode(',', $row['avoids']));
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
	 * @param[in] job_id int the ID for the current job being processed.
	 */
	public function setJobId($job_id) {
		$this->job_id = $job_id;
	}


	/**
	 * Add shift preferences for a worker. If the worker doesn't exist yet,
	 * create an entry, then add their availability preferences.
	 *
	 * @param[in] username string - the username.
	 * @param[in] job_id int the ID of the shift/job.
	 * @param[in] date string the date of the preference.
	 * @param[in] pref int the preference rating for this shift.
	 */
	public function addPrefs($username, $job_id, $date, $pref=NULL) {
		if (!array_key_exists($username, $this->workers)) {
			// echo "Worker {$username} doesn't have any shifts assigned\n";
			return;
		}

		$w = $this->getWorker($username);
		$w->addAvailability($job_id, $date, $pref);
	}


	/**
	 * Add default preferences for those who haven't responded to the survey.
	 *
	 * @param[in] slackers array list of usernames who haven't responded.
	 */
	public function addNonResponderPrefs($slackers) {
		$dates_by_shift = $this->schedule->getDatesByShift();
		// $first_half_dates_by_shift = $this->schedule->getFirstHalfDatesByShift();
		// $second_half_dates_by_shift = $this->schedule->getSecondHalfDatesByShift();

		foreach($slackers as $username) {
			$w = $this->getWorker($username);
			if (is_null($w)) {
				echo "worker $u is null, they don't have shifts assigned\n";
				exit;
			}
			$w->addNonResponsePrefs($dates_by_shift);
		}
	}


	/**
	 * Sort the available workers to see who has the tightest availabilty, and
	 * schedule them first.
	 *
	 * @return array list of workers sorted by schedule availability
	 */
	public function sortAvailable() {
		$j = $this->job_id;
		$this->least_available = [];

		foreach($this->workers as $u=>$w) {
			$avail = $w->getNumAvailableShiftsRatio($j);
			if ($avail == 0) {
				continue;
			}

			$this->least_available[$u] = $avail;
		}

		// need to assign a placeholder for manual fixing later
		if (empty($this->least_available)) {
			$name = get_job_name($j);
			echo "Ran out of workers, using a placeholder for {$name}\n";
		}

		asort($this->least_available);
		return $this->least_available;
	}


	/**
	 * Find out how many shifts each worker is assigned, and set that value for
	 * each Worker object.
	 */
	public function loadNumShiftsAssigned($username=NULL) {
		$this->loadNumShiftsAssignedFromDatabase($username);
		$this->loadNumShiftsAssignedFromOverrides($username);
	}

	public function loadNumShiftsAssignedFromDatabase($username=NULL) {
		$dinners_per_job = get_num_dinners_per_assignment(
			get_current_season_months());

		$job_ids_clause = get_job_ids_clause();
		$user_clause = is_null($username) ? '' :
			"AND u.username='{$username}'";

		// set the number of shifts per assigned worker
		$sid = SEASON_ID;
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

		$count = 0;
		foreach($this->dbh->query($sql) as $row) {
			$count++;

			$username = $row['username'];
			$job_id = $row['job_id'];
			$worker = $this->getWorker($username);

			// determine the number of shifts across the season
			$num_instances = isset($dinners_per_job[$job_id]) ?
				($row['instances'] * $dinners_per_job[$job_id]) : 
				($row['instances'] * $this->num_shifts_per_season);
			$worker->addNumShiftsAssigned($job_id, $num_instances);
			$this->total_labor_avail[$job_id] += $num_instances;
		}

		return TRUE;
	}

	/**
	 * Get the number of shifts a worker has been assigned from the
	 * overrides list, and add those to the list of shifts assigned.
	 *
	 * @param[in] username string the name of the user viewing the survey.
	 */
	protected function loadNumShiftsAssignedFromOverrides($username=NULL) {
		global $all_jobs;
		$num_shift_overrides = get_num_shift_overrides();

		// set the number of shifts in overrides - additional shift volunteers
		// if limited to one username, then don't load them all...
		$shift_overrides = $num_shift_overrides;

		if ($username) {
			// XXX why is this being overridden?
			$shift_overrides = array();
			if (isset($num_shift_overrides[$username])) {
				$shift_overrides = array($username => $num_shift_overrides[$username]);
			}
		}

		foreach($shift_overrides as $or_username => $jobs) {
			$worker = $this->getWorker($or_username);

			foreach($jobs as $job_id=>$instances) {
				if (!isset($all_jobs[$job_id])) {
					echo "Could not find job ID: $job_id\n";
					continue;
				}
				$worker->addNumShiftsAssigned($job_id, $instances);
				$this->total_labor_avail[$job_id] += $instances;
			}
		}
	}


	/**
	 * Get the worker object based on username.
	 * (Schedule)
	 *
	 * @return Worker object.
	 */
	public function getWorker($username) {
		$w = array_get($this->workers, $username);
		if (is_null($w)) {
			$w = $this->addWorker($username);
		}
		return $w;
	}

	/**
	 * #!#
	 */
	public function addWorker($username) {
		$w = new Worker($username);
		$this->workers[$username] = $w;
		return $w;
	}

	/**
	 * Get a list of names of the people who did not respond to the survey.
	 * @return array of usernames.
	 */
	public function getNonResponderNames() {
		$list = array();
		foreach($this->workers as $u=>$w) {
			if (!$w->hasResponded()) {
				$list[] = $u;
			}
		}

		return $list;
	}

	public function getAllAvoids() {
		$out = array();
		foreach($this->workers as $w) {
			$avoids = $w->getAvoids();
			if (empty($avoids)) {
				continue;
			}

			$out[$w->getUsername()] = $avoids;
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
     * @param[in] only_unfilled_workers boolean if true, then only display the
     *     workers and their jobs which have unfilled shifts.
	 */
	public function printResults($only_unfilled_workers=FALSE) {
		global $all_jobs;
		$num_jobs_assigned = array();
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
