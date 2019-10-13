<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/globals.php';

class GlobalsTest extends PHPUnit_Framework_TestCase {
	public function test_get_days_of_week() {
		$days = get_days_of_week();
		$this->assertInternalType('array', $days);
		$this->assertEquals(count($days), 7, print_r($debug, TRUE));
	}

	public function test_get_pref_names() {
		$prefs = get_pref_names();
		$this->assertInternalType('array', $prefs);
		$this->assertEquals(count($prefs), 3, print_r($debug, TRUE));
	}

	public function test_get_weekday_meal_days() {
		$days = get_weekday_meal_days();
		$this->assertInternalType('array', $days);
		$this->assertNotEmpty($days);
	}

	public function test_get_mtg_nights() {
		$mtgs = get_mtg_nights();
		$this->assertInternalType('array', $mtgs);
		$this->assertEquals(count($mtgs), 2, print_r($debug, TRUE));
	}

	/**
	 * Test that creating a sqlite connection returns a PDO instance.
	 */
	public function test_create_sqlite_connection() {
		$dbh = create_sqlite_connection();
		$this->assertInstanceOf(PDO, $dbh);
	}

	// XXX this changes every season...
	public function test_get_job_ids_clause() {
		$result = get_job_ids_clause();
		// XXX this will need to be changed each season
		$expected = 'job_id=5011 OR job_id=5012 OR job_id=5016 OR job_id=5004 OR job_id=5013 OR job_id=5014 OR job_id=5017 OR job_id=5015 OR job_id=5018';
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
		return [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, TRUE],
			[SUNDAY_HEAD_COOK, FALSE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
			[WEEKDAY_TABLE_SETTER, FALSE],
		];
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
		return [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, FALSE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, TRUE],
			[SUNDAY_CLEANER, TRUE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
			[WEEKDAY_TABLE_SETTER, FALSE],
		];
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
		return [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, TRUE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
			[WEEKDAY_ASST_COOK, TRUE],
			[WEEKDAY_CLEANER, FALSE],
			[WEEKDAY_TABLE_SETTER, FALSE],
		];
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
		return [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, FALSE],
			[MEETING_NIGHT_CLEANER, TRUE],
			[SUNDAY_HEAD_COOK, FALSE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, TRUE],
			[WEEKDAY_HEAD_COOK, FALSE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, TRUE],
			[WEEKDAY_TABLE_SETTER, FALSE],
		];
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
		return [
			[0, FALSE],
			[MEETING_NIGHT_ORDERER, TRUE],
			[MEETING_NIGHT_CLEANER, FALSE],
			[SUNDAY_HEAD_COOK, TRUE],
			[SUNDAY_ASST_COOK, FALSE],
			[SUNDAY_CLEANER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
			[WEEKDAY_ASST_COOK, FALSE],
			[WEEKDAY_CLEANER, FALSE],
			[WEEKDAY_TABLE_SETTER, FALSE],
			[WEEKDAY_HEAD_COOK, TRUE],
		];
	}
}
?>
