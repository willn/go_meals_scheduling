<?php
define('AVOID_PERSON', -2);
define('PREFER_PERSON', 1);

define('DEFAULT_HOBART_SCORE', 7);
define('DEFAULT_AVOID_WORKER_SCORE', 7);
define('DEFAULT_PREFERS_SCORE', 4);

define('GO_DINING_ROOM_ID', 22);
define('GO_COMMUNITY_ONLY', 'GO');
define('ALL_3_COMMUNITY_IDS', 'GO;SW;TS');

# '2019-10-28T02:01:03'
define('ISO_8601_DATE_ONLY', 'Y-m-d');

abstract class Meal {
	protected $schedule;
	protected $date;
	protected $day_of_week;

	protected $point_factors = [
		'hobart' => DEFAULT_HOBART_SCORE,
		'avoid_workers' => DEFAULT_AVOID_WORKER_SCORE,
		'prefers' => DEFAULT_PREFERS_SCORE,
	];

	// array of username => pref
	protected $possible_workers = [];

	// username string
	protected $assigned = [];

	// unique meal ID
	protected $meal_num;

	protected $time_of_meal;
	protected $communities;

	/**
	 * Initialize a meal.
	 * @param[in] schedule Schedule object.
	 * @param[in] date string a date string which looks like '5/6/2013'
	 * @param[in] meal_num int a unique number for this meal
	 */
	public function __construct($schedule, $date, $meal_num) {
		$this->schedule = $schedule;
		$this->setDate($date);
		$this->meal_num = $meal_num;
	}

	public function setDate($d) {
		$this->date = $d;
		$this->day_of_week = date('N', strtotime($d));
	}

	public function getDate() {
		return $this->date;
	}

	public function getDayOfWeek() {
		return $this->day_of_week;
	}

	/**
	 * Initialize the shifts for this meal.
	 * Add an empty slot for each shift to be filled for this job type.
	 * Example: weekday asst cooks should get 2 empty slots to fill.
	 *
	 * @param[in] job_id_list XXX
	 */
	public function initShifts($job_id_list) {
		$job_instances = get_num_workers_per_job_per_meal();

		foreach(array_values($job_id_list) as $job_id) {
			if (empty($job_instances[$job_id])) {
				continue;
			}

			// fill in the number of open shifts
			$num = $job_instances[$job_id];
			for($i=0; $i<$num; $i++) {
				$this->assigned[$job_id][] = NULL;
			}
		}
	}


	/**
	 * Add a name to the list of possible workers for a given job, with their
	 * preference number.
	 */
	public function addWorkerPref($username, $job_id, $pref) {
		// only add prefs for shifts which are defined on this date.
		if (!isset($this->assigned[$job_id])) {
			$all_jobs = get_all_jobs();
			if (!isset($all_jobs[$job_id])) {
				echo "Could not find JOB ID: {$job_id} FATAL\n";
				exit;
			}

			$all_jobs_out = print_r($all_jobs, TRUE);
			$assn_out = print_r($this->assigned, TRUE);
			echo <<<EOTXT
The job "{$all_jobs[$job_id]}" isn't scheduled for this date: {$this->date}
U:{$username} P:{$pref}
all jobs: {$all_jobs_out}
assigned: {$assn_out}
FATAL

EOTXT;
			exit;
		}

		$this->possible_workers[$job_id][$username] = $pref;
	}

	/**
	 * Assign various point-type variables.
	 * This is intended for changing the rules of the game, so that multiple
	 * runs can be processed and each one would turn out a little differently.
	 *
	 * @param[in] hobart_factor int the amount of points to assign towards the
	 *     hobart factor.
	 * @param[in] avail_factor int the amount of points to assign towards
	 *     availability.
	 * @param[in] avoid_workers_factor int the amount of points to assign towards
	 *     avoiding working with someone.
	 */
	public function setPointFactors($hobart_factor=NULL,
		$avoid_workers_factor=NULL) {

		if (!is_null($hobart_factor)) {
			$this->point_factors['hobart'] = $hobart_factor;
		}
		if (!is_null($avoid_workers_factor)) {
			$this->point_factors['avoid_workers'] = $avoid_workers_factor;
			$this->point_factors['prefers'] =
				$this->point_factors['avoid_workers'] * PREFER_TO_AVOID_WORKER_RATIO;
		}
	}

	/**
	 * This is intended for changing the rules of the game, so that multiple
	 * runs can be processed and each one would turn out a little differently.
	 */
	public function getPointFactors() {
		return $this->point_factors;
	}

