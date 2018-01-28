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

}
?>
