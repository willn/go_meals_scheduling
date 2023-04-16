<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');

require_once '../public/constants.php';
require_once '../public/config.php';
require_once '../public/season.php';
require_once '../public/classes/worker.php';
require_once '../public/classes/calendar.php';
require_once 'testing_utils.php';

class CalendarTest extends TestCase {
	protected $calendar;

	protected $availability = [
		'7/3/2022' => [
			SUNDAY_HEAD_COOK => [
				2 => ['alice', 'bob'],
				1 => ['charlie', 'doug', 'edward', 'fred'],
			]
		],
		'10/17/2022' => [
			MEETING_NIGHT_ORDERER => [
				2 => ['doug', 'edward', 'fred'],
				1 => ['bob'],
			]
		],
		'10/26/2022' => [
			WEEKDAY_HEAD_COOK => [
				2 => ['charlie', 'doug', 'edward', 'fred'],
				1 => ['alice', 'bob'],
			]
		]
	];

	public function setUp() : void {
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
				<a class="conflict">conflict</a>
				<a class="OK">OK</a>
				<a class="prefer">prefer</a>

			</td>
EOHTML;
		$two = <<<EOHTML
			<td class="weekday_selector weekday_num_999">
				Sun:<br>
				<a class="conflict">conflict</a>
				<a class="OK">OK</a>
				<a class="prefer">prefer</a>

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
<tr class="day_labels"><td width="1%"><!-- weekly spacer --></td><th class="day_of_week">Sun</th><th class="day_of_week">Mon</th><th class="day_of_week">Tue</th><th class="day_of_week">Wed</th><th class="day_of_week">Thu</th><th class="day_of_week">Fri</th><th class="day_of_week">Sat</th></tr><tr class="weekdays"><td width="1%"><!-- weekly spacer --></td><td class="weekday_selector weekday_num_0">Sun:<br><a class="conflict">conflict</a><a class="OK">OK</a><a class="prefer">prefer</a></td><td class="weekday_selector weekday_num_1">Mon:<br><a class="conflict">conflict</a><a class="OK">OK</a><a class="prefer">prefer</a></td><td class="weekday_selector weekday_num_2">Tue:<br><a class="conflict">conflict</a><a class="OK">OK</a><a class="prefer">prefer</a></td><td class="weekday_selector weekday_num_3">Wed:<br><a class="conflict">conflict</a><a class="OK">OK</a><a class="prefer">prefer</a></td><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr>
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
	 * @dataProvider provideAddMessage
	 */
	public function testAddMessage($month_num, $day_of_week, $expected) {
		$result = $this->calendar->addMessage($month_num, $day_of_week);
		$this->assertEquals($expected, $result);
	}

	public function provideAddMessage() {
		return [
			// cell is empty
			[NULL, NULL, ''],

			// non tuesdays
			[MONDAY, MARCH, ''],
			[WEDNESDAY, MARCH, ''],
			[TUESDAY, MAY, ''],
			[MONDAY, JULY, ''],

			// non-summer season months
			[TUESDAY, MARCH, ''],
			[TUESDAY, DECEMBER, ''],

			// middle of summer
			[TUESDAY, JULY, (DOING_CSA_FARM_MEALS ? Calendar::FARM_MSG : '')],
			[TUESDAY, OCTOBER, (DOING_CSA_FARM_MEALS ? Calendar::FARM_MSG : '')],
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
		$out = [
			['x', 'x'],
			['Meeting night takeout orderer', 'Meeting night takeout orderer'],
			['Meeting night cleaner', 'Meeting night cleaner'],
			['Sunday head cook', 'head cook'],
			['Sunday meal asst cook', 'asst cook'],
			['Sunday Meal Cleaner', 'Cleaner'],
			['Weekday head cook', 'head cook'],
			['Weekday meal asst cook', 'asst cook'],
			['Weekday Meal cleaner', 'cleaner'],
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$out['Weekday Table Setter'] = 'Table Setter';
		}
		return $out;
	}

	public function testGetJobsIndex() {
		$result = $this->calendar->getJobsIndex('all');
		$this->assertNotEmpty($result);

		$entry = '';
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$entry = "\n" .
				'<li><a href="/meals_scheduling/report.php?key=6247">Weekday Table Setter</a></li>';
		}
		// UPDATE-EACH-SEASON
		$out = <<<EOHTML
<ul id="filter_overlay"><li><a href="/meals_scheduling/report.php?key=all">all</a></li><li><a href="/meals_scheduling/report.php?key=6784">Weekday head cook</a></li><li><a href="/meals_scheduling/report.php?key=6785">Weekday meal asst cook</a></li><li><a href="/meals_scheduling/report.php?key=6789">Weekday Meal cleaner</a></li><li><a href="/meals_scheduling/report.php?key=6786">Sunday head cook</a></li><li><a href="/meals_scheduling/report.php?key=6787">Sunday meal asst cook</a></li><li><a href="/meals_scheduling/report.php?key=6790">Sunday Meal Cleaner</a></li><li><a href="/meals_scheduling/report.php?key=6788">Meeting night takeout orderer</a></li><li><a href="/meals_scheduling/report.php?key=6791">Meeting night cleaner</a></li></ul>

EOHTML;
		$this->assertEquals($result, remove_html_whitespace($out));
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
			'10/1/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/2/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/3/2018' => [0 => MEETING_NIGHT_ORDERER, 1 => MEETING_NIGHT_CLEANER],
			'10/7/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/8/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/9/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/10/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/14/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/15/2018' => [0 => MEETING_NIGHT_ORDERER, 1 => MEETING_NIGHT_CLEANER],
			'10/16/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/17/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/21/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/22/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/23/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/24/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/28/2018' => [0 => SUNDAY_HEAD_COOK, 1 => SUNDAY_ASST_COOK, 2 => SUNDAY_CLEANER], // sunday
			'10/29/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
			'10/30/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
		];

		$result1 = [
			WEEKDAY_HEAD_COOK => 12,
			WEEKDAY_ASST_COOK => 12,
			WEEKDAY_CLEANER => 12,
			MEETING_NIGHT_ORDERER => 2,
			MEETING_NIGHT_CLEANER => 2,
			SUNDAY_HEAD_COOK => 4,
			SUNDAY_ASST_COOK => 4,
			SUNDAY_CLEANER => 4,
		];
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$result1[WEEKDAY_TABLE_SETTER] = 12;
		}

