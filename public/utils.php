<?php
require_once 'constants.inc';

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
 */
function get_season_id() {
	$start_date = 'September 1st, 2007, 12pm';
	$start = new DateTime($start_date);

	$now = new DateTime();
	$diff = date_diff($start, $now);

	$out = ($diff->y * 3) + floor($diff->m / 3);
	return $out;
}

function get_season_name($date=NULL) {
	if (is_null($date)) {
		$date = time();
	}
	$month = date('n', $date);

	switch($month) {
		case 11:
		case 12:
		case 1:
		case 2:
			return WINTER;

		case 3:
		case 4:
		case 5:
		case 6:
			return SUMMER;

		case 7:
		case 8:
		case 9:
		case 10:
			return FALL;
	}
}

/**
 * Add the easter date to the holidates array.
 */
function add_easter($holidays) {
	// add easter, which floats between march and april
	$easter_month = date('n', easter_date(SEASON_YEAR));
	$easter_day = date('j', easter_date(SEASON_YEAR));
	$holidays[$easter_month][] = $easter_day;

	return $holidays;
}

/*
 * Get the list of holidays for the current season
 * @param[in] season_name string (spring, summer, fall, winter)
 * @return associative array where the keys are the months, and the values are
 *     dates in the months.
 */
function get_holidays($season_name) {
	// month num => array(date,...)
	$holidays = [];

	switch($season_name) {
		case WINTER:
			$holidays = [
				1 => [1],
			];
			break;

		case SPRING:
		case SUMMER:
			// 4th of july
			$holidays = [
				7 => array(4),
			];
			$holidays = add_easter($holidays);

			// add memorial day
			$mem_day = date('j', strtotime('last monday of May, ' . SEASON_YEAR));
			// sunday, day before
			$holidays[5][] = ($mem_day - 1);
			// monday, memorial day
			$holidays[5][] = $mem_day;

			// sunday before labor day
			// if last day of aug is sunday, then next day is labor day... skip
			$last_aug = date('D', strtotime('last day of August, ' . SEASON_YEAR));
			if ($last_aug == 'Sun') {
				$holidays[8][] = 31;
			}

			break;

		case FALL:
			// start with fixed dates
			$holidays = array(
				10 => array(31),
				12 => array(24,25, 31),
			);

			// labor day
			$labor_day = date('j', strtotime('first monday of September, ' . SEASON_YEAR));
			// if the Sunday before is in Sept, then skip it
			if ($labor_day > 1) {
				$holidays[9][] = ($labor_day - 1);
			}
			$holidays[9][] = $labor_day;

			// thanksgiving
			$thx_day = date('j', strtotime('fourth thursday of November, ' . SEASON_YEAR));
			$holidays[11][] = $thx_day;
			$last_sunday = date('j', strtotime('last sunday of November, ' . SEASON_YEAR));
			if ($last_sunday > $thx_day) {
				$holidays[11][] = $last_sunday;
			}

			break;
	}

	ksort($holidays);
	return $holidays;
}
?>
