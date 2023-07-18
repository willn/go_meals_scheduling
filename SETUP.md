# Directions for setting up & running the Great Oak Meals Scheduling Survey

## Update settings in order to calculate the needed shift counts

### Ask meals committee questions:
* How many meals are they planning on serving this season?
  - Will they be reducing the load down from the "full schedule"?
* Do they want to update the list of hobarters?
  - Look for `function get_hobarters`
* Do they want to offer CSA farm meals this summer?
  - Update `DOING_CSA_FARM_MEALS`

### check that there aren't uncommitted modifications
`git status`

### update public/season.php
* set `SEASON_NAME`
* make sure the right months are included in `get_current_season_months()`
* remove any previous season's data:
  - `get_num_shift_overrides()`
  - `get_skip_dates()`
  - `get_regular_day_overrides()`
  - `get_meeting_night_overrides()`



## SEASON-START:
If this is mid-season, skip to the [MID-SEASON section](SETUP.md#mid-season)

### edit public/season.php
* set the appropriate `DEADLINE` date
* display a farm meals night message? (`DOING_CSA_FARM_MEALS`)
* set the `SUB_SEASON_FACTOR` (perhaps for .5 to get 3 months)

### Update unit tests
* run & fix unit tests
  - The number of assignments needed is in tests/CalendarTest.php, in
  `provideGetAssignmentsNeededForCurrentSeason()`

### update and clean up the database

* grab the database from the work app and export / import to mysql:
```
cd sql/
scp gocoho.tklapp.com:/home/django/work/db.sqlite3 .

# XXX Note: there is a lot of opportunity for automation below

# copy & paste the following lines:
sqlite3 db.sqlite3 <<'END_OF_SQL'
.output work.sql
.dump auth_user work_app_assignment work_app_committee work_app_job work_app_season
.exit
END_OF_SQL

# confirm that we got the needed tables
grep 'CREATE TABLE' work.sql | sed 's/^CREATE TABLE IF NOT EXISTS //' | cut -d\" -f2
	auth_user
	work_app_committee
	work_app_season
	work_app_job
	work_app_assignment

# trim some lines from the top
PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

# trim from the bottom:
COMMIT;

# On the lines for CREATE TABLE, make some changes:
% s/integer/MEDIUMINT/g
% s/AUTOINCREMENT/AUTO_INCREMENT/gi

# remove the quotes from the each of the CREATE TABLE lines
. s/"//g

# rename any column named "index" to something else
/CREATE TABLE.*index
Then rename index to indexCount

# Grow the size of 'description' column in the work_app_job table from 40 to 80

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

# import the wanted tables
mysql -u gocoho_work_allocation -p gocoho_work_allocation < work.sql
mysql -u gocoho_work_allocation -p gocoho_work_allocation -e "alter table auth_user drop column password;"

# confirm - there should be 4 tables
mysql> show tables;
+----------------------------------+
| Tables_in_gocoho_work_allocation |
+----------------------------------+
| auth_user                        |
| work_app_assignment              |
| work_app_committee               |
| work_app_job                     |
| work_app_season                  |
+----------------------------------+
5 rows in set (0.00 sec)

# add the Gather IDs 
mysql -u gocoho_work_allocation -p gocoho_work_allocation < add_gather_ids.sql

# confirm that the recent users have a gather ID
mysql -u gocoho_work_allocation -p gocoho_work_allocation -e "select username, gather_id from auth_user order by id desc limit 20;"

# confirm those were added:
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



## MID-SEASON
If this is mid-season, follow these directions, otherwise skip to [FINISH-START-OF-SEASON](SETUP.md#finish-start-of-season).

### edit public/season.php
* set the appropriate `DEADLINE` date

### clear out prior data from tables
`./connect_to_mysql.sh < sql/reset_mid_season.sql`

### Add any new users

If someone new has moved in, or started working in the system, their usernames
will need to be added.

```
# Create entries for these people in the `auth_user` table - last is gather ID
sqlite> INSERT INTO auth_user VALUES(NULL, NULL, 0, 'FIRST-NAME', 'LAST-NAME',
	'example@asdf.com', 0, 1, '2023-07-15', 'username', 12349999);

# get the ID of the person
mysql> select id from auth_user where username='XXX';
164

# get the max assignment id:
mysql> select max(id) from work_app_assignment;
9254

# get the current season ID:
mysql> select max(id) from work_app_season;

# add a new entry
	"id", -- max assignment id + 1
	"type", 
	"instances",
	"job_id",
	"reason_id",
	"season_id",
	"worker_id"
mysql> INSERT INTO work_app_assignment VALUES(13623, '', 1, 4594, 0, 33, 164);
```



## FINISH-START-OF-SEASON

### stage everything from the meals dev repo to `public_html`
```
# on remote host:
gocoho
cd public_html/meals_scheduling/
rm -rf *

# on localhost:
./push_all_to_production.sh

cd sql/
# This needs to be the local mysql **root** user
mysqldump -u root -p gocoho_work_allocation > transfer.sql

# transfer the file to production
scp -i ~/.ssh/id_dsa -P 1022 transfer.sql gocoho@gocoho.org:

# on gocoho:
cd ~
mysql -u gocoho_work_allocation -p gocoho_work_allocation < transfer.sql
```

### test to make sure everything works, view in web browser
* resync code to clean up
* confirm that the calendar dates are correct
* confirm the holidays and meeting nights are correct
* fill in some entries and save them
* load listing page again to make sure that the database is writeable

### notify participants that the survey is ready

### set up database backup routine on the webserver:
`crontab -e`

### schedule a few reminders spaced out over the rest of the session to send reminder emails to laggards



# MID-SURVEY

## Missing labor

Figure out which meals shifts are missing labor. This can be helpful to do in
advance so that volunteers can be sought while the survey is open. In addition,
this can be used to cancel / skip certain types of meals.

## uncomment RosterTest::testCompareLabor()

This output should be helpful in divulging which shift types have the
right or wrong amount of labor.



# END-OF-SURVEY

## disable cronjobs

## copy closed database locally:
```
# on gocoho:
mysqldump -u gocoho_work_allocation -p gocoho_work_allocation > end_of_survey.sql

# on localhost:
cd go_meals_scheduling/sql/
rsync -e 'ssh -p 1022' -avz gocoho@gocoho.org:/home/gocoho/end_of_survey.sql .

# load it up locally
mysql -u gocoho_work_allocation -p gocoho_work_allocation < end_of_survey.sql
```

## check for any un-assigned workers
```
cd ../auto_assignments/
php execute.php -u
cd ..
```

## If we know that we need to cancel 1 or more meals, edit
```
vi public/constants.php   # set DEBUG_FIND_CANCEL_MEALS to TRUE
cd auto_assignments/
# open another window to watch
tail -f error_log

# run the allocation
php execute.php -s
```

## Cancel extra meals
Look at the "Number of placeholders" line to see where we're missing labor.
if we need to cancel meals, then mark these as skip dates.
```
# add dates to get_skip_dates
vi public/season.php

# continue running and adjusting skipped dates, until no more "Use a placeholder # for" messages
php execute.php -s
```

## Borrowed labor
Confirm whether any "borrowed" labor was actually needed. If not, then remove the borrowed labor.

# run the unit test for CalendarTest::testRenderSeasonDateSummary until things line up
cd tests/
phpunit CalendarTest.php
# make adjustments on the number of meals to make these tests pass
git status
git diff
git add
git commit
```

## make a run, and analyze the results
```
./analyze_results.sh
```

## upload a copy of the `schedule.txt` to google drive & import into a spreadsheet
* try to move the under-assigned workers to fill the 'XXX' spots, making trades
* do any swapping needed

## if there are no meeting night cleaners...
* then delete the placeholders for those shifts, just leave it blank

## confirm preferences
```
# Download from google spreadsheet, save as tab separated values (TSV)
cd ~/Downloads
mv <downloaded filename> schedule.txt

# On the scheduling system, report page, filter to "all" jobs. Copy the "confirm checks" section & paste:
vi checks.sh
# paste & save
chmod +x checks.sh
./checks.sh | more
# read the comments and make sure they apply cleanly with auto-checks, or make trades
```

## Teen workers

Ensure that teen workers are paired with a parent.

## look for table setter conflicts
Check to make sure that there are no 'table setter' assignments which conflict with head or asst cooking.
* Download from google spreadsheet, as tab-delimited
* mv file to auto-assignments/schedule.txt
* cd tests/
* phpunit CheckForConflictsTest.php

## run conflicts validation:
```
# is this needed with the new unit tests?
cd ../utils/
php validate_schedule.php -f ../auto_assignments/schedule.txt
```

## Translate from Google sheet to Gather imports

Download sheet again, this time in *CSV* form.

```
mv ~/Downloads/<file name>.csv utils/final_schedule.csv
cd go_meals_scheduling_dev/utils/
php translate_to_gather_imports.php > imports.csv
```
If the above translation has 1 or more missing ID names, it will output a list
of names.

Look up their ID in Gather, then:

1. for this season, update database table `auth_users` to include their IDs
2. for the future, copy and paste these entries to `sql/add_gather_ids.sql`
3. Run the translate script again.

### make any tweaks
* Confirm which communities are mentioned (covid era restricts to only GO)
* Confirm the times and dates of the meals
* Unfortunately, it appears that gather import does not support "capacity" at this time.

### Upload to Gather
* open gather site, and upload the clean entries
* resolve any scheduling conflicts
  - check the Kitchen and Dining Room availability
  - meals currently add 2h 15m before and after the announced meal serving time.
    - Sundays: 3:15 - 7:45
    - Weekdays: 4:00 - 8:30
    - Meeting Night: 3:30 - 8:00
* If Gather complains about row 2, that is actually the second row in the file.
  The header is the 1st.


## Development notes between seasons

update public/config.php, update the season name, year, and season id

- pop into sqlite:
`sqlite3 !$`

- update the data to use the current season id:
`update work_app_assignment set season_id=11 where season_id=10;`

```
# create some entries (cook & clean) for testing of survey
# sqlite> insert into work_app_assignment values(NULL, 'a', 3, 4592, 1, 33, 59);
# sqlite> insert into work_app_assignment values(NULL, 'a', 1, 4596, 1, 33, 59);
```


