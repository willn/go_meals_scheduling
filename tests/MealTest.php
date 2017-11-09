<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/config.php';
require_once '../auto_assignments/meal.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class MealTest extends PHPUnit_Framework_TestCase {
	protected $meal;

	public function setUp() {
		$this->meal = new Meal('foo', '1/1/2000', 10);
	}

	/**
	 * @dataProvider datesProvider
	 */
	public function testDayOfWeek($date, $expected) {
		$this->meal->setDate($date);
		$this->assertEquals($expected, $this->meal->getDayOfWeek());
	}

	public function datesProvider() {
		return array(
			array('Wed Dec 11 23:15:42 EST 2013', 4),
			array('12/31/13', 2),
			array('10/6/13', 7)
		);
	}

	/**
	 * Test the mutator for setting and getting a date.
	 */
	public function testSetAndGetDate() {
		$date = '5/6/2013';
		$this->meal->setDate($date);
		$this->assertEquals($date, $this->meal->getDate());
	}

	/**
	 * @dataProvider shiftsProvider
	public function testAddShifts($date, $shifts, $expected) {
		$this->meal->setDate($date);
		$this->meal->addShifts($shifts);
		$assigned = $this->meal->getAssigned();
		$this->assertEquals($expected, $assigned,
			print_r(array('expected' => $expected, 'assigned' => $assigned)));
	}

	public function shiftsProvider() {
		return array(
			array(
				'12/11/2013',
				array(0 => 100, 1 => 200),
				array()
			),
			array(
				'12/11/2013',
				array(0 => 2095, 1 => 2098),
				array(
					2095 => array(0 => NULL),
					2098 => array(0 => NULL),
				),
			),
			array(
				'12/15/2013',
				array(0 => 2093, 1 => 2094, 2 => 2097),
				array(
					2093 => array(0 => NULL),
					2094 => array(0 => NULL, 1 => NULL),
					2097 => array(0 => NULL, 1 => NULL, 2 => NULL),
				),
			),
			array(
				'12/11/2013',
				array(0 => 2093, 1 => 2094, 2 => 2097),
				array(),
			),
		);
	}
*/
}
?>
