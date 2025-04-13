<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';

require_once '../public/season.php';
require_once '../public/classes/roster.php';
require_once '../public/classes/calendar.php';
require_once '../auto_assignments/schedule.php';
require_once 'testing_utils.php';

class RosterTest extends TestCase {
	private $roster;
	private $total_labor_avail;

	public function setUp() : void {
		$this->loadLabor();
	}

	private function loadLabor() {
		$this->roster = new Roster();
		$this->roster->loadNumShiftsAssigned();
		$this->total_labor_available = $this->roster->getTotalLaborAvailable();
		ksort($this->total_labor_available);
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
		$this->assertEquals($expected, $db_labor);
	}

	public function provideLoadNumAssignmentsFromDatabase() {
		$out = [
			[
				[
					// UPDATE-EACH-SEASON
					'all' => 0,
					MEETING_NIGHT_CLEANER => 0,
					MEETING_NIGHT_ORDERER => 0,

					SUNDAY_ASST_COOK => 22,
					SUNDAY_CLEANER => 33, 
					SUNDAY_HEAD_COOK => 11,

					WEEKDAY_ASST_COOK => 61,
					WEEKDAY_CLEANER => 93,
					WEEKDAY_HEAD_COOK => 31,
					# WEEKDAY_LAUNDRY => 0,

					BRUNCH_ASST_COOK => 6,
					BRUNCH_CLEANER => 9, 
					BRUNCH_HEAD_COOK => 3,
					# BRUNCH_LAUNDRY => 0,
				]
			]
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[0][0]['WEEKDAY_TABLE_SETTER'] = 0;
		}
		return $out;
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
		$this->assertEquals($expected, $db_labor);
	}

	public function provideLoadNumAssignmentsFromOverrides() {
		$out = [
			[
				[
					'all' => 0,
					// UPDATE-EACH-SEASON
					MEETING_NIGHT_CLEANER => 0,
					MEETING_NIGHT_ORDERER => 0,

					SUNDAY_ASST_COOK => 0,
					SUNDAY_CLEANER => 0, 
					SUNDAY_HEAD_COOK => 0,

					WEEKDAY_ASST_COOK => 0,
					WEEKDAY_CLEANER => 0,
					WEEKDAY_HEAD_COOK => 0,
					# BRUNCH_LAUNDRY => 0,

					BRUNCH_ASST_COOK => 0,
					BRUNCH_CLEANER => 0,
					BRUNCH_HEAD_COOK => 0,
					# WEEKDAY_LAUNDRY => 0,
				]
			]
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[0][0]['WEEKDAY_TABLE_SETTER'] = 0;
		}
		return $out;
	}

	/**
	 * @dataProvider provideTestOverrideUsersExist
	 *
	 * This checks to see if any override users do not exist in the db already.
	 *
	 * NOTE: If this is true, then they need to be created manually, as seen
	 * in DatabaseInitializer->initializeExtraWorkers(). This
	 * should not happen at the beginning of a new season, since
	 * `utils/initialize_database.php` should have been run. This is more
	 * likely to happen at the beginning of a new sub-season in the middle
	 * of the work season, when new people move in and volunteer for jobs
	 * before they have been given a new work system account.
	 *
	 * Fix this by adding an entry in the provider.
	 */
	public function testOverrideUsersExist($list) {
		$num_shift_overrides = array_keys(get_num_shift_overrides());

		$this->roster = new Roster();
		$this->roster->initLaborCount();
		# load the workers formally assigned labor in the work system
		$this->roster->loadNumMealsFromDatabase();

		# get the list of all workers registered
		$workers = array_keys($this->roster->getWorkers());

		$diff = array_diff($num_shift_overrides, $workers);
		$this->assertEquals($list, $diff);
	}

	public function provideTestOverrideUsersExist() {
		return [
			[
				/**
				 * List override-only users who don't
				 * have a formal work assignment.
				 */
				[
				]
			]
		];
	}

	/**
	 * @dataProvider provideGetTotalLaborAvailable
	 * Note: this may need to be adjusted each season or sub-season.
	 */
	public function testGetTotalLaborAvailable($expected) {
		$this->loadLabor();
		$result = $this->total_labor_available;
		# write_out_data(__METHOD__, $result);
		$this->assertEquals($expected, $result);
	}

