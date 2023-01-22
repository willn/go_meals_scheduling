<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/classes/meal.php';
require_once '../public/classes/roster.php';
require_once '../auto_assignments/schedule.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class MealTest extends TestCase {
	protected $meal;
	protected $schedule;
	protected $roster;

	protected $shifts = [
		'fake' => [100,200],
		'meeting' => [
			MEETING_NIGHT_CLEANER,
			MEETING_NIGHT_ORDERER,
		],
		'weekday' => [
			WEEKDAY_HEAD_COOK,
			WEEKDAY_ASST_COOK,
			WEEKDAY_CLEANER,
			# WEEKDAY_TABLE_SETTER,
		],
		'sunday' => [
			SUNDAY_HEAD_COOK,
			SUNDAY_ASST_COOK,
			SUNDAY_CLEANER,
		],
	];

	public function setUp() : void {
		$this->roster = new Roster();
		$this->schedule = new Schedule();
		$this->schedule->setRoster($this->roster);
		$this->meal = new SundayMeal($this->schedule, '04/25/2018', 10);
	}

	public function testConstructors() {
		$sunday = new SundayMeal('foo', '04/22/2018', 10);
		$this->assertInstanceOf('SundayMeal', $sunday);

		$weekday = new WeekdayMeal('foo', '04/23/2018', 10);
		$this->assertInstanceOf('WeekdayMeal', $weekday);

		$mtg = new MeetingNightMeal('foo', '04/25/2018', 10);
		$this->assertInstanceOf('MeetingNightMeal', $mtg);
	}

	/**
	 * @dataProvider pointFactorsProvider
	 */
	public function testSetPointFactors($hobart, $avoid_workers, $prefer) {
		$this->meal->setPointFactors($hobart, $avoid_workers);
		$expected = [
			'hobart' => !is_null($hobart) ? $hobart : DEFAULT_HOBART_SCORE,
			'avoid_workers' => !is_null($avoid_workers) ? $avoid_workers :
				DEFAULT_AVOID_WORKER_SCORE,
			'prefers' => !is_null($prefer) ? $prefer : DEFAULT_PREFERS_SCORE,
		];

		$this->assertEquals($this->meal->getPointFactors(), $expected);
	}

	public function pointFactorsProvider() {
		// hobart_factor, avoids_factor
		return [
			[NULL, NULL, NULL],
			[NULL, 2, 1.1],
			[1, 2, 1.1],
			[1, NULL, DEFAULT_PREFERS_SCORE],
			[0, 0, 0],
			[1, 1, .55],
			[1, 10, 5.5],
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
				$this->shifts['fake'],
				[]
			],
			[
				'04/16/2018',
				$this->shifts['meeting'],
				[
					MEETING_NIGHT_CLEANER => [NULL],
					MEETING_NIGHT_ORDERER => [NULL],
				],
			],
			[
				'04/17/2018',
				$this->shifts['weekday'],
				[
					WEEKDAY_HEAD_COOK => [NULL],
					WEEKDAY_ASST_COOK => [NULL, NULL],
					WEEKDAY_CLEANER => [NULL, NULL, NULL],
					# WEEKDAY_TABLE_SETTER => [NULL],
				],
			],
			[
				'04/22/2018',
				$this->shifts['sunday'],
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
			['04/16/2018', $this->shifts['meeting'], MEETING_NIGHT_ORDERER, 1],
			['04/16/2018', $this->shifts['meeting'], MEETING_NIGHT_CLEANER, 1],
			['04/17/2018', $this->shifts['weekday'], WEEKDAY_HEAD_COOK, 1],
			['04/17/2018', $this->shifts['weekday'], WEEKDAY_ASST_COOK, 2],
			['04/17/2018', $this->shifts['weekday'], WEEKDAY_CLEANER, 3],
			# ['04/17/2018', $this->shifts['weekday'], WEEKDAY_TABLE_SETTER, 1],
			['04/22/2018', $this->shifts['sunday'], SUNDAY_HEAD_COOK, 1],
			['04/22/2018', $this->shifts['sunday'], SUNDAY_ASST_COOK, 2],
			['04/22/2018', $this->shifts['sunday'], SUNDAY_CLEANER, 3],
		];
	}

	public function testGetTimes() {
		$sunday = new SundayMeal($this->schedule, '04/22/2018', 123);
		$this->assertEquals($sunday->getTime(), '5:30');
		$this->assertEquals($sunday->getCommunities(), 'GO');

		$weekday = new WeekdayMeal($this->schedule, '04/23/2018', 124);
		$this->assertEquals($weekday->getTime(), '6:15');
		$this->assertEquals($weekday->getCommunities(), 'GO');

		$meeting = new MeetingNightMeal($this->schedule, '04/16/2018', 125);
		$this->assertEquals($meeting->getTime(), '5:45');
		$this->assertEquals($meeting->getCommunities(), 'GO');
	}

	/**
	 * @dataProvider provideAddWorkerPref
	 */
	public function testAddWorkerPref($username, $type, $job_id, $pref, $expected) {
		$this->meal->initShifts($this->shifts[$type]);
		$this->meal->addWorkerPref($username, $job_id, $pref);
		$workers = $this->meal->getPossibleWorkers();
		$this->assertEquals($workers, $expected);
	}

	public function provideAddWorkerPref() {
		return [
			['aaa', 'sunday', SUNDAY_HEAD_COOK, 1, [SUNDAY_HEAD_COOK => ['aaa' => 1]]],
			['bbb', 'weekday', WEEKDAY_HEAD_COOK, 0, [WEEKDAY_HEAD_COOK => ['bbb' => 0]]],
			['ccc', 'meeting', MEETING_NIGHT_ORDERER, 0, [MEETING_NIGHT_ORDERER => ['ccc' => 0]]],
		];
	}

	/**
	 * @dataProvider providePickWorker
	 */
	public function testPickWorker($override, $type, $job_id,
		$worker_freedom, $expected) {

		$this->roster->loadNumMealsFromOverrides(NULL, $override);
		$this->roster->addPrefs('aaa', $job_id, $this->meal->getDate(), 1);
		$this->roster->addPrefs('bbb', $job_id, $this->meal->getDate(), 1);
		$this->roster->addPrefs('ccc', $job_id, $this->meal->getDate(), 1);

		$this->meal->initShifts($this->shifts[$type]);
		$this->meal->addWorkerPref('aaa', $job_id, 1);
		$this->meal->addWorkerPref('bbb', $job_id, 2);
		$this->meal->addWorkerPref('ccc', $job_id, 0);

		$user = $this->meal->pickWorker($job_id, $worker_freedom);
		$this->assertEquals($user, $expected);
	}

	public function providePickWorker() {
		$override = [
			'aaa' => [SUNDAY_HEAD_COOK => 1],
			'bbb' => [SUNDAY_HEAD_COOK => 1],
			'ccc' => [SUNDAY_HEAD_COOK => 1],
		];

		$freedom = [
			'aaa' => 3.5,
			'bbb' => 2,
			'ccc' => 2.3,
		];

		return [
			[$override, 'sunday', SUNDAY_HEAD_COOK, $freedom, 'bbb'],
		];
	}
}
?>
