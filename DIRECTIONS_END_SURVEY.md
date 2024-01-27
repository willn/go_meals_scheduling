# END-OF-SURVEY

## disable cronjobs

## copy closed database locally:
```
# on gocoho:
mysqldump -u gocoho_work_allocation -p gocoho_work_allocation > end_of_survey.sql

# on localhost:
cd meals_scheduling/sql/
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

# continue running and adjusting skipped dates, until it no longer reports on "Placeholder count X"
php execute.php -s
```

## Borrowed labor
Confirm whether any "borrowed" labor was actually needed. If not, then
remove the borrowed labor.

### run the unit tests and adjust until things work
```
cd tests/
./run.sh

# make adjustments on the number of meals to make these tests pass
git status
git diff
git add
git commit
```

## make a run, and analyze the results
`./analyze_results.sh`

## if there are no meeting night cleaners...
* then delete the placeholders for those shifts, just leave it blank

## upload a copy of the `schedule.txt` to google drive & import into a spreadsheet
* refer to workers.txt and try to move the under-assigned workers to
  fill the 'XXX' spots, making trades
* do any swapping needed

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
```

Read the comments for special requests at the bottom of the full report as well.

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
cd meals_scheduling/utils/
php translate_to_gather_imports.php > imports.csv
```
If the above translation has 1 or more missing ID names, it will output a list
of names.

Look up their ID in Gather, then:

1. for this season, update database table `auth_users` to include their IDs
2. for the future, copy and paste these entries to `sql/add_gather_ids.sql`
3. Run the translate script again.

### make any tweaks
* Confirm which communities are mentioned
* Confirm the times and dates of the meals
* Unfortunately, it appears that gather import does not support "capacity" at this time.

### Upload to Gather
* open gather site, and upload the entries
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


