<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/globals.php';

class GlobalsTest extends PHPUnit_Framework_TestCase {
	public function test_get_days_of_week() {
		$days = get_days_of_week();
		$this->assertInternalType('array', $days);
		$this->assertEquals(count($days), 7, print_r($debug, TRUE));
	}

	public function test_get_weekday_meal_days() {
		$days = get_weekday_meal_days();
		$this->assertInternalType('array', $days);
		$this->assertNotEmpty($days);
	}
}
?>
