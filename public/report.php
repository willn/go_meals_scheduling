<?php
require_once('globals.php');
require_once('mysql_api.php');

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

// --------   per-worker summary   -----------

// get list of pref (available) counts:
$prefs_table = SCHEDULE_PREFS_TABLE;
$shifts_table = SCHEDULE_SHIFTS_TABLE;
$auth_user_table = AUTH_USER_TABLE;
$sql = <<<EOSQL
	SELECT u.username as username, p.worker_id, s.job_id, count(*) as num
		FROM {$prefs_table} as p, {$auth_user_table} as u, {$shifts_table} as s
		WHERE u.id = p.worker_id
			AND p.date_id=s.id
			{$job_key_clause}
			AND p.pref > 0
		GROUP BY p.worker_id, s.job_id
EOSQL;
$user_pref_count = [];
$mysql_api = get_mysql_api();
foreach($mysql_api->get($sql) as $row) {
	$user_job = $row['username'] . '_' . $row['job_id'];
	$user_pref_count[$user_job] = $row['num'];
}

$job_id_clause = ($job_key != 'all') ?
	"j.id = '{$job_key}' AND\n" : '';

// get the number of assignments per each worker
$ids_clause = get_job_ids_clause();
$sid = get_season_id();
$jobs_table = SURVEY_JOB_TABLE;
$assn_table = ASSIGN_TABLE;
$sql = <<<EOSQL
	SELECT u.username, a.job_id, j.description, a.instances
		FROM {$assn_table} as a, {$auth_user_table} as u, {$jobs_table} as j
		WHERE a.season_id={$sid} AND
			({$ids_clause}) AND
			a.type="a" AND
			a.worker_id = u.id AND
			{$job_id_clause}
			a.job_id = j.id
		order by u.username
EOSQL;
$diffs = array();
$assignments = array();
foreach($mysql_api->get($sql) as $row) {
	$user_job = $row['username'] . '_' . $row['job_id'];

	$num_prefs = isset($user_pref_count[$user_job]) ?
		$user_pref_count[$user_job] : 0;
	$row['num_prefs'] = $num_prefs;
	$assignments[$user_job] = $row;

	// figure out how many meals one should work based on their assignments
	// XXX this ought to be extracted into a function... one ought to exist already.
	$shifts = $row['instances'] * 4;
	$diffs[$user_job] = ($shifts > 0) ?
		round($num_prefs / $shifts, 2) : 0;
}

# comment out for now, not working, unsure on value
#$assigned_counts = get_assigned_counts($job_key);

// count the number of shifts actually assigned to workers
$rows = '';
// generate html
$current_season = get_current_season_months();
$per_shift = array();
foreach($diffs as $key=>$diff) {
	$row = $assignments[$key];
	$shifts = $row['instances'] *
		get_num_meals_per_assignment($current_season, $row['job_id']);

	// initialize unseen job
	if (!isset($per_shift[$row['description']])) {
		$per_shift[$row['description']] = 0;
	}
	// track the number of assigned shifts for each job
	$per_shift[$row['description']] += $shifts;

	if ($diff < 1) {
		$diff = "<span class=\"highlight\">{$diff}</span>";
	}

	$job_name = ($job_key != 0) ? '' :
		"<td class=\"nowrap\">{$row['description']}</td>";

	$num_prefs = $row['num_prefs'];
	if ($num_prefs == '') {
		$num_prefs = 0;
	}

	$shift_id = $job_key;
	if ($job_key == 'all') {
		list($unused, $shift_id) = explode('_', $key);
	}

	$rows .= <<<EOHTML
<tr>
	<td>{$row['username']}</td>
	{$job_name}
	<td align="right">{$shifts}</td>
	<td align="right">{$num_prefs}</td>
	<td align="right">{$diff}</td>
</tr>
EOHTML;
}

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

ksort($per_shift);
foreach($per_shift as $job_name=>$num_assn_shifts) {
	// figure out how many shifts the schedule calls for on this type of meal
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
	$num_meals_per_assn = get_num_meals_per_assignment($current_season, $job_id);
	$num_assns_needed = ($num_meals_per_assn == 0) ? 0 :
		floor(($num_meals_in_season * $num_workers_per_shift) /
			$num_meals_per_assn);
}

$months_overlay = $calendar->renderMonthsOverlay($current_season);

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

<h2>Per-worker</h2>
<table cellpadding="3" cellspacing="0" border="0" width="100%" id="per_worker">
<thead>
	<tr>
		<th>Name</th>
		{$job_name}
		<th style="text-align: right;">shifts</th>
		<th style="text-align: right;">available</th>
		<th style="text-align: right;">diff</th>
		<th style="text-align: right;">ratio</th>
	</tr>
</thead>
<tbody>
	{$rows}
</tbody>
</table>

<ul id="end"></ul>

</body>
</html>
EOHTML;

