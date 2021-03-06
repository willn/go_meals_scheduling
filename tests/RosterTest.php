<?php
global $relative_dir;
$relative_dir = '../public/';

require_once '../public/season.php';
require_once '../public/classes/roster.php';
require_once '../public/classes/calendar.php';

class RosterTest extends PHPUnit_Framework_TestCase {
	private $roster;
	private $labor;

	public function setUp() {
		$this->loadLabor();
	}

	private function loadLabor() {
		$this->roster = new Roster();
		$this->roster->loadNumShiftsAssigned();
		$this->labor = $this->roster->getTotalLaborAvailable();
		ksort($this->labor);
	}

	/**
	 * @dataProvider provideLoadNumAssignmentsFromDatabase
	 */
	public function testLoadNumAssignmentsFromDatabase($expected) {
		$this->roster->initLaborCount();
		$this->roster->loadNumMealsFromDatabase();
		$db_labor = $this->roster->getTotalLaborAvailable();

		ksort($db_labor);
		ksort($expected);
		$this->assertEquals($db_labor, $expected);
	}

	public function provideLoadNumAssignmentsFromDatabase() {
		return [
			[
				[
					'all' => 0,
					WEEKDAY_TABLE_SETTER => 33,
					WEEKDAY_HEAD_COOK => 33.0,
					WEEKDAY_ASST_COOK => 63.0,
					SUNDAY_HEAD_COOK => 11.0,
					SUNDAY_ASST_COOK => 22.0,
					MEETING_NIGHT_ORDERER => 6.0,
					WEEKDAY_CLEANER => 81,
					SUNDAY_CLEANER => 33, 
					MEETING_NIGHT_CLEANER => 6,
				]
			]
		];
	}

	/**
	 * @dataProvider provideLoadNumAssignmentsFromOverrides
	 */
	public function testLoadNumAssignmentsFromOverrides($expected) {
		$this->roster->initLaborCount();
		$this->roster->loadNumMealsFromOverrides();
		$db_labor = $this->roster->getTotalLaborAvailable();

		ksort($db_labor);
		ksort($expected);
		$this->assertEquals($db_labor, $expected);
	}

	public function provideLoadNumAssignmentsFromOverrides() {
		return [
			[
				[
					'all' => 0,
					WEEKDAY_TABLE_SETTER => 0,
					WEEKDAY_HEAD_COOK => 0,
					WEEKDAY_ASST_COOK => 1,
					SUNDAY_HEAD_COOK => 0,
					SUNDAY_ASST_COOK => 0,
					MEETING_NIGHT_ORDERER => 0,
					WEEKDAY_CLEANER => 11,
					SUNDAY_CLEANER => 0, 
					MEETING_NIGHT_CLEANER => 0,
				]
			]
		];
	}

	/**
	 * This checks to see if any override users do not exist in the db already.
	 *
	 * NOTE: If this is true, then they need to be created manually, as seen
	 * in DatabaseInitializer->initializeExtraWorkers(). This
	 * should not happen at the beginning of a new season, since
	 * `utils/initialize_database.php` should have been run. This is more
	 * likely to happen at the beginning of a new sub-season in the middle
	 * of the work season, when new people move in and volunteer for jobs
	 * before they have been given a new work system account.
	 */
	public function testOverrideUsersExist() {
		$num_shift_overrides = array_keys(get_num_shift_overrides());

		$this->roster = new Roster();
		$this->roster->initLaborCount();
		$this->roster->loadNumMealsFromDatabase();
		$workers = array_keys($this->roster->getWorkers());

		$diff = array_diff($num_shift_overrides, $workers);
		$this->assertEquals([], $diff);
	}


	/**
	 * @dataProvider provideGetTotalLaborAvailable
	 * Note: this may need to be adjusted each season or sub-season.
	 */
	public function testGetTotalLaborAvailable($expected) {
		$this->loadLabor();
		$this->assertEquals($this->labor, $expected);
	}

	public function provideGetTotalLaborAvailable() {
		return [
			[
				[
					'all' => 0,
					WEEKDAY_TABLE_SETTER => 33,
					WEEKDAY_HEAD_COOK => 33.0,
					WEEKDAY_ASST_COOK => 64.0,
					SUNDAY_HEAD_COOK => 11.0,
					SUNDAY_ASST_COOK => 22.0,
					MEETING_NIGHT_ORDERER => 6.0,
					WEEKDAY_CLEANER => 92,
					SUNDAY_CLEANER => 33, 
					MEETING_NIGHT_CLEANER => 6.0,
				]
			]
		];
	}

