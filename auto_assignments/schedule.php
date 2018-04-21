<?php
define('PREFER_TO_AVOID_RATIO', .55);

define('DEFAULT_HOBART_SCORE', 7);
define('DEFAULT_AVAIL_SCORE', 5);
define('DEFAULT_AVOIDS_SCORE', 7);
define('DEFAULT_PREFERS_SCORE', 4);

require_once('../public/utils.php');
require_once('../public/classes/calendar.php');

/**
 * A Schedule object maintains the list of who has been scheduled to work on certain days.
 */
class Schedule {
	protected $meals = array();
	protected $roster;
	protected $job_id;
	protected $calendar;

	protected $point_factors = array(
		'hobart' => DEFAULT_HOBART_SCORE,
		'avail' => DEFAULT_AVAIL_SCORE,
		'avoids' => DEFAULT_AVOIDS_SCORE,
		'prefers' => DEFAULT_PREFERS_SCORE,
	);

	// which shifts happen on which dates
	protected $dates_and_shifts = array();

	// the most difficult shifts to fill
	// date => job_id => counts
	protected $least_possible = array();

	// make this a member variable as a cache
	protected $dates_by_shift = array();

	public function __construct($season_months=[]) {
		$this->calendar = new Calendar($season_months);
	}

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
	 * Assign various point-type variables.
	 * This is intended for changing the rules of the game, so that multiple
	 * runs can be processed and each one would turn out a little differently.
	 *
	 * @param[in] hobart_factor int the amount of points to assign towards the
	 *     hobart factor.
	 * @param[in] avail_factor int the amount of points to assign towards
	 *     availability.
	 * @param[in] avoids_factor int the amount of points to assign towards
	 *     avoids.
	 */
	public function setPointFactors($hobart_factor=NULL,
		$avail_factor=NULL, $avoids_factor=NULL) {

		if (!is_null($hobart_factor)) {
			$this->point_factors['hobart'] = $hobart_factor;
		}
		if (!is_null($avail_factor)) {
			$this->point_factors['avail'] = $avail_factor;
		}
		if (!is_null($avoids_factor)) {
			$this->point_factors['avoids'] = $avoids_factor;
			$this->point_factors['prefers'] =
				$this->point_factors['avoids'] * PREFER_TO_AVOID_RATIO;
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
	 */
	public function initializeShifts() {
		$this->calendar->disableWebDisplay();
		$this->dates_and_shifts = $this->calendar->evalDates();

		foreach($this->dates_and_shifts as $date=>$shifts) {
			$num_meals = count($this->meals);
			$this->meals[$date] = get_a_meal_object($this, $date, $num_meals);
			$this->meals[$date]->initShifts($shifts);
		}
	}


	/**
	 * Get the list of shifts (job IDs) and the dates listed for that type of
	 * job.
	 *
	 * @param array job_id => array( list of dates ).
	 * @return XXX
	 */
	public function getDatesByShift() {
		if (empty($this->dates_by_shift)) {
			foreach($this->dates_and_shifts as $date=>$shifts) {
				foreach($shifts as $index=>$job_id) {
					$this->dates_by_shift[$job_id][] = $date;
				}
			}
		}

		return $this->dates_by_shift;
	}


	/**
	 * Add the list of possible workers for each shift and their preference
	 * value on a per-job-date basis.
	 *
	 * @param[in] username string the username.
	 * @param[in] job_id int the ID of the shift.
	 * @param[in] date string the date of the job.
	 * @param[in] pref num the numeric value preference score.
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
	 * @param[in] slackers array list of usernames of people who didn't take
	 * their survey.
	 */
	public function addNonResponderPrefs($slackers) {
		$dates_by_shift = $this->getDatesByShift();

		foreach($slackers as $username) {
			$w = $this->getWorker($username);
			if (is_null($w)) {
				echo "worker $username does not exist\n";
				exit;
			}
			$shifts_assigned = $w->getAssignedShifts();
			$pref = NON_RESPONSE_PREF;

			foreach($shifts_assigned as $job_id) {
				// figure out which dates and shifts to assign
				$d_by_s = $dates_by_shift;
				foreach ($d_by_s[$job_id] as $date) {
					if (!isset($this->meals[$date])) {
						echo "meal for date:{$date} doesn't exist\n";
						exit;
					}

					$m = $this->meals[$date];
					$m->addWorkerPref($username, $job_id, $pref);
				}
			}
		}
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

		$j = $this->job_id;

		$prev = empty($this->least_possible) ? 
			array_keys($this->meals) :
			array_keys($this->least_possible);
		$this->least_possible = [];

		foreach($prev as $date) {
			$m = $this->meals[$date];

			// skip dates which don't need workers
			if (!$m->hasOpenShifts($j)) {
				continue;
			}

			// get number of possible workers for this date/shift
			$poss = $m->getNumPossibleWorkerRatio($j);
			// shift filled - move along
			if (($poss == 0) || is_null($poss)) {
				continue;
			}

			// uh oh - not enough workers!
			if ($poss < 1) {
				$job_name = get_job_name($j);
				echo <<<EOTXT
D:{$date}, job:{$j} {$job_name} may not have enough workers: {$poss}

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
		return TRUE;
	}


	/**
	 * Of the available workers for this shift, choose one and assign it.
	 * Note, this isn't solely based on availability, but also proximity of
	 * other assignments and user requests, etc.
	 *
	 * @param[in] worker_freedom the array of workers and their difficulty to
	 *     assign ratios.
	 * @return boolean. If TRUE, then the meal was filled successfully.
	 */
	public function fillMeal($worker_freedom) {
		$job_id = $this->job_id;

		$date = get_first_associative_key($this->least_possible);
		if ($date == '') {
			echo "EMPTY DATE\n";
			return FALSE;
		}
		$meal = $this->meals[$date];
		$username = $meal->fill($job_id, $worker_freedom);
		if (is_null($username)) {
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
		if ($username == PLACEHOLDER) {
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
		foreach($this->meals as $m) {
			$count += $m->getNumPlaceholders();
		}

		return $count;
	}


	/**
	 * Display the schedule
	 * @param[in] format string the chosen output format (txt, or sql). How the
	 *     output should be displayed.
	 */
	public function printResults($format='txt' ) {
		if ($format === 'txt') {
			$this->printTabbedHeaders();
		}

		$missed_hobarters = 0;
		foreach($this->meals as $date=>$m) {
			if (!$m->printResults($format)) {
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
	 * Display table headers
	 * XXX Unforunately, these are hard-coded for now.
	 */
	public function printTabbedHeaders() {
		echo "date\thead_cook\tasst1\tasst2\tcleaner1\tcleaner2\tcleaner3\ttable_setter\n";
	}


	/**
	 * Count the number of meals in the schedule
	 * @return int number of meals
	 */
	public function getNumMeals() {
		$count = 0;
		foreach($this->meals as $date=>$m) {
			if (is_object($m)) {
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
		$assignments = array();
		foreach($this->meals as $date=>$m) {
			$assignments[$date] = $m->getAssigned();
		}
		return $assignments;
	}
}
?>
