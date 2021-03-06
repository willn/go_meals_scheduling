<?php
require_once 'globals.php';
require_once 'constants.inc';
require_once 'classes/meal.php';
require_once 'gather_utils.php';

define('SEASON_ID', get_season_id());

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
 * Get the upcoming season's ID.
 * Since the seasons are no longer mathematically predictable, this returns the
 * highest number from the sqlite file.
 */
function get_season_id() {
	$dbh = create_sqlite_connection();

	$sql = 'SELECT max(id) FROM work_app_season';
	$id = NULL;
	foreach ($dbh->query($sql) as $row) {
		$id = $row[0];
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
 * @param[in] holidays associative array for each months, each entry is
 *     an array of dates within that month which are recognized as a holiday,
 *     meaning - skip assigning that day.
 * Example: [
 *   10 => [31],
 *   12 => [24, 25, 31]
 * ]
 * @param[in] season associative array, keys are the month nums and values
 *     are the month names.
 * @return associative array - the same as the holidays passed in.
 */
function add_easter($holidays, $season=[]) {
	/*
	XXX why did I skip adding easter? Is it for an end of year season?
	// if Easter doesn't happen this season, then don't add anything
	if (!isset($season[3]) && !isset($season[4])) {
		return $holidays;
	}
	*/

	$does_wrap = does_season_wrap($season);
	$year = ($does_wrap) ? (SEASON_YEAR + 1) : SEASON_YEAR;

	// get unix timestamp of easter at noon
	$easter_ts = easter_date($year) + (12 * 60 * 60);
	$easter_month = date('n', $easter_ts);
	$easter_day = date('j', $easter_ts);
	$holidays[$easter_month][] = $easter_day;

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
		12 => [24,25, 31],
	];

	$season = get_current_season_months();
	$holidays = add_easter($holidays, $season);

	// *** memorial day ***
	$mem_day = date('j', strtotime('last monday of May, ' . SEASON_YEAR));
	// sunday, day before
	$holidays[5][] = ($mem_day - 1);
	// monday, memorial day
	$holidays[5][] = $mem_day;

	// *** sunday before labor day ***
	// if last day of aug is sunday, then next day is labor day... skip
	$last_aug = date('D', strtotime('last day of August, ' . SEASON_YEAR));
	if ($last_aug == 'Sun') {
		$holidays[8][] = 31;
	}

	// *** labor day ***
	$labor_day = date('j', strtotime('first monday of September, ' . SEASON_YEAR));
	// if the Sunday before is in Sept, then skip it
	if ($labor_day > 1) {
		$holidays[9][] = ($labor_day - 1);
	}
	$holidays[9][] = $labor_day;

	// *** thanksgiving ***
	$thx_day = date('j', strtotime('fourth thursday of November, ' . SEASON_YEAR));
	$holidays[11][] = $thx_day;
	$last_sunday = date('j', strtotime('last sunday of November, ' . SEASON_YEAR));
	if ($last_sunday > $thx_day) {
		$holidays[11][] = $last_sunday;
	}

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
 * @param[in] date string of the date for the given meal.
 * @return int the number associated with a given constant for a type of meal night.
 */
function get_meal_type_by_date($date) {
	$date_ts = strtotime($date);
	if (!$date_ts) {
		return NOT_A_MEAL;
	}

	$day_of_week = date('N', $date_ts);
	if (!$day_of_week) {
		return NOT_A_MEAL;
	}

	$month_num = date('n', $date_ts);
	$day_num = date('j', $date_ts);
	if (!$month_num || !$day_num) {
		return NOT_A_MEAL;
	}

	// check to see if this is a holiday
	$holidays = get_holidays(SEASON_NAME);
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

	$unique = ARE_SUNDAYS_UNIQUE ? 1 : 0;
	if (ARE_SUNDAYS_UNIQUE && ($day_of_week == 7)) {
		return SUNDAY_MEAL;
	}

	// this is a weekday
	$meal_days = get_weekday_meal_days();
	if (in_array($day_of_week, $meal_days)) {
		$reg_day_overrides = get_regular_day_overrides();

		$is_reg_day_override = FALSE;
		if (array_key_exists($month_num, $reg_day_overrides) &&
			in_array($day_num, $reg_day_overrides[$month_num])) {
				$is_reg_day_override = TRUE;
		}

		$mtg_nights = get_mtg_nights();
		$ordinal_int = intval(($day_num - 1) / 7) + 1;
		if (!$is_reg_day_override &&
			array_key_exists($day_of_week, $mtg_nights) &&
			($mtg_nights[$day_of_week] == $ordinal_int)) {
			return MEETING_NIGHT_MEAL;
		}

		return WEEKDAY_MEAL;
	}

	return NOT_A_MEAL;
}

/**
 * Essentially a factory function, instantiate a Meal child class based on the date.
 *
 * @param[in] date string the date of the meal.
 * @return object a child of the Meal class.
 */
function get_a_meal_object($schedule, $date, $meal_num) {
	$type = get_meal_type_by_date($date);
	switch($type) {
		case SUNDAY_MEAL: return new SundayMeal($schedule, $date, $meal_num);
		case WEEKDAY_MEAL: return new WeekdayMeal($schedule, $date, $meal_num);
		case MEETING_NIGHT_MEAL: return new MeetingNightMeal($schedule, $date, $meal_num);
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
