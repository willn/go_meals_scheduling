<?php
require_once 'globals.php';
require_once 'constants.php';
require_once 'classes/meal.php';
require_once 'mysql_api.php';

/**
 * Get an element from an array, with a backup.
 */
function array_get($array, $key, $default=NULL) {
	if (is_array($array) && !empty($array) && isset($array[$key])) {
		return $array[$key];
	}

	return $default;
}

/**
 * Combine and add the values of 2 associative arrays which have numbers as
 * values.
 *
 * @param array $one an associative array where the values are numbers.
 * @param array $two an associative array where the values are numbers.
 * @return array The combined values.
 */
function associative_array_add($one, $two) {
	$all_keys = $one + $two;
	$combined = [];

	foreach($one as $key => $value) {
		$combined[$key] = $value;
		if (isset($two[$key])) {
			$combined[$key] += $two[$key];
		}
		unset($all_keys[$key]);
	}

	// process any missed keys from two
	foreach($all_keys as $key => $value) {
		$combined[$key] = $two[$key];
	}
	return $combined;
}

/**
 * Get the upcoming season's ID.
 * Since the seasons are no longer mathematically predictable, this returns the
 * highest number from the sqlite file.
 */
function get_season_id() {
	$mysql_api = get_mysql_api();

	$sql = 'SELECT max(id) as max FROM work_app_season';
	$id = NULL;
	foreach ($mysql_api->get($sql) as $row) {
		$id = $row['max'];
	}

	return $id;
}

/**
 * Get whether this season wraps around to a new year or not.
 * @return boolean If TRUE, then the season wraps around.
 */
function does_season_wrap($season_months) {
	$count = 0;
	foreach($season_months as $month) {
		if (($month === 'January') && ($count !== 0)) {
			return TRUE;
		}
		$count++;
	}

	return FALSE;
}

/**
 * Add the easter date to the holidates array.
 *
 * By ecclesiastical rules, which fixes the date of the equinox to March 21,
 * the earliest possible date for Easter is March 22 and the latest possible
 * is April 25.
 *
 * @param array $holidays associative array for each months, each entry is
 *     an array of dates within that month which are recognized as a holiday,
 *     meaning - skip assigning that day.
 * Example: [
 *   10 => [31],
 *   12 => [24, 25, 31]
 * ]
 * @param array $season associative array, keys are the month nums and values
 *     are the month names.
 * @return array - the same as the holidays passed in.
 */
function add_easter($holidays, $season=[]) {
	// NOTE: Easter floats between March & April, so it's weird...
	# $does_wrap = does_season_wrap($season);
	# $year = ($does_wrap) ? (SEASON_YEAR + 1) : SEASON_YEAR;

	// is the next April in the current year or next?
	$this_month = date('n');
	$year = ($this_month > 4 ) ? (SEASON_YEAR + 1) : SEASON_YEAR;

	// get unix timestamp of easter at noon
	$easter_ts = easter_date($year) + (12 * 60 * 60);
	$easter_month = date('n', $easter_ts);
	$easter_day = date('j', $easter_ts);
	$holidays[$easter_month][] = $easter_day;

	return $holidays;
}


/**
 * Add Memorial Day date to the holidates array.
 * This is the last Monday of May, and the Sunday right before it.
 *
 * NOTE: This is making a big assumption:
 * That the month in which the survey an allocation are happening will
 *    finish before the meals to be assigned begins. For example, if we're running
 *    the survey in October, the first meal would happen in November.
 *
 * @param array $holidays associative array for each months, each entry is
 *     an array of dates within that month which are recognized as a holiday,
 *     meaning - skip assigning that day.
 * Example: [
 *   10 => [31],
 *   12 => [24, 25, 31]
 * ]
 * @return array - the same as the holidays passed in.
 */
function add_memorial_day($holidays) {
	$this_month = date('n');
	$may_year = ($this_month > 5 ) ? SEASON_YEAR + 1 : SEASON_YEAR;

	$mem_day = date('j', strtotime('last monday of May ' . $may_year));
	// sunday, day before
	$holidays[5][] = ($mem_day - 1);
	// monday, memorial day
	$holidays[5][] = $mem_day;

	return $holidays;
}

