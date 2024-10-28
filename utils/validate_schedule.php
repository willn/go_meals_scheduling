<?php

global $relative_dir;
$relative_dir = '../public/';
require_once('../public/config.php');
require_once('../public/globals.php');
require_once('../public/classes/worker.php');
require_once('../public/classes/roster.php');

// TODO: support json directly from the auto-assigner

// pull in the specified file
$options = getopt('f:');
if (!isset($options['f'])) {
	print "Usage: -f filename.txt\n";
	exit;
}
if (!file_exists($options['f'])) {
	print "Could not find schedule file\n";
	exit;
}

// query the database for avoids
$r = new Roster();
$r->loadRequests();
$avoids = $r->getAllAvoids();
$found_conflict = FALSE;

// parse the schedule file into meaningful arrays
$schedule = file($options['f']);
foreach($schedule as $meal_line) {
	$e = explode("\t", trim($meal_line));
	$date = $e[0];

	// parse the cooks
	$cooks = array(
		$e[1] => 1,
		$e[2] => 1,
		$e[3] => 1,
	);

	// parse this differently, the meeting nights may not have trailing tabs
	$cleaners = array($e[4] => 1);
	if (isset($e[5])) {
		$cleaners[$e[5]] = 1;
	}
	if (isset($e[6])) {
		$cleaners[$e[6]] = 1;
	}

	// look for schedule 'avoid' conflicts
	foreach($avoids as $worker=>$names) {
		if (isset($cooks[$worker])) {
			foreach($names as $other) {
				if (isset($cooks[$other])) {
					print "CONFLICT: {$date} cook {$worker} wants to avoid {$other}\n";
					$found_conflict = TRUE;
				}
			}
		}

		if (isset($cleaners[$worker])) {
			foreach($names as $other) {
				if (isset($cleaners[$other])) {
					print "CONFLICT: {$date} cleaner {$worker} wants to avoid {$other}\n";
					$found_conflict = TRUE;
				}
			}
		}
	}
}

if (!$found_conflict) {
	print "No conflicts found\n";
}
?>
