<?php
/* -------- seasonal config --------- */
define('DEADLINE', strtotime('October 21, 2018, 8:00pm'));

/* ----------- job ids --------------- */
define('MEETING_NIGHT_ORDERER', 4393);
define('MEETING_NIGHT_CLEANER', 4396);
define('SUNDAY_HEAD_COOK', 4391);
define('SUNDAY_ASST_COOK', 4392);
define('SUNDAY_CLEANER', 4395);
define('WEEKDAY_HEAD_COOK', 4389);
define('WEEKDAY_ASST_COOK', 4390);
define('WEEKDAY_CLEANER', 4394);
define('WEEKDAY_TABLE_SETTER', 4382);

/**
 * Get the number of shift overrides.
 * Note: this is formatted like this:
 * username => array(job_id => num_meals)
 */
function get_num_shift_overrides() {
	// username => [job_id => num_meals]
	return [];
}

/**
 * Get the list of dates to skip, don't schedule a meal on this date.
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [];
}

/**
 * Get the list of dates to force making a regular weekday night instead of
 * a meeting.
 *
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_regular_day_overrides() {
	return [];
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

