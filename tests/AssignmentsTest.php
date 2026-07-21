<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../auto_assignments/';
require_once '../auto_assignments/assignments.php';
require_once '../public/mysql_api.php';

class AssignmentsTest extends TestCase {
	private $assignments;

	private $calendar;
	private $roster;
	private $schedule;

	public function setUp() : void {
		$this->calendar = new Calendar();
		$this->roster = new Roster();
		$this->schedule = new Schedule();
		$this->assignments = new Assignments($this->calendar,
			$this->roster, $this->schedule);
	}

	public function testConstruct() {
		$this->assertInstanceOf('Assignments', $this->assignments);
	}

	public function testInitializeCreatesCalendar() {
		$this->assignments->initialize(['January', 'February', 'March']);
		$this->assertInstanceOf(Calendar::class,
			$this->assignments->calendar);
	}

	public function testInitializeCreatesRosterAndScheduleLinks() {
		$this->assignments->initialize();
		$this->assertInstanceOf(Roster::class,
			$this->assignments->roster);
		$this->assertInstanceOf(Schedule::class,
			$this->assignments->schedule);
	}

	public function testConstructorStoresInjectedObjects() {
		$calendar = $this->createMock(Calendar::class);
		$roster = $this->createMock(Roster::class);
		$schedule = $this->createMock(Schedule::class);

		$assignments = new Assignments($calendar, $roster, $schedule);

		$this->assertSame($calendar, $assignments->calendar);
		$this->assertSame($roster, $assignments->roster);
		$this->assertSame($schedule, $assignments->schedule);
	}

	public function testRunLoadsEverything() {
		$dates = ['2026-01-01'];

		$calendar = $this->createMock(Calendar::class);
		$calendar->expects($this->once())->method('disableWebDisplay');
		$calendar->expects($this->once())
			->method('evalDates')
			->willReturn($dates);

		$schedule = $this->createMock(Schedule::class);
		$schedule->expects($this->once())
			->method('initializeMealsAndShifts')
			->with($dates);

		$roster = $this->createMock(Roster::class);
		$roster->expects($this->once())->method('loadNumShiftsAssigned');
		$roster->expects($this->once())->method('loadRequests');

		$assignments = $this->getMockBuilder(Assignments::class)
			->setConstructorArgs([$calendar,$roster,$schedule])
			->onlyMethods(['loadPrefs'])
			->getMock();
		$assignments->expects($this->once())->method('loadPrefs');
		$assignments->run();
	}


	public function testGetNumPlaceholdersInitiallyZero() {
		$this->assignments->initialize();
		$this->assertEquals(0, $this->assignments->getNumPlaceholders());
	}

	public function testFindCancelCountsNoShortage() {
		$shifts_needed = [WEEKDAY_HEAD_COOK => 20];
		$labor_available = [WEEKDAY_HEAD_COOK => 20];
		$result = $this->assignments->findCancelCounts($shifts_needed,
			$labor_available);
		$this->assertEquals([], array_filter($result));
	}

	public function testFindCancelCountsMissingLaborEntry() {
		$shifts_needed = [WEEKDAY_HEAD_COOK => 20];
		$labor_available = [];

		$result = $this->assignments->findCancelCounts($shifts_needed,
			$labor_available);
		$this->assertEquals([], $result);
	}

	public function testFindCancelCountsNoJobs() {
		$result = $this->assignments->findCancelCounts([], []);
		$this->assertEquals([], $result);
	}

	public function testFindCancelCountsExcessLabor() {
		$shifts_needed = [WEEKDAY_HEAD_COOK => 10];
		$labor_available = [WEEKDAY_HEAD_COOK => 100];
		$result = $this->assignments->findCancelCounts($shifts_needed,
			$labor_available);

		foreach ($result as $cancelled) {
			$this->assertEquals(0, $cancelled);
		}
	}

	public function testInitializeCanBeCalledTwice() {
		$this->assignments->initialize();
		$this->assignments->initialize();

		$this->assertInstanceOf(Calendar::class, $this->assignments->calendar);
		$this->assertInstanceOf(Schedule::class, $this->assignments->schedule);
		$this->assertInstanceOf(Roster::class, $this->assignments->roster);
	}

