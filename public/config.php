<?php
global $relative_dir;
if (!strlen($relative_dir)) {
	$relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.inc";
date_default_timezone_get('America/Detroit');

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('april 21, 2017, 8:15pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 3745);
define('MEETING_NIGHT_CLEANER', 3748);
define('SUNDAY_HEAD_COOK', 3743);
define('SUNDAY_ASST_COOK', 3744);
define('SUNDAY_CLEANER', 3747);
define('WEEKDAY_HEAD_COOK', 3741);
define('WEEKDAY_ASST_COOK', 3742);
define('WEEKDAY_CLEANER', 3746);
define('WEEKDAY_TABLE_SETTER', 3735);

// forced skip dates
global $skip_dates;
$skip_dates = array(
);


// have a meal on this date which wouldn't otherwise
global $override_dates;
$override_dates = array(
	//11 => array(26)
);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => array(job_id => num_meals)
	return [
/*
		'hope' => [
			WEEKDAY_CLEANER => 2,
		],
*/
	];
}

/*
 * XXX unassigned:
 * - 2 sunday cleans
 */

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
 * @param[in] job_id int the ID of the job being requested.
 * @return int the number of dinners needed for this job.
 */
function get_num_dinners_per_assignment($job_id=NULL) {
	// job_id => num dinners per season
	static $dinners = array(
		MEETING_NIGHT_CLEANER => 2,
		MEETING_NIGHT_ORDERER => 2,

		SUNDAY_HEAD_COOK => 2,
		SUNDAY_ASST_COOK => 2,
		SUNDAY_CLEANER => 4,

		WEEKDAY_ASST_COOK => 2,
		WEEKDAY_HEAD_COOK => 2,
		WEEKDAY_CLEANER => 4,
		WEEKDAY_TABLE_SETTER => 4,
	);

	if (is_null($job_id)) {
		return $dinners;
	}

	return array_get($dinners, $job_id, 0);
}

// #!# is this used anywhere?
$hours_per_job = array(
	MEETING_NIGHT_ORDERER => 1,
	MEETING_NIGHT_CLEANER => 1.5,

	SUNDAY_HEAD_COOK => 4,
	SUNDAY_ASST_COOK => 2,
	SUNDAY_CLEANER => 1.5,

	WEEKDAY_HEAD_COOK => 4,
	WEEKDAY_ASST_COOK => 2,
	WEEKDAY_CLEANER => 1.5,
);

// job_id => array( dow => count), 1 = MON, 7 = SUN
// per job, list number of open shifts per day of week
function get_job_instances() {
	return [
		MEETING_NIGHT_CLEANER => array(1=>1, 3=>1),
		MEETING_NIGHT_ORDERER => array(1=>1, 3=>1),

		SUNDAY_HEAD_COOK => array(7=>1),
		SUNDAY_ASST_COOK => array(7=>2),
		SUNDAY_CLEANER => array(7=>3),

		WEEKDAY_HEAD_COOK => array(1=>1, 2=>1, 3=>1, 4=>1),
		WEEKDAY_ASST_COOK => array(1=>2, 2=>2, 3=>2, 4=>2),
		WEEKDAY_CLEANER => array(1=>3, 2=>3, 3=>3, 4=>3),
		WEEKDAY_TABLE_SETTER => array(1=>1, 2=>1, 3=>1, 4=>1),
	];
}

global $hobarters;
$hobarters = array(
	'amyh',
	'debbi',
	'erik',
	'hope',
	'jillian',
	'jimgraham',
	'kathyboblitt',
	'mac',
	'maryking',
	'patti',
	'rod',
	'sarah',
	'sharon',
	'ted',
	'willie',
);

?>
