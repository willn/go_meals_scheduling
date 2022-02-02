<?php
global $relative_dir;
$relative_dir = '../public';

require_once '../public/season.php';
require_once '../public/utils.php';
require_once '../public/constants.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class SeasonTest extends PHPUnit_Framework_TestCase {
	public function test_get_num_shift_overrides() {
		$overrides = get_num_shift_overrides();
		$this->assertInternalType("array", $overrides);

		if (empty($overrides)) {
			return;
		}

		// test that the overrides array is structured properly
		foreach($overrides as $name => $assignments) {
			$this->assertInternalType("string", $name);
			$this->assertInternalType("array", $assignments);
			$this->assertNotEmpty($assignments);

			foreach($assignments as $job_id => $num_assign) {
				$this->assertInternalType("int", $job_id);
				$this->assertInternalType("int", $num_assign);
			}
		}
	}

	public function test_get_skip_dates() {
		$skips = get_skip_dates();
		$this->assertInternalType("array", $skips);

		if (empty($skips)) {
			return;
		}

		foreach($skips as $month => $list_of_days) {
			$this->assertInternalType("int", $month);
			$this->assertInternalType("array", $list_of_days);
			$this->assertNotEmpty($list_of_days);

			foreach($list_of_days as $day) {
				$this->assertInternalType("int", $day);

				$ts = mktime(0, 0, 0, $day, $month, SEASON_YEAR);
				$this->assertNotEquals(0, $ts);
			}
		}
	}

	public function test_get_regular_day_overrides() {
		$reg_overrides = get_regular_day_overrides();
		$this->assertInternalType("array", $reg_overrides);

		if (empty($reg_overrides)) {
			return;
		}

		foreach($reg_overrides as $month => $list_of_days) {
			$this->assertInternalType("int", $month);
			$this->assertInternalType("array", $list_of_days);
			$this->assertNotEmpty($list_of_days);

			foreach($list_of_days as $day) {
				$this->assertInternalType("int", $day);

				$ts = mktime(0, 0, 0, $day, $month, SEASON_YEAR);
				$this->assertNotEquals(0, $ts);
			}
		}
	}

	/**
	 * @dataProvider provide_get_current_season_months
	 */
	public function test_get_current_season_months($season_name) {
		$result = get_current_season_months($season_name);
		$this->assertNotEmpty($result);

		$keys = array_keys($result);
		foreach($keys as $k) {
			$this->assertInternalType("int", $k);
		}

		$values = array_values($result);
		foreach($values as $v) {
			$this->assertInternalType("string", $v);
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
