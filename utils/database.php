<?php
/*
 * An abstract database handler
 */

global $relative_dir;
$relative_dir = '../public';
require_once("{$relative_dir}/config.php");
require_once("{$relative_dir}/globals.php");

class DatabaseHandler {
	protected $mysql_api;

	public function __construct() {
		$this->mysql_api = get_mysql_api();
	}

	/**
	 * Test the database connection
	 */
	public function test() {
		$auth_user_table = AUTH_USER_TABLE;
		$sql = "SELECT count() FROM {$auth_user_table}";
		$count = 0;
		foreach ($this->mysql_api->get($sql) as $row) {
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
