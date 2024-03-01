<?php
/**
 * Various shared utiliies which can make testing easier.
 */

/**
 * Remove whitespace before and after html tags
 */
function remove_html_whitespace($input) {
	$input = preg_replace('/>\s+/', ">", $input);
	return preg_replace('/\s+</', '<', $input);
}

/**
 *
 */
function get_data_filename($method) {
	return 'auto-data/' . str_replace('::', '__', $method) . '.json';
}

/**
 * Write out the data to a file.
 * #!# expected is pass by reference
 */
function write_out_data($method, $data) {
	$data_file = get_data_filename($method);
	if (file_exists($data_file)) {
		return;
	}

	# write out the data
	file_put_contents($data_file, json_encode($data, true));
}
