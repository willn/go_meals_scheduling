<?php
set_include_path('../' . PATH_SEPARATOR . '../public/');

require_once 'season.php';
require_once 'utils.php';
require_once 'season.php';
require_once 'constants.php';

/*
 * If these names change, be sure to update the is_a_*_job() functions.
 * List in order of importance.
 */
function get_mtg_jobs() {
	return [
		MEETING_NIGHT_ORDERER => MEETING_NIGHT_ORDERER_NAME,
		MEETING_NIGHT_CLEANER => MEETING_NIGHT_CLEANER_NAME,
	];
}

/*
 * Get the list of Sunday jobs.
 */
function get_sunday_jobs() {
	return [
		SUNDAY_ASST_COOK => SUNDAY_ASST_COOK_NAME,
		SUNDAY_CLEANER => SUNDAY_CLEANER_NAME,
		SUNDAY_HEAD_COOK => SUNDAY_HEAD_COOK_NAME,
	];
}

/*
 * Get the list of brunch jobs
 */
function get_brunch_jobs() {
	return [
		BRUNCH_ASST_COOK => BRUNCH_ASST_COOK_NAME,
		BRUNCH_CLEANER => BRUNCH_CLEANER_NAME,
		BRUNCH_HEAD_COOK => BRUNCH_HEAD_COOK_NAME,
		# BRUNCH_LAUNDRY => BRUNCH_LAUNDRY_NAME,
	];
}


/**
 * Get the list of the weekday jobs
 */
function get_weekday_jobs() {
	$out = [
		WEEKDAY_ASST_COOK => WEEKDAY_ASST_COOK_NAME,
		WEEKDAY_CLEANER => WEEKDAY_CLEANER_NAME,
		WEEKDAY_HEAD_COOK => WEEKDAY_HEAD_COOK_NAME,
		# WEEKDAY_LAUNDRY => WEEKDAY_LAUNDRY_NAME,
	];

	if (defined('WEEKDAY_TABLE_SETTER')) {
		$out[WEEKDAY_TABLE_SETTER] = 'Weekday Table Setter';
	}
	return $out;
}

/**
 * Get a list of all of the jobs
 */
function get_all_jobs() {
	$all_jobs = ['all' => 'all'] + get_weekday_jobs() + get_sunday_jobs() +
		get_brunch_jobs() + get_mtg_jobs();
	return $all_jobs;
}

/**
 * Get a list of all the cook jobs.
 */
function get_cook_jobs() {
	$all_jobs = get_all_jobs();
	$jobs = [];
	foreach($all_jobs as $jid=>$name) {
		if ((stripos($name, 'cook') !== FALSE) ||
			(stripos($name, 'takeout orderer') !== FALSE)) {
			$jobs[] = $jid;
		}
	}

	if (defined('WEEKDAY_TABLE_SETTER')) {
		// attempt to avoid conflicts with cooking shifts...
		$jobs[] = WEEKDAY_TABLE_SETTER;
	}
	return $jobs;
}

/**
 * Get a list of all the cleaning jobs.
 */
function get_clean_jobs() {
	$all_jobs = get_all_jobs();
	$jobs = [];
	foreach($all_jobs as $jid=>$name) {
		if ((stripos($name, 'clean') !== FALSE) ||
			(stripos($name, 'Meeting night cleaner') !== FALSE)) {
			$jobs[] = $jid;
		}
	}
	return $jobs;
}

/*
 * Get how many meals are contained within the requested job, for a
 * "bundled assignment".
 *
 * @param array $season list of the months in the season.
 * @param int $job_id the ID of the job being requested.
 * @param string $sub_season_factor number (default 1) if the jobs were assigned
 *     across an entire season, but we're only scheduling part of it,
 *     then this would be a fractional number (<1). Split the number of
 *     jobs according to the factor.
 * @return int the number of meals needed for this job. XXX or maybe an array?
 */
