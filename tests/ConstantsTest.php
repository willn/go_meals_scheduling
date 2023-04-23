<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
#require_once '../public/utils.php';
require_once '../public/constants.php';

/**
 * This is simple example to ensure the testing framework functions properly.
 */
class ConstantsTest extends TestCase {

	/**
	 * Test that DEBUG is turned off
	 */
	public function test_debug_off() {
		$this->assertEquals(FALSE, DEBUG, 'Is DEBUG FALSE?');
		$this->assertEquals(FALSE, DEBUG_FIND_CANCEL_MEALS, 'Is DEBUG_FIND_CANCEL_MEALS False?');
	}
}
?>
