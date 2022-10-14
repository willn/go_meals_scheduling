<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 22, 2022, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', FALL_WINTER);
define('DOING_CSA_FARM_MEALS', FALSE);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', 1);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_CLEANER', 6791);
define('MEETING_NIGHT_ORDERER', 6788);
define('SUNDAY_ASST_COOK', 6787);
define('SUNDAY_CLEANER', 6790);
define('SUNDAY_HEAD_COOK', 6786);
define('WEEKDAY_ASST_COOK', 6785);
define('WEEKDAY_CLEANER', 6789);
define('WEEKDAY_HEAD_COOK', 6784);
# define('WEEKDAY_TABLE_SETTER', 6774); not this season

/**
 * Get the number of shift overrides.
 *
 * @return array username => array(job_id => num_meals)
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

