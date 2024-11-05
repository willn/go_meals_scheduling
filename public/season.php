<?php
date_default_timezone_set('America/Detroit');
require_once 'constants.php';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 26, 2024, 9:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SPRING, SPRING_SUMMER, SUMMER, FALL, FALL_WINTER, WINTER
 */
define('SEASON_NAME', FALL);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

/* ----------- job ids --------------- */
define('SUNDAY_ASST_COOK', 7919);
define('SUNDAY_CLEANER', 7923);
define('SUNDAY_HEAD_COOK', 7918);
define('BRUNCH_ASST_COOK', 8042);
define('BRUNCH_CLEANER', 8043);
define('BRUNCH_HEAD_COOK', 8041);
define('WEEKDAY_ASST_COOK', 7917);
define('WEEKDAY_CLEANER', 7922);
define('WEEKDAY_HEAD_COOK', 7916);

// placeholder for future jobs
# define('WEEKDAY_LAUNDRY', 9998);
# define('BRUNCH_LAUNDRY', 9999);

// previous season jobs, keep for unit tests & for the future
define('MEETING_NIGHT_CLEANER', 7629);
define('MEETING_NIGHT_ORDERER', 7626);

/* ----------- job titles --------------- */
define('MEETING_NIGHT_CLEANER_NAME', 'Meeting night cleaner');
define('MEETING_NIGHT_ORDERER_NAME', 'Mtg takeout orderer (2 meals)');
define('SUNDAY_ASST_COOK_NAME', 'Sunday evening asst cook');
define('SUNDAY_CLEANER_NAME', 'Sunday Evening Meal cleaner');
define('SUNDAY_HEAD_COOK_NAME', 'Sunday Evening head cook');
define('BRUNCH_ASST_COOK_NAME', 'Saturday Brunch asst cook');
define('BRUNCH_CLEANER_NAME', 'Saturday Brunch Meal cleaner');
define('BRUNCH_HEAD_COOK_NAME', 'Saturday Brunch head cook');
# define('BRUNCH_LAUNDRY_NAME', 'Saturday Brunch Laundry');
define('WEEKDAY_ASST_COOK_NAME', 'Weekday asst cook');
define('WEEKDAY_CLEANER_NAME', 'Weekday Meal cleaner');
define('WEEKDAY_HEAD_COOK_NAME', 'Weekday head cook');
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
		# cancel these both sub-seasons
		'adam' => [BRUNCH_CLEANER => 1],
		'alexc' => [
			BRUNCH_CLEANER => 1,
			WEEKDAY_CLEANER => -1,
		],
		'dan' => [
			BRUNCH_ASST_COOK => -1,
			BRUNCH_CLEANER => -3,
		],
		'danielle' => [
			BRUNCH_CLEANER => 1,
			BRUNCH_HEAD_COOK => -2, # shift to second half of season
		],
		'eric' => [BRUNCH_CLEANER => 1],
		# eric could have another brunch head cook in 2nd half of season
		'lauram' => [BRUNCH_HEAD_COOK => 1], #borrow 1 from 2nd half of season
		'missy' => [
			BRUNCH_CLEANER => -3,
			WEEKDAY_CLEANER => 1,
		],
	];
}

/*
Brunch assignments:
Nov 2nd: Lissa, Adam, Danielle (for her meal)
Dec 7th: Lissa, Alex and Eric (for his meal)
*/


/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		11 => [3, 5, 27], # election day already has a meal
		12 => [23, 29],
		1 => [4, 5], # short staff on brunches
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
		/*
		 * the MLK day community meeting will be Wed., Jan 22
		 * instead of Jan. 20. Due to Ash Wednesday, the meeting
		 * that would have been on March 5 meeting would be
		 * Monday, March 3.
		 */
		3 => [5],
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
		# No meeting night meals this season
		1 => [6, 22],
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

