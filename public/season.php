<?php
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 24 , 2026, 6:15pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', FALL);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

// Whether we're hosting CSA farm meals this summer.
define('DOING_CSA_FARM_MEALS', FALSE);

/* ----------- job ids --------------- */
define('SUNDAY_ASST_COOK', 11711);
define('SUNDAY_CLEANER', 11701);
define('SUNDAY_HEAD_COOK', 11705);
define('BRUNCH_ASST_COOK', 11715);
define('BRUNCH_CLEANER', 11720);
define('BRUNCH_HEAD_COOK', 11714);
define('WEEKDAY_ASST_COOK', 11703);
define('WEEKDAY_CLEANER', 11702);
define('WEEKDAY_HEAD_COOK', 11704);

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
		12 => [21], // moved to 12-02
		1 => [18], // MLK day
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
		12 => [2], // moved from 12-21
		1 => [20], // meeting night moved to 20th due to MLK day
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