	/**
	 * Find out how many slots are empty for a given job id.
	 */
	public function getNumOpenSpacesForShift($job_id) {
		if (empty($this->assigned[$job_id])) {
			echo "no jobs assigned for this meal / job: D:{$this->date}, J:{$job_id} FATAL\n";
			exit;
		}

		$count = 0;
		foreach($this->assigned[$job_id] as $worker) {
			if (is_null($worker)) {
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Find the popularity vs open spaces ratio for this meal's job.
	 * Indicator of how difficult it will be to fill this meal.
	 *
	 * @param[in] job_id int ID of the meal's job
	 * @return float the popularity / open spot ratio
	 */
	public function getNumPossibleWorkerRatio($job_id) {
		$job_instances = get_num_workers_per_job_per_meal();

		// check to see if this is the wrong date for this job
		if (!isset($job_instances[$job_id]) || 
			($job_instances[$job_id] == 0)) {
			return 0;
		}

		$open_spaces = $this->getNumOpenSpacesForShift($job_id);
		if ($open_spaces == 0) {
			return 0;
		}

		// check that workers are defined
		if (empty($this->possible_workers[$job_id])) {
			$job_name = get_job_name($job_id);
			echo <<<EOTXT
no possible workers defined for job {$job_id}, {$job_name}, {$this->date}

EOTXT;
			return 0;
		}

		return count($this->possible_workers[$job_id]) / $open_spaces;
	}


	/**
	 * Find if this meal has shifts yet to be filled.
	 * (Meal)
	 */
	public function hasOpenShifts($job_id) {
		$job_instances = get_num_workers_per_job_per_meal();

		// if this day of week isn't defined. For example, a sunday shift on a
		// weekday...
		if (!isset($job_instances[$job_id])) {
			return FALSE;
		}

		// if this day of week has no shifts to fill
		$num_to_fill = $job_instances[$job_id];
		if ($num_to_fill == 0) {
			return FALSE;
		}

		// if there are no shift slots for this job
		// e.g. meeting nights are only on mon & wed, but not EVERY mon & wed
		if (empty($this->assigned[$job_id])) {
			return FALSE;
		}

		// if the number of assignments is full - all assigned
		$count = 0;
		foreach($this->assigned[$job_id] as $worker) {
			if (!is_null($worker)) {
				$count++;
			}
		}
		if ($count == $num_to_fill) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Get list of workers who should be avoided for this date based on anyone
	 * who is already assigned to this meal.
	 *
	 * @param[in] job_id int the number of the job to get preferences for.
	 * @return array key-value pairs, one for 'avoid_workers', another for 'prefer'.
	 */
	protected function getAvoidAndPreferWorkerList($job_id,
		$assigned_worker_names) {
		if (empty($assigned_worker_names)) {
			return [];
		}

		$assigned_worker_objects = $this->getAssignedWorkerObjectsByJobId(
			$job_id, $assigned_worker_names);

		$avoid_workers_list = [];
		$prefer_list = [];
		foreach($assigned_worker_objects as $worker) {
			// get list of names worker does not want to work with
			// Array ( [0] => aaron, [1] => nancy)
			$av_list = $worker->getAvoids();
			if (!empty($av_list)) {
				$avoid_workers_list = array_merge($avoid_workers_list, $av_list);
			}

			// get list of names worker wants to work with
			$pr_list = $worker->getPrefers();
			if (!empty($pr_list)) {
				$prefer_list = array_merge($prefer_list, $pr_list);
			}
		}

		$avoid_workers = [];
		if (!empty($avoid_workers_list)) {
			// flip from a list to an associative array of name => AVOID_PERSON
			// AVOIDS: Array ( [aaron] => -2, [nancy] => -2 )
			$avoid_workers = array_combine(array_values($avoid_workers_list),
				array_fill(0, count($avoid_workers_list), AVOID_PERSON));
		}

		$prefers = [];
		if (!empty($prefer_list)) {
			// flip from a list to an associative array of name => PREFER_PERSON
			$prefers = array_combine(array_values($prefer_list),
				array_fill(0, count($prefer_list), PREFER_PERSON));
		}

		// look for contention of preferences. Resolve by combining the two.
		if (!empty($avoid_workers) && !empty($prefers)) {
			foreach($avoid_workers as $name=>$value) {
				if (isset($prefers[$name])) {
					unset($prefers[$name]);
					$avoid_workers[$name] = AVOID_PERSON + PREFER_PERSON;
				}
			}
		}

		return [
			'avoids' => $avoid_workers,
			'prefers' => $prefers,
		];
	}

	/**
	 * Run through each eligible worker for this job, and pick one based on
	 * various points, characteristics, etc.
	 *
	 * @param[in] job_id int the number of the current job to fill
	 */
	protected function pickWorker($job_id, $worker_freedom) {
		$worker_points = [];

		$assigned_worker_names = $this->getAssignedWorkerNamesByJobId($job_id);
		$list = $this->getAvoidAndPreferWorkerList($job_id,
			$assigned_worker_names);

		// find the value of each characteristic (globally set per instance)
		$point_factors = $this->getPointFactors();

		// if the person has marked a lot of people to avoid or prefer to work
		// with, then that will carry less weight than if they only mark 1
		$avoid_point_factor = empty($list['avoids']) ? 1 :
			($point_factors['avoids'] / count($list['avoids']));
		$prefer_point_factor = empty($list['prefers']) ? 1 :
			($point_factors['prefers'] / count($list['prefers']));

		/*
		 * Walk down the list of people's availability, and find out who is
		 * able to work. If they are, then assign points and ultimately sort on
		 * those points to find the best worker for this slot.
		 */
		foreach($worker_freedom as $username=>$avail_pref) {
			// initialize
			$drawbacks = $promotes = 0;

			// skip if this worker can't work on this day
			if (!isset($this->possible_workers[$job_id][$username])) {
				continue;
			}

			$worker = $this->schedule->getWorker($username);

			// skip if this worker is fully assigned
			if ($worker->isFullyAssigned($this->date, $job_id)) {
				continue;
			}

			$today = $worker->getDateScore($this->date, $job_id);
			// skip if there's a date conflict
			if ($today == HAS_CONFLICT) {
				continue;
			}
			$promotes += $today;

			// #!# unfortunately, bundling doesn't seem to work because we're
			// only examining each worker once...

			// if a worker has an availability rating of 1 or less, then they
			// must get this assignment, otherwise they'll end up with fewer
			// assignments than necessary.
			if ($avail_pref <= 1) {
				return $username;
			}

			// try to promote one hobarter per shift, only look at group
			// cleaning shifts (i.e. not meeting nights)
			if (is_a_group_clean_job($job_id) && is_a_hobarter($username)) {
				// spread out hobarters
				if ($this->isHobarterAssignedToShift($job_id)) {
					$drawbacks += $point_factors['hobart'];
				}
				else {
					$promotes += $point_factors['hobart'];
				}
			}

			// check to see if others already assigned to this meal have marked
			// prefer or avoid for the current person
			if (isset($list['avoids'][$username])) {
				$drawbacks += $avoid_point_factor;
			}
			else if (isset($list['prefers'][$username])) {
				$promotes += $prefer_point_factor;
			}

			// check to see if the current person wants to avoid anyone who
			// is currently assigned...
			$worker_avoids = $worker->getAvoids();
			if (!empty($worker_avoids)) {
				foreach($worker_avoids as $avoid_person) {
					if (isset($assigned_worker_names[$avoid_person])) {
						$drawbacks += $point_factors['avoids'];
					}
				}
			}

			// look at workers who marked 'prefer'
			$promotes += $this->possible_workers[$job_id][$username];

			// conjure up a worker point rating
			$adjacent = $worker->getAdjancencyScore($this->date);
			$denominator = ($drawbacks + $adjacent) * $avail_pref;
			$worker_points[$username] = ($denominator == 0) ?
				$promotes : ($promotes / $denominator);
		}

		// a higher score is better
		arsort($worker_points);
		$username = get_first_associative_key($worker_points);

		// may need to insert a placeholder for later manual correction
		return is_null($username) ? PLACEHOLDER : $username;
	}

	/**
	 * Find a worker who can take a shift for this job.
	 *
	 * @param[in] job_id int the number of the shift we're trying to
	 *     assign
	 * @param[in] worker_freedom array of username => num possible
	 *     shifts ratio
	 *
	 * @return string username or NULL if assignment failed
	 */
	public function fill($job_id, $worker_freedom) {
		// don't add anymore workers, this meal is fully assigned
		if (!$this->hasOpenShifts($job_id)) {
			echo "this meal {$this->date} $job_id is filled\n";
			sleep(1);
			return NULL;
		}

		// get the name of the worker to fill this slot with
		$username = $this->pickWorker($job_id, $worker_freedom);

		// assign to the first available shift slot
		$is_available = FALSE;
		foreach($this->assigned[$job_id] as $key=>$w) {
			if (!is_null($w)) {
				// slot is taken already
				continue;
			}

			$is_available = TRUE;
			break;
		}

		if (!$is_available) {
			echo "all slots are full\n";
			return PLACEHOLDER;
		}

		$this->assigned[$job_id][$key] = $username;
		if ($username == PLACEHOLDER) {
			return $username;
		}

		$worker = $this->schedule->getWorker($username);

		// remove from this meal's pool if not bundling
		if (!$worker->wantsBundling()) {
			unset($this->possible_workers[$job_id][$username]);
		}
/* #!# disabled for now... not sure this works properly
		// if the first assignment, consider bundling
		else if ($key == 0) {
			$num_needed = count($this->assigned[$job_id]) - ($key + 1);
			// first, skip if this job is fully staffed already
			if ($num_needed < 1) {
				return $username;
			}

			// Make sure the worker needs enough shifts to fulfill the bundle
			$to_fill = $worker->getNumShiftsOpen($job_id);
			if ($to_fill < $num_needed) {
				return $username;
			}

#!# update remaining shifts left
			// do the bundling
			foreach($this->assigned[$job_id] as $num=>$w) {
				if ($num == $key) {
					continue;
				}

				$this->assigned[$job_id][$num] = $username;

				// #!# this breaks shit... but without it, people get
				// over-assigned.
				// $worker->setAssignedShift($job_id, $this->date);
			}
		}
*/

		return $username;
	}


	/**
	 * Is a worker a hobarter?
	 * @return boolean if true, then a hobarter is already assigned to the
	 *     shift.
	 */
	public function isHobarterAssignedToShift($job_id) {
		foreach($this->assigned[$job_id] as $worker) {
			// don't count un-assigned shifts
			if (is_null($worker) || ($worker == PLACEHOLDER)) {
				continue;
			}

			if (is_a_hobarter($worker)) {
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * Find out how many placeholders have been assigned to this meal.
	 * @return int number of placeholders for this meal.
	 */
	public function getNumPlaceholders() {
		$count = 0;
		foreach($this->assigned as $job_id=>$assignments) {
			foreach($assignments as $assn) {
				if ($assn == PLACEHOLDER) {
					$count++;
				}
			}
		}

		return $count;
	}


	/**
	 * Display the assigned workers for this meal.
	 *
	 * @param[in] format string the chosen output format (txt, sql,
	 *     gather_csv, or csv). How the output should be displayed.
	 * @param[in] gather_ids associative array mapping work system
	 *     usernames to Gather Google IDs.
	 * @return boolean, if false then a hobart shift was needed and not filled
	 *     with a hobarter. TRUE either means it was filled or not needed.
	 * #!# this should be a get... but need to figure out a way to deal with the return
	 */
	public function printResults($format='txt', $gather_ids=[]) {
		if (empty($this->assigned)) {
			return;
		}

		$hobarters = get_hobarters();

		// testing flags:
		$only_cleaners = FALSE;
		$has_clean_job = FALSE;
		$hobarter_found = FALSE;

		// for Gather imports
		$head_cook = [];
		$asst_cooks = [];
		$cleaners = [];
		$table_setters = [];

		$is_mtg_night_job = FALSE;
		$out_data = [];
		// check to make sure that all of the required instances are filled
		foreach($this->assigned as $job_id=>$assignments) {

			// check for un-assigned names
			foreach($assignments as $shift_num=>$name) {
				if (is_null($name)) {
					$assignments[$shift_num] = PLACEHOLDER;
				}
			}

			$invited = ALL_3_COMMUNITY_IDS;
			if (is_a_mtg_night_job($job_id)) {
				$is_mtg_night_job = TRUE;
				$invited = GO_COMMUNITY_ONLY;
				/*
				 * Pad the assignments array for output since meeting
				 * nights are missing some shifts.
				 */
				$assignments[] = '';
				$assignments[] = '';
			}

			$order = 0;
			$out_data[$order] = $this->getTime();
			$order++;
			$out_data[$order] = $this->getCommunities();

			// head cook
			if (is_a_head_cook_job($job_id)) {
				$head_cook = $assignments;
				$order = 2;
			}
			// asst cooks
			else if (is_a_cook_job($job_id)) {
				$asst_cooks = $assignments;
				$order = 3;
			}
			// cleaners
			else if (is_a_clean_job($job_id)) {
				$cleaners = $assignments;
				$order = 4;
				if (!$is_mtg_night_job) {
					foreach($assignments as $shift_num=>$name) {
						if (is_a_hobarter($name)) {
							$hobarter_found = TRUE;
							break;
						}
					}
					$has_clean_job = TRUE;
				}
			}
			// table-setters
			else {
				$table_setters = $assignments;
				$order = 5;
			}

			if (($only_cleaners) && ($order != 4)) {
				continue;
			}

			switch($format) {
			case 'txt':
				$line = implode("\t", $assignments);
				$out_data[$order] = $line;
				break;
			case 'sql':
			case 'csv':
				$out_data = array_merge($out_data, $assignments);
				break;
			}
		}
		ksort($out_data);

		switch($format) {
		case 'txt':
			print "$this->date\t" . implode("\t", $out_data) . "\n";
			break;

		case 'sql':
			$cols = ($is_mtg_night_job) ? '(meal_date, cook, cleaner1)' :
				'(meal_date, cook, asst1, asst2, cleaner1, cleaner2, cleaner3)';
			$workers = [];
			foreach($out_data as $j) {
				$workers[] = "'{$j}'";
			}
			$names = implode(', ', $workers);

			print "insert into go_meal {$cols} values ('{$this->date}', {$names});\n";
			break;

		case 'csv':
			print "$this->date," . implode(',', $out_data) . "\n";

		case 'gather_csv':
			#!# should this be broken out into another function?

			$line = [
				'create', // action
				date(ISO_8601_DATE_ONLY, strtotime($this->date)) . 'T' . $this->getIsoTime(),
				GO_DINING_ROOM_ID, // locations
				$invited, // communities
				implode(';', map_usernames_to_gather_id($head_cook, $gather_ids)),
				implode(';', map_usernames_to_gather_id($asst_cooks, $gather_ids)),
				implode(';', map_usernames_to_gather_id($cleaners, $gather_ids)),
				implode(';', map_usernames_to_gather_id($table_setters, $gather_ids)),
			];
			print implode(',', $line) . "\n";
		}

		// did a hobart shift go unfilled?
		return (!$has_clean_job || $hobarter_found);
	}

	/**
	 * For testing, return the list of assigned workers for this meal.
	 */
	public function getAssigned() {
		return $this->assigned;
	}


	/**
	 * Get the list of workers who are assigned to this (or related)
	 * shift(s).
	 *
	 * @param[in] job_id int the number of the current job being requested.
	 * @return array list of string / usernames of people currently assigned
	 *     for this meal and this type of job.
	 */
	public function getAssignedWorkerNamesByJobId($job_id) {
		$is_cleaning = is_a_clean_job($job_id);

		$names = [];
		foreach($this->assigned as $jid=>$job) {
			$j_clean = is_a_clean_job($jid);
			if ($is_cleaning !== $j_clean) {
				continue;
			}

			foreach($job as $shift_num=>$username) {
				if (is_null($username)) {
					continue;
				}
				$names[$username] = 1;
			}
		}
		return $names;
	}

	/**
	 * Get the list of worker objects who are assigned to the same job type. If
	 * the list of names is supplied, then don't look up the list of usernames.
	 *
	 * @param[in] job_id int the number of the current job being requested.
	 * @return array list of worker objects currently assigned
	 *     for this meal and this type of job.
	 */
	public function getAssignedWorkerObjectsByJobId($job_id, $names=[]) {
		if (empty($names)) {
			$names = $this->getAssignedWorkerNamesByJobId($job_id);
		}

		$workers = [];
		foreach ($names as $n=>$unused) {
			$w = $this->schedule->getWorker($n);
			if (!is_null($w)) {
				$workers[] = $w;
			}
		}
		return $workers;
	}

	/**
	 * Get the time of this meal instance.
	 *
	 * @return string the time of the meal.
	 */
	public function getIsoTime() {
		return $this->iso_time_of_meal;
	}

	/**
	 * Get the time of this meal instance in 24-hour format.
	 *
	 * @return string the time of the meal.
	 */
	public function getTime() {
		return $this->time_of_meal;
	}

	/**
	 * Get the list of communities that are invited to this meal.
	 *
	 * @return string A comma-delimited list of short community strings.
	 */
	public function getCommunities() {
		return $this->communities;
	}
}

class SundayMeal extends Meal {
	protected $time_of_meal = '5:30';
	protected $iso_time_of_meal = '17:30:00';
	protected $communities = 'GO, SW, TS';
}

class WeekdayMeal extends Meal {
	protected $time_of_meal = '6:15';
	protected $iso_time_of_meal = '18:15:00';
	protected $communities = 'GO, SW, TS';
}

class MeetingNightMeal extends Meal {
	protected $time_of_meal = '5:45';
	protected $iso_time_of_meal = '17:45:00';
	protected $communities = 'GO';
}

?>
