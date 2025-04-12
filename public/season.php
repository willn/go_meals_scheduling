<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('April 19, 2025, 8:30pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', SPRING);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

/* ----------- job ids --------------- */
define('SUNDAY_ASST_COOK', 8218);
define('SUNDAY_CLEANER', 8208);
define('SUNDAY_HEAD_COOK', 8212);
define('BRUNCH_ASST_COOK', 8222);
define('BRUNCH_CLEANER', 8227);
define('BRUNCH_HEAD_COOK', 8221);
define('WEEKDAY_ASST_COOK', 8210);
define('WEEKDAY_CLEANER', 8209);
define('WEEKDAY_HEAD_COOK', 8211);

// previous season jobs, keep for unit tests & for the future
define('MEETING_NIGHT_CLEANER', 7629);
define('MEETING_NIGHT_ORDERER', 7626);

/* ----------- job titles --------------- */
define('MEETING_NIGHT_CLEANER_NAME', 'Meeting night cleaner');
define('MEETING_NIGHT_ORDERER_NAME', 'Mtg takeout orderer (2 meals)');
define('SUNDAY_ASST_COOK_NAME', 'Sunday evening asst cook (2 meals)');
define('SUNDAY_CLEANER_NAME', 'Sunday Evening Meal cleaner (6 meals)');
define('SUNDAY_HEAD_COOK_NAME', 'Sunday Evening head cook (2 meals)');
define('BRUNCH_ASST_COOK_NAME', 'Saturday Brunch asst cook');
define('BRUNCH_CLEANER_NAME', 'Saturday Brunch Cleaner (2 instances)');
define('BRUNCH_HEAD_COOK_NAME', 'Saturday Brunch Head Cook');
# define('BRUNCH_LAUNDRY_NAME', 'Saturday Brunch Laundry');
define('WEEKDAY_ASST_COOK_NAME', 'Weekday asst cook');
define('WEEKDAY_CLEANER_NAME', 'Weekday Meal cleaner');
define('WEEKDAY_HEAD_COOK_NAME', 'Weekday head cook');
# define('WEEKDAY_LAUNDRY_NAME', 'Weekday Laundry');

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
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [

	/*
	Shift some skipped meals to the second half of the season...

	Weekday HC: 31 (-1)
	Weekday AC: 62 (-2)
	Weekday CL: 31 (-1)

	Sunday HC: 11 (-1)
	Sunday AC: 22 (-2)
	Sunday CL: 11 (-1)
	*/

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
		10 => [1],
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
		10 => [6],
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

