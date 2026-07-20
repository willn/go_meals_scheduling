<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../auto_assignments/';
require_once '../auto_assignments/assignments.php';

class AssignmentsTest extends TestCase {
	private $assignments;

	public function setUp() : void {
		$calendar = new Calendar();
		$roster = new Roster();
		$schedule = new Schedule();
		$this->assignments = new Assignments($calendar, $roster, $schedule);
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
}
?>
