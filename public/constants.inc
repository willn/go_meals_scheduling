<?php
/*
 * Collection of constant declarations
 */
define('BASE_DIR', '/meals_scheduling');

define('SKIP_EMAIL', FALSE); // set to true for debugging
define('RESULTS_FILE', 'schedule.txt');

# ---------- date stuff -------------
define('WINTER', 'winter');
define('SPRING', 'spring');
define('FALL', 'fall');
define('SUMMER', 'summer');

$this_year = intval(date('Y'));
$this_month = date('n');
// if it's nov or dec, then use "next year"
if (($this_month == 11) || ($this_month == 12)) {
	$this_year++;
}
define('SEASON_YEAR', $this_year);

define('SEASON_ID', get_season_id());

/*
 * SEASON_NAME is used to lookup the months involved.
 * Possible answers are: SUMMER, FALL, WINTER
 */
// ###
define('SEASON_NAME', FALL);

# -------- list of sqlite tables ---------
# tables from the work survey (read-only)
define('SURVEY_JOB_TABLE', 'work_app_job');
define('ASSIGN_TABLE', 'work_app_assignment');

# this is altered
define('AUTH_USER_TABLE', 'auth_user');

# tables for meals scheduling (read & write)
define('SCHEDULE_COMMENTS_TABLE', 'schedule_comments');
define('SCHEDULE_PREFS_TABLE', 'schedule_prefs');
define('SCHEDULE_SHIFTS_TABLE', 'schedule_shifts');

// temporary constants... I hope
define('NOT_A_MEAL', 0);
define('HOLIDAY_NIGHT', 1);
define('SKIP_NIGHT', 2);
define('SUNDAY_MEAL', 3);
define('WEEKDAY_MEAL', 4);
define('MEETING_NIGHT_MEAL', 5);

?>