		return [
			[[], []],
			[
				[
					'10/1/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
					'10/2/2018' => [0 => WEEKDAY_HEAD_COOK, 1 => WEEKDAY_ASST_COOK, 2 => WEEKDAY_CLEANER],
				],
				[WEEKDAY_HEAD_COOK => 2, WEEKDAY_ASST_COOK => 2, WEEKDAY_CLEANER => 2],
			],
			[$example1, $result1],
		];
	}

	/**
	 * @dataProvider provideGetNumberAssignmentsPerJobId
	 */
	public function testGetNumberAssignmentsPerJobId($input,
		$sub_season_factor, $expected, $season) {

		$this->calendar->setSeasonMonths($season);
		$result = $this->calendar->getNumberAssignmentsPerJobId($input,
			$sub_season_factor);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideGetNumberAssignmentsPerJobId() {
		// 6-month seasons use a special allocation
		$input_6mo = [
			WEEKDAY_HEAD_COOK => 10, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 10, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 10, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 10, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 10, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 10, // SUNDAY_CLEANER
		];
		$expected_6mo = [
			WEEKDAY_HEAD_COOK => 5.0, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10.0, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 5.0, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 5.0, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 5.0, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 5.0, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10.0, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 5.0, // SUNDAY_CLEANER
		];
		$season_6mo = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
			10 => 'October',
		];

		// 3-month seasons is half of a special 6mo allocation
		$input_3mo = [
			WEEKDAY_HEAD_COOK => 10, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 10, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 10, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 10, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 10, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 10, // SUNDAY_CLEANER
		];
		$expected_3mo = [
			WEEKDAY_HEAD_COOK => 10.0, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 20.0, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 10.0, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 10.0, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 10.0, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 10.0, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 20.0, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 10.0, // SUNDAY_CLEANER
		];
		$season_3mo = [
			5 => 'May',
			6 => 'June',
			7 => 'July',
		];

		// 3-month season for summer 2019 (part 2)
		$input_3mo_s19 = [
			WEEKDAY_HEAD_COOK => 32, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 32, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 32, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 6, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 6, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 12, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 12, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 12, // SUNDAY_CLEANER
		];
		$expected_3mo_s19 = [
			WEEKDAY_HEAD_COOK => 32, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 64, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 32, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 6, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 6, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 12, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 24, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 12, // SUNDAY_CLEANER
		];
		$season_3mo_s19 = [
			8 => 'August',
			9 => 'September',
			10 => 'October',
		];

		$input_4mo = [
			WEEKDAY_HEAD_COOK => 10, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 10, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 10, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 10, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 10, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 10, // SUNDAY_CLEANER
		];
		$expected_4mo = [
			WEEKDAY_HEAD_COOK => 5.0, // WEEKDAY_HEAD_COOK
			WEEKDAY_ASST_COOK => 10.0, // WEEKDAY_ASST_COOK
			WEEKDAY_CLEANER => 8.0, // WEEKDAY_CLEANER
			MEETING_NIGHT_ORDERER => 5.0, // MEETING_NIGHT_ORDERER
			MEETING_NIGHT_CLEANER => 5.0, // MEETING_NIGHT_CLEANER
			SUNDAY_HEAD_COOK => 5.0, // SUNDAY_HEAD_COOK
			SUNDAY_ASST_COOK => 10.0, // SUNDAY_ASST_COOK
			SUNDAY_CLEANER => 8.0, // SUNDAY_CLEANER
		];
		$season_4mo = [
			6 => 'June',
			7 => 'July',
			8 => 'August',
			9 => 'September',
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$input_6mo[WEEKDAY_TABLE_SETTER] = 10;
			$expected_6mo[WEEKDAY_TABLE_SETTER] = 2.0;

			$input_3mo[WEEKDAY_TABLE_SETTER] = 9;
			$expected_3mo[WEEKDAY_TABLE_SETTER] = 3.0;

			$input_3mo_s19[WEEKDAY_TABLE_SETTER] = 32;
			$expected_3mo_s19[WEEKDAY_TABLE_SETTER] = 11;

			$input_4mo[WEEKDAY_TABLE_SETTER] = 10;
			$expected_4mo[WEEKDAY_TABLE_SETTER] = 3.0;
		}

		return [
			[[], 1, [], [1=>'empty season test']],
			[$input_6mo, 1, $expected_6mo, $season_6mo],
			[$input_3mo, .5, $expected_3mo, $season_3mo],
			[$input_3mo_s19, .5, $expected_3mo_s19, $season_3mo_s19],
			[$input_4mo, 1, $expected_4mo, $season_4mo],
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
			MEETING_NIGHT_ORDERER => 2.0,
			MEETING_NIGHT_CLEANER => 2.0,
			SUNDAY_HEAD_COOK => 4.0,
			SUNDAY_ASST_COOK => 2.0,
			SUNDAY_CLEANER => 2.0,
		];
		$entry = '';
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$input1[WEEKDAY_TABLE_SETTER] = 12.0;
			$entry = "\n<br>Weekday Table Setter 12";
		}
		$expected1 = <<<EOHTML
