<?php
use PHPUnit\Framework\TestCase;

require_once '../public/constants.php';
require_once '../public/utils.php';
require_once '../public/config.php';
require_once '../public/globals.php';
require_once '../auto_assignments/schedule.php';

// UPDATE-EACH-SEASON
define('EASTER_MONTH', 3);
define('EASTER_DAY', 31);

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
				[5 => [26, 27], 7 => [4], 12 => [25]]],
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
				[7 => [4], 9 => $this->get_september_dates(), 12 => [25]]],
		];
	}

	public function get_september_dates() {
		return [(LABOR_DAY - 1), LABOR_DAY];
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
				[7 => [4], 11 => [28], 12 => [25]]],
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
		$september_dates = [(LABOR_DAY - 1), LABOR_DAY];

		$days = [
			1 => [1],
			// easter changes
			EASTER_MONTH => [EASTER_DAY],
			// Memorial Day changes
			5 => [26, 27],
			7 => [4],
			9 => $this->get_september_dates(),
			10 => [31],
			11 => [28],
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
		$this->assertEquals($expected, $result);
	}

	public function provide_get_meal_type_by_date() {
		return [
			['', NOT_A_MEAL],
			['07/04/2018', HOLIDAY_NIGHT],
			['04/15/2018', WEEKEND_OVER_SUNDAYS ? WEEKEND_MEAL : SUNDAY_MEAL],
			['04/16/2018', MEETING_NIGHT_MEAL],
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
		$this->assertTrue($meal instanceof $expected);
	}

	public function provide_get_a_meal_object() {
		return [
			['04/15/2018', WEEKEND_OVER_SUNDAYS ? 'WeekendMeal' : 'SundayMeal'],
			['04/16/2018', 'MeetingNightMeal'],
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
}
?>
