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
		$this->assertEquals($result, $expected);
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
		$this->assertEquals(remove_html_whitespace($result),
			remove_html_whitespace($expected));
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
		$this->assertEquals($result, $expected);
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

}
?>
