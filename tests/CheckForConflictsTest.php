<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
require_once '../utils/check_for_conflicts.php';

class CheckForConflictsTest extends TestCase {

	/**
	 * @dataProvider provide_check_for_table_setter_conflicts
	public function test_check_for_table_setter_conflicts($file, $expected) {
		$result = check_for_table_setter_conflicts($file);
		$this->assertEquals($expected, $result);
	}

	public function provide_check_for_table_setter_conflicts() {
		return [
			# an empty array means no problems were found
			['../auto_assignments/schedule.txt', ['XXX']],
		];
	}
	 */

	/**
	 * @dataProvider provide_check_for_conflicts
	 */
	public function test_check_for_conflicts($file, $expected) {
		$result = check_for_conflicts($file);
		$this->assertEquals($expected, $result);
	}

	public function provide_check_for_conflicts() {
		return [
			# an empty array means no problems were found
			['../auto_assignments/schedule.txt', []],
		];
	}

}

