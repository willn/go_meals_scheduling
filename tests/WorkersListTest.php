<?php
global $relative_dir;
$relative_dir = '../public';

require_once '../public/globals.php';
require_once '../public/classes/WorkersList.php';


class WorkersListTest extends PHPUnit_Framework_TestCase {
	private $list;

	public function setUp() {
		$this->list = new WorkersList('test');
	}

	/**
	 * Test constructing a worker object.
	 */
	public function testConstruct() {
		$this->assertInstanceOf(WorkersList, $this->list);
	}

	public function testGetWorkers() {
		$result = $this->list->getWorkers();
		$this->assertNotEmpty($result);

		foreach($result as $worker=>$info) {
			$this->assertNotEmpty($worker);
			
			$this->assertCount(2, $info);
			$this->assertNotEmpty($info['id']);
			$this->assertNotEmpty($info['username']);
		}
	}
}
?>