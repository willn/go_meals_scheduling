<?php
/*
 * Grab the list of current jobs for this season, i.e. their job IDs.
 */
global $relative_dir;
$relative_dir = '../public/';

require_once("{$relative_dir}/globals.php");
require_once("{$relative_dir}/config.php");

$csi = new CurrentSeasonIds();
$csi->run();

class CurrentSeasonIds {
	protected $dbh;

	public function __construct() {
		global $dbh;
		$this->dbh = $dbh;
	}

	/**
	 * Process the database initialization.
	 */
	public function run() {
		// don't use all_jobs here, because these string definitions are used
		// to ultimately create the all_jobs array.  :)
		$jobs = array(
			'MEETING_NIGHT_ORDERER' => 'Meeting night takeout orderer',
			'MEETING_NIGHT_CLEANER' => 'Meeting night cleaner',
			'SUNDAY_HEAD_COOK' => 'Sunday head cook%',
			'SUNDAY_ASST_COOK' => 'Sunday meal ass%',
			'SUNDAY_CLEANER' => 'Sunday Meal Cleaner',
			'WEEKDAY_HEAD_COOK' => 'Weekday head cook%',
			'WEEKDAY_ASST_COOK' => 'Weekday meal ass%',
			'WEEKDAY_CLEANER' => 'Weekday Meal cleaner',
		);

		$sql_format = <<<EOSQL
select id from survey_job where season_id=%d and description like '%s';
EOSQL;

		foreach($jobs as $define => $desc) {
			$sql = sprintf($sql_format, SEASON_ID, $desc);
			foreach ($this->dbh->query($sql) as $row) {
				echo "define('{$define}', {$row['id']});\n";
			}
		}
	}
}
?>

