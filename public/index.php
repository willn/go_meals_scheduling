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
	$worker_name = array_get($_GET, 'worker');
	if (!is_null($worker_name)) {
		print $survey->build($worker_name);
	}

	// display the menu of worker names
	if (!isset($_GET['worker'])) {
		$respondents = new Respondents();
		print $respondents->toString();
		print $report_link;
	}
}

$season_id = get_season_id();
print <<<EOHTML
<p>Season ID: {$season_id}</p>
</body>
</html>
EOHTML;
