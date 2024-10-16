# Start the survey

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

--> ensure that this works in the survey, CRUD & assignments.

### update and clean up the database

* grab the database from the work app and export / import to mysql:
```
cd sql/
scp gocoho.tklapp.com:/home/django/work/db.sqlite3 .
# run the database prep script for cleaning
../utils/translate_sqlite_to_mysql.sh

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
mysql -u gocoho_work_allocation -p gocoho_work_allocation
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
```

* Look for any recently added users who are missing a Gather ID
```
select username from auth_user
	WHERE date_joined > DATE_ADD(CURDATE(), INTERVAL -366 DAY) AND
		gather_id is NULL order by date_joined;
```
If there is anyone listed here, find their ID in Gather & update the
list in the `add_gather_ids.sql` file

* get new job IDs for the season, and update the defines for each job in config.php
```
cd utils/
php find_current_season_jobs.php
```

If there are problems, compare the names of the jobs in the system with
entries like `WEEKDAY_HEAD_COOK_NAME`.

Copy the results block and replace the previous season's entries in this file:
`vi ../public/season.php`

* Update the unit tests which are going to fail based on changed info
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
git push
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
git push
```

Move on to [DIRECTIONS_START_COMPLETE](./DIRECTIONS_START_COMPLETE.md)

