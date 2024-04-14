#!/bin/bash

# ----------------------------------------------------------------------
# This file is meant to help prepare output from a sqlite database to be
# imported into a MySQL database.
# ----------------------------------------------------------------------

sqlite3 db.sqlite3 <<'END_OF_SQL'
.output work.sql
.dump auth_user work_app_assignment work_app_committee work_app_job work_app_season
.exit
END_OF_SQL

# confirm that we have the desired tables
grep 'CREATE TABLE' work.sql | sed 's/^CREATE TABLE IF NOT EXISTS //' | cut -d\" -f2;

# remove some unwanted lines
cat work.sql | grep -v '^PRAGMA foreign_keys=OFF;$' | \
	grep -v '^BEGIN TRANSACTION;$' | \
	grep -v '^COMMIT;$' > work2.sql

# translate some options
grep '^CREATE TABLE' work2.sql | \
	sed 's/"//g' | \
	sed 's/integer/MEDIUMINT/g' | \
	sed 's/AUTOINCREMENT/AUTO_INCREMENT/g' | \
	sed 's/description varchar(40)/description varchar(80)/g' | \
	sed 's/index/indexCount/' > work3.sql
cat work2.sql | grep -v '^CREATE TABLE' >> work3.sql

echo "alter table auth_user drop column password;" >> work3.sql

rm -f work2.sql
mv -f work3.sql work.sql

