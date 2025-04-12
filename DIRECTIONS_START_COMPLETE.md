# FINISH-START-OF-SEASON

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

# Clean up the transfer SQL
./clean_transfer_sql.sh

# transfer the SQL file to production
scp -i ~/.ssh/id_dsa transfer.sql gocoho@gocoho.org:

# on gocoho:
cd ~
mysql -u gocoho_work_allocation -p gocoho_work_allocation < transfer.sql
rm transfer.sql
```

### test to make sure everything works, view in web browser
* resync code to clean up
   - `./push_all_to_production.sh`
* in the web browser, confirm that the calendar dates are correct
   - confirm the holidays and meeting nights are correct
   - fill in some entries and save them
   - load listing page again to make sure that the database is writeable

### notify participants that the survey is ready

### set up database backup routine on the webserver:
```
gocoho
crontab -e
```

### schedule reminders

Spaced these out over the rest of the survey session to send reminder
emails to nag laggards.


