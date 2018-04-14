<?php
ini_set('display_errors', 1);

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
$start = microtime(TRUE);

$options = getopt('cijqsuw');
if (empty($options)) {
	echo <<<EOTXT
Usage:
	-c	output as CSV
	-i	output as SQL insert statements
	-j	output to json format
	-q  quiet mode: don't display the results (used for debugging)
	-s	display schedule
	-u	only unfulfilled workers
	-w	display workers

EOTXT;
	exit;
}

global $relative_dir;
$relative_dir = '../public/';

// remove special case...
unset($all_jobs['all']);

$job_ids_clause = get_job_ids_clause();

$assignments = new Assignments();
$assignments->run();

// output to json for integration with the report
if (array_key_exists('j', $options)) {
	$assignments->saveResults();
}

// output as SQL insert statements
if (array_key_exists('i', $options)) {
	$assignments->outputSqlInserts();
}

// output as CSV
if (array_key_exists('c', $options)) {
	$assignments->outputCSV();
}

// quiet mode... XXX does this actually work? have value?
if (array_key_exists('q', $options)) {
	$assignments->printResults($options);
}

$end = microtime(TRUE);
echo "elapsed time: " . ($end - $start) . "\n";
?>
