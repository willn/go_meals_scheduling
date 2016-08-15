<?php
/*
 * An abstract database handler
 */

global $relative_dir;
$relative_dir = '../public';
require_once("{$relative_dir}/config.php");
require_once("{$relative_dir}/globals.php");

class DatabaseHandler {
	protected $dbh;

	public function __construct() {
		global $dbh;
		$this->dbh = $dbh;
	}

	/**
	 * Test the database connection
	 */
	public function test() {
		$sql = 'SELECT count() FROM auth_user';
		$count = 0;
		foreach ($this->dbh->query($sql) as $row) {
			$count = $row[0];
			break;
		}

		if ($count == 0) {
			echo "FAIL: unable to select any users!\n";
			return;
		}

		echo "PASS\n";
	}
}

/*
$t = new DatabaseHandler();
$t->test();
*/
?>
