<?php
require_once('../public/utils.php');
require_once('../public/classes/calendar.php');

/**
 * A Schedule object maintains the list of who has been scheduled to work on certain days.
 */
class Schedule {
	protected $meals = [];
	protected $roster;
	protected $job_id;
	protected $calendar;

	// the most difficult shifts to fill
	// date => job_id => counts
	protected $least_possible = [];

	// which shifts happen on which dates
	protected $dates_and_shifts = [];
	protected $dates_by_shift_cache = [];

	public function getPossibleRatios() {
		return $this->least_possible;
	}

	/**
	 * Get the worker object by username from the roster.
	 * @return Worker object.
	 */
	public function getWorker($username) {
		return $this->roster->getWorker($username);
	}

	/**
	 * Get the meals object.
	 * This is primarily for unit-testing.
	 */
	public function getMeals() {
		return $this->meals;
	}

	/**
	 * Set the roster value for cross-referencing.
	 */
	public function setRoster($r) {
		$this->roster = $r;
	}

	/**
	 * Set the current job id.
	 */
	public function setJobId($job_id) {
		$this->job_id = $job_id;

		// reset the listing of availability for all meals
		$this->least_possible = [];
	}

	/**
	 * Get the job id
	 */
	public function getJobId() {
		return $this->job_id;
	}

	/**
	 * Figure out which days have meals, and which shifts are needed
	 * for those days. Create each of those meals instances with those shifts.
	 *
	 * @param array $dates_and_shifts dates are the key, value is a list of job
	 *     IDs needed for that meal type.
	 *     Example: [SUNDAY_ASST_COOK, SUNDAY_CLEANER, SUNDAY_HEAD_COOK]
	 */
	public function initializeShifts($dates_and_shifts=[]) {
		$this->dates_and_shifts = $dates_and_shifts;

		foreach($dates_and_shifts as $date=>$job_list) {
			$this->meals[$date] = get_a_meal_object($this, $date);

			// don't initialize skipped meals
			if (!is_null($this->meals[$date])) {
				$this->meals[$date]->initShifts($job_list);
			}
		}
	}

	/**
	 * Get the list of shifts (job IDs) and the dates listed for that type of
	 * job.
	 *
	 * @return array job_id => array( list of dates ).
	 */
	public function getDatesByShift() {
		if (empty($this->dates_by_shift_cache)) {
			$this->loadDatesByShiftCache();
		}

		return $this->dates_by_shift_cache;
	}

	/**
	 * Load the dates by shift into the cache variable.
	 */
	public function loadDatesByShiftCache() {
		foreach($this->dates_and_shifts as $date=>$shifts) {
			foreach($shifts as $job_id) {
				$this->dates_by_shift_cache[$job_id][] = $date;
			}
		}
		return $this->dates_by_shift_cache;
	}


	/**
	 * Add the list of possible workers for each shift and their preference
	 * value on a per-job-date basis.
	 *
	 * @param string $username the username.
	 * @param int $job_id the ID of the shift.
	 * @param string $date the date of the job.
	 * @param int $pref the numeric value preference score.
	 */
	public function addPrefs($username, $job_id, $date, $pref) {
		// only add preferences for scheduled approved meals
		if (!isset($this->meals[$date])) {
			return FALSE;
		}

		$this->meals[$date]->addWorkerPref($username, $job_id, $pref);
		return TRUE;
	}


	/**
	 * Add a set of default preferences for the slackers who didn't respond to
	 * the work survey.
	 * XXX: maybe this should be moved to Roster.
	 *
	 * @param array $slackers list of usernames of people who didn't take
	 *     their survey.
	 * @return int count of affected slackers.
	 */
	public function addNonResponderPrefs($slackers) {
		$dates_by_shift = $this->getDatesByShift();
		$count = 0;

		foreach($slackers as $username) {
			$worker = $this->getWorker($username);
			if (!is_object($worker)) {
				echo "worker $username does not exist FATAL\n";
				exit;
			}

			$shifts_assigned = $worker->getAssignedShifts();
			foreach($shifts_assigned as $job_id) {
				// protect against error with the foreach below
				if (!isset($dates_by_shift[$job_id])) {
					continue;
				}

				// figure out which dates and shifts to assign
				foreach ($dates_by_shift[$job_id] as $date) {
					// don't initialize skipped meals
					if (!isset($this->meals[$date])) {
						error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . 
							"meal for date:{$date} doesn't exist");
						continue;
					}

					$meal = $this->meals[$date];
					$meal->addWorkerPref($username, $job_id, NON_RESPONSE_PREF);
					$count++;
				}
			}
		}

