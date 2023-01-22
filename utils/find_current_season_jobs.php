<?php
/*
 * Grab the list of current jobs for this season, i.e. their job IDs.
 */

global $relative_dir;
if (!strlen($relative_dir)) {
    $relative_dir = '../public/';
}
require_once("../public/globals.php");

$csi = new CurrentSeasonIds();
$csi->run();

class CurrentSeasonIds {
	protected $mysql_api;

	public function __construct() {
		$this->mysql_api = get_mysql_api();
	}

	/**
	 * Process the database initialization.
	 */
	public function run() {
		// don't use all_jobs here, because these string definitions are used
		// to ultimately create the all_jobs array.  :)
		$jobs = array(
			'MEETING_NIGHT_CLEANER' => 'Meeting night cleaner',
			'MEETING_NIGHT_ORDERER' => 'Mtg takeout orderer (2 meals)',
			'SUNDAY_ASST_COOK' => 'Sunday asst cook',
			'SUNDAY_CLEANER' => 'Sunday Meal Cleaner',
			'SUNDAY_HEAD_COOK' => 'Sunday head cook',
			'WEEKDAY_ASST_COOK' => 'Weekday asst cook',
			'WEEKDAY_CLEANER' => 'Weekday Meal cleaner',
			'WEEKDAY_HEAD_COOK' => 'Weekday head cook',
			'WEEKDAY_TABLE_SETTER' => 'Weekday Table Setter',
		);

		$jobs_table = SURVEY_JOB_TABLE;
		$sql_format = <<<EOSQL
SELECT id from {$jobs_table} where season_id=%d and description like '%s';
EOSQL;
		$prev = 0;
		$count = 0;
		foreach($jobs as $define => $desc) {
			$sql = sprintf($sql_format, get_season_id(), $desc . '%');
			$result = $this->mysql_api->get($sql);
			foreach ($result as $row) {
				echo "define('{$define}', {$row['id']});\n";
				$count++;
			}
			if ($prev === $count) {
				echo "missed SQL: $sql\n";
				exit;
			}
			$prev++;
		}

		if ($count !== count($jobs)) {
			echo "\n\nERROR: only found {$count} of " . count($jobs) . "\n";
		}
	}
}
?>
