<?php
require_once '../public/utils.php';

/**
 * Parse a schedule.txt file into an associative array.
 */
function parse_schedule_file($filename='schedule.txt') {
	if (!file_exists($filename)) {
		return NULL;
	}

	$content = file($filename);
	$header = array_shift($content);
	$cols = array_map(trim, explode("\t", $header));

	$out = [];
	foreach($content as $line) {
		$pieces = array_map(trim, explode("\t", $line));
		$out[] = array_combine($cols, $pieces);
	}

	file_put_contents('data/mega_season.json', json_encode($out));
	return $out;
}

/**
 * Example a schedule file, looking for table setters who are also assigned
 * a head or asst cooking shift during the same meal.
 */
function check_for_table_setter_conflicts($filename) {
	if (!file_exists($filename)) {
		echo "file does not exist!\n";
		return NULL;
	}

	$data = parse_schedule_file($filename);
	if (empty($data)) {
		echo "empty data! {$filename}\n";
		return [];
	}

	$conflicts = [];
	foreach($data as $meal) {
		if ($meal['date'] === '5/1/2018') {
			#print_r($data);
		}
		$table_setter = array_get($meal, 'table_setter');
		if (empty($table_setter)) {
			continue;
		}

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
	}

	return $conflicts;
}

