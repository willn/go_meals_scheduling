<?php
require_once 'constants.inc';

/* -------- seasonal config --------- */
define('DEADLINE', strtotime('Apr 18, 2019, 9:00pm'));

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SUMMER, FALL, WINTER, SPRING
 */
define('SEASON_NAME', SUMMER);

// If this is a whole season, then 1, half .5, etc.
define('SUB_SEASON_FACTOR', .5);

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
		'annie' => [
			SUNDAY_ASST_COOK => -2,
			SUNDAY_HEAD_COOK => 2,
		],
		'dan' => [
			WEEKDAY_ASST_COOK => 2,
		],
		'jake' => [
			WEEKDAY_ASST_COOK => -2,
			SUNDAY_ASST_COOK => 3,
		],
		'rod' => [
			WEEKDAY_ASST_COOK => 1,
		],
		'sharon' => [
			WEEKDAY_HEAD_COOK => 1,
		],
		'ted' => [
			WEEKDAY_HEAD_COOK => 1,
		],

		/*
		* add/remove shifts used for borrowing in 1st sub-season:
		'annie' => [
			SUNDAY_ASST_COOK => -2,
			SUNDAY_HEAD_COOK => -1
			WEEKDAY_TABLE_SETTER => 2,
		],
		'bennie' => [
			WEEKDAY_CLEANER => 2,
		],
		'dan' => [
			WEEKDAY_ASST_COOK => -2,
			WEEKDAY_CLEANER => 1,
		],
		'gayle' => [
			WEEKDAY_CLEANER => 1,
		],
		'hermann' => [
			WEEKDAY_TABLE_SETTER => -1,
		],
		'jake' => [WEEKDAY_ASST_COOK => -2],
		'marys' => [
			WEEKDAY_CLEANER => 1,
		],
		'megan' => [SUNDAY_HEAD_COOK => 1],
		'michael' => [
			WEEKDAY_CLEANER => 1,
		],
		'nancy' => [
			WEEKDAY_TABLE_SETTER => -1,
		],
		'polly' => [
			WEEKDAY_CLEANER => 2,
		],
		'rod' => [
			WEEKDAY_ASST_COOK => -1,
		],
		'sharon' => [
			WEEKDAY_HEAD_COOK => -1,
		],
		'ted' => [
			WEEKDAY_HEAD_COOK => -1,
		],

		Extra labor likely needed for the 2nd sub-season:
		- 3 meals of Weekday head cook
		- 6 meals of Weekday asst cook
		- 3 meals of Weekday table setter
		- 3 meals of Weekday Meal cleaner
		- 1 meal of Sunday asst cook

		We would have this extra labor:
		- 4 meals of Weekday asst cook
		- 6 meals of Weekday Meal cleaner

		Perhaps some of these could be converted?
		Perhaps a new member could pick up some of the above?
		*/
	];
}

/**
 * Get the list of dates to skip or cancel, don't schedule a meal on this date.
 * @return array keys are the month number, values are an array of day numbers.
 */
function get_skip_dates() {
	return [
		6 => [3, 24],
		7 => [22],
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

