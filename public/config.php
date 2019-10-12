<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/season.php";
require_once "{$relative_dir}/constants.inc";
date_default_timezone_get('America/Detroit');

define('DOMAIN', '@gocoho.org');
define('FROM_EMAIL', 'willie' . DOMAIN);

# Are Sunday meals treated separately from weeknights?
define('ARE_SUNDAYS_UNIQUE', TRUE);

/*
 * If these names change, be sure to update the is_a_*_job() functions.
 * List in order of importance.
 */
function get_mtg_jobs() {
	return [
		MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
		MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
	];
}

/*
 * list in order of importance
 */
function get_sunday_jobs() {
	return [
		// #!# note, we're looking for the string 'asst cook' in the code
		SUNDAY_HEAD_COOK => 'Sunday head cook',
		SUNDAY_ASST_COOK => 'Sunday meal asst cook',
		SUNDAY_CLEANER => 'Sunday Meal Cleaner',
	];
}


// list in order of importance
function get_weekday_jobs() {
	return [
		WEEKDAY_HEAD_COOK => 'Weekday head cook',
		WEEKDAY_ASST_COOK => 'Weekday meal asst cook',
		WEEKDAY_CLEANER => 'Weekday Meal cleaner',
		WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
	];
}

/**
 * Get a list of all of the jobs
 */
function get_all_jobs() {
	$all_jobs = [];
	$all_jobs['all'] = 'all';
	$all_jobs += get_weekday_jobs() + get_sunday_jobs() + get_mtg_jobs();
	return $all_jobs;
}

/*
 * Get how many meals are contained within the requested job, for a
 * "bundled assignment".
 *
 * @param[in] season array list of the months in the season.
 * @param[in] job_id int the ID of the job being requested.
 * @param[in] sub_season_factor number (default 1) if the jobs were assigned
 *     across an entire season, but we're only scheduling part of it,
 *     then this would be a fractional number (<1). Split the number of
 *     jobs according to the factor.
 * @return int the number of meals needed for this job.
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

		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_CLEANER => $num_months,
		WEEKDAY_HEAD_COOK => 2,
		WEEKDAY_TABLE_SETTER => $num_months,
	];

	if ($sub_season_factor < 1) {
		$adjust_jobs = [
			MEETING_NIGHT_CLEANER,
			MEETING_NIGHT_ORDERER,
			SUNDAY_ASST_COOK,
			SUNDAY_HEAD_COOK,
			WEEKDAY_ASST_COOK,
			WEEKDAY_HEAD_COOK,
		];
		foreach($adjust_jobs as $job) {
			$meals[$job] = ceil($meals[$job] * $sub_season_factor);
		}
	}

	// XXX do not like this... try to replace these, so that it's only using a single return type
	if (is_null($job_id)) {
		return $meals;
	}

	return array_get($meals, $job_id, 0);
}

/**
 * Get the number of workers per job per meal.
 *
 * @param[in] job_id (optional, default NULL) If NULL, then return the entire
 *     list. If not null, and a real job id is passed in, then return the number
 *     of shifts needed for that job id.
 * @return array associative key-value pairs of job id to number of instances
 *     this job is needed to staff a given meal.
 */
function get_num_workers_per_job_per_meal($job_id=NULL) {
	static $instances = [
		MEETING_NIGHT_CLEANER => 1,
		MEETING_NIGHT_ORDERER => 1,

		SUNDAY_HEAD_COOK => 1,
		SUNDAY_ASST_COOK => 2,
		SUNDAY_CLEANER => 3,

		WEEKDAY_HEAD_COOK => 1,
		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_CLEANER => 3,
		WEEKDAY_TABLE_SETTER => 1,
	];

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
		// 'bill',
		'debbi',
		'erik',
		'hope',
		'jillian',
		'kathyboblitt',
		'mac',
		'maryking',
		'patti',
		'rod',
		'sharon',
		'ted',
		'willie',
		'yimiau',
	];
}
?>
