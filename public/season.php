<?php
/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 21, 2018, 8:00pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 4595);
define('MEETING_NIGHT_CLEANER', 4598);
define('SUNDAY_HEAD_COOK', 4593);
define('SUNDAY_ASST_COOK', 4594);
define('SUNDAY_CLEANER', 4597);
define('WEEKDAY_HEAD_COOK', 4591);
define('WEEKDAY_ASST_COOK', 4592);
define('WEEKDAY_CLEANER', 4596);
define('WEEKDAY_TABLE_SETTER', 4584);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
	return [
		/*
		 * XXX these 3 need to be distributed throughout the 6-month
		 * mega season... so do a pair of 1 and 1 for each 2-mo sub-season.
		 */

		// nov-dec:
		// ---------------------------------
		'maryking' => [WEEKDAY_CLEANER => 1],
		'patti' => [WEEKDAY_CLEANER => 1],
		'nancy' => [SUNDAY_ASST_COOK => -1],
		'liam' => [SUNDAY_ASST_COOK => 1],

		/*
		// jan-feb:
		// ---------------------------------
		'patti' => [WEEKDAY_CLEANER => 1],
		'rod' => [WEEKDAY_CLEANER => 1],
		'nancy' => [SUNDAY_ASST_COOK => -1],
		'liam' => [SUNDAY_ASST_COOK => 1],

		// leftovers from nov-dec:
		// likely shorter "season" due to 2 big holidays...
		'annie' => [WEEKDAY_ASST_COOK => 2],
		'gayle' => [WEEKDAY_CLEANER => 1],
		'gregd' => [WEEKDAY_HEAD_COOK => 1, WEEKDAY_ASST_COOK => 1],
		'jennifer' => [WEEKDAY_ASST_COOK => 1],
		'keithg' => [WEEKDAY_HEAD_COOK => 1],
		'mac' => [WEEKDAY_CLEANER => 2],
		'marta' => [WEEKDAY_ASST_COOK => 1, WEEKDAY_TABLE_SETTER => 4],
		'marys' => [WEEKDAY_CLEANER => 1],
		'michael' => [WEEKDAY_CLEANER => 3],
		'tammy' => [WEEKDAY_HEAD_COOK => 1],
		'terrence' => [WEEKDAY_CLEANER => 1],
		*/

		/*
		// mar-apr:
		// ---------------------------------
		'maryking' => [WEEKDAY_CLEANER => 1],
		'rod' => [WEEKDAY_CLEANER => 1],
		'nancy' => [SUNDAY_ASST_COOK => -1],
		'liam' => [SUNDAY_ASST_COOK => 1],
		*/
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
				1=>'January',
				2=>'February',
				3=>'March',
				4=>'April',
			];

		case SPRING:
		case SUMMER:
			return [	
				5=>'May',
				6=>'June',
				7=>'July',
				8=>'August',
				9=>'September',
				10=>'October',
			];

		case FALL:
			return [
				11=>'November',
				12=>'December',
			];
	}
}

