<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';
require_once '../public/globals.php';
require_once '../auto_assignments/schedule.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class UtilsTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provide_array_get
	 */
	public function test_array_get($expect, $key, $default=NULL) {
		$list = array('foo', 'bar', 'baaz', 'quux');
		$this->assertEquals($expect, array_get($list, $key, $default));
	}

	public function provide_array_get() {
		return array(
			array('foo', 0),
			array('quux', 3),
			array(NULL, 10),
			array(NULL, -1),
			array('hey', 9, 'hey'),
		);
	}

	public function test_get_season_id() {
		$result = intval(get_season_id());
		$this->assertGreaterThan(0, $result);
	}

	/**
	 * @dataProvider provide_does_season_wrap
	 */
	public function test_does_season_wrap($input, $expected) {
		$result = does_season_wrap($input);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_does_season_wrap() {
		$winter = [
			1 => 'January',
			2 => 'February',
			3 => 'March',
			4 => 'April',
		];
		$summer = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
		];
		$fall = [
			9 => 'September',
			10 => 'October',
			11 => 'November',
			12 => 'December',
		];

		return [
			[[], FALSE],
			[$winter, FALSE],
			[$summer, FALSE],
			[$fall, FALSE],
			[($fall + $winter), TRUE],
			[[12 => 'December', 1 => 'January'], TRUE],
		];
	}

	/**
	 * @dataProvider provide_add_easter
	 */
	public function test_add_easter($input, $expected) {
		$dates = [];
		$result = add_easter($dates, $input);
		$this->assertEquals($expected, $result);
	}

	public function provide_add_easter() {
		return [
			[[], []],
			[[7 => 'July'], []],
			// 2019
			[[3 => 'March', 4 => 'April'], [4 => [21]]],
		];
	}

	/**
	 * @dataProvider first_associative_key_provider
	*/
	public function test_get_first_associative_key($dates, $expected) {
		$this->assertEquals(get_first_associative_key($dates), $expected);
	}

	public function first_associative_key_provider() {
		return [
			[[], NULL],
			[['2/11/2018'=>2, '3/21/2018'=>4, '2/5/2018'=>6], '2/11/2018'],
			[
				[
					'gayle' => 0.0299999999999999988897769753748434595763683319091796875,
					'anne' => 0.01190476190476190410105772343740682117640972137451171875,
					'polly' => 0.00609756097560975630911261902156184078194200992584228515625,
				],
				'gayle'
			]
		];
	}

	/**
	 * @dataProvider provide_get_meal_type_by_date
	 */
	public function test_get_meal_type_by_date($input, $expected) {
		$result = get_meal_type_by_date($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_get_meal_type_by_date() {
		return [
			['', NOT_A_MEAL],
			['07/04/2018', HOLIDAY_NIGHT],
			#['???', SKIP_NIGHT], // current list is empty
			['04/15/2018', SUNDAY_MEAL],
			['04/16/2018', MEETING_NIGHT_MEAL],
			['04/18/2018', WEEKDAY_MEAL],

			['2018/04/18', WEEKDAY_MEAL],
		];
	}

	/**
	 * @dataProvider provide_get_a_meal_object
	 */
	public function test_get_a_meal_object($date, $expected) {
		$schedule = new Schedule();
		$meal = get_a_meal_object($schedule, $date, 1);
		$this->assertTrue($meal instanceof $expected);
	}

	public function provide_get_a_meal_object() {
		return [
			['04/15/2018', SundayMeal],
			['04/16/2018', MeetingNightMeal],
			['04/18/2018', WeekdayMeal],
		];
	}
}
?>