/**
 * Add Labor Day date to the holidates array.
 * This is the first Monday of September, and the Sunday right before it.
 * That Sunday could be the last day of August.
 *
 * NOTE: This is making a big assumption:
 * That the month in which the survey an allocation are happening will
 *    finish before the meals to be assigned begins. For example, if we're running
 *    the survey in October, the first meal would happen in November.
 *
 * @param array $holidays associative array for each months, each entry is
 *     an array of dates within that month which are recognized as a holiday,
 *     meaning - skip assigning that day.
 * Example: [
 *   10 => [31],
 *   12 => [24, 25, 31]
 * ]
 * @return array - the same as the holidays passed in.
 */
function add_labor_day($holidays) {
	$this_month = date('n');

	// is the next September in the current year or next?
	$sept_year = ($this_month > 9 ) ? (SEASON_YEAR + 1) : SEASON_YEAR;

	// labor day
	$labor_day = intval(date('j',
		strtotime('first monday of September ' . $sept_year)));

	// add Sunday before labor day...
	// If preceding Sunday is in August
	if ($labor_day === 1) {
		$holidays[8][] = 31;
	}
	else {
		$holidays[9][] = ($labor_day - 1);
	}
	$holidays[9][] = $labor_day;

	return $holidays;
}


/**
 * Add Thanksgiving date to the holidates array.
 * This is the 4th Thursday of November, and the Sunday right after it.
 *
 * NOTE: This is making a big assumption:
 * That the month in which the survey an allocation are happening will
 *    finish before the meals to be assigned begins. For example, if we're running
 *    the survey in October, the first meal would happen in November.
 *
 * @param array $holidays associative array for each months, each entry is
 *     an array of dates within that month which are recognized as a holiday,
 *     meaning - skip assigning that day.
 * Example: [
 *   10 => [31],
 *   12 => [24, 25, 31]
 * ]
 * @return array - the same as the holidays passed in.
 */
function add_thanksgiving_day($holidays) {
	$this_month = date('n');
	$nov_year = ($this_month > 11) ? SEASON_YEAR + 1 : SEASON_YEAR;

	// add Thanksgiving
	$thx_day = date('j', strtotime('fourth thursday of November ' . $nov_year));
	$holidays[11][] = $thx_day;

	// also add the following Sunday
	$last_sunday = date('j', strtotime('last sunday of November ' . SEASON_YEAR));
	if ($last_sunday > $thx_day) {
		$holidays[11][] = $last_sunday;
	}

	return $holidays;
}

/*
 * Get the list of all holidays.
 * @return associative array where the keys are the months, and the values are
 *     dates in the months.
 */
function get_holidays() {
	// start with static holidays
	$holidays = [
		1 => [1],
		7 => [4],
		10 => [31],
		12 => [24,25,31],
	];

	// get dynamic dates
	$season = get_current_season_months();
	$holidays = add_easter($holidays, $season);
	$holidays = add_memorial_day($holidays);
	$holidays = add_labor_day($holidays);
	$holidays = add_thanksgiving_day($holidays);

	ksort($holidays);
	return $holidays;
}

/**
 * Get the first key from the array
 */
function get_first_associative_key($dict) {
	if (empty($dict)) {
		return NULL;
	}

	// do this in 2 steps to avoid errors / warnings
	$tmp = array_keys($dict);
	return array_shift($tmp);
}

/**
 * Get the type of meal for a given date.
 * Isolate the logic for determining which kind of meal to use for a given date.
 *
 * @param string $date string of the date for the given meal.
 * @return int the number associated with a given constant for a type of meal night.
 */
