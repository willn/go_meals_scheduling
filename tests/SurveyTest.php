<?php
/*
set_include_path('../' . PATH_SEPARATOR . '../public/');
global $relative_dir;
$relative_dir = '../auto_assignments/';
require_once '../auto_assignments/assignments.php';
*/

global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../public/classes/survey.php';

class SurveyTest extends PHPUnit_Framework_TestCase {
	private $assignments;

	public function setUp() {
		$this->survey = new Survey();
		$this->survey->setWorker('willie', 59);
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
		$example1 = '{"username":"willie","posted":"1","avoid_worker":["amyh","annie"],"prefer_worker":["becky","bennie"],"clean_after_self":"no","comments":"it\'s ok","date_11\/4\/2019_4592":"2","date_11\/4\/2019_4596":"2","date_11\/5\/2019_4592":"1","date_11\/5\/2019_4596":"1","date_11\/11\/2019_4592":"2","date_11\/11\/2019_4596":"2","date_11\/12\/2019_4592":"1","date_11\/12\/2019_4596":"1","date_11\/13\/2019_4592":"0","date_11\/13\/2019_4596":"0","date_11\/19\/2019_4592":"1","date_11\/19\/2019_4596":"1","date_11\/20\/2019_4592":"0","date_11\/20\/2019_4596":"0","date_11\/26\/2019_4592":"1","date_11\/26\/2019_4596":"1","date_11\/27\/2019_4592":"0","date_11\/27\/2019_4596":"0","date_12\/2\/2019_4592":"2","date_12\/2\/2019_4596":"2","date_12\/3\/2019_4592":"1","date_12\/3\/2019_4596":"1","date_12\/9\/2019_4592":"2","date_12\/9\/2019_4596":"2","date_12\/10\/2019_4592":"1","date_12\/10\/2019_4596":"1","date_12\/11\/2019_4592":"0","date_12\/11\/2019_4596":"0","date_12\/17\/2019_4592":"1","date_12\/17\/2019_4596":"1","date_12\/18\/2019_4592":"0","date_12\/18\/2019_4596":"0","date_12\/23\/2019_4592":"2","date_12\/23\/2019_4596":"2","date_12\/30\/2019_4592":"2","date_12\/30\/2019_4596":"2"}';

		$result1 = "replace into schedule_comments
	values(
		59,
		datetime('now'),
		'it''s ok',
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
}
?>
