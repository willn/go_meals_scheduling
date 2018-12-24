<?php
global $relative_dir;
$relative_dir = '../public';
require_once '../utils/check_for_conflicts.php';

class CheckForConflictsTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provide_parse_schedule_file
	public function test_parse_schedule_file($filename, $expected) {
		$result = parse_schedule_file($filename);
		$this->assertEquals($expected, $result);
	}

	public function provide_parse_schedule_file() {
		$mega = file_get_contents('data/mega_season.json');

		return [
			[NULL, NULL],
			['data/schedule.tsv', json_decode($mega, TRUE)],
		];
	}
	 */

	/**
	 * @dataProvider provide_check_for_table_setter_conflicts
	 */
	public function test_check_for_table_setter_conflicts($filename, $expected) {
		$result = check_for_table_setter_conflicts($filename);
		$this->assertEquals($expected, $result);
	}

	public function provide_check_for_table_setter_conflicts() {
		return [
			['data/schedule.tsv', []],
			['data/bad.tsv', [
				'5/1/2018 table setter & asst2:nancy',
				'5/7/2018 table setter & asst2:katie'
			]],
		];
	}

}
?>
