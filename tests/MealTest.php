<?php
set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/classes/meal.php';
require_once '../auto_assignments/schedule.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class MealTest extends PHPUnit_Framework_TestCase {
	protected $meal;

	protected $shifts = [
		[100,200],
		[
			MEETING_NIGHT_CLEANER,
			MEETING_NIGHT_ORDERER,
		],
		[
			WEEKDAY_HEAD_COOK,
			WEEKDAY_ASST_COOK,
			WEEKDAY_CLEANER,
			WEEKDAY_TABLE_SETTER,
		],
		[
			SUNDAY_HEAD_COOK,
			SUNDAY_ASST_COOK,
			SUNDAY_CLEANER,
		],
	];

	public function setUp() {
		$this->meal = new SundayMeal('foo', '04/22/2018', 10);
	}

	public function testConstructors() {
		$sunday = new SundayMeal('foo', '04/22/2018', 10);
		$this->assertInstanceOf(SundayMeal, $sunday);

		$weekday = new WeekdayMeal('foo', '04/23/2018', 10);
		$this->assertInstanceOf(WeekdayMeal, $weekday);

		$mtg = new MeetingNightMeal('foo', '04/25/2018', 10);
		$this->assertInstanceOf(MeetingNightMeal, $mtg);
	}

	/**
	 * @dataProvider pointFactorsProvider
	 */
	public function testSetPointFactors($hobart, $avail, $avoid_workers, $prefer) {
		$this->meal->setPointFactors($hobart, $avail, $avoid_workers);
		$expected = [
			'hobart' => !is_null($hobart) ? $hobart : DEFAULT_HOBART_SCORE,
			'avail' => !is_null($avail) ? $avail : DEFAULT_AVAIL_SCORE,
			'avoid_workers' => !is_null($avoid_workers) ? $avoid_workers :
				DEFAULT_AVOID_WORKER_SCORE,
			'prefers' => !is_null($prefer) ? $prefer : DEFAULT_PREFERS_SCORE,
		];

		$this->assertEquals($this->meal->getPointFactors(), $expected);
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

	/**
	 * @dataProvider datesProvider
	 */
	public function testDayOfWeek($date, $expected) {
		$this->meal->setDate($date);
		$this->assertEquals($expected, $this->meal->getDayOfWeek());
	}

	public function datesProvider() {
		return [
			['Wed Dec 11 11:15:42 EST 2013', 3],
			['12/31/13', 2],
			['10/6/13', 7]
		];
	}

	/**
	 * Test the mutator for setting and getting a date.
	 */
	public function testSetAndGetDate() {
		$date = '5/6/2013';
		$this->meal->setDate($date);
		$this->assertEquals($date, $this->meal->getDate());
	}

	/**
	 * @dataProvider shiftsProvider
	 */
	public function testAddShifts($date, $shifts, $expected) {
		$this->meal->setDate($date);
		$this->meal->initShifts($shifts);
		$assigned = $this->meal->getAssigned();
		$this->assertEquals($expected, $assigned,
			print_r(['expected' => $expected, 'assigned' => $assigned], TRUE));
	}

	public function shiftsProvider() {
		return [
			[
				'12/11/2013',
				$this->shifts[0],
				[]
			],
			[
				'04/16/2018',
				$this->shifts[1],
				[
					MEETING_NIGHT_CLEANER => [NULL],
					MEETING_NIGHT_ORDERER => [NULL],
				],
			],
			[
				'04/17/2018',
				$this->shifts[2],
				[
					WEEKDAY_HEAD_COOK => [NULL],
					WEEKDAY_ASST_COOK => [NULL, NULL],
					WEEKDAY_CLEANER => [NULL, NULL, NULL],
					WEEKDAY_TABLE_SETTER => [NULL],
				],
			],
			[
				'04/22/2018',
				$this->shifts[3],
				[
					SUNDAY_HEAD_COOK => [NULL],
					SUNDAY_ASST_COOK => [NULL, NULL],
					SUNDAY_CLEANER => [NULL, NULL, NULL],
				],
			],
		];
	}

	/**
	 * @dataProvider provideGetNumOpenSpacesForShift
	 */
	public function testGetNumOpenSpacesForShift($date, $shifts, $job_id, $expected) {
		$this->meal->setDate($date);
		$this->meal->initShifts($shifts);
		$assigned = $this->meal->getNumOpenSpacesForShift($job_id);
		$this->assertEquals($expected, $assigned,
			print_r(['expected' => $expected, 'assigned' => $assigned], TRUE));
	}

	public function provideGetNumOpenSpacesForShift() {
		return [
			['04/16/2018', $this->shifts[1], MEETING_NIGHT_ORDERER, 1],
			['04/16/2018', $this->shifts[1], MEETING_NIGHT_CLEANER, 1],
			['04/17/2018', $this->shifts[2], WEEKDAY_HEAD_COOK, 1],
			['04/17/2018', $this->shifts[2], WEEKDAY_ASST_COOK, 2],
			['04/17/2018', $this->shifts[2], WEEKDAY_CLEANER, 3],
			['04/17/2018', $this->shifts[2], WEEKDAY_TABLE_SETTER, 1],
			['04/22/2018', $this->shifts[3], SUNDAY_HEAD_COOK, 1],
			['04/22/2018', $this->shifts[3], SUNDAY_ASST_COOK, 2],
			['04/22/2018', $this->shifts[3], SUNDAY_CLEANER, 3],
		];
	}

	public function testGetTimes() {
		$schedule = new Schedule();

		$sunday = new SundayMeal($schedule, '04/22/2018', 123);
		$this->assertEquals($sunday->getTime(), '5:30');
		$this->assertEquals($sunday->getCommunities(), 'GO, SW, TS');

		$weekday = new WeekdayMeal($schedule, '04/23/2018', 124);
		$this->assertEquals($weekday->getTime(), '6:15');
		$this->assertEquals($weekday->getCommunities(), 'GO, SW, TS');

		$meeting = new MeetingNightMeal($schedule, '04/16/2018', 125);
		$this->assertEquals($meeting->getTime(), '5:45');
		$this->assertEquals($meeting->getCommunities(), 'GO');
	}

	#public function testGetCommunities() { }
}
?>
