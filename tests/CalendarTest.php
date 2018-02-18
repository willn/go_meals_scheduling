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
	public function testGetWeekdaySelectorHtml($day_num, $day_of_week) {
		$result = $this->calendar->getWeekdaySelectorHtml($day_num, $day_of_week);
		$this->assertNotEmpty($result);
	}

	public function provideGetWeekdaySelectorHtml() {
		return [
			[1, 'Tue'],
			[999, 'Sun'],
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
			['Meeting night takeout orderer', 'takeout orderer'],
			['Meeting night cleaner', 'cleaner'],
			['Sunday head cook (two meals/season)', 'head cook'],
			['Sunday meal asst cook (two meals/season)', 'asst cook'],
			['Sunday Meal Cleaner', 'Cleaner'],
			['Weekday head cook (two meals/season)', 'head cook'],
			['Weekday meal asst cook (2 meals/season)', 'asst cook'],
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
			'10/1/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/2/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/3/2018' => [0 => 4194, 1 => 4197],
			'10/7/2018' => [0 => 4192, 1 => 4193, 2 => 4196], // sunday
			'10/8/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/9/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/10/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/14/2018' => [0 => 4192, 1 => 4193, 2 => 4196], // sunday
			'10/15/2018' => [0 => 4194, 1 => 4197],
			'10/16/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/17/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/21/2018' => [0 => 4192, 1 => 4193, 2 => 4196], // sunday
			'10/22/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/23/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/24/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/28/2018' => [0 => 4192, 1 => 4193, 2 => 4196], // sunday
			'10/29/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
			'10/30/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
		];
		$result1 = [
			4190 => 12, // WEEKDAY_HEAD_COOK
			4191 => 12, // WEEKDAY_ASST_COOK
			4195 => 12, // WEEKDAY_CLEANER
			4184 => 12, // WEEKDAY_TABLE_SETTER
			4194 => 2, // MEETING_NIGHT_ORDERER
			4197 => 2, // MEETING_NIGHT_CLEANER
			4192 => 4, // SUNDAY_HEAD_COOK
			4193 => 4, // SUNDAY_ASST_COOK
			4196 => 4, // SUNDAY_CLEANER
		];

		return [
			[[], []],
			[
				[
					'10/1/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
					'10/2/2018' => [0 => 4190, 1 => 4191, 2 => 4195, 3 => 4184],
				],
				[4190 => 2, 4191 => 2, 4195 => 2, 4184 => 2],
			],
			[$example1, $result1],
		];
	}

	/**
	 * @dataProvider provideGetNumberAssignmentsPerJobId
	 */
	public function testGetNumberAssignmentsPerJobId($input, $expected) {
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
			4190 => 10, // WEEKDAY_HEAD_COOK
			4191 => 10, // WEEKDAY_ASST_COOK
			4195 => 10, // WEEKDAY_CLEANER
			4184 => 10, // WEEKDAY_TABLE_SETTER
			4194 => 10, // MEETING_NIGHT_ORDERER
			4197 => 10, // MEETING_NIGHT_CLEANER
			4192 => 10, // SUNDAY_HEAD_COOK
			4193 => 10, // SUNDAY_ASST_COOK
			4196 => 10, // SUNDAY_CLEANER
		];
		$expected0 = [
			4190 => 5, // WEEKDAY_HEAD_COOK
			4191 => 10, // WEEKDAY_ASST_COOK
			4195 => 8, // WEEKDAY_CLEANER
			4184 => 3, // WEEKDAY_TABLE_SETTER
			4194 => 5, // MEETING_NIGHT_ORDERER
			4197 => 5, // MEETING_NIGHT_CLEANER
			4192 => 5, // SUNDAY_HEAD_COOK
			4193 => 10, // SUNDAY_ASST_COOK
			4196 => 8, // SUNDAY_CLEANER
		];

		return [
			[[], []],
			[$input0, $expected0],
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
			4190 => 12.0,
			4191 => 6.0,
			4195 => 4.0,
			4184 => 12.0,
			4194 => 2.0,
			4197 => 2.0,
			4192 => 4.0,
			4193 => 2.0,
			4196 => 2.0,
		];
		$expected1 = <<<EOHTML
<p>Weekday head cook (two meals/season) 12
<br>Weekday meal asst cook (2 meals/season) 6
<br>Weekday Meal cleaner 4
<br>Weekday Table Setter 12
<br>Meeting night takeout orderer 2
<br>Meeting night cleaner 2
<br>Sunday head cook (two meals/season) 4
<br>Sunday meal asst cook (two meals/season) 2
<br>Sunday Meal Cleaner 2
</p>
EOHTML;

		return [
			[$input1, $expected1],
		];
	}

}
?>
