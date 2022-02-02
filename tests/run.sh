# lint check
echo "--- running lint check first..."
errs=0
files_checked=0
for i in `find ../ -name "*.php" -or -name "*.inc"`; do
	lint=`php -l $i | grep 'Errors'`;
	[[ ! -z $lint ]] && echo "FAIL $i --> php lint issue" && let errs=errs+1
	let files_checked=files_checked+1
done
if [ "$errs" -gt 0 ]
then
	exit
fi
echo "PASS all files pass lint $files_checked"


# now run phpunit
echo "--- running unit tests..."
for i in `ls *Test.php`; do
	phpunit $i $1 2>&1 > $1.out;
	if [[ $? -eq 0 ]]; then
		echo "PASS $i"
		rm -f $1.out
	else
		echo "FAIL $i: $?"
	fi
done

tests_run=`grep 'function test' *Test.php | wc -l`
echo "--- Number of tests run: $tests_run"
