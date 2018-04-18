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
	 */
	public function testAddShifts($date, $shifts, $expected) {
		$this->meal->setDate($date);
		$this->meal->initShifts($shifts);
		$assigned = $this->meal->getAssigned();
		$this->assertEquals($expected, $assigned,
			print_r(['expected' => $expected, 'assigned' => $assigned], TRUE));
	}

	public function shiftsProvider() {
		return [
			[
				'12/11/2013',
				[100,200],
				[]
			],
			[
				'04/16/2018',
				[
					MEETING_NIGHT_CLEANER,
					MEETING_NIGHT_ORDERER,
				],
				[
					MEETING_NIGHT_CLEANER => [NULL],
					MEETING_NIGHT_ORDERER => [NULL],
				],
			],
			[
				'04/17/2018',
				[
					WEEKDAY_HEAD_COOK,
					WEEKDAY_ASST_COOK,
					WEEKDAY_CLEANER,
					WEEKDAY_TABLE_SETTER,
				],
				[
					WEEKDAY_HEAD_COOK => [NULL],
					WEEKDAY_ASST_COOK => [NULL, NULL],
					WEEKDAY_CLEANER => [NULL, NULL, NULL],
					WEEKDAY_TABLE_SETTER => [NULL],
				],
			],
			[
				'04/22/2018',
				[
					SUNDAY_HEAD_COOK,
					SUNDAY_ASST_COOK,
					SUNDAY_CLEANER,
				],
				[
					SUNDAY_HEAD_COOK => [NULL],
					SUNDAY_ASST_COOK => [NULL, NULL],
					SUNDAY_CLEANER => [NULL, NULL, NULL],
				],
			],
		];
	}
}
?>
