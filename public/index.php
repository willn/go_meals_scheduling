<?php
session_start();

require_once 'utils.php';

require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'classes/worker.php';
require_once 'classes/survey.php';
require_once 'classes/roster.php';

require_once 'display/includes/header.php';
require_once 'participation.php';

$survey = new Survey();

echo <<<EOHTML
<h1>Great Oak Meals Scheduling</h1>
EOHTML;

// ----- check to see if the database is writable:
global $db_is_writable;
if (!$db_is_writable) {
	echo <<<EOHTML
		<div class="warning">
			ERROR: Database is not writable
		</div>
EOHTML;
}

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
		display_respondents();
		display_worker_menu($workers);

		print $report_link;
	}
}

$season_id = SEASON_ID;
print <<<EOHTML
<p>Season ID: {$season_id}</p>
</body>
</html>
EOHTML;

/**
 * Display the responders summary
 */
function display_respondents() {
	$r = new Respondents();
	echo <<<EOHTML
		<div class="special_info">
			{$r->getTimeRemaining()}
			{$r->getSummary()}
		</div>
EOHTML;
}

/**
 * Display the menu of worker names so to choose which name to save preferences.
 */
function display_worker_menu() {
	$workers_list = new WorkersList();
	if (empty($workers_list)) {
		echo "<h2>No workers configured</h2>\n";
		return;
	}

	// display names for "login"
	print <<<EOHTML
		<div class="workers_list">
			{$workers_list->getWorkersListAsLinks()}
		</div>
EOHTML;
}

/**
 * @param[in] workers list of available workers
 * @param[in] survey Survey object.
 * @param[in] get_w string worker's name from the _GET array
 */
function build_survey($workers, $survey, $get_w) {
	// --------- build the survey --------------------
	$all_jobs = get_sunday_jobs() + get_weekday_jobs() + get_mtg_jobs();

	$w = array_get($workers, $get_w);
	$survey->setWorker($w['username'], $w['id']);
	$survey->loadWorkerInfo($w['first_name'], $w['last_name']);

	print $survey->toString();
}
?>
