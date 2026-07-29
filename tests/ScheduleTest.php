<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../auto_assignments/scheduling.php';
require_once '../public/classes/calendar.php';
require_once '../public/classes/meal.php';
require_once '../public/classes/roster.php';

/**
 * Test the scheduling framework.
 * NOTE: for now, this doesn't do anything, since calling PDO breaks the tests.
 */
class ScheduleTest extends TestCase {
	protected $schedule;

	public function setUp() : void {
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
		$this->assertEquals($this->schedule->getPopularity(), []);
	}

	public function jobIdsProvider() {
		return [
			[0],
			[1],
			[8],
		];
	}

	public function testPlaceholderCount() {
		$this->schedule->initPlaceholderCount(SUNDAY_HEAD_COOK);
		$this->assertEquals(0,
			$this->schedule->getPlaceholderCount(SUNDAY_HEAD_COOK));
	}

	public function testPlaceholderCountsIndependent() {
		$this->schedule->initPlaceholderCount(SUNDAY_HEAD_COOK);
		$this->schedule->initPlaceholderCount(BRUNCH_HEAD_COOK);

		$this->assertEquals(0,
			$this->schedule->getPlaceholderCount(SUNDAY_HEAD_COOK));
		$this->assertEquals(0,
			$this->schedule->getPlaceholderCount(BRUNCH_HEAD_COOK));
	}

	public function testGetColumnOrder() {
		$expected = [
			'date',
			'time',
			'communities',
			'head_cook',
			'asst1',
			'asst2',
			'cleaner1',
			'cleaner2',
			'cleaner3',
			'laundry',
		];
		$this->assertEquals($expected, $this->schedule->getColumnOrder());
	}

	public function testGetTabbedHeaders() {
		$expected =
			"date\ttime\tcommunities\thead_cook\tasst1\tasst2\tcleaner1\tcleaner2\tcleaner3\tlaundry\n";
		$this->assertEquals($expected, $this->schedule->getTabbedHeaders());
	}

	public function testGetGatherHeaders() {
		$expected =
			"Action,Date/time,Locations,Communities,Head Cook,Assistant Cook,Cleaner\n";
		$this->assertEquals($expected, $this->schedule->getGatherHeaders());
	}

	public function testGetNumMealsInitiallyZero() {
		$this->assertEquals(0, $this->schedule->getNumMeals());
	}

	public function testGetDatesByShift() {
		$dates = [
			'1/4/2026' => [SUNDAY_HEAD_COOK, SUNDAY_ASST_COOK],
			'1/11/2026' => [SUNDAY_HEAD_COOK],
			'1/25/2026' => [SUNDAY_CLEANER],
		];
		$this->schedule->initializeMealsAndShifts($dates);
		$expected = [
			SUNDAY_HEAD_COOK => ['1/4/2026', '1/11/2026'],
			SUNDAY_ASST_COOK => ['1/4/2026'],
			SUNDAY_CLEANER => ['1/25/2026'],
		];
		$this->assertEquals($expected, $this->schedule->getDatesByShift());
	}

	public function testLoadDatesByShiftCacheDuplicateJobs() {
		$this->schedule->initializeMealsAndShifts([
			'1/4/2026' => [SUNDAY_HEAD_COOK],
			'1/11/2026' => [SUNDAY_HEAD_COOK],
			'1/25/2026' => [SUNDAY_HEAD_COOK],
		]);
		$expected = [
			SUNDAY_HEAD_COOK => [
				'1/4/2026',
				'1/11/2026',
				'1/25/2026',
			],
		];
		$this->assertEquals($expected, $this->schedule->loadDatesByShiftCache());
	}

	public function testAddPrefsMissingMeal() {
		$result = $this->schedule->addWorkerAvailability('bob', SUNDAY_HEAD_COOK,
			'1/1/2099', 2);
		$this->assertFalse($result);
	}

	public function testGetMealsInitiallyEmpty() {
		$this->assertEquals([], $this->schedule->getMeals());
	}

	public function testGetAssignedInitiallyEmpty() {
		$this->assertEquals([], $this->schedule->getAssignments());
	}

	public function testGetAssignedAfterInitialization() {
		$this->schedule->initializeMealsAndShifts(
			['1/4/2026' => [SUNDAY_HEAD_COOK]]);
		$assigned = $this->schedule->getAssignments();
		$this->assertArrayHasKey('1/4/2026', $assigned);
	}

	public function testGetWorker() {
		$roster = new Roster();
		$this->schedule->setRoster($roster);
		$worker = $roster->addWorker('fred');
		$this->assertSame($worker, $this->schedule->getWorker('fred'));
	}

	public function provideAddNonResponderPrefs() {
		return [
			[
				['8/22/2026' => [BRUNCH_HEAD_COOK, BRUNCH_ASST_COOK, BRUNCH_CLEANER]], 
				[
					'8/22/2026' => [
						BRUNCH_HEAD_COOK => [0 => NULL],
						BRUNCH_ASST_COOK => [0 => NULL, 1 => NULL],
						BRUNCH_CLEANER => [0 => NULL, 1 => NULL, 2 => NULL],
					]
				],
			],

			[
				['7/10/2022' => [SUNDAY_HEAD_COOK, SUNDAY_ASST_COOK, SUNDAY_CLEANER]], 
				[
					'7/10/2022' => [
						SUNDAY_HEAD_COOK => [0 => NULL],
						SUNDAY_ASST_COOK => [0 => NULL, 1 => NULL],
						SUNDAY_CLEANER => [0 => NULL, 1 => NULL, 2 => NULL],
					]
				],
			],

/*
			[
				['10/17/2022' => [MEETING_NIGHT_ORDERER]],
				[
					'10/17/2022' => [
						MEETING_NIGHT_ORDERER => [0 => NULL],
					]
				],
			],
*/

			[
				['10/26/2022' => [WEEKDAY_HEAD_COOK, WEEKDAY_ASST_COOK, WEEKDAY_CLEANER]],
				[
					'10/26/2022' => [
						WEEKDAY_HEAD_COOK => [0 => NULL],
						WEEKDAY_ASST_COOK => [0 => NULL, 1 => NULL],
						WEEKDAY_CLEANER => [0 => NULL, 1 => NULL, 2 => NULL],
						# WEEKDAY_LAUNDRY => [0 => NULL],
					]
				],
			],
		];
	}
}
?>