function get_meal_type_by_date($date) {
	# skip invalid dates
	$date_ts = strtotime($date);
	if (!$date_ts) {
		return NOT_A_MEAL;
	}

	# skip invalid dates
	$day_of_week = date('N', $date_ts);
	if ($day_of_week == FALSE) {
		return NOT_A_MEAL;
	}

	# skip un-supported days of the week
	if ($day_of_week == THURSDAY) {
		return NOT_A_MEAL;
	}

	$month_num = date('n', $date_ts);
	$day_num = date('j', $date_ts);

	# if either the month or day of month turned out to be invalid
	if (($month_num == FALSE) || ($day_num == FALSE)) {
		return NOT_A_MEAL;
	}

	// check to see if this is a holiday
	$holidays = get_holidays();
	if (isset($holidays[$month_num]) &&
		in_array($day_num, $holidays[$month_num])) {
		return HOLIDAY_NIGHT;
	}

	// check to see if we're override skipping this date
	$skip_dates = get_skip_dates();
	if (isset($skip_dates[$month_num]) &&
		in_array($day_num, $skip_dates[$month_num])) {
		return SKIP_NIGHT;
	}

	switch($day_of_week) {
		case FRIDAY:
		case SATURDAY:
			return WEEKEND_MEAL;
	}

	if ($day_of_week == SUNDAY) {
		return WEEKEND_OVER_SUNDAYS ? WEEKEND_MEAL : SUNDAY_MEAL;
	}

	// this is a weekday
	$meal_days = get_weekday_meal_days();
	if (in_array($day_of_week, $meal_days)) {

		if (is_weekday_override($month_num, $day_num)) {
			return WEEKDAY_MEAL;
		}
		if (is_meeting_override($month_num, $day_num)) {
			return MEETING_NIGHT_MEAL;
		}

		# is this a meeting night?
		$mtg_nights = get_mtg_nights();
		$ordinal_int = intval(($day_num - 1) / 7) + 1;
		if (array_key_exists($day_of_week, $mtg_nights) &&
			($mtg_nights[$day_of_week] == $ordinal_int)) {
			return MEETING_NIGHT_MEAL;
		}

		return WEEKDAY_MEAL;
	}

	return NOT_A_MEAL;
}

/**
 * Determine whether this date is a meeting -> weeknight override.
 *
 * @return boolean, If TRUE then this is an override date.
 */
function is_weekday_override($month_num, $day_num) {
	# look for meeting -> weekday overrides
	$overrides = get_weekday_overrides();

	if (array_key_exists($month_num, $overrides) &&
		in_array($day_num, $overrides[$month_num])) {
			return TRUE;
	}
	return FALSE;
}

/**
 * Determine whether this date is a meeting -> weeknight override.
 *
 * @return boolean, If TRUE then this is an override date.
 */
function is_meeting_override($month_num, $day_num) {
	# look for weekday -> meeting overrides
	$overrides = get_meeting_night_overrides();

	if (array_key_exists($month_num, $overrides) &&
		in_array($day_num, $overrides[$month_num])) {
			return TRUE;
	}
	return FALSE;
}

/**
 * Essentially a factory function, instantiate a Meal child class based on the date.
 *
 * @param object $schedule instance of Schedule class.
 * @param string $date string the date of the meal.
 * @return object a child of the Meal class.
 */
function get_a_meal_object($schedule, $date) {
	$type = get_meal_type_by_date($date);
	switch($type) {
		case WEEKEND_MEAL:
			return new WeekendMeal($schedule, $date);
		case SUNDAY_MEAL:
			return new SundayMeal($schedule, $date);
		case WEEKDAY_MEAL:
			return new WeekdayMeal($schedule, $date);
		case MEETING_NIGHT_MEAL:
			return new MeetingNightMeal($schedule, $date);
		case SKIP_NIGHT:
		case HOLIDAY_NIGHT:
		case NOT_A_MEAL:
		default:
			return new Error();
	}
}

/**
 * Check to see if this is a valid season name.
 * @return boolean if this is a valid season name.
 */
function is_valid_season_name($season) {
	switch($season) {
		case WINTER:
		case SPRING:
		case SUMMER:
		case FALL:
			return TRUE;
	}

	return FALSE;
}

?>
