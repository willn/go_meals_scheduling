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
		$labor = $this->roster->getTotalLaborAvailable();
/*
		$current = [
			'all' => 0,
			MEETING_NIGHT_ORDERER => 0,
			MEETING_NIGHT_CLEANER => 0,
			SUNDAY_HEAD_COOK => 0,
			SUNDAY_ASST_COOK => 0,
			SUNDAY_CLEANER => 0,
			WEEKDAY_HEAD_COOK => 0,
			WEEKDAY_ASST_COOK => 0,
			WEEKDAY_CLEANER => 0,
			WEEKDAY_TABLE_SETTER => 0,
		];
		$this->assertEquals($current, $labor, 'line: ' . __LINE__);
*/

		// ----- get worker shifts to fill
		$shifts = $this->roster->getWorkerShiftsToFill();
		$this->assertGreaterThan(1, count($shifts));

		$summary = [];
		foreach($shifts as $worker => $assignments) {
			$this->assertInternalType('string', $worker);
			$this->assertNotEmpty($assignments);

			foreach($assignments as $job_id => $assn_count) {
				$this->assertInternalType('int', $job_id);
				$this->assertInternalType('int', $assn_count);

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
			['nothing', NULL],

			// the current season is 2 months
			[NULL, count(get_current_season_months())],
		];
	}
}
?>
