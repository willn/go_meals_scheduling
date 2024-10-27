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

define('NOT_A_MEAL', 'not-meal');
define('HOLIDAY_NIGHT', 'holiday');
define('SKIP_NIGHT', 'skip');
define('SUNDAY_MEAL', 'sunday');
define('WEEKDAY_MEAL', 'weekday');
define('MEETING_NIGHT_MEAL', 'meeting');
define('BRUNCH_MEAL', 'brunch');

define('ALL_ID', 'all');

define('DEBUG', FALSE);
define('DEBUG_ASSIGNMENTS', FALSE);
define('DEBUG_FIND_CANCEL_MEALS', FALSE);

define('JSON_ASSIGNMENTS_FILE', 'results/output.json');

define('PLACEHOLDER', 'XXXXXXXX');
define('SKIP_USER', 'SKIP_USER');

# date & shift preference levels
define('HAS_CONFLICT_PREF', 0);
define('NON_RESPONSE_PREF', .5);
define('OK_PREF', 1);
define('PREFER_DATE_PREF', 2);
define('CLEAN_AFTER_COOK_PREF', 3);
define('DO_BUNDLING_PREF', 5);

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
