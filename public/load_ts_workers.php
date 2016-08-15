<?php
/*
 * This is meant to pull the list of TS workers from the $ts_workers /
 * num_shift_overrides array and load them into the appropriate
 * database tables. Ideally, this would avoid the manual entries.
 */

require_once('config.php');
require_once('globals.php');
if (!isset($ts_workers) || empty($ts_workers)) {
	echo "couldn't ts_workers find array or it was empty\n";
	exit;
}

global $dbh;

// -------------- add community column to auth_user
$sql = 'alter table auth_user add column community varchar(2)';
$dbh->exec($sql);
$sql = 'update auth_user set community="go"';
$dbh->exec($sql);


// -------------- collect max user ID
$sql = 'select id from auth_user order by id desc limit 1';
$max_user_id = NULL;
foreach ($dbh->query($sql) as $row) {
	$max_user_id = $row['id'];
	break;
}
if (is_null($max_user_id)) {
	echo "max id is null\n";
	exit;
}

// -------------- collect max assignment ID
$sql = 'select id from survey_assignment order by id desc limit 1';
$max_assign_id = NULL;
foreach ($dbh->query($sql) as $row) {
	$max_assign_id = $row['id'];
	break;
}
if (is_null($max_user_id)) {
	echo "max id is null\n";
	exit;
}

// ----------- construct the SQL
$insert = '';
foreach($ts_workers as $username=>$jobs) {
	$max_user_id++;

	$insert = <<<EOSQL
insert into auth_user values({$max_user_id}, '{$username}', '',
	'', '{$username}', '', 0, 1, 0, '', '', 'ts');
EOSQL;
	$dbh->query($insert);

	foreach($jobs as $job_id => $instances) {
		$max_assign_id++;
		$sid = SEASON_ID;
		$insert = <<<EOSQL
insert into survey_assignment values({$max_assign_id}, {$sid}, 'a',
	$max_user_id, $job_id, $instances, 1);
EOSQL;
		$dbh->query($insert);
	}
}


?>
