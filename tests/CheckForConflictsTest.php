<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../utils/check_for_conflicts.php';

class CheckForConflictsTest extends PHPUnit_Framework_TestCase {

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
			['../auto_assignments/schedule.txt', [
				'9/25/2022 head cook and asst2',
				'10/18/2022 cleaner 1 and 3',
				'10/26/2022 cleaner 1 and 2',
				'10/26/2022 cleaner 1 and 3',
				'10/26/2022 cleaner 3 and 2',
			]],
		];
	}

}

