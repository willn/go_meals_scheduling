<?php
require_once '../public/classes/worker.php';
require_once '../public/season.php';

class WorkerTest extends PHPUnit_Framework_TestCase {
	private $worker;
	private $default_username;

	public function setUp() {
		$this->worker = new Worker($this->default_username);
	}

	/**
	 * Test constructing a worker object.
	 */
	public function testConstruct() {
		$this->assertInstanceOf('Worker', $this->worker);
	}

	/**
	 * @dataProvider provideSetNames
	 */
	public function testSetNames($first, $last, $expected) {
		$this->worker->setNames($first, $last);
		$result = $this->worker->getName();
		$this->assertEquals($expected, $result);
	}

	public function provideSetNames() {
		return [
			['', '', $this->default_username],
			['Bruce', 'Lee', 'Bruce Lee'],
			['Madonna', '', 'Madonna '],
		];
	}

	/**
	 * Test set & get of ID
	 */
	public function testGetSetId() {
		$id = 123;
		$this->worker->setId($id);
		$this->assertEquals($this->worker->getId(), $id); 
	}

	/*
	public function testGetAllPreferences() {
		$result = $this->worker->getAllPreferences();
		$this->assertEquals('XXX', $result);
	}
	*/

	public function testAddNumShiftsAssigned() {
		$result = $this->worker->getNumShiftsToFill();
		$this->assertEquals([], $result);

		$this->worker->addNumShiftsAssigned(123, 3);
		$result = $this->worker->getNumShiftsToFill();
		$this->assertEquals([123 => 3], $result);
		$shifts = $this->worker->getAssignedShifts();
		$this->assertEquals([123], $shifts);

		$this->worker->addNumShiftsAssigned(123, 1);
		$result = $this->worker->getNumShiftsToFill();
		$this->assertEquals([123 => 4], $result);
		$shifts = $this->worker->getAssignedShifts();
		$this->assertEquals([123], $shifts);

		$this->worker->addNumShiftsAssigned(456, 2);
		$result = $this->worker->getNumShiftsToFill();
		$this->assertEquals([123 => 4, 456 => 2], $result);
		$shifts = $this->worker->getAssignedShifts();
		$this->assertEquals([123, 456], $shifts);
	}

	public function testAddAvailability() {
		$result = $this->worker->getAvailability();
		$this->assertEquals([], $result);

		$date1 = '3/18/2019';
		$this->worker->addAvailability(123, $date1, 1);
		$result = $this->worker->getAvailability();
		$this->assertEquals([123 => [$date1 => 1]], $result);

		$date2 = '3/19/2019';
		$this->worker->addAvailability(456, $date2, 2);
		$result = $this->worker->getAvailability();
		$this->assertEquals([123 => [$date1 => 1], 456 => [$date2 => 2]], $result);

		$this->worker->addAvailability(123, $date2, 2);
		$result = $this->worker->getAvailability();
		$this->assertEquals([123 => [$date1 => 1, $date2 => 2], 456 =>
			[$date2 => 2]], $result);

		$this->worker->addAvailability(999, $date2, .5);
		$result = $this->worker->getAvailability();
		$this->assertEquals([123 => [$date1 => 1, $date2 => 2], 456 =>
			[$date2 => 2], 999 => [$date2 => .5]], $result);

		$this->worker->addAvailability(999, $date1, 0);
		$result = $this->worker->getAvailability();
		$this->assertEquals([123 => [$date1 => 1, $date2 => 2], 456 =>
			[$date2 => 2], 999 => [$date1 => 0, $date2 => .5]], $result);
	}

	private function setUpGetAdjancencyScore() {
		$this->worker->addNumShiftsAssigned(SUNDAY_HEAD_COOK, 2);
		$this->worker->setAssignedShift(SUNDAY_HEAD_COOK, '4/2/2022');
	}

	/**
	 * @dataProvider provideGetAdjancencyScore
	 */
	public function testGetAdjancencyScore($date, $expected) {
		$this->setUpGetAdjancencyScore();
		$result = $this->worker->getAdjancencyScore($date);
		$this->assertEquals($result, $expected);
	}

	public function provideGetAdjancencyScore() {
		return [
			['3/24/2022', 0],
			['3/25/2022', Worker::ADJACENCY_LIMIT / 8],
			['3/26/2022', Worker::ADJACENCY_LIMIT / 7],
			['3/30/2022', Worker::ADJACENCY_LIMIT / 3],
			['3/31/2022', Worker::ADJACENCY_LIMIT / 2],
			['4/1/2022', Worker::ADJACENCY_LIMIT],
			['4/2/2022', 0],
			['4/3/2022', Worker::ADJACENCY_LIMIT],
			['4/4/2022', Worker::ADJACENCY_LIMIT / 2],
			['4/5/2022', Worker::ADJACENCY_LIMIT / 3],
			['4/9/2022', Worker::ADJACENCY_LIMIT / 7],
			['4/10/2022', Worker::ADJACENCY_LIMIT / 8],
			['4/11/2022', 0],
		];
	}
}
?>
