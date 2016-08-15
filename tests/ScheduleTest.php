<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../auto_assignments/schedule.php';
// require_once '../public/classes/calendar.php';
require_once '../public/classes/sqlite_interface.php';

/**
 * Test the scheduling framework.
 * NOTE: for now, this doesn't do anything, since calling PDO breaks the tests.
 */
class ScheduleTest extends PHPUnit_Framework_TestCase {
	protected $schedule;

	public function setUp() {
		// $this->schedule = new Schedule();
		global $dbh;

		$iface = new SqliteInterface('sqlite::memory');
		$iface->query('create table user (id INTEGER PRIMARY KEY, name TEXT)');
		$iface->query('insert into user (name) values("bob")');
	}

	/**
	 * @dataProvider varsProvider
	 */
	public function testSetVars($vars) {
/*
		$this->schedule->setVariables($vars);
		$pts = $this->schedule->getPointFactors();
		$this->assertEquals(
			$pts['prefer'] * PREFER_TO_AVOID_RATIO);
*/
	}

	public function varsProvider() {
		return array(
			array(0, 0, 0),
			array(1, 2, 3, 1.65),
			array(8, 9, 9, 4.95),
		);
	}
}
?>
