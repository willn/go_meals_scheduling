<?php
/*
 * Initialize the database to get it ready for use with the meals scheduling
 * work:
 * - remove 'aetherbunny' placeholder user
 * - loads the user IDs
 * - loads the maximum ASSIGN_TABLE ID
 * - add meals scheduling tables
 */

global $relative_dir;
$relative_dir = '../public/';

require_once 'database.php';
require_once "{$relative_dir}/constants.php";

class DatabaseInitializer extends DatabaseHandler {
	protected $users = array();
	protected $max_assign_id;

	protected $errors = array();

	/**
	 * Process the database initialization.
	 */
	public function run() {
		$this->removeAetherBunny();
		$this->addSchedulingTables();
		$this->initializeExtraWorkers();

		if (!empty($this->errors)) {
			echo implode("\n", $this->errors) . "\n";
		}
	}

	/**
	 * Remove the placeholder "aether" user from the assignments table.
	 */
	protected function removeAetherBunny() {
		$id = NULL;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
SELECT id, username from {$auth_user_table} where username="aether"
EOSQL;
		foreach ($this->mysql_api->get($sql) as $row) {
			$id = $row['id'];
			break;
		}

		if (empty($id)) {
			$this->errors[] = "could not find bunny's ID!\n";
			return;
		}

		$table = ASSIGN_TABLE;
		$sql = "delete from {$table} where worker_id={$id}";
		$this->mysql_api->query($sql);
		#!# need to add some error checking here... since table may not exist

		// confirm this worked
		$sql = "SELECT count(*) from {$table} where worker_id={$id}";
		echo "SQL:$sql\n";
		foreach ($this->mysql_api->get($sql) as $row) {
			if ($row[0] != 0) {
				$this->errors[] = "Aether Bunny assignments NOT removed";
				return;
			}
		}

		echo "Aether Bunny entries removed\n";
		return;
	}

	/**
	 * Add tables to the database needed for recording scheduling.
	 */
	protected function addSchedulingTables() {
		$schema_sql_file = '../sql/scheduling_survey_schema.sql';
		if (!file_exists($schema_sql_file)) {
			$this->errors[] = "Could not find survey schema file\n";
			return;
		}

		$sql = file_get_contents($schema_sql_file);
		$this->mysql_api->query($sql);
		echo "Added scheduling tables\n";
	}

	/**
	 * Get a list of existing usernames from the database.
	 */
	protected function getUsernamesFromDb() {
		$users = [];
		$key = 'username';
		$auth_user_table = AUTH_USER_TABLE;
		$sql = "SELECT {$key} FROM {$auth_user_table}";
		foreach($this->mysql_api->get($sql, PDO::FETCH_ASSOC) as $row) {
			$name = array_get($row, $key);
			$users[$name] = TRUE;
		}
		return $users;
	}

	/**
	 * Insert a user entry into the database.
	 */
	protected function insertUserEntry($username) {
		if (!preg_match('/^[A-Za-z\.]+$/', $username)) {
			echo "Not a valid name: $username\n";
			return;
		}

		$sql = <<<EOSQL
INSERT INTO auth_user
	(username, first_name, last_name, email, password, is_staff,
		is_active, is_superuser, last_login, date_joined)
	VALUES('{$username}', '{$username}', '{$username}', '', '', 0, 1,
		0, '', '')
EOSQL;
		if ($this->mysql_api->query($sql) !== FALSE) {
			echo "Added {$username}\n";
		}
	}

	/**
	 * Get the user's worker ID.
	 */
	protected function getUserId($username) {
		if (isset($this->users[$username])) {
			return $this->users[$username];
		}

		$key = 'id';
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
SELECT {$key} FROM {$auth_user_table} WHERE username='{$username}';
EOSQL;

		$id = NULL;
		foreach($this->mysql_api->get($sql, PDO::FETCH_ASSOC) as $row) {
			$id = array_get($row, $key);
			break;
		}
		$this->users[$username] = $id;

		return $id;
	}

	/**
	 * Insert an assignment entry into the database.
	 */
	protected function insertAssignment($username) {
		$worker_id = $this->getUserId($username);

		$season_id = get_season_id();
		$table = ASSIGN_TABLE;
		$sql = <<<EOSQL
INSERT INTO {$table}
	(season_id, type, worker_id, job_id, instances, reason_id)
	VALUES($season_id, 'a', '{$worker_id}', 0, 0, 0);
EOSQL;

		if ($this->mysql_api->query($sql) !== FALSE) {
			echo "Added assignment for {$username}\n";
		}
	}

	/**
	 * Add any users who aren't mentioned in the work database.
	 * Two tables: auth_user and ASSIGN_TABLE
	 *
	 */
	protected function initializeExtraWorkers() {
		$extras = get_num_shift_overrides();
		if (empty($extras)) {
			echo "No extra users to initialize.\n";
			return;
		}

		$users = $this->getUsernamesFromDb();
		foreach($extras as $name => $assignments) {
			if (!isset($users[$name])) {
				$this->insertUserEntry($name);
			}

			$this->insertAssignment($name);
		}
	}
}

$dbi = new DatabaseInitializer();
$dbi->run();
?>
