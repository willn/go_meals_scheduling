<?php
require_once '../public/classes/worker.php';

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
		$this->assertInstanceOf(Worker, $this->worker);
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
}
?>
