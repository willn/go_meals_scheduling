<?php
session_start();

require_once 'utils.php';

require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/worker.php';
require_once 'classes/survey.php';
require_once 'classes/roster.php';

require_once 'display/includes/header.php';

$survey = new Survey();

echo <<<EOHTML
<h1>Great Oak Meals Scheduling</h1>
EOHTML;

$dir = BASE_DIR;
$report_link = <<<EOHTML
<p class="summary_report">See the <a href="{$dir}/report.php">summary report</a></p>
EOHTML;

// ----- deadline check ----
$now = time();
if ($now > DEADLINE) {
	$formatted_date = date('r', DEADLINE);
	echo <<<EOHTML
		<h2>Survey has closed</h2>
		<p>As of {$formatted_date}</p>
		{$report_link}
EOHTML;
}
else {
	$workers = $survey->getWorkers();

	$worker_name = array_get($_GET, 'worker');
	if (!is_null($worker_name)) {
		build_survey($workers, $survey, $worker_name);
	}

	// display the menu of worker names
	if (!isset($_GET['worker']) ||
		!array_key_exists($_GET['worker'], $workers)) {
		display_respondents($workers);
		print $report_link;
	}
}

$season_id = get_season_id();
print <<<EOHTML
<p>Season ID: {$season_id}</p>
</body>
</html>
EOHTML;

/**
 * Display the responders summary
 *
 * @param array $workers list of all worker names.
 */
function display_respondents($workers) {
	$respondents = new Respondents();
	echo <<<EOHTML
		<div class="special_info">
			{$respondents->getTimeRemaining()}
			{$respondents->getSummary()}
		</div>
		{$respondents->renderWorkerMenu()}
EOHTML;
}

/**
 * @param array $workers list of available workers
 * @param object $survey Survey object.
 * @param string $get_w worker's name from the _GET array
 */
function build_survey($workers, $survey, $get_w) {
	// --------- build the survey --------------------
	$all_jobs = get_all_jobs();

	$w = array_get($workers, $get_w);
	$survey->setWorker($w['username'], $w['id']);
	$survey->loadWorkerInfo($w['first_name'], $w['last_name']);

	print $survey->toString();
}
?>
