<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class ConfigTest extends PHPUnit_Framework_TestCase {
	private $season = [];

	public function setUp() {
		$this->season = get_current_season_months();
	}

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
	public function test_get_num_dinners_per_assignment($season, $job_id, $expect) {
		$result = get_num_dinners_per_assignment($season, $job_id);
		$debug = array(
			'season' => $season,
			'job_id' => $job_id,
			'expect' => $expect,
			'result' => $result,
		);
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_num_dinners_per_assignment() {
		$season_6mos = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
		];
		$season_4mos = [
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
		];

		return [
			[$season_6mos, 'undefined entry', 0],

			[$season_6mos, MEETING_NIGHT_CLEANER, 3],
			[$season_6mos, MEETING_NIGHT_ORDERER, 3],
			[$season_6mos, SUNDAY_HEAD_COOK, 3],
			[$season_6mos, SUNDAY_ASST_COOK, 3],
			[$season_6mos, SUNDAY_CLEANER, 6],
			[$season_6mos, WEEKDAY_ASST_COOK, 3],
			[$season_6mos, WEEKDAY_HEAD_COOK, 3],
			[$season_6mos, WEEKDAY_CLEANER, 6],
			[$season_6mos, WEEKDAY_TABLE_SETTER, 6],

			[$season_4mos, MEETING_NIGHT_CLEANER, 2],
			[$season_4mos, MEETING_NIGHT_ORDERER, 2],
			[$season_4mos, SUNDAY_HEAD_COOK, 2],
			[$season_4mos, SUNDAY_ASST_COOK, 2],
			[$season_4mos, SUNDAY_CLEANER, 4],
			[$season_4mos, WEEKDAY_ASST_COOK, 2],
			[$season_4mos, WEEKDAY_HEAD_COOK, 2],
			[$season_4mos, WEEKDAY_CLEANER, 4],
			[$season_4mos, WEEKDAY_TABLE_SETTER, 4],

			# check for mistakes
			[32, WEEKDAY_ASST_COOK, 3],
		];
	}
}
?>
