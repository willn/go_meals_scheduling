<?php

require_once('globals.php');

/**
 * Map the given username to the appropriate Gather ID. If it exists.
 *
 * @param array $usernames work system usernames to be replaced.
 * @param array $gather_ids associative array of all work system usernames to gather IDs.
 * @return array (associative) with only the entries found in the first
 *     array. The keys are work system IDs, the values are Gather IDs.
 */
function map_usernames_to_gather_id($usernames, $gather_ids) {
	// in case there's an unassigned shift, retain that.
	$gather_ids[PLACEHOLDER] = PLACEHOLDER;
	$replaced = array_intersect_key($gather_ids, array_flip($usernames));

	return (count($replaced) === count($usernames)) ? array_filter($replaced) : 0;
}

?>
