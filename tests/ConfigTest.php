<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class ConfigTest extends PHPUnit_Framework_TestCase {
	private $season = [];

	/**
	 * Test that the functions which return list of jobs and their names work.
	 */
	public function test_get_jobs() {
		$jobs = get_mtg_jobs();
		$this->assertEquals(TRUE, is_array($jobs));
		$this->assertCount(2, $jobs);
		$this->assertArrayHasKey(MEETING_NIGHT_ORDERER, $jobs);
		$this->assertArrayHasKey(MEETING_NIGHT_CLEANER, $jobs);

		$jobs = get_sunday_jobs();
		$this->assertEquals(TRUE, is_array($jobs));
		$this->assertCount(3, $jobs);
		$this->assertArrayHasKey(SUNDAY_HEAD_COOK, $jobs);
		$this->assertArrayHasKey(SUNDAY_ASST_COOK, $jobs);
		$this->assertArrayHasKey(SUNDAY_CLEANER, $jobs);

		$jobs = get_weekday_jobs();
		$this->assertEquals(TRUE, is_array($jobs));
		$weekday_count = defined('WEEKDAY_TABLE_SETTER') ? 4 : 3;
		$this->assertCount($weekday_count, $jobs);
		$this->assertArrayHasKey(WEEKDAY_HEAD_COOK, $jobs);
		$this->assertArrayHasKey(WEEKDAY_ASST_COOK, $jobs);
		$this->assertArrayHasKey(WEEKDAY_CLEANER, $jobs);
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$this->assertArrayHasKey(WEEKDAY_TABLE_SETTER, $jobs);
		}
	}

	public function test_get_all_jobs() {
		$jobs = get_all_jobs();
		$job_count = defined('WEEKDAY_TABLE_SETTER') ? 10 : 9;
		$this->assertEquals($job_count, count($jobs));

		foreach($jobs as $id => $name) {
			// convert this one string to an int
			if ($id === ALL_ID) {
				$id = 1;
			}
			$this->assertInternalType("int", $id);
			$this->assertInternalType("string", $name);
		}
	}

	/**
	 * @dataProvider provide_get_num_meals_per_assignment
	 */
	public function test_get_num_meals_per_assignment($season, $job_id,
		$sub_season_factor, $expect) {

		$result = get_num_meals_per_assignment($season, $job_id, $sub_season_factor);
		$debug = [
			'season' => $season,
			'job_id' => $job_id,
			'expect' => $expect,
			'result' => $result,
		];
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_num_meals_per_assignment() {
		$season_6mos = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
		];
		$season_4mos = [
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
		];
		$season_3mos = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
		];
		$season_2mos = [
			11 => 'November',
			12 => 'December',
		];

		return [
			// 6-month seasons use a different algorithm
			[$season_6mos, MEETING_NIGHT_CLEANER, 1, 2],
			[$season_6mos, MEETING_NIGHT_ORDERER, 1, 2],
			[$season_6mos, SUNDAY_HEAD_COOK, 1, 2],
			[$season_6mos, SUNDAY_ASST_COOK, 1, 2],
			[$season_6mos, SUNDAY_CLEANER, 1, 6],
			[$season_6mos, WEEKDAY_ASST_COOK, 1, 2],
			[$season_6mos, WEEKDAY_HEAD_COOK, 1, 2],
			[$season_6mos, WEEKDAY_CLEANER, 1, 6],
			#[$season_6mos, WEEKDAY_TABLE_SETTER, 1, 6],

			[$season_4mos, MEETING_NIGHT_CLEANER, 1, 2],
			[$season_4mos, MEETING_NIGHT_ORDERER, 1, 2],
			[$season_4mos, SUNDAY_HEAD_COOK, 1, 2],
			[$season_4mos, SUNDAY_ASST_COOK, 1, 2],
			[$season_4mos, SUNDAY_CLEANER, 1, 4],
			[$season_4mos, WEEKDAY_ASST_COOK, 1, 2],
			[$season_4mos, WEEKDAY_HEAD_COOK, 1, 2],
			[$season_4mos, WEEKDAY_CLEANER, 1, 4],
			#[$season_4mos, WEEKDAY_TABLE_SETTER, 1, 4],

			[$season_3mos, MEETING_NIGHT_CLEANER, .5, 1],
			[$season_3mos, MEETING_NIGHT_ORDERER, .5, 1],
			[$season_3mos, SUNDAY_HEAD_COOK, .5, 1],
			[$season_3mos, SUNDAY_ASST_COOK, .5, 1],
			[$season_3mos, SUNDAY_CLEANER, .5, 3],
			[$season_3mos, WEEKDAY_ASST_COOK, .5, 1],
			[$season_3mos, WEEKDAY_HEAD_COOK, .5, 1],
			[$season_3mos, WEEKDAY_CLEANER, .5, 3],
			#[$season_3mos, WEEKDAY_TABLE_SETTER, .5, 3],

			/*
			 * Dropped support for 2 month seasons
			[$season_2mos, MEETING_NIGHT_CLEANER, .33, 1],
			[$season_2mos, MEETING_NIGHT_ORDERER, .33, 1],
			[$season_2mos, SUNDAY_HEAD_COOK, .33, 1],
			[$season_2mos, SUNDAY_ASST_COOK, .33, 1],
			[$season_2mos, WEEKDAY_ASST_COOK, .33, 1],
			[$season_2mos, WEEKDAY_HEAD_COOK, .33, 1],
			*/
			[$season_2mos, SUNDAY_CLEANER, .33, 2],
			#[$season_2mos, WEEKDAY_TABLE_SETTER, .33, 2],
			[$season_2mos, WEEKDAY_CLEANER, .33, 2],
		];
	}

	/**
	 * @dataProvider provide_get_num_workers_per_job_per_meal
	 */
	public function test_get_num_workers_per_job_per_meal($input, $expected) {
		$result = get_num_workers_per_job_per_meal($input);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_get_num_workers_per_job_per_meal() {
		$all = [
			MEETING_NIGHT_CLEANER => 1,
			MEETING_NIGHT_ORDERER => 1,

			SUNDAY_HEAD_COOK => 1,
			SUNDAY_ASST_COOK => 2,
			SUNDAY_CLEANER => 3,

			WEEKDAY_HEAD_COOK => 1,
			WEEKDAY_ASST_COOK => 2,
			WEEKDAY_CLEANER => 3,
		];
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$all[WEEKDAY_TABLE_SETTER] = 1;
		}

		return [
			[NULL, $all],
			[SUNDAY_HEAD_COOK, 1],
			[WEEKDAY_ASST_COOK, 2],
			[SUNDAY_CLEANER, 3],
			[0, 0],
		];
	}

	/**
	 * Test that the hobarters list works.
	 */
	public function test_get_hobarters(){
		$workers = get_hobarters();
		$this->assertEquals(TRUE, is_array($workers));
		$this->assertCount(11, $workers);
		$this->assertContains('willie', $workers);
	}
}
?>
