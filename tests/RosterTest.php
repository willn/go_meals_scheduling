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
	public function testLoadNumShiftsAssigned() {
		$result = $this->roster->getTotalLaborAvailable();
		$current = [
			'all' => 0,
			MEETING_NIGHT_ORDERER => 4,
			MEETING_NIGHT_CLEANER => 4,
			SUNDAY_HEAD_COOK => 8,
			SUNDAY_ASST_COOK => 16,
			SUNDAY_CLEANER => 22,
			WEEKDAY_HEAD_COOK => 23,
			WEEKDAY_ASST_COOK => 45,
			WEEKDAY_CLEANER => 67,
			WEEKDAY_TABLE_SETTER => 22,
		];
		$this->assertEquals($current, $result, 'line: ' . __LINE__);

/*
		$result = $this->roster->getWorkerShiftsToFill();
		$current = [
			'amyh' => [
				4597 => 2,
			],
			'annie' => [
				4597 => 4,
				4592 => 7,
			],
			'augustd' => [
				4592 => 1,
			],
			'bennie' => [
				4597 => 2,
				4596 => 6,
			],
			'catherine' => [
				4596 => 2,
				4592 => 1,
				4591 => 1,
			],
			'dale' => [
				4591 => 2,
			],
			'dan' => [
				4597 => 4,
				4596 => 2,
				4592 => 2,
				4598 => 1,
				4593 => 1,
			],
			'debbi' => [
				4597 => 2,
			],
			'drew' => [
				4596 => 2,
				4592 => 1,
				4593 => 1,
			],
			'emilyadama' => [
				4591 => 2,
			],
			'eric' => [
				4593 => 2,
			],
			'fatima' => [
				4597 => 2,
				4594 => 4,
			],
			'gail' => [
				4595 => 1,
			],
			'gayle' => [
				4597 => 1,
				4596 => 2,
				4592 => 1,
				4584 => 1,
			],
			'glenn' => [
				4596 => 2,
				4592 => 2,
			],
			'gregd' => [
				4592 => 4,
				4591 => 3,
				4598 => 1,
				4596 => 2,
			],
			'hermann' => [
				4584 => 2,
			],
			'iand' => [
				4592 => 1,
			],
			'jan' => [
				4592 => 4,
				4591 => 1,
			],
			'jennifer' => [
				4592 => 4,
				4591 => 2,
				4598 => 1,
			],
			'jillian' => [
				4596 => 2,
			],
			'katie' => [
				4595 => 1,
			],
			'keithg' => [
				4596 => 4,
				4592 => 1,
				4591 => 2,
				4593 => 1,
				4594 => 1,
			],
			'kelly' => [
				4591 => 4,
			],
			'lindsay' => [
				4594 => 5,
				4595 => 1,
			],
			'mac' => [
				4596 => 10,
				4597 => 2,
			],
			'mario' => [
				4596 => 2,
			],
			'marta' => [
				4592 => 4,
				4584 => 12,
				4594 => 2,
			],
			'maryking' => [
				4593 => 2,
			],
			'marys' => [
				4596 => 3,
				4592 => 3,
			],
			'megan' => [
				4584 => 2,
				4594 => 1,
			],
			'michael' => [
				4596 => 11,
			],
			'nancy' => [
				4592 => 2,
				4584 => 2,
				4594 => 0,
			],
			'nicholas' => [
				4584 => 4,
				4598 => 1,
			],
			'pam' => [
				4597 => 2,
				4594 => 1,
			],
			'patti' => [
				4592 => 1,
				4596 => 1,
			],
			'polly' => [
				4596 => 2,
				4592 => 3,
			],
			'rebecca' => [
				4595 => 1,
			],
			'rod' => [
				4596 => 5,
				4592 => 2,
			],
			'sharon' => [
				4591 => 3,
			],
			'tammy' => [
				4591 => 4,
			],
			'ted' => [
				4596 => 2,
			],
			'terrence' => [
				4596 => 7,
				4594 => 1,
			],
			'tevah' => [
				4592 => 1,
				4593 => 1,
			],
			'thomas' => [
				4597 => 3,
			],
			'liam' => [
				4594 => 1,
			],
		];
		$this->assertEquals($current, $result, 'line: ' . __LINE__);
*/
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
			[WINTER, 2],
			[SPRING, 2],
			[SUMMER, 6],
			[FALL, 2],
			['nothing', NULL],

			// the current season is 2 months
			[NULL, 2],
		];
	}
}
?>