	public function provideGetTotalLaborAvailable() {
		$out = [
			[
				[
					// UPDATE-EACH-SEASON
					'all' => 0,
					MEETING_NIGHT_CLEANER => 0,
					MEETING_NIGHT_ORDERER => 0,

					SUNDAY_ASST_COOK => 22,
					SUNDAY_CLEANER => 33, 
					SUNDAY_HEAD_COOK => 11,

					WEEKDAY_ASST_COOK => 61,
					WEEKDAY_CLEANER => 93,
					WEEKDAY_HEAD_COOK => 31,
					# WEEKDAY_LAUNDRY => 0,

					BRUNCH_ASST_COOK => 6,
					BRUNCH_CLEANER => 9, 
					BRUNCH_HEAD_COOK => 3,
					# BRUNCH_LAUNDRY => 0,
				]
			]
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[0][0]['WEEKDAY_TABLE_SETTER'] = 34;
		}
		return $out;
	}

	/**
	 * XXX This is checking for a consistency between the workers array and
	 * the roster array... how / why are those different?
	 */
	public function testRosterAndWorkerAssignmentsSynced() {
		// ----- get worker shifts to fill
		$shifts = $this->roster->getWorkerShiftsToFill();
		// complain if shifts is empty
		$this->assertGreaterThan(1, count($shifts));

		$summary = [];
		$all_jobs = get_all_jobs();
		// initialize all jobs to zeroes
		foreach(array_keys($all_jobs) as $job_id) {
			$summary[$job_id] = 0;
		}

		$debug = [];
		foreach($shifts as $worker => $assignments) {
			$debug['worker'] = $worker;
			$this->assertIsString($worker);
			$this->assertNotEmpty($assignments);

			foreach($assignments as $job_id => $assn_count) {
				$debug['job id'] = $job_id;
				$this->assertIsInt($job_id);
				$debug['assn count'] = $assn_count;

				/*
				 * XXX This should be looking for non-zero values
				 * however, issue #16: https://github.com/willn/go_meals_scheduling/issues/16
				 * $this->assertGreaterThan(0, intval($assn_count), print_r($debug, TRUE));
				 */
				$this->assertGreaterThan(-1, intval($assn_count), print_r($debug, TRUE));
				$summary[$job_id] += $assn_count;
			}
		}

		ksort($summary);
		$this->assertEquals($this->total_labor_available, $summary);
	}

	/**
	 * XXX Unfortunately, this doesn't do anything interesting until there
	 * are some available shifts data available... meaning that someone
	 * has filled out a survey.
	 */
	public function testSortAvailable() {
		$result = $this->roster->sortAvailable();
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
			'results' => $results,
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
	 *
	 * NOTE: This is where we'll find out if we're short on labor for
	 * the upcoming season. This is how we'll figure out how many meals
	 * to cancel due to labor shortages.
	public function testCompareLabor($job_id, $assigned_labor, $need, $diff, $job_name) {
		$all_jobs = get_all_jobs();

		$debug = [
			'job_id' => $job_id,
			'assigned_labor' => $assigned_labor,
			'need' => $need,
			'diff' => $diff,
			'job_name' => $job_name,
		];
		$this->assertGreaterThanOrEqual($diff, 0, print_r($debug, TRUE));
	}
	 */

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
			$assigned_labor = $this->total_labor_available[$job_id];
			$success = ($need >= $assigned_labor);

			# if we're skipping meeting night cleaner, then skip it
			if (($job_id == MEETING_NIGHT_CLEANER ) && ($assigned_labor == 0)) {
				continue;
			}

			// this is relevant as an ordered list, not an associative array
			$counts[] = [
				'job_id' => $job_id,
				'assigned_labor' => $assigned_labor,
				'need' => $need,
				'diff' => ($need - $assigned_labor),
				'job_name' => $all_jobs[$job_id],
			];
		}
		return $counts;
	}


	/**
	 * @dataProvider provideAddNonResponderPrefs
	public function testAddNonResponderPrefs($dates_by_shift, $expected) {
		$this->roster->initLaborCount();

		$schedule = new Schedule();
		$this->roster->setSchedule($schedule);

		// -------- 1st test ---------
		$slackers = ['aaa', 'bbb', 'ccc', 'ddd', 'eee'];
		foreach($slackers as $username) {
			$worker = $this->roster->addWorker($username);
			$worker->addNumShiftsAssigned(SUNDAY_HEAD_COOK, 1);
			$worker->addNumShiftsAssigned(MEETING_NIGHT_ORDERER, 1);
			$worker->addNumShiftsAssigned(WEEKDAY_HEAD_COOK, 1);
		}
		$result = $this->roster->addNonResponderPrefs($slackers);

		$num = count($slackers);
		$debug = [
			'result' => $result,
			'num' => $num,
			'slackers' => $slackers,
		];
		$this->assertEquals($result, $num, print_r($debug, TRUE));

		// -------- 2nd test ---------
		$assigned = $this->roster->getAssigned();
		$debug = [
			'assigned' => $assigned,
			'expected' => $expected
		];
		$this->assertEquals($assigned, $expected, print_r($debug, TRUE));
	}
	 */

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

}
?>
