<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/classes/sqlite_interface.php';
require_once '../public/classes/calendar.php';
require_once 'testing_utils.php';

class CalendarTest extends PHPUnit_Framework_TestCase {
	protected $calendar;

	public function setUp() {
		$this->calendar = new Calendar();
	}

	public function testGetWeeklySpacerHtml() {
		$result = $this->calendar->getWeeklySpacerHtml();
		$this->assertNotEmpty($result);
	}

	/**
	 * @dataProvider provideGetWeekdaySelectorHtml
	 */
	public function testGetWeekdaySelectorHtml($day_num, $day_of_week, $expected) {
		$result = $this->calendar->getWeekdaySelectorHtml($day_num, $day_of_week);
		$this->assertEquals($result, $expected);
	}

	public function provideGetWeekdaySelectorHtml() {
		$one = <<<EOHTML
			<td class="weekday_selector weekday_num_1">
				Tue:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;
		$two = <<<EOHTML
			<td class="weekday_selector weekday_num_999">
				Sun:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;

		return [
			[1, 'Tue', $one],
			[999, 'Sun', $two],
		];
	}

	/**
	 * @dataProvider provideRenderMonthsOverlay
	 */
	public function testRenderMonthsOverlay($input, $expected) {
		$result = $this->calendar->renderMonthsOverlay($input);
		$result = remove_html_whitespace($result);
		$expected = remove_html_whitespace($expected);
		$this->assertEquals($expected, $result);
	}

	public function provideRenderMonthsOverlay() {
		$random = [
			3 => 'Mar',
			6 => 'Jun',
			10 => 'Oct',
		];

		$one = <<<EOHTML
<ul id="summary_overlay">
<li>Quick links:</li>
<li><a href="#Mar">Mar</a></li>
<li><a href="#Jun">Jun</a></li>
<li><a href="#Oct">Oct</a></li>
<li><a href="#worker_comments">comments</a></li>
<li><a href="#confirm_checks">confirm checks</a></li>
<li><a href="#end">end</a></li>
</ul>
EOHTML;

		return [
			[$random, $one],
		];
	}

	/**
	 * @dataProvider provideRenderDaySelectors
	 */
	public function testRenderDaySelectors($worker, $expected) {
		$result = $this->calendar->renderDaySelectors($worker);
		$this->assertEquals(remove_html_whitespace($expected),
			remove_html_whitespace($result));
	}

	public function provideRenderDaySelectors() {
		$one = <<<EOHTML
<tr class="day_labels">
				<td width="1%"><!-- weekly spacer --></td>
								<th class="day_of_week">Sun</th><th class="day_of_week">Mon</th>				<th class="day_of_week">Tue</th><th class="day_of_week">Wed</th>				<th class="day_of_week">Thu</th><th class="day_of_week">Fri</th>				<th class="day_of_week">Sat</th>
			</tr>
				<tr class="weekdays">
					<td width="1%"><!-- weekly spacer --></td>
								<td class="weekday_selector weekday_num_0">
				Sun:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>			<td class="weekday_selector weekday_num_1">
				Mon:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>			<td class="weekday_selector weekday_num_2">
				Tue:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>			<td class="weekday_selector weekday_num_3">
				Wed:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td><td class="blank"></td><td class="blank"></td><td class="blank"></td>
				</tr>
EOHTML;

		$two = <<<EOHTML
<tr class="day_labels"><th class="day_of_week">Sun</th><th class="day_of_week">Mon</th><th class="day_of_week">Tue</th><th class="day_of_week">Wed</th><th class="day_of_week">Thu</th><th class="day_of_week">Fri</th><th class="day_of_week">Sat</th></tr>
EOHTML;

		return [
			[TRUE, $one],
			[FALSE, $two],
		];
	}


	/**
	 * @dataProvider provideRenderJobNameForDay
	 */
	public function testRenderJobNameForDay($input, $expected) {
		$result = $this->calendar->renderJobNameForDay($input);
		$this->assertEquals($expected, $result);
	}

	public function provideRenderJobNameForDay() {
		return [
			['x', 'x'],
			['Meeting night takeout orderer', 'Meeting night takeout orderer'],
			['Meeting night cleaner', 'Meeting night cleaner'],
			['Sunday head cook', 'head cook'],
			['Sunday meal asst cook', 'asst cook'],
			['Sunday Meal Cleaner', 'Cleaner'],
			['Weekday head cook', 'head cook'],
			['Weekday meal asst cook', 'asst cook'],
			['Weekday Meal cleaner', 'cleaner'],
			['Weekday Table Setter', 'Table Setter'],
		];
	}

