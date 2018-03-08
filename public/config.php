<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
date_default_timezone_get('America/Detroit');

define('FROM_EMAIL', 'willie' . DOMAIN);

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('march 21, 2018, 8:00pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 4194);
define('MEETING_NIGHT_CLEANER', 4197);
define('SUNDAY_HEAD_COOK', 4192);
define('SUNDAY_ASST_COOK', 4193);
define('SUNDAY_CLEANER', 4196);
define('WEEKDAY_HEAD_COOK', 4190);
define('WEEKDAY_ASST_COOK', 4191);
define('WEEKDAY_CLEANER', 4195);
define('WEEKDAY_TABLE_SETTER', 4184);

# Are Sunday meals treated separately from weeknights?
define('ARE_SUNDAYS_UNIQUE', TRUE);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
	return [
		/*
		'example' => [
			WEEKDAY_ASST_COOK => 1,
		],
		*/
	];
}

/**
 * Get the list of dates to skip, don't schedule a meal on this date.
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [];
}

/**
 * Get the list of dates to force making a regular weekday night instead of
 * a meeting.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_regular_day_overrides() {
	return [
		5 => [21],
		9 => [17],
	];
}

// If these names change, be sure to update the is_a_*_job() functions.
// List in order of importance.
$mtg_jobs = array(
	MEETING_NIGHT_ORDERER => 'Meeting night takeout orderer',
	MEETING_NIGHT_CLEANER => 'Meeting night cleaner',
);
// list in order of importance
$sunday_jobs = array(
	// #!# note, we're looking for the string 'asst cook' in the code
	SUNDAY_HEAD_COOK => 'Sunday head cook (two meals/season)',
	SUNDAY_ASST_COOK => 'Sunday meal asst cook (two meals/season)',
	SUNDAY_CLEANER => 'Sunday Meal Cleaner',
);
// list in order of importance
$weekday_jobs = array(
	WEEKDAY_HEAD_COOK => 'Weekday head cook (two meals/season)',
	WEEKDAY_ASST_COOK => 'Weekday meal asst cook (2 meals/season)',
	WEEKDAY_CLEANER => 'Weekday Meal cleaner',
	WEEKDAY_TABLE_SETTER => 'Weekday Table Setter',
);

/*
 * Get how many dinners are contained within the requested job.
 *
 * @param[in] season array list of the months in the season.
 * @param[in] job_id int the ID of the job being requested.
 * @return int the number of dinners needed for this job.
 */
function get_num_dinners_per_assignment($season, $job_id=NULL) {
	if (empty($season)) {
		$season = get_current_season_months();
	}

	$num_months = count($season);
	$clean_num = $num_months;
	$cook_num = $num_months / 2;

	// job_id => num dinners per season
	$dinners = [
		MEETING_NIGHT_CLEANER => $cook_num,
		MEETING_NIGHT_ORDERER => $cook_num,

		SUNDAY_HEAD_COOK => $cook_num,
		SUNDAY_ASST_COOK => $cook_num,
		SUNDAY_CLEANER => $clean_num,

		WEEKDAY_ASST_COOK => $cook_num,
		WEEKDAY_HEAD_COOK => $cook_num,
		WEEKDAY_CLEANER => $clean_num,
		WEEKDAY_TABLE_SETTER => $clean_num,
	];

	// XXX do not like this... try to replace these, so that it's only using a single return type
	if (is_null($job_id)) {
		return $dinners;
	}

	return array_get($dinners, $job_id, 0);
}

/**
 *
 * ??? job_id => array( dow => count), 1 = MON, 7 = SUN
 * ??? per job, list number of open shifts per day of week
 */
function get_job_instances($job_id=NULL) {
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
		'bill',
		'debbi',
		'erik',
		'hope',
		'jillian',
		'kate',
		'kathyboblitt',
		'kevink',
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