<p>Meeting night cleaner 2
<br>Meeting night takeout orderer 2
<br>Sunday Meal Cleaner 2
<br>Sunday head cook 4
<br>Sunday meal asst cook 2
<br>Weekday Meal cleaner 4
<br>Weekday head cook 12
<br>Weekday meal asst cook 6{$entry}
</p>
EOHTML;

		return [
			[$input1, $expected1],
		];
	}

	/**
	 * @dataProvider provide_list_available_workers
	 */
	public function test_list_available_workers($cur_date_jobs, $is_sunday, $expected) {
		// $this->calendar->disableWebDisplay();
		$result = $this->calendar->list_available_workers($cur_date_jobs, $is_sunday);
		$debug = [
			'cur_date_jobs' => $cur_date_jobs,
			'is_sunday' => $is_sunday,
			'result' => $result,
			'expected' => $expected,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provide_list_available_workers() {
		$sun_1 = [
			SUNDAY_ASST_COOK => [
				2 => [0 => 'fatima'],
				1 => [
					0 => 'keithg',
					1 => 'megan',
					2 => 'nancy',
					3 => 'terrence',
				],
			],
			SUNDAY_HEAD_COOK => [
				2 => [0 => 'maryking'],
				1 => [
					0 => 'dan',
					1 => 'drew',
					2 => 'keithg',
					3 => 'tevah',
				],
			],
			SUNDAY_CLEANER => [
				1 => [
					0 => 'amyh',
					1 => 'annie',
					2 => 'bennie',
					3 => 'dan',
					4 => 'debbi',
					5 => 'mac',
				],
			],
		];

		$sun_cell = <<<EOHTML
<h3 class="jobname">Sunday meal asst cook</h3>
<div class="highlight">prefer:<ul><li>fatima</li></ul></div>
<div class="OK">OK:<ul><li>keithg</li>
<li>
megan</li>
<li>
nancy</li>
<li>
terrence</li></ul></div>
<h3 class="jobname">Sunday head cook</h3>
<div class="highlight">prefer:<ul><li>maryking</li></ul></div>
<div class="OK">OK:<ul><li>dan</li>
<li>
drew</li>
<li>
keithg</li>
<li>
tevah</li></ul></div>
<h3 class="jobname">Sunday Meal Cleaner</h3>
<div class="OK">OK:<ul><li>amyh</li>
<li>
annie</li>
<li>
bennie</li>
<li>
dan</li>
<li>
debbi</li>
<li>
mac</li></ul></div>

EOHTML;

		$mtg_1 = [
		  MEETING_NIGHT_CLEANER => [
			1 => [
			  0 => 'dan',
			  1 => 'nicholas',
			],
			],
			MEETING_NIGHT_ORDERER => [
				1 => [
				  0 => 'gail',
				  1 => 'katie',
				  2 => 'rebecca',
				],
			],
		];

		$mtg_cell = <<<EOHTML
<h3 class="jobname">Meeting night cleaner</h3>
<div class="OK">OK:<ul><li>dan</li>
<li>
nicholas</li></ul></div>
<h3 class="jobname">Meeting night takeout orderer</h3>
<div class="OK">OK:<ul><li>gail</li>
<li>
katie</li>
<li>
rebecca</li></ul></div>

EOHTML;

		$wkd_1 = [
		  WEEKDAY_HEAD_COOK => [
			2 => [0 => 'sharon'],
			1 => [
			  0 => 'catherine',
			  1 => 'emilyadama',
			  2 => 'keithg',
			  3 => 'tammy',
			],
			],
			WEEKDAY_ASST_COOK => [
				1 => [
					0 => 'annie',
					1 => 'catherine',
					2 => 'dan',
					3 => 'drew',
					4 => 'glenn',
					5 => 'keithg',
					6 => 'marta',
					7 => 'nancy',
					8 => 'rod',
					9 => 'tevah',
				],
			],
			WEEKDAY_CLEANER => [
				1 => [
					0 => 'catherine',
					1 => 'dan',
					2 => 'drew',
					3 => 'glenn',
					4 => 'jillian',
					5 => 'keithg',
					6 => 'mac',
					7 => 'mario',
					8 => 'michael',
					9 => 'rod',
				],
			],
		];

		if (defined('WEEKDAY_TABLE_SETTER')) {
			$wkd_1[] = [
				WEEKDAY_TABLE_SETTER => [
					1 => [
						0 => 'hermann',
						1 => 'marta',
						2 => 'megan',
						3 => 'nancy',
						4 => 'nicholas',
					],
				],
			];
		}

		$weekend_cell = <<<EOHTML
<h3 class="jobname">Weekday head cook</h3>
<div class="highlight">prefer:<ul><li>sharon</li></ul></div>
<div class="OK">OK:<ul><li>catherine</li>
<li>
emilyadama</li>
<li>
keithg</li>
<li>
tammy</li></ul></div>
<h3 class="jobname">Weekday meal asst cook</h3>
<div class="OK">OK:<ul><li>annie</li>
<li>
catherine</li>
<li>
dan</li>
<li>
drew</li>
<li>
glenn</li>
<li>
keithg</li>
<li>
marta</li>
<li>
nancy</li>
<li>
rod</li>
<li>
tevah</li></ul></div>
<h3 class="jobname">Weekday Meal cleaner</h3>
<div class="OK">OK:<ul><li>catherine</li>
<li>
dan</li>
<li>
drew</li>
<li>
glenn</li>
<li>
jillian</li>
<li>
keithg</li>
<li>
mac</li>
<li>
mario</li>
<li>
michael</li>
<li>
rod</li></ul></div>

EOHTML;

		return [
			[$sun_1, TRUE, $sun_cell],
			[$mtg_1, FALSE, $mtg_cell],
			[$wkd_1, FALSE, $weekend_cell],
		];
	}

	/**
	 * @dataProvider provideRenderWorkerComments
	 */
	public function testRenderWorkerComments($input, $expected) {
		$result = $this->calendar->renderWorkerComments($input);
		$result = remove_html_whitespace($result);
		$expected = remove_html_whitespace($expected);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideRenderWorkerComments() {
		$basic_response = <<<EOHTML
<h2 id="worker_comments">Comments</h2>
<h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results"></div>
EOHTML;

		$small_data_file = file_get_contents('data/worker_comments.json');
		$small_data = json_decode($small_data_file, TRUE);

		$small_output = <<<EOHTML
<h2 id="worker_comments">Comments</h2>
		<fieldset>
			<legend>apples - 2018-12-22 21:39:00</legend>

		</fieldset>		<fieldset>
			<legend>bananas - 2018-12-18 23:19:47</legend>
			<p>avoids: cherries<br>
prefers: apples,donuts,eggplant<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>cherries - 2018-12-18 23:19:04</legend>
			<p>prefers: apples<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>donuts - 2018-12-22 01:47:37</legend>
			<p>avoids: apples<br>
clean_after_self: no<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>eggplant - 2018-12-15 12:33:15</legend>
			<p><br>There is so much to comment on around here!</p>

		</fieldset><h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results">echo "-----------";
echo 'bananas' avoid workers 'cherries'
grep 'bananas' schedule.txt | grep 'cherries'
echo "-----------";
echo 'bananas' prefers 'apples'
grep 'bananas' schedule.txt | grep 'apples'
echo "-----------";
echo 'bananas' prefers 'donuts'
grep 'bananas' schedule.txt | grep 'donuts'
echo "-----------";
echo 'bananas' prefers 'eggplant'
grep 'bananas' schedule.txt | grep 'eggplant'
echo "-----------";
echo 'cherries' prefers 'apples'
grep 'cherries' schedule.txt | grep 'apples'
echo "-----------";
echo 'donuts' avoid workers 'apples'
grep 'donuts' schedule.txt | grep 'apples'
echo "-----------";
echo 'donuts' clean after self: 'no'
grep 'donuts.*donuts' schedule.txt</div>
EOHTML;

		return [
			[[], $basic_response],
			[$small_data, $small_output],
		];
	}

	/**
	 * Test that the dates returned from the calendar fit the proper form.
	public function testEvalDates() {
		$this->calendar->enableWebDisplay();
		$dates_and_shifts = $this->calendar->evalDates(NULL, $this->availability);
		$this->assertNotEmpty($dates_and_shifts);

		$example = file_get_contents('data/example_calendar.html');
		$this->assertEquals($dates_and_shifts . "\n", $example);
	}
	 */

	/**
	 * Test that the dates returned from the calendar fit the proper form.
	public function testEvalDatesWithWorker() {
		$this->calendar->enableWebDisplay();
		$worker = new Worker('jane');
		$dates_and_shifts = $this->calendar->evalDates($worker, $this->availability);
		$this->assertNotEmpty($dates_and_shifts);

		$example = file_get_contents('data/example_worker_calendar.html');
		$this->assertEquals($dates_and_shifts . "\n", $example);
	}
	 */


	/**
	 * Test that the dates returned from the calendar fit the proper form.
	 */
	public function testJustDatesFromEvalDates() {
		$this->calendar->disableWebDisplay();
		$dates_and_shifts = $this->calendar->evalDates();
		$this->assertNotEmpty($dates_and_shifts);

		foreach($dates_and_shifts as $date => $shifts) {
			$this->assertStringMatchesFormat('%d/%d/%d', $date);
			$this->assertNotEmpty($shifts);
			foreach($shifts as $id) {
				$this->assertGreaterThan(1000, $id);
			}
		}
	}


    /**
     * @dataProvider provideRenderDay
     */
    public function testRenderDay($date, $name, $key, $saved_pref, $expected) {
        $result = $this->calendar->renderday($date, $name, $key, $saved_pref);
        $this->assertEquals($expected, $result);
    }

    public function provideRenderDay() {
		// UPDATE-EACH-SEASON
		$zero = <<<EOHTML
			<div class="choice">
			Meeting night takeout orderer
			<select name="date_5/4/2022_6788" class="preference_selection">
				<option value="0" selected>conflict</option>
				<option value="1">OK</option>
				<option value="2">prefer</option>

			</select>
			</div>
EOHTML;

		$one = <<<EOHTML
			<div class="choice">
			Meeting night takeout orderer
			<select name="date_6/20/2022_6788" class="preference_selection">
				<option value="0">conflict</option>
				<option value="1" selected>OK</option>
				<option value="2">prefer</option>

			</select>
			</div>
EOHTML;

		$two = <<<EOHTML
			<div class="choice">
			Meeting night takeout orderer
			<select name="date_7/18/2022_6788" class="preference_selection">
				<option value="0">conflict</option>
				<option value="1">OK</option>
				<option value="2" selected>prefer</option>

			</select>
			</div>
EOHTML;

		return [
			['5/4/2022', 'Meeting night takeout orderer', MEETING_NIGHT_ORDERER, 0, $zero],
			['6/20/2022', 'Meeting night takeout orderer', MEETING_NIGHT_ORDERER, 1, $one],
			['7/18/2022', 'Meeting night takeout orderer', MEETING_NIGHT_ORDERER, 2, $two],
		];
	}

    /**
     * @dataProvider providegetAssignmentsNeededForCurrentSeason
     */
    public function testGetAssignmentsNeededForCurrentSeason($expected) {
        $result = $this->calendar->getAssignmentsNeededForCurrentSeason();
        $this->assertEquals($expected, $result);
    }

    public function provideGetAssignmentsNeededForCurrentSeason() {
		// UPDATE-EACH-SEASON
		$six_month_season = [
			MEETING_NIGHT_CLEANER => 6.0,
			MEETING_NIGHT_ORDERER => 6.0,

			SUNDAY_CLEANER => 11.0,
			SUNDAY_HEAD_COOK => 11.0,
			SUNDAY_ASST_COOK => 22.0,

			WEEKDAY_ASST_COOK => 90.0,
			WEEKDAY_CLEANER => 45.0,
			WEEKDAY_HEAD_COOK => 45.0,
		];
		$counts = $six_month_season;
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$counts[WEEKDAY_TABLE_SETTER] = 63;
		}

		// UPDATE-EACH-SEASON
		$three_month_season = [
			MEETING_NIGHT_CLEANER => 6,
			MEETING_NIGHT_ORDERER => 6,

			SUNDAY_ASST_COOK => 24,
			SUNDAY_CLEANER => 12,
			SUNDAY_HEAD_COOK => 12,

			WEEKDAY_ASST_COOK => 64,
			WEEKDAY_CLEANER => 32,
			WEEKDAY_HEAD_COOK => 32,
		];

		if (SUB_SEASON_FACTOR === .5) {
			// change which season-length array we're using
			$counts = $three_month_season;
			if (defined('WEEKDAY_TABLE_SETTER')) {
				$counts[WEEKDAY_TABLE_SETTER] = 34;
			}
		}

		$wk_cleaners = isset($counts[WEEKDAY_CLEANER]) ?
			ceil($counts[WEEKDAY_CLEANER] / 3) : 0;
		$setters_string = '';
		if (defined('WEEKDAY_TABLE_SETTER')) {
			$setter_string = "\n" . ceil($counts[WEEKDAY_TABLE_SETTER] / 3);
		}
		$sun_cleaners = isset($counts[SUNDAY_CLEANER]) ?
			ceil($counts[SUNDAY_CLEANER] / 3) : 0;

        return [
            [$counts],
        ];
    }

    public function testGetShiftCounts() {
		$this->calendar->enableWebDisplay();
		$dates_and_shifts = $this->calendar->evalDates(NULL, $this->availability);
        $result = $this->calendar->getShiftCounts();

		// UPDATE-EACH-SEASON
		$expected = [
			'sunday' => 12,
			'weekday' => 32,
			'meeting' => 6,
		];
		$expected['total'] = array_reduce($expected, function($carry, $item) {
			$carry += $item;
			return $carry;
		});

		$debug = [
			'expected' => $expected,
			'result' => $result,
		];
        $this->assertEquals($expected, $result, print_r($debug, TRUE));
    }
}
?>
