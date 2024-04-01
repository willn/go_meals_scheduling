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
			'MEETING_NIGHT_CLEANER' => MEETING_NIGHT_CLEANER_NAME,
			'MEETING_NIGHT_ORDERER' => MEETING_NIGHT_ORDERER_NAME,

			# 'SUNDAY_ASST_COOK' => SUNDAY_ASST_COOK_NAME,
			# 'SUNDAY_CLEANER' => SUNDAY_CLEANER_NAME,
			# 'SUNDAY_HEAD_COOK' => SUNDAY_HEAD_COOK_NAME,

			'WEEKEND_ASST_COOK' => WEEKEND_ASST_COOK_NAME,
			'WEEKEND_CLEANER' => WEEKEND_CLEANER_NAME,
			'WEEKEND_HEAD_COOK' => WEEKEND_HEAD_COOK_NAME,
			'WEEKEND_LAUNDRY' => WEEKEND_LAUNDRY_NAME,

			'WEEKDAY_ASST_COOK' => WEEKDAY_ASST_COOK_NAME,
			'WEEKDAY_CLEANER' => WEEKDAY_CLEANER_NAME,
			'WEEKDAY_HEAD_COOK' => WEEKDAY_HEAD_COOK_NAME,
			'WEEKDAY_LAUNDRY' => WEEKDAY_LAUNDRY_NAME,
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
