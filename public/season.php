<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('July 25, 2024, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', SUMMER);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);
// Weekend meals instead of Sunday meals
define('WEEKEND_OVER_SUNDAYS', TRUE);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_CLEANER', 7629);
define('MEETING_NIGHT_ORDERER', 7626);
define('WEEKEND_ASST_COOK', 7624);
define('WEEKEND_CLEANER', 7628);
define('WEEKEND_HEAD_COOK', 7623);
define('WEEKDAY_ASST_COOK', 7622);
define('WEEKDAY_CLEANER', 7627);
define('WEEKDAY_HEAD_COOK', 7621);

// placeholder for future jobs
define('WEEKDAY_LAUNDRY', 9998);
define('WEEKEND_LAUNDRY', 9999);

// previous season jobs, keep for unit tests & for the future
define('SUNDAY_ASST_COOK', 7053);
define('SUNDAY_CLEANER', 7056);
define('SUNDAY_HEAD_COOK', 7052);

/* ----------- job titles --------------- */
define('MEETING_NIGHT_CLEANER_NAME', 'Meeting night cleaner');
define('MEETING_NIGHT_ORDERER_NAME', 'Mtg takeout orderer (2 meals)');
define('SUNDAY_ASST_COOK_NAME', 'Sunday asst cook');
define('SUNDAY_CLEANER_NAME', 'Sunday Meal cleaner');
define('SUNDAY_HEAD_COOK_NAME', 'Sunday head cook');
define('WEEKEND_ASST_COOK_NAME', 'Weekend asst cook');
define('WEEKEND_CLEANER_NAME', 'Weekend Meal cleaner');
define('WEEKEND_HEAD_COOK_NAME', 'Weekend head cook');
define('WEEKEND_LAUNDRY_NAME', 'Weekend Laundry');
define('WEEKDAY_ASST_COOK_NAME', 'Weekday asst cook');
define('WEEKDAY_CLEANER_NAME', 'Weekday Meal cleaner');
define('WEEKDAY_HEAD_COOK_NAME', 'Weekday head cook');
define('WEEKDAY_LAUNDRY_NAME', 'Weekday Laundry');


/**
 * Whether we're hosting CSA farm meals this summer.
 */
function doing_csa_farm_meals() {
	return TRUE;
}

/**
 * Get the number of shift overrides.
 *
 * @return array username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	return [

		'amyh' => [WEEKDAY_CLEANER => 1],
		'lissa' => [
			WEEKDAY_CLEANER => 5,
			WEEKDAY_ASST_COOK => 3,
		],
		'marta' => [WEEKDAY_CLEANER => 2],
		'pat' => [WEEKDAY_CLEANER => 1],
		'sallie' => [WEEKDAY_CLEANER => 1],
		'trisha' => [WEEKDAY_CLEANER => 1],
		'dales' => [
			WEEKDAY_CLEANER => 4,
			WEEKEND_CLEANER => 2,
			WEEKDAY_ASST_COOK => 2,
		],

		'alexc' => [
			WEEKDAY_CLEANER => -4,
			WEEKDAY_ASST_COOK => -2,
		],
		'andrew' => [WEEKDAY_CLEANER => -3],
		'jeff' => [WEEKDAY_CLEANER => -3],
		'keithg' => [WEEKDAY_CLEANER => -3],
		'kelly' => [WEEKDAY_CLEANER => -2],
		'mac' => [WEEKEND_CLEANER => -2],
		'suzette' => [WEEKDAY_ASST_COOK => -2],
		'rossella' => [WEEKDAY_ASST_COOK => -1],
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		8 => [3, 7, 11, 12, 14, 18, 20, 27, 28],
		9 => [3, 7, 10, 11, 15, 24, 25],
		10 => [9, 13, 20, 23],
	];
}

/**
 * Reserve and declare the timing of special weekend meals.
 */
function get_special_weekend_days() {
	return [
	];
}

/**
 * Get the list of dates to force making a regular weekday night meal instead of
 * a meeting.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_weekday_overrides() {
	return [
		10 => [21],
	];
}

/**
 * Get the list of dates to force making a meeting night meal instead of
 * a regular weeknight.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_meeting_night_overrides() {
	return [
	];
}

/**
 * Get the months contained in the current season.
 *
 * @param string $season_name
 * @return array list of month names contained in the requested season.
 */
function get_current_season_months($season_name=NULL) {
	if (is_null($season_name)) {
		$season_name = SEASON_NAME;
	}

	$winter = [
		2=>'February',
		3=>'March',
		4=>'April',
	];
	$spring = [
		5=>'May',
		6=>'June',
		7=>'July',
	];
	$summer = [
		8=>'August',
		9=>'September',
		10=>'October',
	];
	$fall = [
		11=>'November',
		12=>'December',
		1=>'January',
	];

	switch($season_name) {
		case WINTER:
			return $winter;
		case SPRING:
			return $spring;
		case SPRING_SUMMER:
			return ($spring + $summer);
		case SUMMER:
			return $summer;
		case FALL:
			return $fall;
		case FALL_WINTER:
			return ($fall + $winter);
		default:
			return [];
	}
}

