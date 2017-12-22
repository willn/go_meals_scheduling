for i in `ls *Test.php`; do
	# do a lint check first...
	lint=`php -l $i | grep 'Errors'`;
	[[ ! -z $lint ]] && echo "FAIL $i --> php lint issue" && continue

	# now run phpunit
	phpunit $i $1 2>&1 > $1.out;
	if [[ $? -eq 0 ]]; then
		echo "PASS $i"
		rm -f $1.out
	else
		echo "FAIL $i: $?"
	fi
done

