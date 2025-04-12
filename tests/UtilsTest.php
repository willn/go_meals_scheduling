<?php
use PHPUnit\Framework\TestCase;

require_once '../public/constants.php';
require_once '../public/utils.php';
require_once '../public/config.php';
require_once '../public/globals.php';
require_once '../auto_assignments/schedule.php';

// UPDATE-EACH-SEASON
define('EASTER_MONTH', 4);
define('EASTER_DAY', 20);

// XXX this ought to get automated
define('LABOR_DAY', 2);

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class UtilsTest extends TestCase {
	/**
	 * @dataProvider provide_array_get
	 */
	public function test_array_get($expect, $key, $default=NULL) {
		$list = array('foo', 'bar', 'baaz', 'quux');
		$this->assertEquals($expect, array_get($list, $key, $default));
	}

	public function provide_array_get() {
		return [
			['foo', 0],
			['quux', 3],
			[NULL, 10],
			[NULL, -1],
			['hey', 9, 'hey'],
		];
	}

	/**
	 * @dataProvider provide_associative_array_add
	 */
	public function test_associative_array_add($one, $two, $expected) {
		$result = associative_array_add($one, $two);
		$debug = [
			'one' => $one,
			'two' => $two,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_associative_array_add() {
		$one = ['a' => 1, 'b' => 3, 'c' => 5];
		$two = ['a' => 2, 'b' => 4, 'd' => 6];

		return [
			[$one, $two, ['a' => 3, 'b' => 7, 'c' => 5, 'd' => 6]],
		];
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
		$result = add_easter($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_add_easter() {
		$some_other_day = 1;
		// avoid a collision with this other date
		if (EASTER_DAY === $some_other_day) {
			$some_other_day = 2;
		}

		return [
			[[], [EASTER_MONTH => [EASTER_DAY]]],
			[[7 => [4]], [EASTER_MONTH => [EASTER_DAY], 7 => [4]]],
			[
				[EASTER_MONTH => [$some_other_day], 7 => [4]],
				[EASTER_MONTH => [$some_other_day, EASTER_DAY], 7 => [4]],
			],
		];
	}

	/**
	 * @dataProvider provide_add_memorial_day
	 */
	public function test_add_memorial_day($input, $expected) {
		$result = add_memorial_day($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_add_memorial_day() {
		return [
			[
				[7 => [4], 12 => [25]],
				[5 => [25, 26], 7 => [4], 12 => [25]]],
		];
	}

	/**
	 * @dataProvider provide_add_labor_day
	 */
	public function test_add_labor_day($input, $expected) {
		$result = add_labor_day($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_add_labor_day() {
		return [
			[
				[7 => [4], 12 => [25]],
				[7 => [4], 8 => [31], 9 => [1], 12 => [25]]],
		];
	}

	/**
	 * @dataProvider provide_add_thanksgiving_day
	 */
	public function test_add_thanksgiving_day($input, $expected) {
		$result = add_thanksgiving_day($input);
		$this->assertEquals($expected, $result);
	}

	public function provide_add_thanksgiving_day() {
		return [
			[
				[7 => [4], 12 => [25]],
				[7 => [4], 11 => [27, 30], 12 => [25]]],
		];
	}

	/**
	 * @dataProvider provide_get_holidays
	 */
	public function test_get_holidays($expect) {
		$result = get_holidays();
		$debug = [
			'expect' => $expect,
			'result' => $result,
		];
		$this->assertEquals($expect, $result, print_r($debug, TRUE));
	}

	public function provide_get_holidays() {
		$days = [
			1 => [1, 19],
			// easter changes
			EASTER_MONTH => [EASTER_DAY],
			// Memorial Day changes
			5 => [25, 26],
			7 => [4],
			8 => [31],
			9 => [1],
			10 => [31],
			11 => [11, 27, 30],
			12 => [24, 25, 31],
		];

		return array(
			[$days],
		);
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
		$debug = [
			'result' => $result,
			'expected' => $expected,
			'input' => $input,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_get_meal_type_by_date() {
		return [
			['', NOT_A_MEAL],
			['07/04/2018', HOLIDAY_NIGHT],
			['07/19/2025', BRUNCH_MEAL],
			['04/14/2018', NOT_A_MEAL],
			['04/15/2018', SUNDAY_MEAL],
			# ['04/16/2018', MEETING_NIGHT_MEAL], # disable for now
			['04/16/2018', NOT_A_MEAL],
			['04/18/2018', WEEKDAY_MEAL],

			['2018/04/18', WEEKDAY_MEAL],
		];
	}

	/**
	 * @dataProvider provide_is_weekday_override
	 */
	public function test_is_weekday_override($month_num, $day_num, $expected) {
		$result = is_weekday_override($month_num, $day_num);
		$this->assertEquals($expected, $result);
	}

	public function provide_is_weekday_override() {
		return [
			[1, 1, FALSE],
			[1, 18, FALSE],
			[12, 31, FALSE],

			# [1, 16, TRUE],
		];
	}

	/**
	 * @dataProvider provide_is_meeting_override
	 */
	public function test_is_meeting_override($month_num, $day_num, $expected) {
		$result = is_meeting_override($month_num, $day_num);
		$this->assertEquals($expected, $result);
	}

	public function provide_is_meeting_override() {
		return [
			[1, 1, FALSE],
			[1, 16, FALSE],
			[12, 31, FALSE],
			[1, 18, FALSE],
		];
	}


	/**
	 * @dataProvider provide_get_a_meal_object
	 */
	public function test_get_a_meal_object($date, $expected) {
		$schedule = new Schedule();
		$meal = get_a_meal_object($schedule, $date);
		$debug = [
			'date' => $date,
			'expected' => $expected,
			'meal' => get_class($meal),
		];
		$this->assertTrue($meal instanceof $expected, print_r($debug, TRUE));
	}

	public function provide_get_a_meal_object() {
		return [
			['04/19/2025', 'BrunchMeal'],
			['07/19/2025', 'BrunchMeal'],
			['04/15/2018', 'SundayMeal'],
			# ['04/16/2018', 'MeetingNightMeal'], # disable for now
			['04/16/2018', 'Error'],
			['04/18/2018', 'WeekdayMeal'],
			['01/01/2018', 'Error'],
		];
	}

	/**
	 * @dataProvider provide_is_valid_season_name
	 */
	public function test_is_valid_season_name($name, $expected) {
		$result = is_valid_season_name($name);
		$this->assertEquals($result, $expected);
	}

	public function provide_is_valid_season_name() {
		return [
			[SPRING, TRUE],
			[SUMMER, TRUE],
			[FALL, TRUE],
			[WINTER, TRUE],

			// mis-spelling of summer
			['SUMMMER', FALSE],
			[NULL, FALSE],
			['XXX', FALSE],
			[123, FALSE],
			['', FALSE],
			[[], FALSE],
		];
	}

	/**
	 * @dataProvider provide_is_saturday
	 */
	public function test_is_saturday($date, $expected) {
		$result = is_saturday($date);
		$debug = [
			'result' => $result,
			'expected' => $expected,
			'date' => $date,
		];
		$this->assertEquals($result, $expected, print_r($debug, TRUE));
	}

	public function provide_is_saturday() {
		return [
			['9/14/2024', TRUE],
			['9/15/2024', FALSE],
		];
	}

	/**
	 * @dataProvider provide_is_first_saturday
	 */
	public function test_is_first_saturday($date, $expected) {
		$result = is_first_saturday($date);
		$debug = [
			'result' => $result,
			'expected' => $expected,
			'date' => $date,
		];
		$this->assertEquals($result, $expected, print_r($debug, TRUE));
	}

	public function provide_is_first_saturday() {
		return [
			['9/7/2024', TRUE],

			['9/1/2024', FALSE],
			['9/14/2024', FALSE],
			['9/15/2024', FALSE],
			['9/21/2024', FALSE],
			['9/28/2024', FALSE],
			['9/30/2024', FALSE],
		];
	}

	/**
	 * @dataProvider provide_is_third_saturday
	 */
	public function test_is_third_saturday($date, $expected) {
		$result = is_third_saturday($date);
		$debug = [
			'result' => $result,
			'expected' => $expected,
			'date' => $date,
		];
		$this->assertEquals($result, $expected, print_r($debug, TRUE));
	}

	public function provide_is_third_saturday() {
		return [
			['2/15/2025', TRUE],
			['3/15/2025', TRUE],
			['4/19/2025', TRUE],
			['6/21/2025', TRUE],

			['4/1/2025', FALSE],
			['4/5/2025', FALSE],
			['4/12/2025', FALSE],
			['4/15/2025', FALSE],
			['4/21/2025', FALSE],
			['4/26/2025', FALSE],
			['4/30/2025', FALSE],
		];
	}

	/**
	 * @dataProvider provide_get_nearest_even
	 */
	public function test_get_nearest_even($number, $expected) {
		$result = get_nearest_even($number);
		$debug = [
			'result' => $result,
			'expected' => $expected,
			'number' => $number,
		];
		$this->assertEquals($result, $expected, print_r($debug, TRUE));
	}

	public function provide_get_nearest_even() {
		return [
			[0, 0],
			[1, 0],
			[2, 2],
			[3, 2],
			[4, 4],
			[5, 4],
			[6, 6],
			[7, 6],
			[8, 8],
			[9, 8],
			[10, 10],
		];
	}
}
?>
