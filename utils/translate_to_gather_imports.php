<?php

global $relative_dir;
$relative_dir = '../public/';
require_once "{$relative_dir}/classes/roster.php";

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

/**
 * Map the given username to the appropriate Gather ID. If it exists.
 *
 * @param string $username work system username to be replaced.
 * @param mixed $gather_ids associative array of all work system usernames to gather IDs.
 * @return string the Gather user ID, blank if placeholder, XXX-username if not found.
 */
function get_gather_id($username, $gather_ids) {
	if (isset($gather_ids[$username])) {
		// found, return successfully
		return $gather_ids[$username];
	}

	if (($username == '') || ($username === PLACEHOLDER)) {
		// is blank or placeholder... skip
		return '';
	}

	// was unable to find username
	return "XXX-{$username}";
}

foreach($data as $entry) {
	$keyed = array_combine($header_keys, $entry);
	$time_and_date_str = strtotime($keyed['date'] . ' ' . $keyed['time'] . 'pm');

	// head cook
	$head_cook = get_gather_id($keyed['head_cook'], $gather_ids);

	// assistant cooks
	$assts = [];
	$assts[] = get_gather_id($keyed['asst1'], $gather_ids);
	$assts[] = get_gather_id($keyed['asst2'], $gather_ids);

	// cleaners
	$cleaners = [];
	$cleaners[] = get_gather_id($keyed['cleaner1'], $gather_ids);
	$cleaners[] = get_gather_id($keyed['cleaner2'], $gather_ids);
	$cleaners[] = get_gather_id($keyed['cleaner3'], $gather_ids);

	// table setter
	$table_setter = get_gather_id($keyed['table_setter'], $gather_ids);

	$translated = [
		'date_time' => date('c', $time_and_date_str),
		'locations' => GO_KITCHEN_AND_DINING_ROOM,
		'formula' => NULL,
		'communities' => str_replace(', ', ';', $keyed['communities']),
		'head_cook' => $head_cook,
		'asst_cook' => implode(';', $assts),
		'cleaner' => implode(';', $cleaners),
		'table_setter' => $table_setter,
		'action' => CREATE,
	];	
	echo implode(',', array_values($translated)) . "\n";
}

?>
