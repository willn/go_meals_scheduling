<?php
use PHPUnit\Framework\TestCase;

require_once '../public/season.php';
require_once '../public/utils.php';
require_once '../public/constants.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class SeasonTest extends TestCase {
	public function test_get_num_shift_overrides() {
		$overrides = get_num_shift_overrides();
		$this->assertIsArray($overrides);

		if (empty($overrides)) {
			return;
		}

		// test that the overrides array is structured properly
		foreach($overrides as $name => $assignments) {
			$this->assertIsString($name);
			$this->assertIsArray($assignments);
			$this->assertNotEmpty($assignments);

			foreach($assignments as $job_id => $num_assign) {
				$this->assertIsInt($job_id);
				$this->assertIsInt($num_assign);
			}
		}
	}

	public function test_get_skip_dates() {
		$skips = get_skip_dates();
		$this->assertIsArray($skips);

		if (empty($skips)) {
			return;
		}

		foreach($skips as $month => $list_of_days) {
			$this->assertIsInt($month);
			$this->assertIsArray($list_of_days);
			$this->assertNotEmpty($list_of_days);

			foreach($list_of_days as $day) {
				$this->assertIsInt($day);

				$ts = mktime(0, 0, 0, $day, $month, SEASON_YEAR);
				$this->assertNotEquals(0, $ts);
			}
		}
	}

	public function test_get_weekday_overrides() {
		$reg_overrides = get_weekday_overrides();
		$this->assertIsArray($reg_overrides);

		if (empty($reg_overrides)) {
			return;
		}

		foreach($reg_overrides as $month => $list_of_days) {
			$this->assertIsInt($month);
			$this->assertIsArray($list_of_days);
			$this->assertNotEmpty($list_of_days);

			foreach($list_of_days as $day) {
				$this->assertIsInt($day);

				$ts = mktime(0, 0, 0, $day, $month, SEASON_YEAR);
				$this->assertNotEquals(0, $ts);
			}
		}
	}

	public function test_get_meeting_night_overrides() {
		$overrides = get_meeting_night_overrides();
		$this->assertIsArray($overrides);
		#$this->assertEmpty($overrides);
		$expected = [
			3 => [3]
		];
		$debug = [
			'overrides' => $overrides,
			'expected' => $expected,
		];
		$this->assertEquals($overrides, $expected, print_r($debug, TRUE));
	}

	/**
	 * @dataProvider provide_get_current_season_months
	 */
	public function test_get_current_season_months($season_name) {
		$result = get_current_season_months($season_name);
		$this->assertNotEmpty($result);

		$keys = array_keys($result);
		foreach($keys as $k) {
			$this->assertIsInt($k);
		}

		$values = array_values($result);
		foreach($values as $v) {
			$this->assertIsString($v);
		}
	}

	public function provide_get_current_season_months() {
		return [
			[WINTER],
			[SPRING],
			[SUMMER],
			[FALL],
		];
	}
}
?>
