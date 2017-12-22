<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/utils.php';
require_once '../public/config.php';

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
	 * @dataProvider provide_get_season_name
	 */
	public function test_get_season_name($input, $expected) {
		$result = get_season_name($input);
		$this->assertEquals($result, $expected);
	}

	public function provide_get_season_name() {
		return array(
			array(strtotime('Jan 1st, 2015'), 'winter'),
			array(strtotime('Feb 28th, 2015'), 'winter'),
			array(strtotime('Mar 1st, 2015'), 'summer'),
			array(strtotime('June 30, 2015'), 'summer'),
			array(strtotime('July 1st, 2015'), 'fall'),
			array(strtotime('Oct 31st, 2015'), 'fall'),
			array(strtotime('Nov 1st, 2015'), 'winter'),
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
}
?>
