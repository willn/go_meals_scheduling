<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/globals.php';

class GlobalsTest extends PHPUnit_Framework_TestCase {
	public function test_get_days_of_week() {
		$days = get_days_of_week();
		$this->assertEquals(count($days), 7, print_r($debug, TRUE));
	}
}
?>
