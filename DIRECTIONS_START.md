# Directions for setting up & running the Great Oak Meals Scheduling Survey

## Setup for the upcoming season or sub-season

Update settings in order to calculate the needed shift counts. The Meals
committee needs to know how much meals labor will be needed.

### Ask meals committee questions:
* How many meals are they planning on serving this season?
  - Will they be reducing the load down from the "full schedule"?
* Do they want to update the list of hobarters?
  - Look for `function get_hobarters`
* Do they want to offer CSA farm meals this summer? Disable for fall / winter.
  - Update `function doing_csa_farm_meals()`
* Which meal formula should we use?
  - Update instances of `Meal::BILLING_FORMULA`
* Where will these meals be hosted?
  - Update `utils/translate_to_gather_imports.php`

### Ask Process committee
* Are there any meeting nights that will be rescheduled?

### Full `SUB_SEASON_FACTOR`

Set the `SUB_SEASON_FACTOR` to 1.0 for a 6-month season in order to get
the count needed for the full season.

### check that there aren't any other uncommitted modifications
`git status`

### update public/season.php
* set `SEASON_NAME` - use the combo season name (e.g. `FALL_WINTER`) to get the
  full 6 months.
* make sure the right months are included in `get_current_season_months()`
* remove any previous season's data:
  - `get_num_shift_overrides()`
  - `get_skip_dates()`
  - `get_regular_day_overrides()`
  - `get_meeting_night_overrides()`

### Ensure the number of meals per assignment is correct:

In `public/config.php`, confirm in `function get_num_meals_per_assignment`, and
sync up with what's in the job assigments.

### Get the counts

Push the code to the webserver:

`./push_all_to_production.sh`

Open the scheduling system in a web browser, and check the summary report. At
the bottom, it should have a report listing the amount of labor needed.


## SEASON-START:
If this is mid-season, skip to the [MID-SEASON section](./DIRECTIONS_START_MID_SEASON.md)

### edit public/season.php
* set the appropriate `DEADLINE` date
* set `SEASON_NAME` - use the short season name to get 3 months
* set the `SUB_SEASON_FACTOR` (to .5 to work on 3 months)
* display a farm meals night message? (`function doing_csa_farm_meals`)

### Update unit tests
* run & fix unit tests
  - The number of assignments needed is in tests/CalendarTest.php, in
  `provideGetAssignmentsNeededForCurrentSeason()`

XXX meal laundry position & deal with weekend meals...
--> ensure that this works in the survey, CRUD & assignments.

### update and clean up the database

* grab the database from the work app and export / import to mysql:
```
cd sql/
scp gocoho.tklapp.com:/home/django/work/db.sqlite3 .

# run the database prep script for cleaning
cd sql/
../utils/prep_database.sh

# confirm that we got the needed tables
	auth_user
	work_app_committee
	work_app_season
	work_app_job
	work_app_assignment

# reset the local database
# get the list of current tables
mysql -u gocoho_work_allocation -p gocoho_work_allocation

SELECT CONCAT('DROP TABLE IF EXISTS `', table_name, '`;')
	FROM information_schema.tables
	WHERE table_schema = 'gocoho_work_allocation';

# paste these statements somewhere, remove the pipes, then run them manually to
# drop the tables. Don't drop the entire database, since then we have to reset
# permissions, etc.

mysql> show tables;
Empty set (0.00 sec)

# combine various SQL files into one, and import them all:
cat work.sql add_gather_ids.sql scheduling_survey_schema.sql > imports.sql
mysql -u gocoho_work_allocation -p gocoho_work_allocation < imports.sql

# confirm - there should be 8 tables
mysql> show tables;
+----------------------------------+
| Tables_in_gocoho_work_allocation |
+----------------------------------+
| auth_user                        |
| schedule_comments                |
| schedule_prefs                   |
| schedule_shifts                  |
| work_app_assignment              |
| work_app_committee               |
| work_app_job                     |
| work_app_season                  |
+----------------------------------+
8 rows in set (0.00 sec)

# confirm that the recently created users have a gather ID
select id, username, date_joined from auth_user
	WHERE date_joined > DATE_ADD(CURDATE(), INTERVAL -366 DAY) AND
		gather_id is NULL order by date_joined;

## if missing, find in Gather & update in the `add_gather_ids.sql` file

### get new job IDs for the season, and update the defines for each job in config.php
```
cd utils/
php find_current_season_jobs.php
# copy that block and replace the previous season's entries in this file:
vi ../public/season.php
```

### update the unit tests which are going to fail based on changed info

* look for the UPDATE-EACH-SEASON
* make sure that unit tests work:
```
cd tests
./run.sh
```

### when tests pass, then commit
```
git status
git add
git commit
```

* initialize the database
```
cd ../../utils/
php initialize_database.php
cd ../tests/
./run.sh
```

* if all unit tests pass, then commit
```
git status
git add *
git commit
```

