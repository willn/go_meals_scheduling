<?php
/*
 * Collection of constant declarations
 */
define('BASE_DIR', '/meals_scheduling');

define('SKIP_EMAIL', FALSE); // set to true for debugging
define('RESULTS_FILE', 'schedule.txt');

// database table names
define('SURVEY_JOB_TABLE', 'work_app_job');
define('ASSIGN_TABLE', 'work_app_assignment');

/* ---------- date stuff ------------- */
define('WINTER', 'winter');
define('FALL', 'fall');
define('SUMMER', 'summer');

$this_year = date('Y');
$this_month = date('n');
if (($this_month == 11) || ($this_month == 12)) {
	$this_year++;
}
define('SEASON_YEAR', $this_year);

define('SEASON_ID', get_season_id());
define('SEASON_NAME', get_season_name()); // summer, fall, winter

?>
