<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class ConfigTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provide_get_holidays
	 */
	public function test_get_holidays($expect) {
		$result = get_holidays();
		$debug = [
			'input' => $input,
			'expect' => $expect,
			'result' => $result,
		];
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_holidays() {
		$days = [
			1 => [1],
			4 => [1],
			5 => [27, 28],
			7 => [4],
			9 => [2,3],
			10 => [31],
			11 => [22, 25],
			12 => [24, 25, 31],
		];

		return array(
			[$days],
		);
	}

	/**
	 * @dataProvider provide_get_num_dinners_per_assignment
	 */
	public function test_get_num_dinners_per_assignment($input, $expect) {
		$result = get_num_dinners_per_assignment($input);
		$debug = array(
			'input' => $input,
			'expect' => $expect,
			'result' => $result,
		);
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_num_dinners_per_assignment() {
		return array(
			array(MEETING_NIGHT_CLEANER, 2),
			array('undefined entry', 0),
		);
	}
}
?>