	/**
	 * Note: this may need to be adjusted each season or sub-season.
	 * XXX This is checking for a consistency between the workers array and
	 * the roster array... how / why are those different?
	 */
	public function testRosterAndWorkerAssignmentsSynced() {
		// ----- get worker shifts to fill
		$shifts = $this->roster->getWorkerShiftsToFill();
		// complain if shifts is empty
		$this->assertGreaterThan(1, count($shifts));

		$summary = [];
		$debug = [];
		foreach($shifts as $worker => $assignments) {
			$debug['worker'] = $worker;
			$this->assertInternalType('string', $worker);
			$this->assertNotEmpty($assignments);

			foreach($assignments as $job_id => $assn_count) {
				$debug['job id'] = $job_id;
				$this->assertInternalType('int', $job_id);
				$debug['assn count'] = $assn_count;

				/*
				 * XXX This should be looking for non-zero values
				 * however, issue #16: https://github.com/willn/go_meals_scheduling/issues/16
				 * $this->assertGreaterThan(0, intval($assn_count), print_r($debug, TRUE));
				 */
				$this->assertGreaterThan(-1, intval($assn_count), print_r($debug, TRUE));

				// if empty, initialize
				if (!array_key_exists($job_id, $summary)) {
					$summary[$job_id] = 0;
				}
				$summary[$job_id] += $assn_count;
			}
		}

		unset($this->labor['all']);
		ksort($summary);
		$this->assertEquals($this->labor, $summary);
	}

	/**
	 * XXX Unfortunately, this doesn't do anything interesting until there
	 * are some available shifts data available... meaning that someone
	 * has filled out a survey.
	 */
	public function testSortAvailable() {
		$result = $this->roster->sortAvailable($input);
		$this->assertEquals([], $result);
	}

	/**
	 * @dataProvider provideGetNumShiftsPerSeason
	 * How many meals should each job get over the season?
	 * XXX I'm not sure if this is testing what we want it to...?
	 */
	public function testGetNumShiftsPerSeason($input, $expected) {
		$this->roster->setShifts($input);
		$results = $this->roster->getNumShiftsPerSeason();
		$debug = [
			'expected' => $expected,
			'input' => $input,
			'result' => $result,
		];
		$this->assertEquals($expected, $results, print_r($debug, TRUE));
	}

	public function provideGetNumShiftsPerSeason() {
		return [
			[WINTER, count(get_current_season_months(WINTER))],
			[SPRING, count(get_current_season_months(SPRING))],
			[SUMMER, count(get_current_season_months(SUMMER))],
			[FALL, count(get_current_season_months(FALL))],

			['nothing', count(get_current_season_months())],
			[NULL, count(get_current_season_months())],
		];
	}

	/**
	 * @dataProvider provideCompareLabor
	 *
	 * Confirm whether there is enough labor for the amount of shifts that need
	 * to be filled for the season.
	 */
	public function testCompareLabor($job_id, $assigned_labor, $need) {
		$all_jobs = get_all_jobs();
		$debug = func_get_args();
		$debug['job_name'] = $all_jobs[$job_id];
		$deficit = $need - $assigned_labor;

		// XXX display the job name here...
		$this->assertGreaterThanOrEqual($deficit, 0);
	}

	public function provideCompareLabor() {
		$all_jobs = get_all_jobs();

		// how much labor is available?
		$this->loadLabor();

		// how much labor is needed?
		$calendar = new Calendar();
		$num_shifts_needed = $calendar->getNumShiftsNeeded();
		ksort($num_shifts_needed);

		$counts = [];
		foreach($num_shifts_needed as $job_id => $need) {
			$assigned_labor = $this->labor[$job_id];
			$success = ($need >= $assigned_labor);

			// this is relevant as an ordered list, not an associative array
			$counts[] = [
				'job_id' => $job_id,
				'assigned' => $assigned_labor,
				'need' => $need,
				'diff' => ($need - $assigned_labor),
				'job_name' => $all_jobs[$job_id],
			];
		}
		return $counts;
	}
}
?>
