<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('April 22, 2023, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', SPRING);
define('DOING_CSA_FARM_MEALS', TRUE);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_CLEANER', 7057);
define('MEETING_NIGHT_ORDERER', 7054);
define('SUNDAY_ASST_COOK', 7053);
define('SUNDAY_CLEANER', 7056);
define('SUNDAY_HEAD_COOK', 7052);
define('WEEKDAY_ASST_COOK', 7051);
define('WEEKDAY_CLEANER', 7055);
define('WEEKDAY_HEAD_COOK', 7050);
# define('WEEKDAY_TABLE_SETTER', 7040); // not this season

/**
 * Get the number of shift overrides.
 *
 * @return array username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]

	/*
	 * short in spring 2023:
	 * WEEKDAY_HEAD_COOK - 1 shifts 
	 * WEEKDAY_ASST_COOK - 2 shifts
	 * WEEKDAY_CLEANER - 12 shifts
	 * 
	 * plan is to pull some labor forward from summer to spring hoping that
	 * some new members will be able to pickup some work later in the summer.
	 */

	return [
		// liam will be leaving for the second half, assign him fully for 1st half
		'liam' => [
			WEEKDAY_ASST_COOK => 1,
			SUNDAY_ASST_COOK => 1,
		],

		// pull forward, borrow from summer.
		// When summer comes, flip these to negatives:
		'dale' => [WEEKDAY_HEAD_COOK => 1],

		'marta' => [WEEKDAY_ASST_COOK => 1],
		'nancy' => [WEEKDAY_ASST_COOK => 1],

		'amanda' => [WEEKDAY_CLEANER => 2],
		'amyh' => [WEEKDAY_CLEANER => 2],
		'catherine' => [WEEKDAY_CLEANER => 1],
		'eric' => [WEEKDAY_CLEANER => 1],
		'kelly' => [WEEKDAY_CLEANER => 2],
		'michael' => [WEEKDAY_CLEANER => 2],
		'ted' => [WEEKDAY_CLEANER => 2],
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		5 => [2, 9, 14, 23],
		6 => [5, 18, 20],
		7 => [19, 26, 31],
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

