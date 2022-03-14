# Directions for setting up & running the Great Oak Meals Scheduling Survey

-------------------------------

## Update settings in order to calculate the needed shift counts

### Ask meals committee questions:
* Do they want to update the list of hobarters?
  - Look for `function get_hobarters(`
* Do they want to offer CSA farm meals this summer?

### check that there aren't uncommitted modifications
`git status`

### update public/season.php
* set the SEASON_NAME
* make sure the right months are included in get_current_season_months()

### remove any previous season's data from public/season.php
  - get_num_shift_overrides()
  - get_regular_day_overrides()
  - get_skip_dates()

### Update unit tests
The number of assignments needed is in tests/CalendarTest.php, in 
provideGetAssignmentsNeededForCurrentSeason()

-------------------------------

## Prepare to launch the survey

### edit public/season.php
* set the appropriate DEADLINE date
* display a farm meals night message? (DOING_CSA_FARM_MEALS)
* set the SUB_SEASON_FACTOR

### get new job IDs for the season, and update the defines for each job in config.php
```
	cd utils/
	php find_current_season_jobs.php
	# copy that block and replace the previous season's entries in this file:
	vi ../public/season.php
	# sorting these alphabetically can help with debugging
```

# update the unit tests which are going to fail based on changed info
# look for the UPDATE-EACH-SEASON

# make sure that unit tests work:
	cd tests
	./run.sh
	# the various testCompareLabor tests will fail until the rest of setup

# when tests pass, then commit
	git status
	# commit changes

-------------------------------

## MID-SEASON: if this is mid-season, follow these directions, otherwise skip

### clear out existing tables
```
	cd public/sqlite_data/
	sqlite3 work_allocation.db
	sqlite> .read reset_mid_season.sql
	// exit sqlite
	cd ../
	git diff | view -
```

### skip to FINISH-START-OF-SEASON


## SEASON-START:

# grab the latest sqlite file from work hosting, fix permissions, and commit:
	# login to the work web UI, go to more reports, and "Download SQLite3 database from host"
	# locally...
	open http://gocoho.tklapp.com/download/database/
	# download the latest sqlite file
	cd ~/Downloads/
	unzip filedb.zip
	scp -P 1022 home/django/work/db.sqlite3 gocoho@gocoho.org:meals_scheduling_dev/public/sqlite_data/
	rm -rf home/ filedb.zip

	# on the remote host...
	cd meals_scheduling_dev/public/sqlite_data
	mv db.sqlite3 work_allocation.db
	chmod 644 work_allocation.db
	sqlite work_allocation.db
	# view the current state of tables
	sqlite> .tables
	# drop a bunch of tables
	sqlite> .read drops.sql
	# confirm
	sqlite> .tables
		# there should be 4 tables:
		# sqlite> .tables
		# auth_user            work_app_job
		# work_app_assignment  work_app_season

 	# add the Gather IDs 
	sqlite> .read add_gather_ids.sql
	# confirm
	sqlite> .schema auth_user
	# The last field listed should be "gather_id"
	sqlite> exit
	git add !$
	git commit !$

# initialize the database
	cd ../../utils/
	php initialize_database.php
	cd ../tests/
	./run.sh

	# if all unit tests pass, then commit
	git status
	git add *
	git commit

-------------------------------

## FINISH-START-OF-SEASON

### stage everything from the meals dev repo to public_html
```
	cd ~
	#!# careful! - this will blank out any collected data...
	rsync -e 'ssh -p 1022' -avz public/ gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/
```

### test to make sure everything works, view in web browser
* confirm that the calendar dates are correct
* confirm the holidays and meeting nights are correct
* fill in some entries and save them, then revert
* #!# careful! - this will blank out any collected data...
* rsync -e 'ssh -p 1022' -avz public/ gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/
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

### confirm with the Meals committee about which communities can attemnd the max capacity per meal

-------------------------------

## END-OF-SURVEY

### disable cronjobs

XXX
## commit closed database:
```
	rsync -e 'ssh -p 1022' -avz gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/sqlite_data/work_allocation.db public/sqlite_data/work_allocation.db
	git status
	git commit public/sqlite_data/work_allocation.db
```

### check for any un-assigned workers
```
	cd auto_assignments/
	php execute.php -u
```

### If we know that we need to cancel 1 or more meals, edit
```
	vi public/constants.php   # set DEBUG_GET_LEAST_POSSIBLE to TRUE
	cd auto_assignments/
	php execute.php -s
	tail -f error
```

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

### if some meals need to be cancelled, this would be a good time to look for
  the hardest to fill days:
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

### download a copy of the schedule, and upload it to google drive
```
	# on localhost
	cd ~/Desktop/
	scp -i ~/.ssh/id_dsa -P 1022 \
		gocoho@gocoho.org:/home/gocoho/meals_scheduling_dev/auto_assignments/results.txt .
```

### Import into a google spreadsheet
* try to move the under-assigned workers to fill the 'XXX' spots, making trades
* do any swapping needed

### confirm preferences
Copy and paste from "Confirm results check" section, and create custom ones for anything that's not a personal avoid request.

1. Download from google spreadsheet, save as tab separated values (TSV)
  - rename file to schedule.txt
2. go to http://gocoho.org/meals_scheduling/report.php?key=all#confirm_checks
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

### run avoids validation:
```
	# is this needed with the new unit tests?
	cd ../utils/
	php validate_schedule.php -f ../tests/data/schedule.tsv
```

- upload any changes to google sheets and announce it


-------------------------------

## translate from Google sheet to Gather imports

on mac:
- Download sheet in CSV form
- mv GO\ Meals\ Feb-April\ 2020\ -\ results.csv final_schedule.csv
- Upload from desktop to gocoho host
- scp -P 1022 final_schedule.csv gocoho@gocoho.org:meals_scheduling_dev/utils/

on gocoho:
- ssh gocoho
- cd meals_scheduling_dev/utils/
- php translate_to_gather_imports.php > imports.csv
- #!# DO WE NEED TO ENTER "waive" FORMULA FOR MEETING NIGHT MEALS?
- look for username mistranslations...
  - `grep XXX imports.csv`
  - if any users are missing, look up their user ID in Gather and edit
    the sqlite table "auth_users" to update anyone's missing gather_id

### make any changes to communities invited / max capacity
* Confirm which communities are mentioned (covid era restricts to only GO)
* Confirm the times and dates of the meals
* Confirm the capacities...
  - Unfortunately, it appears that gather import does not support "capacity"

### on mac:
- download the imports file
- scp -P 1022 gocoho@gocoho.org:meals_scheduling_dev/utils/imports.csv .
- open gather site, and upload the clean entries
- resolve any scheduling conflicts... meals currently add 2:15 before and after
  the announced meal serving time.
  - Sundays: 3:15 - 7:45
  - Weekdays: 4:00 - 8:30
  - Meeting Night: 3:30 - 8:00


-------------------------------

## Development notes between seasons

update public/config.php, update the season name, year, and season id

- pop into sqlite:
	sqlite3 !$

- update the data to use the current season id:
	update work_app_assignment set season_id=11 where season_id=10;

```
# create some entries (cook & clean) for testing of survey
# sqlite> insert into work_app_assignment values(NULL, 'a', 3, 4592, 1, 33, 59);
# sqlite> insert into work_app_assignment values(NULL, 'a', 1, 4596, 1, 33, 59);
```


-------------------------------

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
