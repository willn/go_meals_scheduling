<?php
/*
 * Automated meals scheduling assignments
 * $Id: assignments.php,v 1.13 2014/08/15 21:59:30 willn Exp $
 */

require_once 'globals.php';
require_once 'classes/calendar.php';
require_once 'assign_workers.php';
require_once 'assign_schedule.php';
global $dbh;

$r = new Roster();
$s = new Schedule();
$s->setRoster($r);

// load the dates and shifts needed
$s->initializeShifts();
$s->printNumMeals();

// remove special case...
unset($all_jobs['all']);

$job_ids_clause = get_job_ids_clause();


// --------------------------------------------------------------
// load all the survey prefs for every date
$sql = <<<EOSQL
	SELECT s.string as date, s.job_id, a.username, p.pref
		FROM auth_user as a, schedule_prefs as p, schedule_shifts as s
		WHERE p.pref>0
			AND a.id=p.worker_id
			AND s.id = p.date_id
		ORDER BY s.string ASC,
			p.pref DESC,
			a.username ASC;
EOSQL;

foreach($dbh->query($sql) as $row) {
	$u = $row['username'];
	$d = $row['date'];
	$ji = $row['job_id'];
	$p = $row['pref'];

	$r->addWorker($u, $ji, $d, $p);

	// only add meals which are scheduled...
	$s->addPrefs($u, $ji, $d, $p);
}


// --------------------------------------------------------------
// get the list of workers who didn't respond to the survey, and their assigned
// job IDs
$sid = SEASON_ID;
$sql = <<<EOSQL
SELECT u.username, a.job_id
	FROM survey_assignment as a, auth_user as u
	WHERE a.season_id={$sid}
		AND u.id=a.worker_id
		AND ({$job_ids_clause})
		AND a.worker_id NOT IN
			(SELECT worker_id FROM schedule_prefs GROUP BY worker_id)
	GROUP BY a.worker_id, a.job_id
EOSQL;

$non_respond = array();
foreach($dbh->query($sql) as $row) {
	// fill in 'OK' preferences for all non-responders
	#!# only add 'default' response for dates which should contain the shift / job_id
	$s->addNonResponder($row['username'], $row['job_id']);
}

// --------------------------------------------------------------
// find how many each worker has been assigned
$sql = <<<EOSQL
SELECT u.username, a.job_id, a.instances
	FROM survey_assignment as a, auth_user as u
	WHERE a.season_id={$sid}
		AND a.type="a"
		AND a.worker_id = u.id
		AND ({$job_ids_clause})
	ORDER BY u.username
EOSQL;

// assign the number of shifts per worker
foreach($dbh->query($sql) as $row) {
	$r->setNumberOfShifts($row['username'], $row['instances'], $row['job_id']);
}

// sort
foreach(array_keys($all_jobs) as $j) {
	$s->setJobId($j);
	$r->setJobId($j);

	// keep assigning until all the meals have been assigned
	while ($s->sortPossible() && !$s->isFinished()) {
		$worker_freedom = $r->sortAvailable();
		$s->fillMeal($worker_freedom);
	}
}

$s->printResults();
$r->printResults();

echo "\n-----[ the end ]-----\n";

?>
