<?php
require_once '../public/season.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class SeasonTest extends PHPUnit_Framework_TestCase {
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
