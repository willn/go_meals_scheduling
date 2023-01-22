<?php
use PHPUnit\Framework\TestCase;

require_once '../public/globals.php';
require_once '../public/classes/WorkersList.php';


class WorkersListTest extends TestCase {
	private $list;

	public function setUp() : void {
		$this->list = new WorkersList('test');
	}

	/**
	 * Test constructing a worker object.
	 */
	public function testConstruct() {
		$this->assertInstanceOf('WorkersList', $this->list);
	}

	public function testGetWorkers() {
		$result = $this->list->getWorkers();
		$this->assertNotEmpty($result);

		foreach($result as $worker=>$info) {
			$this->assertNotEmpty($worker);
			
			// number of fields per worker record
			$this->assertCount(4, $info);

			$this->assertNotEmpty($info['id']);
			$this->assertNotEmpty($info['username']);
			$this->assertNotEmpty($info['first_name']);
			$this->assertNotEmpty($info['last_name']);
		}
	}
}
?>