		return $count;
	}


	/**
	 * Check to see if the assignments for this job have been completed
	 */
	public function isFinished() {
		foreach($this->meals as $date=>$meal) {
			if ($meal->hasOpenShifts($this->job_id)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Sort the various meals to find the one which will be the most difficult
	 * to fill.
	 */
	public function sortPossibleRatios() {
		// don't re-generate the list
		if (!empty($this->least_possible)) {
			return;
		}

		$job_id = $this->job_id;

		$prev = empty($this->least_possible) ? 
			array_keys($this->meals) :
			array_keys($this->least_possible);
		$this->least_possible = [];

		foreach($prev as $date) {
			$meal = $this->meals[$date];

			// skip dates which don't need workers
			if (!$meal->hasOpenShifts($job_id)) {
				continue;
			}

			// get number of possible workers for this date/shift
			$poss = $meal->getNumPossibleWorkerRatio($job_id);
			// shift filled - move along
			if (($poss == 0) || is_null($poss)) {
				continue;
			}

			// uh oh - not enough workers!
			if ($poss < 1) {
				$job_name = get_job_name($job_id);
				echo <<<EOTXT
D:{$date}, job:{$job_id} {$job_name} may not have enough workers: {$poss}

EOTXT;
				continue;
			}

			// record the possibility ratio
			$this->least_possible[$date] = $poss;
		}

		if (empty($this->least_possible)) {
			return FALSE;
		}

		asort($this->least_possible);

		if (DEBUG_FIND_CANCEL_MEALS) {
			$job_name = get_job_name($job_id);
			error_log(__FILE__ . ' ' . __LINE__ . " ratios for {$job_name} (<1 is bad): " .
				var_export($this->least_possible, TRUE));
		}

		return TRUE;
	}


	/**
	 * Of the available workers for this shift, choose one and assign it.
	 * Note, this isn't solely based on availability, but also proximity of
	 * other assignments and user requests, etc.
	 *
	 * @param array $worker_freedom workers and their difficulty to
	 *     assign ratios.
	 * @return boolean. If TRUE, then the meal was filled successfully.
	 */
	public function fillMeal($worker_freedom) {
		if (empty($this->least_possible)) {
			return FALSE;
		}

		$job_id = $this->job_id;

		$date = get_first_associative_key($this->least_possible);
		if ($date == '') {
			echo "EMPTY DATE\n";
			return FALSE;
		}
		$meal = $this->meals[$date];
		$username = $meal->fill($job_id, $worker_freedom);
		if (empty($username)) {
			echo "null user\n";
			return FALSE;
		}

		// update the current meal's possibility ratio
		$poss = $meal->getNumPossibleWorkerRatio($job_id);
		if ($poss == 0) {
			unset($this->least_possible[$date]);
		}
		else {
			$this->least_possible[$date] = $poss;
			asort($this->least_possible);
		}

		$worker = $this->getWorker($username);
		// if this wasn't able to be filled, don't do the rest of the steps
		if ($username === PLACEHOLDER) {
			return TRUE;
		}

		// update this worker's availability
		if (!($worker->setAssignedShift($job_id, $date))) {
			echo "unable to set assigned shift!\n";
			return FALSE;
		}

		return TRUE;
	}


	/**
	 * Find out how many placeholders have been assigned.
	 * @return int number of placeholders for the schedule.
	 */
	public function getNumPlaceholders() {
		$count = 0;
		foreach($this->meals as $meal) {
			$np = $meal->getNumPlaceholders();
			$count += $np;
		}

		return $count;
	}

	/**
	 * Get the headers for the schedule
	 * @param string $format string the chosen output format (txt, or sql). How the
	 *     output should be displayed.
	 */
	public function getHeaders($format='txt' ) {
		switch ($format) {
			case 'txt':
				return $this->getTabbedHeaders();

			case 'gather_csv':
				return $this->getGatherHeaders();
		}
	}


	/**
	 * Display the schedule
	 * @param string $format string the chosen output format (txt, or sql). How the
	 *     output should be displayed.
	 */
	public function printResults($format='txt' ) {
		echo $this->getHeaders($format);

		$gather_ids = $this->roster->loadGatherIDs();
		$missed_hobarters = 0;
		foreach($this->meals as $date=>$meal) {
			if (!$meal->printResults($format, $gather_ids)) {
				$missed_hobarters++;
			}
		}

		if ($format == 'txt') {
			$count = $this->getNumMeals();
			echo "MISSED HOBARTERS: {$missed_hobarters} of {$count} " . 
				round($missed_hobarters / $count, 2) . "\n";
		}
	}

	/**
	 * Get the list of columns in order.
	 */
	public function getColumnOrder() {
		$list = [
			'date',
			'time',
			'communities',
			'head_cook',
			'asst1',
			'asst2',
			'cleaner1',
			'cleaner2',
			'cleaner3',
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$list[] = 'table_setter';
		}
		return $list;
	}

	/**
	 * Display table headers
	 * XXX Unforunately, these are hard-coded for now.
	 * @return string the tabbed headers.
	 */
	public function getTabbedHeaders() {
		return implode("\t", $this->getColumnOrder()) . "\n";
	}

	/**
	 * Display table headers for gather import
	 */
	public function getGatherHeaders() {
		$cols = [
			'Action',
			'Date/time',
			'Locations',
			'Communities',
			'Head Cook',
			'Assistant Cook',
			'Cleaner',
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$cols[] = 'Table Setter';
		}
		return implode(",", $cols) . "\n";
	}


	/**
	 * Count the number of meals in the schedule
	 * @return int number of meals
	 */
	public function getNumMeals() {
		$count = 0;
		foreach($this->meals as $date=>$meal) {
			if (is_object($meal)) {
				$count++;
			}
		}
		return $count;
	}


	/**
	 * Get the assignments array.
	 * @return array indexed by date, referring to the list of workers assigned
	 *     to jobs for the meal on that date.
	 */
	public function getAssigned() {
		$assignments = [];
		foreach($this->meals as $date=>$meal) {
			$assignments[$date] = $meal->getAssigned();
		}
		return $assignments;
	}
}
?>
