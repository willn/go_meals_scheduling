<?php
ini_set('display_errors', '1');

# set the include path to be the top-level of the meals scheduling project
set_include_path('../' . PATH_SEPARATOR . '../public/');

global $relative_dir;
$relative_dir = '../public/';
require_once $relative_dir . 'globals.php';
require_once 'assignments.php';

# set the include path to be the top-level of the meals scheduling project
set_include_path('../' . PATH_SEPARATOR . '../public/');

require_once 'public/utils.php';

/*
 * Automated meals scheduling assignments
 */

$options = getopt('cgijsuw');
if (empty($options)) {
	echo <<<EOTXT
Usage:
	-c	output as CSV
	-g	output in Gather import format
	-i	output as SQL insert statements
	-j	output to json format
	-s	display schedule
	-u	only unfulfilled workers
	-w	display workers

EOTXT;
	exit;
}

// remove special case...
unset($all_jobs['all']);

$assignments = new Assignments();
$assignments->run();

if (DEBUG_FIND_CANCEL_MEALS) {
	error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " Number of placeholders: " .
		 print_r( $assignments->getNumPlaceholders(), true ));
}

// output as CSV
if (array_key_exists('c', $options)) {
	$assignments->outputCSV();
}

// output as SQL insert statements
if (array_key_exists('i', $options)) {
	$assignments->outputSqlInserts();
}

// output as Gather import statements
if (array_key_exists('g', $options)) {
	$assignments->outputGatherImports();
}

// output to json for integration with the report
if (array_key_exists('j', $options)) {
	$assignments->saveResults();
}

// run the schedule, output in text format
if (array_key_exists('s', $options)) {
	$assignments->printSchedule($options);
}

// run the schedule and report back a summary of workers
if (array_key_exists('w', $options)) {
	$assignments->printWorkers($options);
}

?>
