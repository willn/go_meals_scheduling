# MID-SEASON

### edit public/season.php
* set the appropriate `DEADLINE` date

### Resolve issues with moved-out workers

For anyone who has moved out since the work assignments were made for the
current season, then keep an eye out for them, and work with the meals and work
committee to figure out if we're going to re-assign or even cancel.

Sometimes holes can be filled by newer members who have not been integrated
into the work system yet.

## Add any new users

If someone new has moved in, or started working in the system, their usernames
will need to be added.

If this user has been mentioned in `get_num_shift_overrides()`, then ensure
that this username matches what is entered into the database in the next few
lines.

```
edit add_new_workers.php
php add_new_workers.php
```

### Add a full work assignment for a new worker

If that new person will be taking on an entire assigned job, then insert that
into the database. Otherwise, use overrides in the `season.php` file to account
for one-offs.
```
# edit utils/add_new_workers.php
php utils/add_new_workers.php
```

## Initialize Database Again

If new workers were added, then the database will need to be initialized again.

```
$ cd utils/
$ php initialize_database.php
```

Continue on to [DIRECTIONS_START_COMPLETE.md](./DIRECTIONS_START_COMPLETE.md)

