<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/classes/meal.php';
require_once '../public/classes/roster.php';
require_once '../auto_assignments/scheduling.php';

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
			# BRUNCH_LAUNDRY,
		],
		'sunday' => [
			SUNDAY_HEAD_COOK,
			SUNDAY_ASST_COOK,
			SUNDAY_CLEANER,
		],
		'brunch' => [
			BRUNCH_HEAD_COOK,
			BRUNCH_ASST_COOK,
			BRUNCH_CLEANER,
			# BRUNCH_LAUNDRY,
		],
	];

	public function setUp() : void {
		$this->roster = new Roster();
		$this->schedule = new Schedule();
		$this->schedule->setRoster($this->roster);
		$this->meal = new SundayMeal($this->schedule, '04/25/2018', 10);
	}

	/**
	 * Create a Worker mock with predictable scoring behavior.
	 */
	private function createTestWorker(
		array $avoids = [],
		array $prefers = [],
		$dateScore = 0,
		$adjacencyScore = 0,
		$fullyAssigned = false
	): Worker {
		$worker = $this->createMock(Worker::class);

		$worker->method('getAvoids')->willReturn($avoids);
		$worker->method('getPrefers')->willReturn($prefers);
		$worker->method('getDateScore')->willReturn($dateScore);
		$worker->method('getAdjancencyScore')->willReturn($adjacencyScore);
		$worker->method('isFullyAssigned')->willReturn($fullyAssigned);

		return $worker;
	}


	public function getShiftsForType($shift_type) {
		$shifts = [];
		switch($shift_type) {
			case 'meeting':
				$shifts = $this->shifts['meeting'];
				break;
			case 'weekday':
				$shifts = $this->shifts['weekday'];
				break;
			case 'sunday':
				$shifts = $this->shifts['sunday'];
				break;
		}

		return $shifts;
	}

	public function testConstructors() {
		$sunday = new SundayMeal('foo', '04/22/2018', 10);
		$this->assertInstanceOf('SundayMeal', $sunday);

		$weekday = new WeekdayMeal('foo', '04/23/2018', 10);
		$this->assertInstanceOf('WeekdayMeal', $weekday);

		$mtg = new MeetingNightMeal('foo', '04/25/2018', 10);
		$this->assertInstanceOf('MeetingNightMeal', $mtg);
	}

	#[DataProvider('pointFactorsProvider')]
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

	public static function pointFactorsProvider(): array {
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

	#[DataProvider('datesProvider')]
	public function testDayOfWeek($date, $expected) {
		$this->meal->setDate($date);
		$this->assertEquals($expected, $this->meal->getDayOfWeek());
	}

	public static function datesProvider(): array {
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

	#[DataProvider('shiftsProvider')]
	public function testAddShifts($date, $shift_type, $expected) {
		$this->meal->setDate($date);

		$shifts = $this->getShiftsForType($shift_type);
		$this->meal->initShifts($shifts);
		$assigned = $this->meal->getAssigned();
		$this->assertEquals($expected, $assigned,
			print_r(['expected' => $expected, 'assigned' => $assigned], TRUE));
	}

	public static function shiftsProvider(): array {
		return [
			[
				'12/11/2013',
				'fake',
				[]
			],
			[
				'04/16/2018',
				'meeting',
				[
					MEETING_NIGHT_CLEANER => [NULL],
					MEETING_NIGHT_ORDERER => [NULL],
				],
			],
			[
				'04/17/2018',
				'weekday',
				[
					WEEKDAY_HEAD_COOK => [NULL],
					WEEKDAY_ASST_COOK => [NULL, NULL],
					WEEKDAY_CLEANER => [NULL, NULL, NULL],
					# WEEKDAY_TABLE_SETTER => [NULL],
				],
			],
			[
				'04/22/2018',
				'sunday',
				[
					SUNDAY_HEAD_COOK => [NULL],
					SUNDAY_ASST_COOK => [NULL, NULL],
					SUNDAY_CLEANER => [NULL, NULL, NULL],
				],
			],
		];
	}

	#[DataProvider('provideGetNumOpenSpacesForShift')]
	public function testGetNumOpenSpacesForShift($date, $shift_type, $job_id, $expected) {
		$this->meal->setDate($date);

		$shifts = $this->getShiftsForType($shift_type);
		$this->meal->initShifts($shifts);
		$assigned = $this->meal->getNumOpenSpacesForShift($job_id);
		$this->assertEquals($expected, $assigned,
			print_r(['expected' => $expected, 'assigned' => $assigned], TRUE));
	}

	public static function provideGetNumOpenSpacesForShift(): array {
		return [
			['04/16/2018', 'meeting', MEETING_NIGHT_ORDERER, 1],
			['04/16/2018', 'meeting', MEETING_NIGHT_CLEANER, 1],

			['04/17/2018', 'weekday', WEEKDAY_HEAD_COOK, 1],
			['04/17/2018', 'weekday', WEEKDAY_ASST_COOK, 2],
			['04/17/2018', 'weekday', WEEKDAY_CLEANER, 3],
			# ['04/17/2018', 'weekday', WEEKDAY_TABLE_SETTER, 1],

			['04/22/2018', 'sunday', SUNDAY_HEAD_COOK, 1],
			['04/22/2018', 'sunday', SUNDAY_ASST_COOK, 2],
			['04/22/2018', 'sunday', SUNDAY_CLEANER, 3],
		];
	}

	public function testGetTimes() {
		$sunday = new SundayMeal($this->schedule, '04/22/2018', 123);
		$this->assertEquals($sunday->getTime(), '5:30');
		$this->assertEquals($sunday->getCommunities(), 'GO, SW, TS');

		$weekday = new WeekdayMeal($this->schedule, '04/23/2018', 124);
		$this->assertEquals($weekday->getTime(), '6:15');
		$this->assertEquals($weekday->getCommunities(), 'GO, SW, TS');

		$meeting = new MeetingNightMeal($this->schedule, '04/16/2018', 125);
		$this->assertEquals($meeting->getTime(), '5:45');
		$this->assertEquals($meeting->getCommunities(), 'GO');

		$brunch = new BrunchMeal($this->schedule, '11/11/2023', 126);
		$this->assertEquals($brunch->getTime(), '10:30');
		$this->assertEquals($brunch->getCommunities(), 'GO, SW, TS');
	}

	#[DataProvider('provideAddWorkerPref')]
	public function testAddWorkerPref($username, $type, $job_id, $pref, $expected) {
		$this->meal->initShifts($this->shifts[$type]);
		$this->meal->addWorkerPref($username, $job_id, $pref);
		$workers = $this->meal->getPossibleWorkers();
		$this->assertEquals($workers, $expected);
	}

	public static function provideAddWorkerPref(): array {
		return [
			['aaa', 'sunday', SUNDAY_HEAD_COOK, 1, [SUNDAY_HEAD_COOK => ['aaa' => 1]]],
			['bbb', 'weekday', WEEKDAY_HEAD_COOK, 0, [WEEKDAY_HEAD_COOK => ['bbb' => 0]]],
			['ccc', 'meeting', MEETING_NIGHT_ORDERER, 0, [MEETING_NIGHT_ORDERER => ['ccc' => 0]]],
			['ddd', 'brunch', BRUNCH_CLEANER, 0, [BRUNCH_CLEANER => ['ddd' => 0]]],
		];
	}

	public function testHasOpenShiftsInitiallyTrue()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->assertTrue($this->meal->hasOpenShifts(SUNDAY_HEAD_COOK));
	}

	public function testHasOpenShiftsFalseWhenFilled()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->setAssignment(SUNDAY_HEAD_COOK, 0, 'alice');
		$this->assertFalse($this->meal->hasOpenShifts(SUNDAY_HEAD_COOK));
	}

	public function testGetAssignedWorkerNamesByJobId()
	{
		$this->meal->initShifts($this->shifts['sunday']);
		$this->meal->setAssignment(SUNDAY_HEAD_COOK, 0, 'head');
		$this->meal->setAssignment(SUNDAY_ASST_COOK, 0, 'assistant');
		$names = $this->meal->getAssignedWorkerNamesByJobId(SUNDAY_HEAD_COOK);
		$expected = [
			'head' => 1,
			'assistant' => 1,
		];
		$this->assertEquals($expected, $names);
	}

	public function testCleanerGrouping()
	{
		$this->meal->initShifts($this->shifts['sunday']);
		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');
		$this->meal->setAssignment(SUNDAY_CLEANER, 1, 'bob');
		$names = $this->meal->getAssignedWorkerNamesByJobId(SUNDAY_CLEANER);
		$expected = [
			'alice' => 1,
			'bob' => 1,
		];
		$this->assertEquals($expected, $names);
	}

	public function testGetNumPossibleWorkerRatio()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->addWorkerPref('a', SUNDAY_HEAD_COOK, 1);
		$this->meal->addWorkerPref('b', SUNDAY_HEAD_COOK, 1);
		$this->meal->addWorkerPref('c', SUNDAY_HEAD_COOK, 1);
		$ratio = $this->meal->getJobPopularity(SUNDAY_HEAD_COOK);
		$this->assertEquals(3.0, $ratio);
	}

	public function testGetNumPlaceholders() {
		$this->meal->setAssignment(123, 'abc', PLACEHOLDER);
		$this->meal->setAssignment(456, 'def', PLACEHOLDER);
		$this->meal->setAssignment(789, 'def', 'somebody');
		$num = $this->meal->getNumPlaceholders();
		$this->assertEquals($num, 2);
	}

	public function testGetNumPlaceholdersCountsNullAssignments()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->setAssignment(SUNDAY_HEAD_COOK, 0, PLACEHOLDER);
		$this->meal->setAssignment(123, 0, null);
		$this->assertEquals(2, $this->meal->getNumPlaceholders());
	}

	public function testCanAssignWorkerReturnsFalseWhenWorkerNotEligible()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->assertFalse($this->meal->canAssignWorker(SUNDAY_HEAD_COOK, 'alice'));
	}

	public function testCanAssignWorkerRejectsFullyAssignedWorker()
	{
		$worker = $this->createMock(Worker::class);
		$worker->method('isFullyAssigned')->willReturn(true);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturn($worker);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->addWorkerPref('alice', SUNDAY_HEAD_COOK, 1);

		$this->assertFalse(
			$this->meal->canAssignWorker(SUNDAY_HEAD_COOK, 'alice')
		);
	}

	public function testGetAvoidAndPreferWorkerListReturnsEmptyListsForWorkerWithoutPreferences()
	{
		$worker = $this->createMock(Worker::class);
		$worker->method('getAvoids')->willReturn([]);
		$worker->method('getPrefers')->willReturn([]);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturn($worker);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);
		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');

		$this->assertSame(
			['avoids' => [], 'prefers' => []],
			$this->meal->getAvoidAndPreferWorkerList(SUNDAY_CLEANER)
		);
	}

	public function testGetAvoidAndPreferWorkerListCombinesPreferences()
	{
		$alice = $this->createMock(Worker::class);
		$alice->method('getAvoids')->willReturn(['bob', 'carol']);
		$alice->method('getPrefers')->willReturn(['dave']);

		$bob = $this->createMock(Worker::class);
		$bob->method('getAvoids')->willReturn(['carol', 'eve']);
		$bob->method('getPrefers')->willReturn(['dave', 'frank']);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')->willReturnMap([
			['alice', $alice],
			['bob', $bob],
		]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');
		$this->meal->setAssignment(SUNDAY_CLEANER, 1, 'bob');

		$expected = [
			'avoids' => [
				'bob' => AVOID_PERSON,
				'carol' => AVOID_PERSON,
				'eve' => AVOID_PERSON,
			],
			'prefers' => [
				'dave' => PREFER_PERSON,
				'frank' => PREFER_PERSON,
			],
		];

		$this->assertSame(
			$expected,
			$this->meal->getAvoidAndPreferWorkerList(SUNDAY_CLEANER)
		);
	}

	public function testPickWorkerImmediatelyReturnsCandidateWithAvailabilityOfOne()
	{
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->addWorkerPref('alice', SUNDAY_HEAD_COOK, 1);
		$this->meal->addWorkerPref('bob', SUNDAY_HEAD_COOK, 1);

		$alice = $this->createMock(Worker::class);
		$alice->method('isFullyAssigned')->willReturn(false);

		$bob = $this->createMock(Worker::class);
		$bob->method('isFullyAssigned')->willReturn(false);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')->willReturnMap([
			['alice', $alice],
			['bob', $bob],
		]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_HEAD_COOK]);
		$this->meal->addWorkerPref('alice', SUNDAY_HEAD_COOK, 1);
		$this->meal->addWorkerPref('bob', SUNDAY_HEAD_COOK, 1);

		$this->assertSame(
			'alice',
			$this->meal->pickWorker(
				SUNDAY_HEAD_COOK,
				['alice' => 1, 'bob' => 2]
			)
		);
	}


	/**
	 * 1. No workers assigned means there are no social preferences to collect.
	 */
	public function testGetAvoidAndPreferWorkerListReturnsEmptyWhenNoWorkersAssigned()
	{
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->assertSame(
			[],
			$this->meal->getAvoidAndPreferWorkerList(SUNDAY_CLEANER)
		);
	}


	/**
	 * 2. Collect avoid/prefer preferences from one assigned worker.
	 */
	public function testGetAvoidAndPreferWorkerListCollectsSingleWorkersPreferences()
	{
		$alice = $this->createTestWorker(
			['bob'],
			['carol']
		);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);
		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');

		$this->assertSame(
			[
				'avoids' => [
					'bob' => AVOID_PERSON,
				],
				'prefers' => [
					'carol' => PREFER_PERSON,
				],
			],
			$this->meal->getAvoidAndPreferWorkerList(SUNDAY_CLEANER)
		);
	}


	/**
	 * 3. Combine preferences from multiple workers and deduplicate names.
	 */
	public function testGetAvoidAndPreferWorkerListCombinesMultipleWorkersPreferences()
	{
		$alice = $this->createTestWorker(
			['bob', 'carol'],
			['dave']
		);

		$bob = $this->createTestWorker(
			['carol', 'eve'],
			['dave', 'frank']
		);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
				['bob', $bob],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');
		$this->meal->setAssignment(SUNDAY_CLEANER, 1, 'bob');

		$this->assertSame(
			[
				'avoids' => [
					'bob' => AVOID_PERSON,
					'carol' => AVOID_PERSON,
					'eve' => AVOID_PERSON,
				],
				'prefers' => [
					'dave' => PREFER_PERSON,
					'frank' => PREFER_PERSON,
				],
			],
			$this->meal->getAvoidAndPreferWorkerList(SUNDAY_CLEANER)
		);
	}


	/**
	 * 4. No candidates means the shift cannot be filled automatically.
	 */
	public function testPickWorkerReturnsPlaceholderWhenNoCandidates()
	{
		$this->assertSame(
			PLACEHOLDER,
			$this->meal->pickWorker(SUNDAY_CLEANER, [])
		);
	}


	/**
	 * 5. A candidate who is not eligible must not be selected.
	 */
	public function testPickWorkerReturnsPlaceholderWhenCandidateIsNotEligible()
	{
		$this->meal->initShifts([SUNDAY_CLEANER]);

		// Alice is deliberately not added to the possible-worker list.
		$this->assertSame(
			PLACEHOLDER,
			$this->meal->pickWorker(
				SUNDAY_CLEANER,
				['alice' => 2]
			)
		);
	}


	/**
	 * 6. An eligible candidate with availability <= 1 is selected immediately.
	 */
	public function testPickWorkerImmediatelySelectsMandatoryCandidate()
	{
		$alice = $this->createTestWorker();
		$bob = $this->createTestWorker();

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
				['bob', $bob],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->meal->addWorkerPref('alice', SUNDAY_CLEANER, 1);
		$this->meal->addWorkerPref('bob', SUNDAY_CLEANER, 1);

		// Bob is mandatory even though he appears later in the list.
		$this->assertSame(
			'bob',
			$this->meal->pickWorker(
				SUNDAY_CLEANER,
				[
					'alice' => 2,
					'bob' => 1,
				]
			)
		);
	}


	/**
	 * 7. Candidates with date conflicts must be skipped.
	 */
	public function testPickWorkerReturnsPlaceholderWhenAllCandidatesHaveDateConflicts()
	{
		$alice = $this->createTestWorker(
			[],
			[],
			HAS_CONFLICT_PREF
		);

		$bob = $this->createTestWorker(
			[],
			[],
			HAS_CONFLICT_PREF
		);

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
				['bob', $bob],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->meal->addWorkerPref('alice', SUNDAY_CLEANER, 1);
		$this->meal->addWorkerPref('bob', SUNDAY_CLEANER, 1);

		// Both availability ratings exceed 1, so the mandatory-assignment
		// branch cannot bypass the date-conflict checks.
		$this->assertSame(
			PLACEHOLDER,
			$this->meal->pickWorker(
				SUNDAY_CLEANER,
				[
					'alice' => 2,
					'bob' => 3,
				]
			)
		);
	}


	/**
	 * 8a. An existing worker's avoid preference penalizes that candidate.
	 */
	public function testPickWorkerAvoidsCandidateDislikedByAssignedWorker()
	{
		$alice = $this->createTestWorker(['bob']);
		$bob = $this->createTestWorker();
		$carol = $this->createTestWorker();

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
				['bob', $bob],
				['carol', $carol],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		// Alice is already assigned to this meal.
		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');

		$this->meal->addWorkerPref('bob', SUNDAY_CLEANER, 1);
		$this->meal->addWorkerPref('carol', SUNDAY_CLEANER, 1);

		// Bob and Carol have otherwise identical scores.
		$this->assertSame(
			'carol',
			$this->meal->pickWorker(
				SUNDAY_CLEANER,
				[
					'bob' => 2,
					'carol' => 2,
				]
			)
		);
	}


	/**
	 * 8b. An existing worker's preference boosts the preferred candidate.
	 */
	public function testPickWorkerPrefersCandidateLikedByAssignedWorker()
	{
		$alice = $this->createTestWorker(
			[],
			['bob']
		);

		$bob = $this->createTestWorker();
		$carol = $this->createTestWorker();

		$this->schedule = $this->createMock(Schedule::class);
		$this->schedule->method('getWorker')
			->willReturnMap([
				['alice', $alice],
				['bob', $bob],
				['carol', $carol],
			]);

		$this->meal = new SundayMeal($this->schedule, '04/22/2018');
		$this->meal->initShifts([SUNDAY_CLEANER]);

		$this->meal->setAssignment(SUNDAY_CLEANER, 0, 'alice');

		$this->meal->addWorkerPref('bob', SUNDAY_CLEANER, 1);
		$this->meal->addWorkerPref('carol', SUNDAY_CLEANER, 1);

		// Bob receives the social-preference bonus.
		$this->assertSame(
			'bob',
			$this->meal->pickWorker(
				SUNDAY_CLEANER,
				[
					'bob' => 2,
					'carol' => 2,
				]
			)
		);
	}

}
?>
