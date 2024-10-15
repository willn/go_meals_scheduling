<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
require_once '../public/constants.php';
require_once '../public/globals.php';

class GlobalsTest extends TestCase {
	public function test_get_days_of_week() {
		$days = get_days_of_week();
		$this->assertIsArray($days);
		$debug = [
			'days' => $days,
		];
		$this->assertEquals(count($days), 7, print_r($debug, TRUE));
	}

	public function test_get_pref_names() {
		$prefs = get_pref_names();
		$this->assertIsArray( $prefs);
		$debug = [
			'prefs' => $prefs,
		];
		$this->assertEquals(count($prefs), 3, print_r($debug, TRUE));
	}

	public function test_get_weekday_meal_days() {
		$days = get_weekday_meal_days();
		$this->assertIsArray( $days);
		$this->assertNotEmpty($days);
	}

	public function test_get_mtg_nights() {
		$mtgs = get_mtg_nights();
		$this->assertIsArray( $mtgs);
		$debug = [
			'mtgs' => $mtgs,
		];
		$this->assertEquals(count($mtgs), 2, print_r($debug, TRUE));
	}

	// XXX this changes every season...
	public function test_get_job_ids_clause() {
		$result = get_job_ids_clause();
		// UPDATE-EACH-SEASON
		$expected = 'job_id=7917 OR job_id=7922 OR job_id=7916 OR job_id=7919 OR job_id=7923 OR job_id=7918 OR job_id=8042 OR job_id=8043 OR job_id=8041 OR job_id=7626 OR job_id=7629';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider provide_get_is_a_mtg_night_job
	 */
	public function test_is_a_mtg_night_job($job_id, $expected) {
		$result = is_a_mtg_night_job($job_id);
		$debug = [
			'job_id' => $job_id,
			'result' => var_export($result, TRUE),
			'expected' => var_export($expected, TRUE),
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_get_is_a_mtg_night_job() {
		$out = [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, TRUE],
			[SUNDAY_HEAD_COOK, FALSE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[] = [WEEKDAY_TABLE_SETTER, FALSE];
		}
		return $out;
	}

	/**
	 * @dataProvider get_is_a_sunday_job
	 */
	public function test_is_a_sunday_job($job_id, $expected) {
		$result = is_a_sunday_job($job_id);
		$debug = [
			'job_id' => $job_id,
			'result' => var_export($result, TRUE),
			'expected' => var_export($expected, TRUE),
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function get_is_a_sunday_job() {
		$out = [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, FALSE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, TRUE],
			[SUNDAY_CLEANER, TRUE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[] = [WEEKDAY_TABLE_SETTER, FALSE];
		}
		return $out;
	}

	/**
	 * @dataProvider get_is_a_cook_job
	 */
	public function test_is_a_cook_job($job_id, $expected) {
		$result = is_a_cook_job($job_id);
		$debug = [
			'job_id' => $job_id,
			'result' => var_export($result, TRUE),
			'expected' => var_export($expected, TRUE),
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function get_is_a_cook_job() {
		$out = [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, TRUE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
			[WEEKDAY_ASST_COOK, TRUE],
			[WEEKDAY_CLEANER, FALSE],
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[] = [WEEKDAY_TABLE_SETTER, FALSE];
		}
		return $out;
	}

	/**
	 * @dataProvider get_is_a_clean_job
	 */
	public function test_is_a_clean_job($job_id, $expected) {
		$result = is_a_clean_job($job_id);
		$debug = [
			'job_id' => $job_id,
			'result' => var_export($result, TRUE),
			'expected' => var_export($expected, TRUE),
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function get_is_a_clean_job() {
		$out = [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, FALSE],
			[MEETING_NIGHT_CLEANER, TRUE],
			[SUNDAY_HEAD_COOK, FALSE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, TRUE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, TRUE],
		];
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[WEEKDAY_TABLE_SETTER] = FALSE;
		}
		return $out;
	}

	/**
	 * @dataProvider get_is_a_head_cook_job
	 */
	public function test_is_a_head_cook_job($job_id, $expected) {
		$result = is_a_head_cook_job($job_id);
		$debug = [
			'job_id' => $job_id,
			'result' => var_export($result, TRUE),
			'expected' => var_export($expected, TRUE),
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function get_is_a_head_cook_job() {
		$out = [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
		];
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out[WEEKDAY_TABLE_SETTER] = FALSE;
		}
		return $out;
	}
}
?>
