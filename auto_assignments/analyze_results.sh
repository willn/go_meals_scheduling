#!/bin/bash

#-----------------------------------------------
# Run an allocation, and analyze the results
#-----------------------------------------------

RESULTS='schedule.txt' 
WORKERS='workers.txt'

echo "count un-filled slots:"
`php execute.php -s > ${RESULTS}`;
grep XXX $RESULTS | grep -v '			';

echo ""
echo "look for the hardest to days to fill:";
grep 'XXXXXXXX.*XXXXXXXX' $RESULTS;

echo ""
echo "check for hobarter ratio:";
grep HOBART $RESULTS;

#Examine workers:
`php execute.php -w > ${WORKERS}`;

echo "";
echo "Is there excess volunteer labor?"
grep OVERAGE $WORKERS;

UNFILLED='not_filled_workers.txt';
echo "";
echo "find the people who aren't fully assigned: ${UNFILLED}";
egrep '(^name|\(0)' workers.txt | grep -B1 'j:' > ${UNFILLED}
cat ${UNFILLED}


echo "";
