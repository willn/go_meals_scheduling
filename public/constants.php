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
define('SPRING_SUMMER', 'spring_summer');
define('SUMMER', 'summer');
define('FALL', 'fall');
define('FALL_WINTER', 'fall_winter');

$this_year = intval(date('Y'));
define('SEASON_YEAR', $this_year);

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

define('ALL_ID', 'all');

define('DEBUG', FALSE);
define('DEBUG_GET_LEAST_POSSIBLE', FALSE);

define('JSON_ASSIGNMENTS_FILE', 'results/output.json');

define('NON_RESPONSE_PREF', .5);
define('PLACEHOLDER', 'XXXXXXXX');
define('SKIP_USER', 'SKIP_USER');
define('HAS_CONFLICT', -1);

define('MONDAY', 1);
define('TUESDAY', 2);
define('WEDNESDAY', 3);
define('THURSDAY', 4);
define('FRIDAY', 5);
define('SATURDAY', 6);
define('SUNDAY', 7); # ISO 8601 representation

define('JANUARY', 1);
define('FEBRUARY', 2);
define('MARCH', 3);
define('APRIL', 4);
define('MAY', 5);
define('JUNE', 6);
define('JULY', 7);
define('AUGUST', 8);
define('SEPTEMBER', 9);
define('OCTOBER', 10);
define('NOVEMBER', 11);
define('DECEMBER', 12);

define('DOMAIN', '@gocoho.org');
define('FROM_EMAIL', 'willie' . DOMAIN);

define('PREFER_TO_AVOID_WORKER_RATIO', .55);

define('DEFAULT_AVAIL', 1);

?>
