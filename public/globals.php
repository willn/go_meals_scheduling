<?php
date_default_timezone_set('America/Detroit');

require_once('utils.php');
require_once('config.php');

define('JSON_ASSIGNMENTS_FILE', 'results/' . SEASON_ID . '.json');

define('NON_RESPONSE_PREF', .5);
define('PLACEHOLDER', 'XXXXXXXX');
define('HAS_CONFLICT', -1);

define('SUNDAY', 0);
define('MONDAY', 1);
define('TUESDAY', 2);
define('WEDNESDAY', 3);
define('THURSDAY', 4);
define('FRIDAY', 5);
define('SATURDAY', 6);

/**
 * Get the names of the days of the week.
 */
function get_days_of_week() {
	return [
		'Sun',
		'Mon',
		'Tue',
		'Wed',
		'Thu',
		'Fri',
		'Sat',
	];
}

/**
 * Get the list of the availability preferences.
 */
function get_pref_names() {
	return [
		0 => 'avoid',
		1 => 'OK',
		2 => 'prefer',
	];
}

$dbh = create_sqlite_connection();

global $all_jobs;
$all_jobs = array();
$all_jobs['all'] = 'all';
$all_jobs += get_mtg_jobs() + get_sunday_jobs() + get_weekday_jobs();

global $all_cook_jobs;
global $all_clean_jobs;
foreach($all_jobs as $jid=>$name) {
	if ((stripos($name, 'cook') !== FALSE) ||
		(stripos($name, 'takeout orderer') !== FALSE)) {
		$all_cook_jobs[] = $jid;
	}
	if ((stripos($name, 'clean') !== FALSE) ||
		(stripos($name, 'Meeting night cleaner') !== FALSE)) {
		$all_clean_jobs[] = $jid;
	}
}

/**
 * Get the list of the weekdays where meals are served.
 */
function get_weekday_meal_days() {
	return [MONDAY, TUESDAY, WEDNESDAY];
}

/**
 * Get the list of meeting nights.
 * key = day of week, value = ordinal occurence of day/week
 */
function get_mtg_nights() {
	return [
		WEDNESDAY => 1,
		MONDAY => 3,
	];
}


/**
 * Create a sqlite connection.
 */
function create_sqlite_connection() {
	global $db_is_writable;
	$db_is_writable = FALSE;

	// connect to SQLite database
	try {
		global $relative_dir;
		if (!isset($relative_dir)) {
			$relative_dir = '';
		}
		else {
			$relative_dir .= '/';
		}

		$db_fullpath = "{$relative_dir}sqlite_data/work_allocation.db";
		$db_is_writable = is_writable($db_fullpath);
		$db_file = "sqlite:{$db_fullpath}";
		$dbh = new PDO($db_file);
		$timeout = 5; // in seconds
		$dbh->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
	}
	catch(PDOException $e) {
		echo "problem loading sqlite file [$db_fullpath]: {$e->getMessage()}\n";
		exit;
	}

	return $dbh;
}

// create the job IDs 'OR' clause
function get_job_ids_clause($prefix='') {
	global $all_jobs;

	if ($prefix != '') {
		$len = strlen($prefix);
		if (strrpos($prefix, '.') != ($len - 1)) {
			$prefix .= '.';
		}
	}

	$job_ids = array();
	foreach(array_keys($all_jobs) as $id) {
		if ($id == 'all') {
			continue;
		}

		$job_ids[] = "{$prefix}job_id={$id}";
	}
	return implode(' OR ', $job_ids);
}

function is_a_mtg_night_job($job_id) {
	return array_key_exists($job_id, get_mtg_jobs());
}

function is_a_sunday_job($job_id) {
	return array_key_exists($job_id, get_sunday_jobs());
}

function is_a_cook_job($job_id) {
	global $all_cook_jobs;
	return in_array($job_id, $all_cook_jobs);
}

function is_a_clean_job($job_id) {
	global $all_clean_jobs;
	return in_array($job_id, $all_clean_jobs);
}

function is_a_head_cook_job($job_id) {
	$weekday_jobs = get_weekday_jobs();
	if (isset($weekday_jobs[$job_id]) &&
		stristr($weekday_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	$sunday_jobs = get_sunday_jobs();
	if (isset($sunday_jobs[$job_id]) &&
		strstr($sunday_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	$mtg_jobs = get_mtg_jobs();
	if (isset($mtg_jobs[$job_id]) &&
		strstr($mtg_jobs[$job_id], 'takeout orderer')) {
		return TRUE;
	}

	return FALSE;
}

function is_a_hobarter($worker) {
	$hobarters = get_hobarters();
	return in_array($worker, $hobarters);
}

function get_job_name($job_id) {
	$weekday_jobs = get_weekday_jobs();
	if (isset($weekday_jobs[$job_id])) {
		return $weekday_jobs[$job_id];
	}

	$sunday_jobs = get_sunday_jobs();
	if (isset($sunday_jobs[$job_id])) {
		return $sunday_jobs[$job_id];
	}

	$mtg_jobs = get_mtg_jobs();
	if (isset($mtg_jobs[$job_id])) {
		return $mtg_jobs[$job_id];
	}

	return '';
}

function is_a_group_clean_job($job_id) {
	return is_a_clean_job($job_id) && !is_a_mtg_night_job($job_id);
}
?>
