<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/globals.php';
require_once '../public/classes/survey.php';
require_once 'testing_utils.php';

class SurveyTest extends TestCase {
	private $assignments;

	public function setUp() : void {
		$this->survey = new Survey();
		$this->survey->setWorker('testuser', 59);
	}

	public function testConstruct() {
		$this->assertInstanceOf('Survey', $this->survey);
	}

	/**
	 * @dataProvider provideSetWorker
	 */	
	public function testSetWorker($username, $worker_id, $expected) {
		$this->survey->setWorker($username, $worker_id);
		$results = $this->survey->getCurrentWorkerInfo();
		$this->assertEquals($expected, $results);
	}

	public function provideSetWorker() {
		return [
			['x', 123, ['username' => 'x', 'worker_id' => 123]],
			['x', NULL, ['username' => 'x', 'worker_id' => 59]],
			[NULL, 123, ['username' => 'testuser', 'worker_id' => 123]],
			[NULL, NULL, ['username' => 'testuser', 'worker_id' => 59]],
		];
	}

	/*
	public function testGetWorkers() {
		$list = $this->survey->getWorkers();
	}
	*/

	/**
	 * @dataProvider provideRenderShiftsSummaryHtml
	public function testRenderShiftsSummaryHtml($input, $expected) {
		$result = $this->survey->renderShiftsSummaryHtml($input);
		$this->assertEquals(remove_html_whitespace($expected), 
			remove_html_whitespace($result));
	}

	public function provideRenderShiftsSummaryHtml() {
		$ex1 = [
			4597 => [
				"name" => SUNDAY_CLEANER_NAME,
				"instances" => 9,
			],
			4596 => [
				"name" => WEEKDAY_CLEANER_NAME . " (6 meals\/instance)",
				"instances" => 8,
			],
			4592 => [
				"name" => WEEKDAY_ASST_COOK_NAME . " (3 meals\/instance)",
				"instances" => 7,
			],
			4584 => [
				"name" => "Weekday table setter (6 meals\/instance)",
				"instances" => 6,
			],
		];

		$sunday_clean = SUNDAY_CLEANER_NAME;
		$wk_clean = WEEKDAY_CLEANER_NAME;
		$wk_asst_cook = WEEKDAY_ASST_COOK_NAME;
		$out1 = <<<EOHTML
<div class="shift_instances">
	<h4>Assigned Meals:</h4>
	<div>9 meals of {$sunday_clean}</div>
	<div>8 meals of {$wk_clean}</div>
	<div>7 meals of {$wk_asst_cook}</div>
	<div>6 meals of Weekday table setter</div>
</div>
EOHTML;

		$ex2 = [
			4597 => [
				"name" => "Mtg takeout/potluck orderer(3 meals/instance)",
				"instances" => 1,
			],
		];
		$out2 = <<<EOHTML
<div class="shift_instances">
	<h4>Assigned Meals:</h4>
	<div>1 meal of Mtg takeout/potluck orderer</div>
</div>
EOHTML;

		return [
			[$ex1, $out1],
			[$ex2, $out2],
			[[], ''],
		];
	}
	 */

	/**
	 * @dataProvider provideSetUsername
	 */
	public function testSetUsername($input, $expected) {
		$result = $this->survey->setUsername($input);
		$this->assertEquals($expected, $result);
	}

	public function provideSetUsername() {
		$err = '<p class="error">Missing username</p>' . "\n";

		return [
			[['username' => 'test'], NULL],

			['', $err],
			['XXX', $err],
			[[], $err],
			[['asdf'=>1], $err],
			[['username'], $err],
		];
	}

	/**
	 * @dataProvider provideGetSaveRequestsSQL
	 */
	public function testGetSaveRequestsSQL($input, $expected) {
		$post = json_decode($input, TRUE);
		$result = $this->survey->getSaveRequestsSQL($post);
		$this->assertEquals($expected, $result);
	}

	public function provideGetSaveRequestsSQL() {
		$example1 = '{"username":"testuser","posted":"1","avoid_workers":["amyh","annie"],"prefer_workers":["becky","bennie"],"clean_after_self":"no","comments":"it\'s ok","date_11\/4\/2019_4592":"2","date_11\/4\/2019_4596":"2","date_11\/5\/2019_4592":"1","date_11\/5\/2019_4596":"1","date_11\/11\/2019_4592":"2","date_11\/11\/2019_4596":"2","date_11\/12\/2019_4592":"1","date_11\/12\/2019_4596":"1","date_11\/13\/2019_4592":"0","date_11\/13\/2019_4596":"0","date_11\/19\/2019_4592":"1","date_11\/19\/2019_4596":"1","date_11\/20\/2019_4592":"0","date_11\/20\/2019_4596":"0","date_11\/26\/2019_4592":"1","date_11\/26\/2019_4596":"1","date_11\/27\/2019_4592":"0","date_11\/27\/2019_4596":"0","date_12\/2\/2019_4592":"2","date_12\/2\/2019_4596":"2","date_12\/3\/2019_4592":"1","date_12\/3\/2019_4596":"1","date_12\/9\/2019_4592":"2","date_12\/9\/2019_4596":"2","date_12\/10\/2019_4592":"1","date_12\/10\/2019_4596":"1","date_12\/11\/2019_4592":"0","date_12\/11\/2019_4596":"0","date_12\/17\/2019_4592":"1","date_12\/17\/2019_4596":"1","date_12\/18\/2019_4592":"0","date_12\/18\/2019_4596":"0","date_12\/23\/2019_4592":"2","date_12\/23\/2019_4596":"2","date_12\/30\/2019_4592":"2","date_12\/30\/2019_4596":"2"}';

		$result1 = "replace into schedule_comments
	values(
		59,
		now(),
		'it\'s ok',
		'amyh,annie',
		'becky,bennie',
		'no',
		'',
		''
	)";

		return [
			[$example1, $result1],
		];
	}

	/**
	 * XXX
	 * @dataProvider provideTestUpdatePreferences
	 */
	public function testUpdatePreferences($shift_id, $worker_id, $pref, $task, $date) {
		$result = $this->survey->updatePreferences($shift_id, $worker_id, $pref,
			$task, $date);
		$this->assertEquals($result, $pref);
	}

	
	public function provideTestUpdatePreferences() {
		return [
			[123, 456, HAS_CONFLICT_PREF, 2, '1/17/24'],
			[123, 456, OK_PREF, 2, '1/17/24'],
			[123, 456, PREFER_DATE_PREF, 2, '1/17/24'],
		];
	}

	/*
	 * NOTE: For getShiftIdByDateAndJobId The 'schedule_shifts'
	 * table is empty until a survey is saved, so it is not stable
	 * from season to season.
	 */

}
?>
