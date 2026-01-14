<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('Jan 24, 2026, 7:30pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', WINTER);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

/* ----------- job ids --------------- */
define('SUNDAY_ASST_COOK', 8523);
define('SUNDAY_CLEANER', 8513);
define('SUNDAY_HEAD_COOK', 8517);
define('BRUNCH_ASST_COOK', 8527);
define('BRUNCH_CLEANER', 8532);
define('BRUNCH_HEAD_COOK', 8526);
define('WEEKDAY_ASST_COOK', 8515);
define('WEEKDAY_CLEANER', 8514);
define('WEEKDAY_HEAD_COOK', 8516);

// previous season jobs, keep for unit tests & for the future
define('MEETING_NIGHT_CLEANER', 7629);
define('MEETING_NIGHT_ORDERER', 7626);

/* ----------- job titles --------------- */
define('MEETING_NIGHT_CLEANER_NAME', 'Meeting night cleaner');
define('MEETING_NIGHT_ORDERER_NAME', 'Mtg takeout orderer (2 meals)');
define('SUNDAY_ASST_COOK_NAME', 'Sunday Assistant Cook (2 meals)');
define('SUNDAY_CLEANER_NAME', 'Sunday Meal Cleaner (6 meals)');
define('SUNDAY_HEAD_COOK_NAME', 'Sunday Head Cook (2 meals)');
define('BRUNCH_ASST_COOK_NAME', '4th Saturday Brunch Asst Cook (2 meals)');
define('BRUNCH_CLEANER_NAME', '4th Saturday Brunch Cleaner (2 meals)');
define('BRUNCH_HEAD_COOK_NAME', '4th Saturday Brunch Head Cook (2 meals)');
# define('BRUNCH_LAUNDRY_NAME', 'Saturday Brunch Laundry');
define('WEEKDAY_ASST_COOK_NAME', 'Weekday Assistant Cook (2 meals)');
define('WEEKDAY_CLEANER_NAME', 'Weekday Meal Cleaner (6 meals)');
define('WEEKDAY_HEAD_COOK_NAME', 'Weekday Head Cook (2 meals)');
# define('WEEKDAY_LAUNDRY_NAME', 'Weekday Laundry');

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
		'melanie' => [
			WEEKDAY_ASST_COOK => -1,
			BRUNCH_ASST_COOK => 1,
		], 
		'melissaf' => [
			WEEKDAY_ASST_COOK => 1,
			BRUNCH_ASST_COOK => -1
		],

		// XXX - for 2nd half of season, renew the melanie to melissaf swap of
		// weekday asst cook & brunch asst cook
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		4 => [25],
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
		1 => [19],
		4 => [1],
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
		1 => [21],
		4 => [6],
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
		2 => 'February',
		3 => 'March',
		4 => 'April',
	];
	$spring = [
		5 => 'May',
		6 => 'June',
		7 => 'July',
	];
	$summer = [
		8 => 'August',
		9 => 'September',
		10 => 'October',
	];
	$fall = [
		11 => 'November',
		12 => 'December',
		1 => 'January',
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