	public function testInitNonResponderPrefsSortsNames() {
		$expected = ['adam', 'mary', 'zebra'];

		$schedule = $this->createMock(Schedule::class);
		$schedule->expects($this->once())
			->method('addNonResponderPrefs')
			->with($expected);

		$roster = $this->createMock(Roster::class);
		$roster->expects($this->once())
			->method('addNonResponderPrefs')
			->with($expected);

		$calendar = $this->createMock(Calendar::class);

		$assignments = new Assignments($calendar,$roster,$schedule);
		$assignments->initNonResponderPrefs(['mary', 'zebra', 'adam']);
	}

	public function testOutputCsv() {
		$schedule = $this->createMock(Schedule::class);

		$schedule->expects($this->once())
			->method('printResults')
			->with('csv');

		$assignments = new Assignments(
			$this->createMock(Calendar::class),
			$this->createMock(Roster::class),
			$schedule
		);

		$assignments->outputCSV();
	}

	public function testOutputSqlInserts() {
		$schedule = $this->createMock(Schedule::class);

		$schedule->expects($this->once())
			->method('printResults')
			->with('sql');

		$assignments = new Assignments(
			$this->createMock(Calendar::class),
			$this->createMock(Roster::class),
			$schedule
		);

		$assignments->outputSqlInserts();
	}

	public function testOutputGatherImports() {
		$schedule = $this->createMock(Schedule::class);

		$schedule->expects($this->once())
			->method('printResults')
			->with('gather_csv');

		$assignments = new Assignments(
			$this->createMock(Calendar::class),
			$this->createMock(Roster::class),
			$schedule
		);

		$assignments->outputGatherImports();
	}

	public function testFindCancelCountsExactLabor() {
		$needed = [WEEKDAY_HEAD_COOK => 20];
		$available = [WEEKDAY_HEAD_COOK => 20];
		$result = $this->assignments->findCancelCounts($needed, $available);
		$this->assertEquals(0, array_sum($result));
	}

	public function testFindCancelCountsMissingLabor() {
		$result = $this->assignments->findCancelCounts(
			[WEEKDAY_HEAD_COOK => 20], []);
		$this->assertEquals([], $result);
	}

	public function testFindCancelCountsEmptyInputs() {
		$this->assertEquals([],
			$this->assignments->findCancelCounts([], []));
	}

	public function testFindCancelCountsLaborSurplus() {
		$needed = [WEEKDAY_HEAD_COOK => 10];
		$available = [WEEKDAY_HEAD_COOK => 100];

		$result = $this->assignments->findCancelCounts($needed, $available);
		foreach ($result as $count) {
			$this->assertEquals(0, $count);
		}
	}

	public function testAssignJobTypeInitializesObjects()
	{
		$calendar = $this->createMock(Calendar::class);
		$schedule = $this->createMock(Schedule::class);
		$schedule->expects($this->once())
			->method('setJobId')
			->with(WEEKDAY_HEAD_COOK);
		$schedule->expects($this->once())
			->method('initPlaceholderCount');
		$schedule->expects($this->once())
			->method('rankMealsByDifficulty');
		$schedule->expects($this->once())
			->method('isFinished')
			->willReturn(true);

		$roster = $this->createMock(Roster::class);
		$roster->expects($this->once())
			->method('setJobId')
			->with(WEEKDAY_HEAD_COOK);
		$roster->expects($this->never())->method('sortAvailable');

		$assignments = new Assignments($calendar, $roster, $schedule);
		$assignments->assignJobType(WEEKDAY_HEAD_COOK);
	}

	public function testFindCancelCountsUsesFloorForMealCapacity() {
		$needed = [WEEKDAY_HEAD_COOK => 12]; // 4 meals
		$available = [WEEKDAY_HEAD_COOK => 11]; // supports only 3 meals
		$result = $this->assignments->findCancelCounts($needed, $available);
		$this->assertEquals(1, array_sum($result));
	}
}
?>
