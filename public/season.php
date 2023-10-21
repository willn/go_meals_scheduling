<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 21, 2023, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', FALL);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);
define('WEEKEND_OVER_SUNDAYS', TRUE);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_CLEANER', 7338);
define('MEETING_NIGHT_ORDERER', 7335);
define('WEEKEND_ASST_COOK', 7334);
define('WEEKEND_CLEANER', 7337);
define('WEEKEND_HEAD_COOK', 7333);
define('WEEKDAY_ASST_COOK', 7332);
define('WEEKDAY_CLEANER', 7336);
define('WEEKDAY_HEAD_COOK', 7331);

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
define('WEEKDAY_ASST_COOK_NAME', 'Weekday asst cook');
define('WEEKDAY_CLEANER_NAME', 'Weekday Meal cleaner');
define('WEEKDAY_HEAD_COOK_NAME', 'Weekday head cook');


/**
 * Whether we're hosting CSA farm meals this summer.
 */
function doing_csa_farm_meals() {
	return FALSE;
}

/**
 * Get the number of shift overrides.
 *
 * @return array username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	return [
		'emilyadama' => [WEEKDAY_HEAD_COOK => 1],
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		// populated with weekend dates, not listing the dates we're using
		11 => [3, 4, 5, 10, 12, 17, 18, 19, 24, 25],
		12 => [1, 2, 8, 9, 10, 16, 17, 22, 23, 29, 30],
		1 => [5, 6, 12, 14, 19, 20, 26, 27, 28],
	];
}

/**
 * Reserve and declare the timing of special weekend meals.
 */
function get_special_weekend_days() {
	return [
		'11/11/2023' => ['head_cook' => 'eric', 'type' => 'brunch'],
		'12/3/2023' => ['head_cook' => 'frank', 'type' => 'brunch'],
		'12/15/2023' => ['head_cook' => 'lauram', 'type' => 'dinner'],
		'1/7/2024' => ['head_cook' => 'dan', 'type' => 'brunch'],
		'1/13/2024' => ['head_cook' => 'eric', 'type' => 'brunch'],
		'1/21/2024' => ['head_cook' => 'dan', 'type' => 'brunch'],
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

