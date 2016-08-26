<?php

$format = <<<EOTXT
Meal.create(
	served_at: Date.parse("%s").midnight + 18.hours + 15.minutes,
	communities: Community.all,
	location: Location.find_by(abbrv: "GO"),
	capacity: 64,
	host_community: Community.find_by(name: "Great Oak"),
	head_cook: User.find_by(alternate_id: '%s'),
	asst_cooks: [User.find_by(alternate_id: '%s'), User.find_by(alternate_id: '%s')],
	cleaners: [User.find_by(alternate_id: '%s'), User.find_by(alternate_id: '%s'),
		User.find_by(alternate_id: '%s')]
)
EOTXT;

$cmds = [];
$lines = file('schedule.txt');
foreach($lines as $l) {
	$e = explode("\t", trim($l));
	$cmds[] = sprintf($format, $e[0], $e[1], $e[2], $e[3], $e[4], $e[5], $e[6]);
}

print implode("\n", $cmds) . "\n";

?>
