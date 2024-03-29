<?php
set_include_path('../' . PATH_SEPARATOR . '../public/');
require_once 'mysql_api.php';

class Worker {
	# this is an arbitrary amount
	const ADJACENCY_LIMIT = 8;

	protected $id;
	protected $username;
	protected $first_name;
	protected $last_name;

	// job_id => date => pref
	protected $avail_shifts = [];

	// job_id => array(dates)
	protected $assigned = [];

	// job_id => count
	protected $num_shifts_to_fill = [];

	protected $requests = [];

	protected $avoid_workers = [];
	protected $prefers = [];

	protected $tasks = [];
	protected $comments;

	protected $mysql_api;

	/**
	 *
	 */
	public function __construct($username) {
		$this->username = $username;

		$this->mysql_api = get_mysql_api();
	}

	public function debugLogSummary() {
		$nsf = print_r($this->num_shifts_to_fill, true);

		error_log('debugLogSummary: ' . __CLASS__ . ' ' . __FUNCTION__ . ' ' .
			__LINE__ . "
			Username:{$this->username},
			id:{$this->id}
			NSF:{$nsf}");
	}

	public function setNames($first, $last) {
		$this->first_name = $first;
		$this->last_name = $last;
	}

	/**
	 * Get the name of the worker - either their full name, or their username
	 * if their full name is not set.
	 *
	 * @return string "first last" OR username
	 */
	public function getName() {
		return ($this->first_name != '') ?
			"{$this->first_name} {$this->last_name}" :
			$this->username;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Set the list of people to avoid assignments with.
	 * @param array $avoid_workers listing of usernames to avoid assignments.
	 */
	public function setAvoids($avoid_workers) {
		if ($avoid_workers == '') {
			return;
		}
		$this->avoid_workers = $avoid_workers;
	}

	/**
	 * Get the list of people this worker wants to avoid working with.
	 * @return array list of usernames.
	 */
	public function getAvoids() {
		return $this->avoid_workers;
	}

	/**
	 * Set the list of people to prefer assignments with.
	 * @param array $prefers listing of usernames to prefer assignments.
	 */
	public function setPrefers($prefers) {
		$this->prefers = $prefers;
	}

	/**
	 * Get the list of people this worker prefers working with.
	 * @return array list of usernames.
	 */
	public function getPrefers() {
		return $this->prefers;
	}

	/**
	 * Get a list of all the preferences.
	 */
	public function getAllPreferences() {
		$all = [];
		$avoid_workers = $this->getAvoids();
		if (!empty($avoid_workers)) {
			$all['avoid_workers'] = $avoid_workers;
		}

		$prefers = $this->getPrefers();
		if (!empty($prefers)) {
			$all['prefers'] = $prefers;
		}

		if (!empty($this->requests['clean_after_self'])) {
			$all['clean_after_self'] = $this->requests['clean_after_self'];
		}

		if (!empty($this->requests['bunch_shifts'])) {
			$all['bunch_shifts'] = $this->requests['bunch_shifts'];
		}

		return empty($all) ? '' : $all;
	}

	/**
	 * Set the number of shifts assigned to a worker per job.
	 *
	 * @param int $job_id the unique ID number for the job / shift being
	 *     assigned.
	 * @param int $instances number of instances this worker
	 *     has assigned of this shift over the entire season
	 */
	public function addNumShiftsAssigned($job_id, $instances) {
		// if first of kind, initialize array key
		if (!isset($this->num_shifts_to_fill[$job_id])) {
			$this->num_shifts_to_fill[$job_id] = $instances;
			return;
		}

		// add shifts if they were already set (can be negative)
		$this->num_shifts_to_fill[$job_id] += $instances;
	}


	/**
	 * Get which shifts this worker has been assigned to work.
	 * @return array listing dates this worker has been assigned so far.
	 */
	public function getAssignedShifts() {
		return array_keys($this->num_shifts_to_fill);
	}

	/**
	 * Get the number of shifts this worker needs to fill.
	 */
	public function getNumShiftsToFill() {
		return $this->num_shifts_to_fill;
	}


	/**
	 * Set the requests for this worker.
	 * @param array $requests list of key => value pairs.
	 */
	public function setRequests($requests) {
		$this->requests = $requests;
	}

	/**
	 * Add an available date, tied to a shift.
	 *
	 * @param int $job_id the ID of the shift
	 * @param string $date date of availability
	 * @param float $pref the preference level of the worker (e.g. OK_PREF).
	 */
	public function addAvailability($job_id, $date, $pref) {
		if (!isset($this->avail_shifts[$job_id])) {
			$this->avail_shifts[$job_id] = [];
		}

		$this->avail_shifts[$job_id][$date] = $pref;
	}

	/**
	 * Get the list of availability. Used for unit tests.
	 */
	public function getAvailability() {
		return $this->avail_shifts;
	}


	/**
	 * Add default preferences for this worker who didn't respond to the
	 * survey.
	 *
	 * @param array $dates_by_shift XXX
	 */
	public function addNonResponsePrefs($dates_by_shift) {
		foreach($this->num_shifts_to_fill as $job_id=>$instances) {
			// sanity check
			if (!isset($dates_by_shift[$job_id])) {
				echo "shift {$job_id} doesn't have any dates!\n";
				exit;
			}

			foreach($dates_by_shift[$job_id] as $date) {
				$this->addAvailability($job_id, $date, NON_RESPONSE_PREF);
			}
		}
	}

	/**
	 * Get the worker's username
	 */
	public function getUsername() {
		return $this->username;
	}


	/**
	 * Find out how many open slots this worker needs to fill yet for a given
	 * job ID.
	 */
	public function getNumShiftsOpen($job_id) {
		if (!isset($this->num_shifts_to_fill[$job_id])) {
			return 0;
		}

		$num_assigned = isset($this->assigned[$job_id]) ?
			count($this->assigned[$job_id]) : 0;
		return $this->num_shifts_to_fill[$job_id] - $num_assigned;
	}


	/**
	 * Get the ratio of this worker's availability / number of shifts they need
	 * to fill yet.
	 *
	 * @param int $job_id the number of the shift currently being assigned.
	 * @return float a ratio of this worker's availability per number of
	 *     shifts they need to fill yet.
	 */
	public function getNumAvailableShiftsRatio($job_id) {
		$open = $this->getNumShiftsOpen($job_id);

		// worker has fewer than 1 open shift for this job - don't divide by 0
		if ($open < 1) {
			return 0;
		}

		// worker has no available shifts for this job
		if (!isset($this->avail_shifts[$job_id])) {
			return 0;
		}

		// number of shifts available to work / number of shifts they need filled
		return count($this->avail_shifts[$job_id]) / $open;
	}


	/**
	 * @return array list of strings / dates which have been assigned to this
	 * worker already.
	 */
	public function getDatesAssigned() {
		$dates = [];
		foreach($this->assigned as $job_id=>$d) {
			$dates = array_merge($dates, $d);
		}
		return array_unique($dates);
	}


	/**
	 * Generate an adjacency score to spread out assignments.
	 *
	 * @param string $date the date to be considered.
	 * @return float score number - higher when closer to an adjancent, previously
	 *     assigned date - 0 if more than the threshold away or none others
	 *     assigned yet.
	 */
	public function getAdjancencyScore($date) {
		$assigned = $this->getDatesAssigned();
		if (empty($assigned)) {
			return 0;
		}

		$current = date('z', strtotime($date));

		$minimum = 0;
		foreach($assigned as $assn_date) {
			$ts = strtotime($assn_date);
			$diff = abs(date('z', $ts) - $current);

			if ($minimum == 0) {
				$minimum = $diff;
				continue;
			}

			if ($minimum > $diff) {
				$minimum = $diff;
			}
		}

		if ($minimum == 0) {
			return 0;
		}

		// if far away, then return with 0, otherwise the ratio
		return ($minimum > self::ADJACENCY_LIMIT) ? 0 :
			(self::ADJACENCY_LIMIT / $minimum);
	}


	/**
	 * Get the list of shifts that this worker is assigned for the date
	 * provided.
	 *
	 * @param string $date the date
	 * @return array of job IDs already assigned for a given date.
	 */
	public function getShiftsAssignedByDate($date) {
		$shifts = [];
		foreach($this->assigned as $job_id=>$dates) {
			if (in_array($date, $dates)) {
				$shifts[] = $job_id;
			}
		}

		return $shifts;
	}


	/*
	 * Check to see if this worker is available to work on this day.
	 * This checks for previously assigned shifts for the day, and if they want
	 * to cook and clean after themselves, as well as avoiding double-assigning
	 * someone to the same shift, i.e. bundling.
	 *
	 * @param string $date the date
	 * @param int $job_id the job number
	 * @return int a numeric string representing any conflicts for the day.
	 */
	public function getDateScore($date, $job_id) {
		$todays_shifts = $this->getShiftsAssignedByDate($date);

		// if not scheduled for today, then no conflict
		if (empty($todays_shifts)) {
			return OK_PREF;
		}

		// would they prefer to clean after themselves?
		// null = not set or don't care, true / false otherwise
		$clean_after_self = NULL;
		if (array_key_exists('clean_after_self', $this->requests)) {
			if ($this->requests['clean_after_self'] == 'yes') {
				$clean_after_self = TRUE;
			}
			else if ($this->requests['clean_after_self'] == 'no') {
				$clean_after_self = FALSE;
			}
		}

		$have_cooking = FALSE;
		$have_cleaning = FALSE;
		foreach($todays_shifts as $s) {
			if (is_a_cook_job($s)) {
				$have_cooking = TRUE;
			}
			if (is_a_clean_job($s)) {
				$have_cleaning = TRUE;
			}
			#!# add mention of table setter
		}

		$do_bundling = $this->wantsBundling();

		if (is_a_cook_job($job_id)) {
			// look to see if they already have a cooking job
			if ($have_cooking) {
				// if they want to bundle, then there's no conflict
				return $do_bundling ? DO_BUNDLING_PREF : HAS_CONFLICT_PREF;
			}

			// conflict found if they already have a cleaning shift and don't
			// want to cook before cleaning
			return (($have_cleaning) && ($clean_after_self === FALSE)) ?
				HAS_CONFLICT_PREF : CLEAN_AFTER_COOK_PREF;
		}
		#!# add mention of table setter

		// this is a cleaning job
		// look to see if they already have a cleaning job
		if ($have_cleaning) {
			// if they want to bundle, then there's no conflict
			return $do_bundling ? DO_BUNDLING_PREF : HAS_CONFLICT_PREF;
		}

		// conflict found if they already have a cooking shift and don't
		// want to clean up after themselves
		return (($have_cooking) && ($clean_after_self === FALSE)) ?
			HAS_CONFLICT_PREF : CLEAN_AFTER_COOK_PREF;
	}

	/**
	 * Find out whether this worker wants to bundle their shifts or not.
	 */
	public function wantsBundling() {
		// try to group SKIPs together to help cancel dates
		if ($this->username === SKIP_USER) {
			return TRUE;
		}

		return (array_get($this->requests, 'bundle_shifts') == TRUE);
	}

	/**
	 * Can this worker work on this date?
	 * Check to see if:
	 * - they have shifts available to fill for this job
	 * - they're assigned to this date already (another shift same day)
	 *
	 * @param int $job_id the job number
	 * @return bool TRUE if this user has been fully assigned
	 */
	public function isFullyAssigned($job_id) {
		return ($this->getNumAvailableShiftsRatio($job_id) === floatval(0));
	}


	/**
	 * Assign a shift to a worker. Note, returning false here will kill the
	 * run.
	 * @param int $job_id the number of the shift currently being assigned.
	 * @param string $date the date of the shift currently being assigned.
	 */
	public function setAssignedShift($job_id, $date) {
		if (($this->getNumShiftsOpen($job_id) < 1) &&
				($this->getUsername() != PLACEHOLDER)) {
			echo "$this->username doesn't have any more shifts to fill ($job_id, $date)!\n";
			return FALSE;
		}

		$this->assigned[$job_id][] = $date;
		unset($this->avail_shifts[$job_id][$date]);

		return TRUE;
	}

	/**
	 * Find if this worker has responded to the survey.
	 * @return boolean, If TRUE then the worker has some available shifts.
	 */
	public function hasResponded() {
		return !empty($this->avail_shifts);
	}


	/**
	 * Display the results for each worker.
	 *
	 * @param bool $only_unfilled_workers if true, then only display the
	 *     workers and their jobs which have unfilled shifts.
	 * @return array list of jobs and number of shifts assigned
	 */
	public function printResults($only_unfilled_workers=FALSE) {
		if (empty($this->assigned)) {
			return array();
		}

		$job_counts = [];

		$job_info = '';
		foreach($this->assigned as $job_id=>$dates) {
			$num_shifts = count($dates);
			natsort($dates);
			$job_counts[$job_id] = $num_shifts;
			$ratio = number_format($num_shifts /
				$this->num_shifts_to_fill[$job_id], 2);

			if (!$only_unfilled_workers || ($ratio != 1)) {
				$name = get_job_name($job_id);
				$dates_list = implode(', ', $dates);
				$to_fill = $this->num_shifts_to_fill[$job_id];
				$job_info .= <<<EOTXT
	j: [{$name}] [{$num_shifts}/{$to_fill}] ({$ratio}) {$dates_list}

EOTXT;
			}
		}

		if ($job_info != '') {
			print <<<EOTXT
name: {$this->username}
{$job_info}

EOTXT;
		}

		return $job_counts;
	}

	/**
	 * Find out which jobs this worker has been assigned.
	 */
	public function getTasks() {
		if (empty($this->tasks)) {
			$this->loadTasks();
		}

		return $this->tasks;
	}

	/**
	 * Load the jobs this worker has been assigned from database and 
	 * overrides.
	 */
	protected function loadTasks() {
		# quit if database connection doesn't exist
		if (is_null($this->mysql_api)) {
			return;
		}

		$all_jobs = get_all_jobs();

		$each_job = [];
		foreach($all_jobs as $id=>$name) {
			$each_job[] = SURVEY_JOB_TABLE . ".id='{$id}'";
		}
		$each_job_sql = implode(' or ', $each_job);

		$sid = get_season_id();
		$jobs_table = SURVEY_JOB_TABLE;
		$assn_table = ASSIGN_TABLE;
		$task_sql = <<<EOSQL
			select {$jobs_table}.description, {$jobs_table}.id
				from {$jobs_table}, {$assn_table}
				where {$jobs_table}.id={$assn_table}.job_id and
					worker_id={$this->id} and
					{$assn_table}.type='a' and
					{$assn_table}.season_id={$sid} and
					( {$each_job_sql} );
EOSQL;

		$tasks = [];
		foreach ($this->mysql_api->get($task_sql) as $row) {
			$tasks[$row['id']] = $row['description'];
		}

		$addl_jobs = [];
		$num_shift_overrides = get_num_shift_overrides();
		if (isset($num_shift_overrides[$this->username])) {
			$additional = $num_shift_overrides[$this->username];
			foreach(array_keys($additional) as $id) {
				$addl_jobs[$id] = $all_jobs[$id];
			}
		}

		$this->tasks = $tasks + $addl_jobs;
	}

	/**
	 * Query the saved comments and other special requests.
	 */
	public function loadComments() {
		$table = SCHEDULE_COMMENTS_TABLE;
		$sql = <<<EOSQL
			SELECT *
				FROM {$table}
				WHERE worker_id={$this->getId()}
EOSQL;
		foreach ($this->mysql_api->get($sql) as $row) {
			$this->comments = $row;
			return;
		}

		$this->comments = [];
	}

	public function getComments() {
		if (is_null($this->comments)) {
			$this->loadComments();
		}

		return $this->comments;
	}

	public function getCommentsText() {
		$comments = $this->getComments();
		return array_key_exists('comments', $comments) ?
			stripslashes($comments['comments']) : '';
	}
}

?>