	public function testGetJobsIndex() {
		$result = $this->calendar->getJobsIndex('all');
		$this->assertNotEmpty($result);
	}

	/**
	 * @dataProvider provideShiftsPerDate
	 */
	public function testShiftsPerDate($input, $expected) {
		$result = $this->calendar->getShiftsPerDate($input);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideShiftsPerDate() {
		$example1 = [
			'10/1/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/2/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/3/2018' => [0 => MEETING_NIGHT_ORDERER, 1 => MEETING_NIGHT_CLEANER],
			'10/7/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/8/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/9/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/10/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/14/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/15/2018' => [0 => MEETING_NIGHT_ORDERER, 1 => MEETING_NIGHT_CLEANER],
			'10/16/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/17/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/21/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/22/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/23/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/24/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/28/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/29/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
			'10/30/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
		];
		$result1 = [
			WEEKDAY_HEAD_COOK => 12, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 12, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 12, // WEEKDAY_CLEANER
			WEEKDAY_TABLE_SETTER => 12, // WEEKDAY_TABLE_SETTER
			MEETING_NIGHT_ORDERER => 2, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 2, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 4, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 4, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 4, // SUNDAY_CLEANER
		];

		return [
			[[], []],
			[
				[
					'10/1/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
					'10/2/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER, 3 => WEEKDAY_TABLE_SETTER],
				],
				[WEEKDAY_HEAD_COOK => 2, WEEKDAY_ASST_COOK => 2, WEEKDAY_CLEANER => 2, WEEKDAY_TABLE_SETTER => 2],
			],
			[$example1, $result1],
		];
	}

	/**
	 * @dataProvider provideGetNumberAssignmentsPerJobId
	 */
	public function testGetNumberAssignmentsPerJobId($input, $expected, $season) {
		$this->calendar->setSeasonMonths($season);
		$result = $this->calendar->getNumberAssignmentsPerJobId($input);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideGetNumberAssignmentsPerJobId() {
		$input0 = [
			WEEKDAY_HEAD_COOK => 10, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 10, // WEEKDAY_CLEANER
			WEEKDAY_TABLE_SETTER => 10, // WEEKDAY_TABLE_SETTER
			MEETING_NIGHT_ORDERER => 10, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 10, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 10, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 10, // SUNDAY_CLEANER
		];
		$expected0 = [
			WEEKDAY_HEAD_COOK => 4, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 7, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 5, // WEEKDAY_CLEANER
			WEEKDAY_TABLE_SETTER => 2, // WEEKDAY_TABLE_SETTER
			MEETING_NIGHT_ORDERER => 4, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 4, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 4, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 7, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 5, // SUNDAY_CLEANER
		];
		$season = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
		];

		return [
			[[], [], [1=>'empty season test']],
			[$input0, $expected0, $season],
		];
	}

	/**
	 * @dataProvider provideRenderNumberAssignments
	 */
	public function testRenderNumberAssignments($input, $expected) {
		$result = $this->calendar->renderNumberAssignments($input);
		$debug = [
			'input' => $input,
			'result' => $result,
			'expected' => $expected,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideRenderNumberAssignments() {
		$input1 = [
			WEEKDAY_HEAD_COOK => 12.0,
			WEEKDAY_ASST_COOK => 6.0,
			WEEKDAY_CLEANER => 4.0,
			WEEKDAY_TABLE_SETTER => 12.0,
			MEETING_NIGHT_ORDERER => 2.0,
			MEETING_NIGHT_CLEANER => 2.0,
			SUNDAY_HEAD_COOK => 4.0,
			SUNDAY_ASST_COOK => 2.0,
			SUNDAY_CLEANER => 2.0,
		];
		$expected1 = <<<EOHTML
<p>Weekday head cook 12
<br>Weekday meal asst cook 6
<br>Weekday Meal cleaner 4
<br>Weekday Table Setter 12
<br>Meeting night takeout orderer 2
<br>Meeting night cleaner 2
<br>Sunday head cook 4
<br>Sunday meal asst cook 2
<br>Sunday Meal Cleaner 2
</p>
EOHTML;

		return [
			[$input1, $expected1],
		];
	}

	/**
	 * @dataProvider provideEvalDates
	public function testEvalDates($worker, $dates, $expected) {
		$this->calendar->disableWebDisplay();
		$result = $this->calendar->evalDates($worker, $dates);
		$debug = [
			'input' => $input,
			'result' => $result,
			'expected' => $expected,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideEvalDates() {
		$mega_season_file = file_get_contents('data/eval_dates_mega_season.json');
		$mega_season = json_decode($mega_season_file, TRUE);

		return [
			[NULL, NULL, $mega_season],
		];
	}
	 */
}
?>
