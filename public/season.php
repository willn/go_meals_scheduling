<?php
require_once 'constants.inc';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 18, 2019, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SUMMER, FALL, WINTER, SPRING
 */
define('SEASON_NAME', FALL);

// If this is a whole season, then 1, half .5, etc.
// XXX change this for 3...
define('SUB_SEASON_FACTOR', .5);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 5015);
define('MEETING_NIGHT_CLEANER', 5018);
define('SUNDAY_HEAD_COOK', 5013);
define('SUNDAY_ASST_COOK', 5014);
define('SUNDAY_CLEANER', 5017);
define('WEEKDAY_HEAD_COOK', 5011);
define('WEEKDAY_ASST_COOK', 5012);
define('WEEKDAY_CLEANER', 5016);
define('WEEKDAY_TABLE_SETTER', 5004);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
	return [
		/*
		Holes to fill for the 6 month season:
		- Sunday head cook: 4
		- Sunday meal asst cook: 6
		- Sunday Meal Cleaner: 12
		- Weekday Meal cleaner: 30
		*/

/*
		// volunteers for the first 3 months
		'dorothy' => [WEEKDAY_CLEANER => 3],
		'niecy' => [WEEKDAY_CLEANER => 3],
		'patti' => [WEEKDAY_CLEANER => 3],
		'polly' => [WEEKDAY_CLEANER => 2],
		'suzette' => [WEEKDAY_CLEANER => 2],
		'tammy' => [WEEKDAY_ASST_COOK => 4],
		'ted' => [WEEKDAY_CLEANER => 2],
*/
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
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
 * @param[in] season_name
 * @return array list of month names contained in the requested season.
 */
function get_current_season_months($season_name=NULL) {
	if (is_null($season_name)) {
		$season_name = SEASON_NAME;
	}

	switch($season_name) {
		case WINTER:
			return [
				2=>'February',
				3=>'March',
				4=>'April',
			];

		case SPRING:
			return [
				5=>'May',
				6=>'June',
				7=>'July',
			];

		case SUMMER:
			return [	
				8=>'August',
				9=>'September',
				10=>'October',
			];

		case FALL:
			return [
				11=>'November',
				12=>'December',
				1=>'January',
			];

		default:
			return;
	}
}

