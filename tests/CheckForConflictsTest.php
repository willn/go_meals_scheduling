<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../utils/check_for_conflicts.php';

class CheckForConflictsTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provide_check_for_table_setter_conflicts
	 */
	public function test_check_for_table_setter_conflicts($input, $expected) {
		$result = check_for_table_setter_conflicts($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_check_for_table_setter_conflicts() {
		return [
			# an empty array means no problems were found
			['../auto_assignments/schedule.txt', []],
		];
	}
}

