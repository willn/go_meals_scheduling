#!/bin/bash

cd public/
FILES=`git ls-tree -r master --name-only`

for FILE in $FILES; do

	# don't over-write the database
	if [[ $FILE == 'sqlite_data/work_allocation.db' ]]; then
		continue;
	fi

	rsync -e 'ssh -p 1022' -avz $FILE gocoho@gocoho.org:/home/gocoho/public_html/meals_scheduling/$FILE
done

cd ..

