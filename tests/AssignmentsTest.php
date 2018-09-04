<?php
set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../auto_assignments/';
require_once '../auto_assignments/assignments.php';

class AssignmentsTest extends PHPUnit_Framework_TestCase {
	private $assignments;

	public function setUp() {
		$this->assignments = new Assignments();
	}

	public function testInitialize() {
		$season_months = [
			'January',
			'February',
			'March',
			'April',
		];
		$this->assignments->initialize($season_months);
		$this->assertEquals($expect, array_get($list, $key, $default));
	}
}
?>
