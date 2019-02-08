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

	public function testConstruct() {
		$this->assertInstanceOf(Assignments, $this->assignments);
	}

	/**
	 * @dataProvider provideRun
	public function testRun($season_months, $expected) {
		$this->assignments->initialize($season_months);
		$debug = [
			'expected' => $expected,
			'months' => $season_months,
		];
		// XXX this isn't doing much yet...
		$this->assertEquals($expected, 'XXX', print_r($debug, TRUE));
	}

	public function provideRun() {
		$season_months = [
			'January',
			'February',
			'March',
			'April',
		];

		return [
			[$season_months, 'XXX'],
		];
	}
	 */
}
?>
