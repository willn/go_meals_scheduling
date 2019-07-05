<?php
global $relative_dir;
$relative_dir = '../public/';

require_once '../public/classes/roster.php';

class RosterTest extends PHPUnit_Framework_TestCase {
	private $roster;

	public function setUp() {
		$this->roster = new Roster();
		$this->roster->loadNumShiftsAssigned();
	}

	/**
	 * Note: this may need to be adjusted each season or sub-season.
	 */
	public function testGetTotalLaborAvailable() {
		$current = [
			'all' => 0,
			MEETING_NIGHT_ORDERER => 6,
			MEETING_NIGHT_CLEANER => 6,
			SUNDAY_HEAD_COOK => 13,
			SUNDAY_ASST_COOK => 25,
			SUNDAY_CLEANER => 36,
			WEEKDAY_HEAD_COOK => 34,
			WEEKDAY_ASST_COOK => 64,
			WEEKDAY_CLEANER => 99,
			WEEKDAY_TABLE_SETTER => 33,
		];

		$labor = $this->roster->getTotalLaborAvailable();
		$this->assertEquals($labor, $current);
	}

	/**
	 * Note: this may need to be adjusted each season or sub-season.
	 * XXX This is checking for a consistency between the workers array and
	 * the roster array... how / why are those different?
	 */
	public function testRosterAndWorkerAssignmentsSynced() {
		$labor = $this->roster->getTotalLaborAvailable();

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

		unset($labor['all']);
		ksort($labor);
		ksort($summary);
		$this->assertEquals($labor, $summary);
	}

	/**
	 * XXX Unfortunately, this doesn't do anything interesting until there
	 * are some available shifts data available.
	public function testSortAvailable() {
		$result = $this->roster->sortAvailable($input);
		$this->assertEquals(['aaa'], $result);
	}
	 */

	/**
	 * @dataProvider provideGetNumShiftsPerSeason
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
}
?>
