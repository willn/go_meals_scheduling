<?php
date_default_timezone_set('America/Detroit');

require_once('utils.php');
require_once('config.php');

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
		HAS_CONFLICT_PREF => 'conflict',
		OK_PREF => 'OK',
		PREFER_DATE_PREF => 'prefer',
	];
}

global $all_clean_jobs;

$all_jobs = get_all_jobs();

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

// create the job IDs 'OR' clause
function get_job_ids_clause($prefix='') {
	$all_jobs = get_all_jobs();

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

function is_a_brunch_job($job_id) {
	return array_key_exists($job_id, get_brunch_jobs());
}

function is_a_cook_job($job_id) {
	$all_cook_jobs = get_cook_jobs();
	return in_array($job_id, $all_cook_jobs);
}

function is_a_clean_job($job_id) {
	$all_clean_jobs = get_clean_jobs();
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
		stristr($sunday_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	$brunch_jobs = get_brunch_jobs();
	if (isset($brunch_jobs[$job_id]) &&
		stristr($brunch_jobs[$job_id], 'head cook')) {
		return TRUE;
	}

	$mtg_jobs = get_mtg_jobs();
	if (isset($mtg_jobs[$job_id]) &&
		stristr($mtg_jobs[$job_id], 'takeout orderer')) {
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

	$brunch_jobs = get_brunch_jobs();
	if (isset($brunch_jobs[$job_id])) {
		return $brunch_jobs[$job_id];
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
