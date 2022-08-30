# Directions for setting up & running the Great Oak Meals Scheduling Survey

## Update settings in order to calculate the needed shift counts

### Ask meals committee questions:
* Do they want to update the list of hobarters?
  - Look for `function get_hobarters(`
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

### Update unit tests
* update config to examine a full 6-month season
  - SUB_SEASON_FACTOR to 1 in order to get the full 6 months, in `public/season.php`
  - set SEASON_NAME to the combo option (FALL_WINTER or SPRING_SUMMER)
* run & fix unit tests
  - The number of assignments needed is in tests/CalendarTest.php, in
  `provideGetAssignmentsNeededForCurrentSeason()`

## SEASON-START:
If this is mid-season, skip to the [MID-SEASON section](SETUP.md#mid-season)

## Prepare to launch the survey

### edit public/season.php
* set the appropriate `DEADLINE` date
* display a farm meals night message? (`DOING_CSA_FARM_MEALS`)
* set the `SUB_SEASON_FACTOR`

### update the database

* grab the latest sqlite file from work hosting, fix permissions, and commit:
  - login to the work web UI, go to more reports, and "Download SQLite3
	database from host"
  - locally:
```
open http://gocoho.tklapp.com/download/database/
cd ~/Downloads/
unzip filedb.zip
mv home/django/work/db.sqlite3 ~/projects/go_meals_scheduling/public/sqlite_data/work_allocation.db
rm -rf home/ filedb.zip
```

* clean the database
```
cd ~/projects/go_meals_scheduling/
chmod 644 work_allocation.db

sqlite3 public/sqlite_data/work_allocation.db
# view the current state of tables
sqlite> .tables

# drop a bunch of tables
sqlite> .read sql/drops.sql

# confirm - there should be 4 tables
sqlite> .tables
auth_user            work_app_job
work_app_assignment  work_app_season
```

* add the Gather IDs 
```
sqlite> .read sql/add_gather_ids.sql
# confirm
sqlite> .schema auth_user
# The last field listed should be "gather_id"
sqlite> exit
git add !$
git commit !$
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
# the various testCompareLabor tests may fail until the rest of setup
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
If this is mid-season, follow these directions, otherwise skip to
FINISH-START-OF-SEASON.

### edit public/season.php
* set the appropriate `DEADLINE` date

### clear out existing tables
```
# from top-level
sqlite3 public/sqlite_data/work_allocation.db
sqlite> .read sql/reset_mid_season.sql
// exit sqlite
git status # resolve differences
```

## FINISH-START-OF-SEASON

### stage everything from the meals dev repo to public_html
```
# careful! - this will blank out any collected data...
rsync -e 'ssh -p 1022' -avz public/ gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/
# permissions might need to be reset:
cd public_html/meals_scheduling/
chmod -R g-w *
```

### test to make sure everything works, view in web browser
* confirm that the calendar dates are correct
* confirm the holidays and meeting nights are correct
* fill in some entries and save them, then revert
* resync code to clean up and reset database
* load listing page again to make sure that the database is writeable

### notify participants that the survey is ready

### set up database backup routine on the webserver:
```
mkdir ~/backups
chmod 700 ~/backups/

crontab -e
# uncomment the following lines:
20 *   *   *   *   /bin/cp -f ~/meals_scheduling_dev/public/sqlite_data/work_allocation.db ~/backups/
50 5 * * * /bin/cp -f public_html/meals_scheduling/sqlite_data/work_allocation.db ~/backups/work_allocation.db_daily
```

### schedule a few reminders spaced out over the rest of the session to send reminder emails to laggards

## MID-SURVEY

### Missing labor

Figure out which meals shifts are missing labor. This can be helpful to do in
advance so that volunteers can be sought while the survey is open. In addition,
this can be used to cancel / skip certain types of meals.

### uncomment RosterTest::testCompareLabor()

This output should be helpful in divulging which shift types have the
right or wrong amount of labor.

## END-OF-SURVEY

### disable cronjobs

### commit closed database:
```
rsync -e 'ssh -p 1022' -avz gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/sqlite_data/work_allocation.db public/sqlite_data/work_allocation.db
git status
git commit public/sqlite_data/work_allocation.db
```

### check for any un-assigned workers
```
cd auto_assignments/
php execute.php -u
cd ..
```

### If we know that we need to cancel 1 or more meals, edit
```
vi public/constants.php   # set DEBUG_GET_LEAST_POSSIBLE to TRUE
cd auto_assignments/
php execute.php -s
tail -f error_log
```

### Cancel extra meals
Look at all of the shifts to see where the pain lies... head, asst, cleaner
if we need to cancel meals, then mark these as skip dates.
```
# add dates to get_skip_dates
vi public/season.php
# run the unit test for CalendarTest::testRenderSeasonDateSummary until
# things line up
cd tests/
phpunit CalendarTest.php
```

### count un-filled slots:
```
php execute.php -s > results.txt
grep XXX !$
```

### look for the hardest to days to fill:
```
grep 'XXXXXXXX.*XXXXXXXX' results.txt
```

### check for hobarter ratio:
```
grep HOBART results.txt
```

### Examine workers:
```
php execute.php -w > workers.txt
```

### Someone may have volunteered to take too many additional meals

Reduce the number of needed volunteer / override positions mentioned
with this:
```
grep OVERAGE workers.txt
```

### find the people who aren't fully assigned:
```
egrep '(^name|\(0)' workers.txt | grep -B1 'j:' > workers_not_full.txt
```

### upload a copy of the results.txt to google drive & import into a spreadsheet
* try to move the under-assigned workers to fill the 'XXX' spots, making trades
* do any swapping needed

### confirm preferences
1. Download from google spreadsheet, save as tab separated values (TSV)
  - rename file to schedule.txt
2.  On the schedule / report page, clear any filters. Go to the "confirm
    checks" section, copy and paste from "Confirm results check" section.
3. Run the checks script against that file.
```
# copy section of text
vi checks.sh
# paste
chmod +x checks.sh
./checks.sh | more
# read the comments and make sure they apply cleanly with auto-checks,
# otherwise make trades
```

### look for table setter conflicts
Check to make sure that there are no 'table setter' assignments which conflict with head or asst cooking.
* Export from google spreadsheet, download as tab-delimited
* see tests/CheckForConflictsTest.php
* #!# need a test to ensure that nobody is double-scheduled... like head & asst or table setter

### run conflicts validation:
```
# is this needed with the new unit tests?
cd ../utils/
php validate_schedule.php -f ../tests/data/schedule.tsv
```

- upload any changes to google sheets and announce it


## Translate from Google sheet to Gather imports

Download sheet in *CSV* form.  Rename file to `final_schedule.csv`

```
cd meals_scheduling_dev/utils/
php translate_to_gather_imports.php > imports.csv
```
If the above translation has 1 or more missing ID names, it will output a list
of names. Look up their ID in Gather, then:

1. for this season, update sqlite table "auth_users" to include their IDs
2. for the future, copy and paste these entries to `sql/add_gather_ids.sql`
3. Run the translate script again.

### make any tweaks
* Confirm which communities are mentioned (covid era restricts to only GO)
* Confirm the times and dates of the meals
* Unfortunately, it appears that gather import does not support "capacity" at this time.

### Upload to Gather
* open gather site, and upload the clean entries
* resolve any scheduling conflicts... meals currently add 2h 15m before and after
  the announced meal serving time.
  - Sundays: 3:15 - 7:45
  - Weekdays: 4:00 - 8:30
  - Meeting Night: 3:30 - 8:00


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


## How to add a user with an override.

They need to be entered into the database...

```
% sqlite3 work_allocation.db

# get the username of the peron
sqlite> select id from auth_user where username='XXX';
164

# get the max assignment id:
sqlite> select max(id) from work_app_assignment;
9254

# add a new entry
insert into work_app_assignment values(
        "id", -- max assignment id + 1
        "type", 
        "instances",
        "job_id",
        "reason_id",
        "season_id",
        "worker_id"
);
insert into work_app_assignment values(13623, '', 1, 4594, 0, 33, 164);
```

