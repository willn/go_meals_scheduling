<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../auto_assignments/schedule.php';
require_once '../public/classes/sqlite_interface.php';
require_once '../public/classes/calendar.php';

/**
 * Test the scheduling framework.
 * NOTE: for now, this doesn't do anything, since calling PDO breaks the tests.
 */
class ScheduleTest extends PHPUnit_Framework_TestCase {
	protected $schedule;

	public function setUp() {
		$this->schedule = new Schedule();
	}

	/**
	 * @dataProvider jobIdsProvider
	 */
	public function testSetJobId($id) {
		$this->schedule->setJobId($id);
		$this->assertEquals($this->schedule->getJobId(), $id);
		$this->assertEquals($this->schedule->getPossibleRatios(), []);
	}

	public function jobIdsProvider() {
		return [
			[0],
			[1],
			[8],
		];
	}

	/**
	 * @dataProvider pointFactorsProvider
	 */
	public function testSetPointFactors($hobart, $avail, $avoids, $prefer) {
		$this->schedule->setPointFactors($hobart, $avail, $avoids);
		$expected = [
			'hobart' => !is_null($hobart) ? $hobart : DEFAULT_HOBART_SCORE,
			'avail' => !is_null($avail) ? $avail : DEFAULT_AVAIL_SCORE,
			'avoids' => !is_null($avoids) ? $avoids : DEFAULT_AVOIDS_SCORE,
			'prefers' => !is_null($prefer) ? $prefer : DEFAULT_PREFERS_SCORE,
		];

		$this->assertEquals($this->schedule->getPointFactors(), $expected);
	}

	public function pointFactorsProvider() {
		// hobart_factor, avail_factor, avoids_factor
		return [
			[NULL, NULL, NULL, NULL],
			[NULL, 1, 2, 1.1],
			[1, NULL, 2, 1.1],
			[1, 2, NULL, DEFAULT_PREFERS_SCORE],
			[0, 0, 0, 0],
			[1, 1, 1, .55],
			[1, 1, 10, 5.5],
		];
	}

}
?>
