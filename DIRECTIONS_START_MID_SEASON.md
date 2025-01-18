# MID-SEASON

### edit public/season.php
* set the appropriate `DEADLINE` date

### clear out prior data from tables
`./connect_to_mysql.sh < sql/reset_mid_season.sql`

### Resolve issues with moved-out workers

For anyone who has moved out since the work assignments were made for the
current season, then keep an eye out for them, and work with the meals and work
committee to figure out if we're going to re-assign or even cancel.

Sometimes holes can be filled by newer members who have not been integrated
into the work system yet.

### Add any new users

If someone new has moved in, or started working in the system, their usernames
will need to be added.

If this user has been mentioned in `get_num_shift_overrides()`, then ensure
that this username matches what is entered into the database in the next few
lines.

```
# Confirm who the most recently added workers are:
SELECT id, first_name, last_name FROM auth_user ORDER BY id DESC LIMIT 5;

# Create entries for these people in the `auth_user` table - last is gather ID
INSERT INTO auth_user VALUES(NULL, NULL, 0, 'FIRST-NAME', 'LAST-NAME',
	'example@asdf.com', 0, 1, '2023-07-15', 'username', 12349999);
```

## Add a full work assignment for a new worker

If that new person will be taking on an entire assigned job, then insert that
into the database. Otherwise, use overrides in the `season.php` file to account
for one-offs.
```
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

## Initialize Database Again

If new workers were added, then the database will need to be initialized again.

```
$ cd utils/
$ php initialize_database.php
```

Continue on to [FINISH-START-OF-SEASON](./DIRECTIONS_START_COMPLETE.md)

