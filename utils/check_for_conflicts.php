<?php
require_once '../public/utils.php';

define('FILE', 'schedule.txt');

/**
 * Parse a schedule.txt file into an associative array.
 */
function parse_schedule_file($filename=FILE) {
	if (!file_exists($filename)) {
		return NULL;
	}

	$content = file($filename);
	$header = array_shift($content);
	$cols = array_map('trim', explode("\t", $header));

	$out = [];
	foreach($content as $line) {
		$pieces = array_map('trim', explode("\t", $line));

		# ignore missed hobarters line
		if ((count($pieces) == 1) && (substr($line, 0, 17) === 'MISSED HOBARTERS:')) {
			continue;
		}

		$out[] = array_combine($cols, $pieces);
	}

	return $out;
}


/**
 * Look to see if there are 2 of the same assignments at once (e.g. 2
 * cleaners for the same person).
 *
 * @return array list of strings describing the problems found
 */
function check_for_conflicts($filename) {
	if (!file_exists($filename)) {
		return ['file does not exist'];
	}

	$data = parse_schedule_file($filename);
	if (empty($data)) {
		return ['empty data'];
	}

	$conflicts = [];
	foreach($data as $meal) {
		$has_error = FALSE;
		$date = array_get($meal, 'date');

		# first half of the meal
		$head_cook = trim(array_get($meal, 'head_cook'));
		$asst1 = trim(array_get($meal, 'asst1'));
		$asst2 = trim(array_get($meal, 'asst2'));
		$cleaner1 = trim(array_get($meal, 'cleaner1'));
		$cleaner2 = trim(array_get($meal, 'cleaner2'));
		$cleaner3 = trim(array_get($meal, 'cleaner3'));

		// ignore if this is a meeting night
		if (empty($asst1 . $asst2 . $cleaner2 . $cleaner3)) {
			continue;
		}

		foreach([$head_cook, $asst1, $asst2, $cleaner1, $cleaner2, $cleaner3] as $job) {
			if ($job == PLACEHOLDER) {
				$has_error = TRUE;
				$conflicts[] = "{$date} has placeholder assigned";
			}
		}

		if ($head_cook == $asst1) {
			$conflicts[] = "{$date} head cook and asst1";
			$has_error = TRUE;
		}
		if ($head_cook == $asst2) {
			$conflicts[] = "{$date} head cook and asst2";
			$has_error = TRUE;
		}
		if ($asst1 == $asst2) {
			$conflicts[] = "{$date} assistant cook duplicates";
			$has_error = TRUE;
		}

		# second half of the meal
		if ($cleaner1 == $cleaner2) {
			$conflicts[] = "{$date} cleaner 1 and 2";
			$has_error = TRUE;
		}
		if ($cleaner1 == $cleaner3) {
			$conflicts[] = "{$date} cleaner 1 and 3";
			$has_error = TRUE;
		}
		if ($cleaner3 == $cleaner2) {
			$conflicts[] = "{$date} cleaner 3 and 2";
			$has_error = TRUE;
		}

		$ts_conflicts = check_for_table_setter_conflicts($meal);
		if (!empty($ts_conflicts)) {
			$conflicts = array_merge($conflicts, $ts_conflicts);
			$has_error = TRUE;
		}

		if ($has_error) {
			error_log('meal has error: ' . print_r( $meal, true ));
		}
	}

	return $conflicts;
}

/**
 * Example a schedule file, looking for table setters who are also assigned
 * a head or asst cooking shift during the same meal.
 *
 * @return array list of strings describing the problems found
 */
function check_for_table_setter_conflicts($meal) {
	if (!defined('WEEKDAY_TABLE_SETTER')) {
		// no job, no problems
		return [];
	}

	$table_setter = $meal['table_setter'];
	if (empty($table_setter)) {
		return [];
	}

	$conflicts = [];
	$date = array_get($meal, 'date');

	if ($table_setter === $meal['head_cook']) {
		$conflicts[] = "{$date} table setter & head cook:{$meal['head_cook']}";
	}
	if ($table_setter === $meal['asst1']) {
		$conflicts[] = "{$date} table setter & asst1:{$meal['asst1']}";
	}
	if ($table_setter === $meal['asst2']) {
		$conflicts[] = "{$date} table setter & asst2:{$meal['asst2']}";
	}

	return $conflicts;
}

