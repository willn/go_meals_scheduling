<?php
use PHPUnit\Framework\TestCase;

require_once '../public/classes/worker.php';
require_once '../public/season.php';
require_once '../public/globals.php';

class WorkerTest extends TestCase {
	private $worker;
	private $default_username;

	public function setUp() : void {
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

	/**
	 * Test getDateScore with no existing assignment.
	 * @dataProvider provideGetDateScoreWithNoExistingShifts
	 */
	public function testGetDateScoreWithNoExistingShifts($date, $job_id, $pref, $expected) {
		$this->worker->addAvailability(123, $date, $pref);
		$result = $this->worker->getDateScore($date, $job_id);
		$this->assertEquals($expected, $result);
	}

	public function provideGetDateScoreWithNoExistingShifts() {
		return [
			['4/3/2022', 123, 1, 1],
			['4/10/2022', 123, 2, 2],
		];
	}


	/**
	 * Test getDateScore when the worker already has a shift on that date.
	 *
	 * @dataProvider provideGetDateScore
	 */
	public function testGetDateScore($existing_job, $new_job, $pref_level, $requests, $expected) {

		$date = '10/18/2026';
		$this->worker->addNumShiftsAssigned($existing_job, 1);
		$this->assertTrue($this->worker->setAssignedShift($existing_job, $date));
		$this->worker->setRequests($requests);
		$this->worker->addAvailability($new_job, $date, $pref_level);

		$result = $this->worker->getDateScore($date, $new_job);
		$this->assertEquals($expected, $result);
	}


	public function provideGetDateScore() {
		return [

			'clean then cook' => [
				SUNDAY_CLEANER,
				SUNDAY_HEAD_COOK,
				1,
				[],
				OK_PREF,
			],

			'cook then clean' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_CLEANER,
				1,
				[],
				OK_PREF,
			],

			'cook then clean prefers both pref of 1' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_CLEANER,
				1,
				['clean_after_self' => 'yes'],
				CLEAN_AFTER_COOK_PREF,
			],
			'clean then cook prefers both pref of 1' => [
				SUNDAY_CLEANER,
				SUNDAY_HEAD_COOK,
				1,
				['clean_after_self' => 'yes'],
				CLEAN_AFTER_COOK_PREF,
			],

			'cook then clean prefers both pref of 2' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_CLEANER,
				2,
				['clean_after_self' => 'yes'],
				CLEAN_AFTER_COOK_PREF * 2,
			],
			'clean then cook prefers both pref of 2' => [
				SUNDAY_CLEANER,
				SUNDAY_HEAD_COOK,
				2,
				['clean_after_self' => 'yes'],
				CLEAN_AFTER_COOK_PREF * 2,
			],

			// The worker does not want to clean after cooking.
			'cook then clean, cleaning declined' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_CLEANER,
				1,
				['clean_after_self' => 'no'],
				HAS_CONFLICT_PREF,
			],
			'clean then cook, cleaning declined' => [
				SUNDAY_CLEANER,
				SUNDAY_HEAD_COOK,
				1,
				['clean_after_self' => 'no'],
				HAS_CONFLICT_PREF,
			],

			// Repeating the same type of shift creates a conflict by default.
			'cook then cook' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_ASST_COOK,
				1,
				[],
				HAS_CONFLICT_PREF,
			],
			'clean then clean' => [
				SUNDAY_CLEANER,
				SUNDAY_CLEANER,
				1,
				[],
				HAS_CONFLICT_PREF,
			],

			// Bundling allows repeated shifts of the same type.
			'bundle two cooking shifts' => [
				SUNDAY_HEAD_COOK,
				SUNDAY_ASST_COOK,
				1,
				['bundle_shifts' => TRUE],
				DO_BUNDLING_PREF,
			],
			'bundle two cleaning shifts' => [
				SUNDAY_CLEANER,
				SUNDAY_CLEANER,
				1,
				['bundle_shifts' => TRUE],
				DO_BUNDLING_PREF,
			],
		];
	}
}
?>
