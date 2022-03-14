
# determine which tests to run
while getopts t: flag
do
    case "${flag}" in
        t) type=${OPTARG};;
    esac
done

# only run the lint tests
run_lint_tests () {
	printf "\n--- running lint check ..."
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
}

# only run the unit tests
run_unit_tests () {
	printf "\n--- running unit tests..."
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
	printf "\n--- Number of tests run: $tests_run"
}

# run all the tests
run_all_tests () {
    echo "--- Run all tests"
	run_lint_tests
	run_unit_tests
}


tests='ALL'
if [ -z ${type+x} ]; then
	run_all_tests; else

	case $type in
		'ALL')
			run_all_tests
			;;
		'L')
			run_lint_tests
			;;
		'U')
			run_unit_tests
			;;
		*)
			echo "error, choose ALL, U or L";
			exit;
		;;
	esac
fi

echo ""


