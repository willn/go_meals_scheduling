<?php
global $relative_dir;
$relative_dir = '../public/';
require_once '../public/classes/xxx.php';

class XXXTest extends PHPUnit_Framework_TestCase {
	private $xxx;

	public function setUp() {
		$this->xxx= new XXX();
	}

	/**
	 * @dataProvider provideZZZ
	 */
	public function testZZZ($input, $expected) {
		$result = $this->xxx->aaa($input);
		$this->assertEquals($expected, $result);
	}

	public function provideZZZ() {
		return [
			[1, 2],
		];
	}
}
?>
