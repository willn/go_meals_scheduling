<?php

global $relative_dir;
$relative_dir = '../public/';
require_once "{$relative_dir}/classes/roster.php";
require_once "{$relative_dir}/gather_utils.php";

define('FILENAME', 'final_schedule.csv');

$lines = file(FILENAME);
$header = array_shift($lines);
$header_keys = str_getcsv($header);
$data = array_map('str_getcsv', $lines);

define('CREATE', 'create');
define('GO_KITCHEN_AND_DINING_ROOM', '22;105');

$header_cols = [
	'date_time' => 'Date/Time',
	'locations' => 'Locations',
	'formula' => 'Formula',
	'communities' => 'Communities',
	'head_cook' => 'Head Cook',
	'asst_cook' => 'Assistant Cook',
	'cleaner' => 'Cleaner',
	'table_setter' => 'Table Setter',
	'action' => 'Action',
];
echo implode(',', array_values($header_cols)) . "\n";

$roster = new Roster();
$gather_ids = $roster->loadGatherIDs();

foreach($data as $entry) {
	$keyed = array_combine($header_keys, $entry);
	$time_and_date_str = strtotime($keyed['date'] . ' ' . $keyed['time'] . 'pm');

	// head cook
	$head_cook = map_usernames_to_gather_id([$keyed['head_cook']],
		$gather_ids);

	// assistant cooks
	$assts = [];
	if (!empty($keyed['asst1'])) {
		$assts[] = $keyed['asst1'];
	}
	if (!empty($keyed['asst2'])) {
		$assts[] = $keyed['asst2'];
	}
	$ids = map_usernames_to_gather_id($assts, $gather_ids);
	$assts = array_values($ids);

	// cleaners
	$cleaners = [];
	if (!empty($keyed['cleaner1'])) {
		$cleaners[] = $keyed['cleaner1'];
	}
	if (!empty($keyed['cleaner2'])) {
		$cleaners[] = $keyed['cleaner2'];
	}
	if (!empty($keyed['cleaner3'])) {
		$cleaners[] = $keyed['cleaner3'];
	}
	$ids = map_usernames_to_gather_id($cleaners, $gather_ids);
	$cleaners = array_values($ids);

	// table setter
	$table_setter = map_usernames_to_gather_id([$keyed['table_setter']],
		$gather_ids);

	$translated = [
		'date_time' => date('c', $time_and_date_str),
		'locations' => GO_KITCHEN_AND_DINING_ROOM,
		'formula' => NULL,
		'communities' => str_replace(', ', ';', $keyed['communities']),
		'head_cook' => implode(';', $head_cook),
		'asst_cook' => implode(';', $assts),
		'cleaner' => implode(';', $cleaners),
		'table_setter' => implode(';', $table_setter),
		'action' => CREATE,
	];	
	echo implode(',', array_values($translated)) . "\n";
}

?>
