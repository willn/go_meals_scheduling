<?php
require_once('globals.php');
require_once('mysql_api.php');
require_once('utils.php');

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '.';
}

require_once "{$relative_dir}/utils.php";
require_once "{$relative_dir}/constants.php";
require_once "{$relative_dir}/config.php";

session_start();

require_once('display/includes/header.php');

if (!isset($_SESSION['access_type'])) {
	if (isset($_GET['guest'])) {
		$_SESSION['access_type'] = 'guest';
	}
	else if (isset($_POST['password']) && ($_POST['password'] == 'robotron')) {
		$_SESSION['access_type'] = 'admin';
	}
	else if (!isset($_SESSION['access_type'])) {
		$dir = BASE_DIR;
		print <<<EOHTML
			<h2>Meals scheduling reporting</h2>
			<h3>Please choose access type:</h3>
			<div class="access_type">
				<a href="{$dir}/report.php?guest=1">guest</a>
			</div>
			<div class="access_type">
				admin
				<form method="post" action="{$_SERVER['PHP_SELF']}">
					<input type="password" name="password">
					<input type="submit" value="go">
				</form>
			</div>
EOHTML;
		exit;
	}
}

require_once('classes/calendar.php');

$calendar = new Calendar();
$job_key = (isset($_GET['key']) && is_numeric($_GET['key'])) ?
	intval($_GET['key']) : 'all';

$calendar->loadAssignments(JSON_ASSIGNMENTS_FILE);
$calendar->setIsReport(TRUE);

$job_key_clause = ($job_key != 0) ? "AND s.job_id={$job_key}" : '';

$current_season_months = get_current_season_months();

$respondents = new Respondents($job_key);
$responses = '';
if ($_SESSION['access_type'] != 'guest') {
	$responses = $respondents->getSummary((time() < DEADLINE));
}

$worker_dates = $calendar->getWorkerDates();
$non_respondents = $respondents->getNonResponders();
$cal_string = $calendar->toString(NULL, $worker_dates, $non_respondents);

$comments = '';
if ($_SESSION['access_type'] == 'admin') {
	$comments = $calendar->getWorkerComments($job_key_clause);
}

$job_name = ($job_key != 0) ? '' : '<th>Job</th>';

$meals_summary = $calendar->getShiftCounts();

/*
XXX keep this for now, and maybe bring it back...
// figure out how many shifts the schedule calls for on this type of meal
foreach($per_shift as $job_name=>$num_assn_shifts) {
	list($meal_type, $shift_type) = explode(' ', $job_name, 2);
	$meal_type = strtolower($meal_type);

	// default number of workers per shift
	$num_workers_per_shift = 1;
	switch($meal_type) {
		// divide by number of shifts per assignment
		case 'weekday':
		case 'brunch':
		case 'sunday':
			if (stristr($shift_type, 'asst cook')) {
				$num_workers_per_shift = 2;
			}
			if (stristr($shift_type, 'cleaner')) {
				$num_workers_per_shift = 3;
			}
			break;
	}

	$all_jobs = get_all_jobs();
	$job_id = array_search($job_name, $all_jobs);
	if ($job_id === FALSE) {
		$all = print_r($all_jobs, TRUE);
	}

	// figure out how many assignments are needed for the season, rounding down
	$num_meals_in_season = $meals_summary[$meal_type];
	$num_meals_per_assn = get_num_meals_per_assignment($job_id);
	$num_assns_needed = ($num_meals_per_assn == 0) ? 0 :
		floor(($num_meals_in_season * $num_workers_per_shift) /
			$num_meals_per_assn);
}
*/

$months_overlay = $calendar->renderMonthsOverlay($current_season_months);

$per_worker_table = render_per_worker_results($job_key);

// ---- toString section ----
print <<<EOHTML
<h2>Meals Schedule Reporting</h2>
<p><a href="index.php">Back to Roster</a></p>
{$months_overlay}
{$calendar->getJobsIndex($job_key)}
<div class="responses">{$responses}</div>
{$cal_string}
{$comments}

<h2>Number of meals scheduled per-day type:</h2>

<p>
Sundays: {$meals_summary['sunday']}
<br>Brunches: {$meals_summary['brunch']}
<br>Weekdays: {$meals_summary['weekday']}
<br> Meetings: {$meals_summary['meeting']}
</p>

{$calendar->renderSeasonDateSummary()}
{$per_worker_table}
<ul id="end"></ul>

</body>
</html>
EOHTML;

