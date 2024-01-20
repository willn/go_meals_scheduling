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
 * Write out the data to a file.
 */
function write_out_data($data) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	if (!array_key_exists(1, $trace) || !array_key_exists('function', $trace[1])) {
		error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " bad trace");
		return;
	}

	$function_name = $trace[1]['function'];
	file_put_contents('auto-data/' . $function_name . '.json', json_encode($data, true));
}
