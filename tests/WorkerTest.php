<?php
require_once '../public/classes/worker.php';

class WorkerTest extends PHPUnit_Framework_TestCase {
	private $worker;

	public function setUp() {
		$this->worker = new Worker('test');
	}

	/**
	 * Test constructing a worker object.
	 */
	public function testConstruct() {
		$this->assertInstanceOf(Worker, $this->worker);
	}

	/**
	 * Test set & get of ID
	 */
	public function testGetSetId() {
		$id = 123;
		$this->worker->setId($id);
		$this->assertEquals($this->worker->getId(), $id); 
	}
}
?>
