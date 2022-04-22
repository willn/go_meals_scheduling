<?php
set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../auto_assignments/schedule.php';
require_once '../public/classes/calendar.php';
require_once '../public/classes/meal.php';
require_once '../public/classes/roster.php';

/**
 * Test the scheduling framework.
 * NOTE: for now, this doesn't do anything, since calling PDO breaks the tests.
 */
class ScheduleTest extends PHPUnit_Framework_TestCase {
	protected $schedule;

	public function setUp() {
		$this->schedule = new Schedule();
	}

	public function testConstruct() {
		$this->assertInstanceOf('Schedule', $this->schedule);
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
	 * @dataProvider provideAddNonResponderPrefs
	 */
	public function testAddNonResponderPrefs($dates_by_shift, $expected) {
		$this->schedule->initializeShifts($dates_by_shift);

		$roster = new Roster();
		$this->schedule->setRoster($roster);

		// -------- 1st test ---------
		$slackers = ['aaa', 'bbb', 'ccc', 'ddd', 'eee'];
		foreach($slackers as $username) {
			$worker = $roster->addWorker($username);
			$worker->addNumShiftsAssigned(SUNDAY_HEAD_COOK, 1);
			$worker->addNumShiftsAssigned(MEETING_NIGHT_ORDERER, 1);
			$worker->addNumShiftsAssigned(WEEKDAY_HEAD_COOK, 1);
		}
		$result = $this->schedule->addNonResponderPrefs($slackers);

		$num = count($slackers);
		$debug = [
			'result' => $result,
			'num' => $num,
			'slackers' => $slackers,
		];
		$this->assertEquals($result, $num, print_r($debug, TRUE));

		// -------- 2nd test ---------
		$assigned = $this->schedule->getAssigned();
		$debug = [
			'assigned' => $assigned,
			'expected' => $expected
		];
		$this->assertEquals($assigned, $expected, print_r($debug, TRUE));
	}

	public function provideAddNonResponderPrefs() {

		return [
			[
				['7/10/2022' => [SUNDAY_HEAD_COOK, SUNDAY_ASST_COOK, SUNDAY_CLEANER]], 
				[
					'7/10/2022' => [
						SUNDAY_HEAD_COOK => [0 => NULL],
						SUNDAY_ASST_COOK => [0 => NULL, 1 => NULL],
						SUNDAY_CLEANER => [0 => NULL, 1 => NULL, 2 => NULL]
					]
				],
			],

			[
				['10/17/2022' => [MEETING_NIGHT_ORDERER]],
				[
					'10/17/2022' => [
						MEETING_NIGHT_ORDERER => [0 => NULL],
					]
				],
			],

			[
				['10/26/2022' => [WEEKDAY_HEAD_COOK, WEEKDAY_ASST_COOK, WEEKDAY_CLEANER]],
				[
					'10/26/2022' => [
						WEEKDAY_HEAD_COOK => [0 => NULL],
						WEEKDAY_ASST_COOK => [0 => NULL, 1 => NULL],
						WEEKDAY_CLEANER => [0 => NULL, 1 => NULL, 2 => NULL]
					]
				],
			],
		];
	}

	/**
	 * @dataProvider provideFillMeal
	public function testFillMeal($worker_freedom) {
		$is_filled = $this->schedule->fillMeal($worker_freedom);
		$this->assertEquals($is_filled, TRUE);
	}

	public function provideFillMeal() {
		$freedom = [
			'aaa' => 3.0,
			'bbb' => 1.5,
			'ccc' => 1.0,
			'ddd' => .5,
			'eee' => .33,
		];

		return [
			[$freedom, 'yyy'],
		];
	}
	 */


	/**
	 * XXX dataProvider provideInitializeShifts
	public function testInitializeShifts($expected_shifts) {
		$this->schedule->initializeShifts();
		$num_meals = $this->schedule->getNumMeals();
		$this->assertEquals($num_meals, 100);

		$dates_by_shift = $this->schedule->getDatesByShift();
		ksort($dates_by_shift);
		$this->assertEquals($dates_by_shift, $expected_shifts);
	}

	public function provideInitializeShifts() {
		$shifts = [
			4382 => [
				0 => '5/1/2018',
				1 => '5/7/2018',
				2 => '5/8/2018',
				3 => '5/9/2018',
				4 => '5/14/2018',
				5 => '5/15/2018',
				6 => '5/16/2018',
				7 => '5/21/2018',
				8 => '5/22/2018',
				9 => '5/23/2018',
				10 => '5/29/2018',
				11 => '5/30/2018',
				12 => '6/4/2018',
				13 => '6/5/2018',
				14 => '6/11/2018',
				15 => '6/12/2018',
				16 => '6/13/2018',
				17 => '6/19/2018',
				18 => '6/20/2018',
				19 => '6/25/2018',
				20 => '6/26/2018',
				21 => '6/27/2018',
				22 => '7/2/2018',
				23 => '7/3/2018',
				24 => '7/9/2018',
				25 => '7/10/2018',
				26 => '7/11/2018',
				27 => '7/17/2018',
				28 => '7/18/2018',
				29 => '7/23/2018',
				30 => '7/24/2018',
				31 => '7/25/2018',
				32 => '7/30/2018',
				33 => '7/31/2018',
				34 => '8/6/2018',
				35 => '8/7/2018',
				36 => '8/8/2018',
				37 => '8/13/2018',
				38 => '8/14/2018',
				39 => '8/15/2018',
				40 => '8/21/2018',
				41 => '8/22/2018',
				42 => '8/27/2018',
				43 => '8/28/2018',
				44 => '8/29/2018',
				45 => '9/4/2018',
				46 => '9/10/2018',
				47 => '9/11/2018',
				48 => '9/12/2018',
				49 => '9/17/2018',
				50 => '9/18/2018',
				51 => '9/19/2018',
				52 => '9/24/2018',
				53 => '9/25/2018',
				54 => '9/26/2018',
				55 => '10/1/2018',
				56 => '10/2/2018',
				57 => '10/8/2018',
				58 => '10/9/2018',
				59 => '10/10/2018',
				60 => '10/16/2018',
				61 => '10/17/2018',
				62 => '10/22/2018',
				63 => '10/23/2018',
				64 => '10/24/2018',
				65 => '10/29/2018',
				66 => '10/30/2018',
			],
			4389 => [
				0 => '5/1/2018',
				1 => '5/7/2018',
				2 => '5/8/2018',
				3 => '5/9/2018',
				4 => '5/14/2018',
				5 => '5/15/2018',
				6 => '5/16/2018',
				7 => '5/21/2018',
				8 => '5/22/2018',
				9 => '5/23/2018',
				10 => '5/29/2018',
				11 => '5/30/2018',
				12 => '6/4/2018',
				13 => '6/5/2018',
				14 => '6/11/2018',
				15 => '6/12/2018',
				16 => '6/13/2018',
				17 => '6/19/2018',
				18 => '6/20/2018',
				19 => '6/25/2018',
				20 => '6/26/2018',
				21 => '6/27/2018',
				22 => '7/2/2018',
				23 => '7/3/2018',
				24 => '7/9/2018',
				25 => '7/10/2018',
				26 => '7/11/2018',
				27 => '7/17/2018',
				28 => '7/18/2018',
				29 => '7/23/2018',
				30 => '7/24/2018',
				31 => '7/25/2018',
				32 => '7/30/2018',
				33 => '7/31/2018',
				34 => '8/6/2018',
				35 => '8/7/2018',
				36 => '8/8/2018',
				37 => '8/13/2018',
				38 => '8/14/2018',
				39 => '8/15/2018',
				40 => '8/21/2018',
				41 => '8/22/2018',
				42 => '8/27/2018',
				43 => '8/28/2018',
				44 => '8/29/2018',
				45 => '9/4/2018',
				46 => '9/10/2018',
				47 => '9/11/2018',
				48 => '9/12/2018',
				49 => '9/17/2018',
				50 => '9/18/2018',
				51 => '9/19/2018',
				52 => '9/24/2018',
				53 => '9/25/2018',
				54 => '9/26/2018',
				55 => '10/1/2018',
				56 => '10/2/2018',
				57 => '10/8/2018',
				58 => '10/9/2018',
				59 => '10/10/2018',
				60 => '10/16/2018',
				61 => '10/17/2018',
				62 => '10/22/2018',
				63 => '10/23/2018',
				64 => '10/24/2018',
				65 => '10/29/2018',
				66 => '10/30/2018',
			],
			4390 => [
				0 => '5/1/2018',
				1 => '5/7/2018',
				2 => '5/8/2018',
				3 => '5/9/2018',
				4 => '5/14/2018',
				5 => '5/15/2018',
				6 => '5/16/2018',
				7 => '5/21/2018',
				8 => '5/22/2018',
				9 => '5/23/2018',
				10 => '5/29/2018',
				11 => '5/30/2018',
				12 => '6/4/2018',
				13 => '6/5/2018',
				14 => '6/11/2018',
				15 => '6/12/2018',
				16 => '6/13/2018',
				17 => '6/19/2018',
				18 => '6/20/2018',
				19 => '6/25/2018',
				20 => '6/26/2018',
				21 => '6/27/2018',
				22 => '7/2/2018',
				23 => '7/3/2018',
				24 => '7/9/2018',
				25 => '7/10/2018',
				26 => '7/11/2018',
				27 => '7/17/2018',
				28 => '7/18/2018',
				29 => '7/23/2018',
				30 => '7/24/2018',
				31 => '7/25/2018',
				32 => '7/30/2018',
				33 => '7/31/2018',
				34 => '8/6/2018',
				35 => '8/7/2018',
				36 => '8/8/2018',
				37 => '8/13/2018',
				38 => '8/14/2018',
				39 => '8/15/2018',
				40 => '8/21/2018',
				41 => '8/22/2018',
				42 => '8/27/2018',
				43 => '8/28/2018',
				44 => '8/29/2018',
				45 => '9/4/2018',
				46 => '9/10/2018',
				47 => '9/11/2018',
				48 => '9/12/2018',
				49 => '9/17/2018',
				50 => '9/18/2018',
				51 => '9/19/2018',
				52 => '9/24/2018',
				53 => '9/25/2018',
				54 => '9/26/2018',
				55 => '10/1/2018',
				56 => '10/2/2018',
				57 => '10/8/2018',
				58 => '10/9/2018',
				59 => '10/10/2018',
				60 => '10/16/2018',
				61 => '10/17/2018',
				62 => '10/22/2018',
				63 => '10/23/2018',
				64 => '10/24/2018',
				65 => '10/29/2018',
				66 => '10/30/2018',
			],
			4391 => [
				0 => '5/6/2018',
				1 => '5/13/2018',
				2 => '5/20/2018',
				3 => '6/3/2018',
				4 => '6/10/2018',
				5 => '6/17/2018',
				6 => '6/24/2018',
				7 => '7/1/2018',
				8 => '7/8/2018',
				9 => '7/15/2018',
				10 => '7/22/2018',
				11 => '7/29/2018',
				12 => '8/5/2018',
				13 => '8/12/2018',
				14 => '8/19/2018',
				15 => '8/26/2018',
				16 => '9/9/2018',
				17 => '9/16/2018',
				18 => '9/23/2018',
				19 => '9/30/2018',
				20 => '10/7/2018',
				21 => '10/14/2018',
				22 => '10/21/2018',
				23 => '10/28/2018',
			],
			4392 => [
				0 => '5/6/2018',
				1 => '5/13/2018',
				2 => '5/20/2018',
				3 => '6/3/2018',
				4 => '6/10/2018',
				5 => '6/17/2018',
				6 => '6/24/2018',
				7 => '7/1/2018',
				8 => '7/8/2018',
				9 => '7/15/2018',
				10 => '7/22/2018',
				11 => '7/29/2018',
				12 => '8/5/2018',
				13 => '8/12/2018',
				14 => '8/19/2018',
				15 => '8/26/2018',
				16 => '9/9/2018',
				17 => '9/16/2018',
				18 => '9/23/2018',
				19 => '9/30/2018',
				20 => '10/7/2018',
				21 => '10/14/2018',
				22 => '10/21/2018',
				23 => '10/28/2018',
			],
			4393 => [
				0 => '5/2/2018',
				1 => '6/6/2018',
				2 => '6/18/2018',
				3 => '7/16/2018',
				4 => '8/1/2018',
				5 => '8/20/2018',
				6 => '9/5/2018',
				7 => '10/3/2018',
				8 => '10/15/2018',
			],
			4394 => [
				0 => '5/1/2018',
				1 => '5/7/2018',
				2 => '5/8/2018',
				3 => '5/9/2018',
				4 => '5/14/2018',
				5 => '5/15/2018',
				6 => '5/16/2018',
				7 => '5/21/2018',
				8 => '5/22/2018',
				9 => '5/23/2018',
				10 => '5/29/2018',
				11 => '5/30/2018',
				12 => '6/4/2018',
				13 => '6/5/2018',
				14 => '6/11/2018',
				15 => '6/12/2018',
				16 => '6/13/2018',
				17 => '6/19/2018',
				18 => '6/20/2018',
				19 => '6/25/2018',
				20 => '6/26/2018',
				21 => '6/27/2018',
				22 => '7/2/2018',
				23 => '7/3/2018',
				24 => '7/9/2018',
				25 => '7/10/2018',
				26 => '7/11/2018',
				27 => '7/17/2018',
				28 => '7/18/2018',
				29 => '7/23/2018',
				30 => '7/24/2018',
				31 => '7/25/2018',
				32 => '7/30/2018',
				33 => '7/31/2018',
				34 => '8/6/2018',
				35 => '8/7/2018',
				36 => '8/8/2018',
				37 => '8/13/2018',
				38 => '8/14/2018',
				39 => '8/15/2018',
				40 => '8/21/2018',
				41 => '8/22/2018',
				42 => '8/27/2018',
				43 => '8/28/2018',
				44 => '8/29/2018',
				45 => '9/4/2018',
				46 => '9/10/2018',
				47 => '9/11/2018',
				48 => '9/12/2018',
				49 => '9/17/2018',
				50 => '9/18/2018',
				51 => '9/19/2018',
				52 => '9/24/2018',
				53 => '9/25/2018',
				54 => '9/26/2018',
				55 => '10/1/2018',
				56 => '10/2/2018',
				57 => '10/8/2018',
				58 => '10/9/2018',
				59 => '10/10/2018',
				60 => '10/16/2018',
				61 => '10/17/2018',
				62 => '10/22/2018',
				63 => '10/23/2018',
				64 => '10/24/2018',
				65 => '10/29/2018',
				66 => '10/30/2018',
			],
			4395 => [
				0 => '5/6/2018',
				1 => '5/13/2018',
				2 => '5/20/2018',
				3 => '6/3/2018',
				4 => '6/10/2018',
				5 => '6/17/2018',
				6 => '6/24/2018',
				7 => '7/1/2018',
				8 => '7/8/2018',
				9 => '7/15/2018',
				10 => '7/22/2018',
				11 => '7/29/2018',
				12 => '8/5/2018',
				13 => '8/12/2018',
				14 => '8/19/2018',
				15 => '8/26/2018',
				16 => '9/9/2018',
				17 => '9/16/2018',
				18 => '9/23/2018',
				19 => '9/30/2018',
				20 => '10/7/2018',
				21 => '10/14/2018',
				22 => '10/21/2018',
				23 => '10/28/2018',
			],
			4396 => [
				0 => '5/2/2018',
				1 => '6/6/2018',
				2 => '6/18/2018',
				3 => '7/16/2018',
				4 => '8/1/2018',
				5 => '8/20/2018',
				6 => '9/5/2018',
				7 => '10/3/2018',
				8 => '10/15/2018',
			],
		];

		return [
			[$shifts],
		];
	}
	 */

/*
	public function testGetDatesByShift() {
		$result = $this->schedule->getDatesByShift();
		// XXX this doesn't seem to be working right now...
	}

/*
	public function testAddPrefs() {
		$this->schedule->initializeShifts();
		$date = '10/15/2018';
		$this->schedule->addPrefs('testuser', 4396, $date, 1);
		$meals = $this->schedule->getMeals();


		$cur_meal = new WeekdayMeal($this->schedule, $date, 90);
		$debug = [
			'meals' => $meals,
			'date' => $date,
			// 'cur' => $cur_meal,
		];
		// $this->assertEquals($meals[$date], $cur_meal, print_r($debug, TRUE));
	}
*/

}
?>