function get_num_meals_per_assignment($season, $job_id=NULL,
	$sub_season_factor=1) {

	if (empty($season) || !is_array($season)) {
		$season = get_current_season_months();
	}

	$num_months = count($season);

	// XXX should this be pulled dynamically from the database?

	/*
	 * This supports 6, 4, and 3 month seasons.
	 * Dropped support for 2 month seasons.
	 */
	$meals = [
		MEETING_NIGHT_CLEANER => 2,
		MEETING_NIGHT_ORDERER => 2,

		SUNDAY_ASST_COOK => 2,
		SUNDAY_CLEANER => $num_months,
		SUNDAY_HEAD_COOK => 2,

		BRUNCH_ASST_COOK => 2,
		BRUNCH_CLEANER => 2,
		BRUNCH_HEAD_COOK => 2,
		# BRUNCH_LAUNDRY => $num_months,

		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_CLEANER => $num_months,
		WEEKDAY_HEAD_COOK => 2,
		# WEEKDAY_LAUNDRY => $num_months,
	];
	if (defined('WEEKDAY_TABLE_SETTER')) {
		$meals[WEEKDAY_TABLE_SETTER] = $num_months;
	}

	// if the season is being split (e.g. cut in half), then we need to figure
	// out how many meals are being assigned for this half
	if ($sub_season_factor < 1) {
		// this only applies to the hard-coded jobs, since the others are based
		// on the number of months being considered
		$adjust_jobs = [
			MEETING_NIGHT_CLEANER,
			MEETING_NIGHT_ORDERER,
			SUNDAY_ASST_COOK,
			SUNDAY_HEAD_COOK,
			BRUNCH_ASST_COOK,
			BRUNCH_HEAD_COOK,
			BRUNCH_CLEANER,
			WEEKDAY_ASST_COOK,
			WEEKDAY_HEAD_COOK,
		];
		foreach($adjust_jobs as $job) {
			// round up to the next nearest increment
			$meals[$job] = ceil($meals[$job] * $sub_season_factor);
		}
	}

	// XXX don't like this... try to replace these, so that it's only using a single return type
	if (is_null($job_id)) {
		return $meals;
	}

	return array_get($meals, $job_id, 0);
}

/**
 * Get the number of workers per job per meal.
 *
 * @param int $job_id (optional, default NULL) If NULL, then return the entire
 *     list. If not null, and a real job id is passed in, then return the number
 *     of shifts needed for that job id.
 * @return int number of instances this job is needed to staff a given meal.
 */
function get_num_workers_per_job_per_meal($job_id=NULL) {
	static $instances = [
		MEETING_NIGHT_CLEANER => 1,
		MEETING_NIGHT_ORDERER => 1,

		SUNDAY_HEAD_COOK => 1,
		SUNDAY_ASST_COOK => 2,
		SUNDAY_CLEANER => 3,

		BRUNCH_HEAD_COOK => 1,
		BRUNCH_ASST_COOK => 2,
		BRUNCH_CLEANER => 3,
		# BRUNCH_LAUNDRY => 1,

		WEEKDAY_HEAD_COOK => 1,
		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_CLEANER => 3,
		# WEEKDAY_LAUNDRY => 1,
	];
	if (defined('WEEKDAY_TABLE_SETTER')) {
		$instances[WEEKDAY_TABLE_SETTER] = 1;
	}

	if (is_null($job_id)) {
		return $instances;
	}

	return array_get($instances, $job_id, 0);
}

/**
 * Get the list of people preferred to do hobarting duty.
 *
 * @return array list of names.
 */
function get_hobarters() {
	return [
		'alex',
		'amyh',
		'dales',
		'dan',
		'erik',
		'hope',
		'jillian',
		'keith',
		'kathyboblitt',
		'mac',
		'maryking',
		'michael',
		'nikki',
		'sallie',
		'ted',
		'willie',
	];
}
?>
