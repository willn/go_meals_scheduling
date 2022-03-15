<?php
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('April 21, 2022, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', SPRING_SUMMER);
define('DOING_CSA_FARM_MEALS', FALSE);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', 1);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_CLEANER', 6264);
define('MEETING_NIGHT_ORDERER', 6261);
define('SUNDAY_ASST_COOK', 6260);
define('SUNDAY_CLEANER', 6263);
define('SUNDAY_HEAD_COOK', 6259);
define('WEEKDAY_ASST_COOK', 6258);
define('WEEKDAY_CLEANER', 6262);
define('WEEKDAY_HEAD_COOK', 6257);
# define('WEEKDAY_TABLE_SETTER', 6247); // temporarily retired

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
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
function get_regular_day_overrides() {
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

