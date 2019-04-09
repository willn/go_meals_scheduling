<?php
require_once 'constants.inc';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('Apr 18, 2019, 8:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SUMMER, FALL, WINTER, SPRING
 */
define('SEASON_NAME', SPRING);

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 4805);
define('MEETING_NIGHT_CLEANER', 4808);
define('SUNDAY_HEAD_COOK', 4803);
define('SUNDAY_ASST_COOK', 4804);
define('SUNDAY_CLEANER', 4807);
define('WEEKDAY_HEAD_COOK', 4801);
define('WEEKDAY_ASST_COOK', 4802);
define('WEEKDAY_CLEANER', 4806);
define('WEEKDAY_TABLE_SETTER', 4794);

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
 * Get the list of dates to skip, don't schedule a meal on this date.
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
				2=>'February',
			];

		default:
			return;
	}
}

