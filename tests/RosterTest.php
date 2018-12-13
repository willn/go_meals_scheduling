<?php
global $relative_dir;
$relative_dir = '../public/';

require_once '../public/classes/roster.php';

class RosterTest extends PHPUnit_Framework_TestCase {
	private $roster;

	public function setUp() {
		$this->roster = new Roster();
	}

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
