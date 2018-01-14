<?php
/*
 * Initialize the database to get it ready for use with the meals scheduling
 * work:
 * - remove 'aetherbunny' placeholder user
 * - add 'community' column
 * - loads the user IDs
 * - loads the maximum survey_assignment ID
 * - adds TS users to the survey_assignment table
 * - add meals scheduling tables
 */

require_once('database.php');

$workers = [
	'michellemyers' => [WEEKDAY_ASST_COOK],
];

class AddExternalWorkers extends DatabaseHandler {
	protected $all_workers;
	protected $external_workers = [];
	protected $max_assign_id;

	protected $errors = array();

	public function __construct($workers) {
		parent::__construct();
		$this->external_workers = $workers;
	}

	/**
	 * Process the database initialization.
	 */
	public function run() {
		if (empty($this->external_workers)) {
			echo "external workers  listis empty\n";
			exit;
		}

		// $this->addCommunityColumn();
		$this->loadUserIds();
		$this->loadMaxAssignmentId();
		$this->addWorkers($this->external_workers);

		if (!empty($this->errors)) {
			echo implode("\n", $this->errors) . "\n";
		}
	}

	/**
	 * Load the list of user IDs from auth_user
	 */
	protected function loadUserIds() {
		// -------------- collect list of existing (GO) usernames
		$auth_user_table = AUTH_USER_TABLE;
		$sql = "select id, username from {$auth_user_table} order by id";
		$this->all_workers = array();
		$result = $this->dbh->query($sql);
		if ($result === FALSE) {
			echo "failed to execute: $sql\n";
			exit;
		}

		foreach ($this->dbh->query($sql) as $row) {
			$this->all_workers[$row['username']] = $row['id'];
		}

		if (empty($this->all_workers)) {
			echo "no users found\n";
			exit;
		}
	}

	/**
	 * Load the max survey assignment ID.
	 */
	protected function loadMaxAssignmentId() {
		// -------------- collect max assignment ID
		$sql = 'select id from survey_assignment order by id desc limit 1';
		$this->max_assign_id = NULL;
		foreach ($this->dbh->query($sql) as $row) {
			$this->max_assign_id = $row['id'];
			break;
		}
		if (is_null($this->max_assign_id)) {
			echo "max id is null\n";
			exit;
		}
		echo "max assignment ID is {$this->max_assign_id}\n";
	}

	/**
	 * Add external workers into the initialized database.
	 */
	protected function addWorkers($overrides) {
		$insert_auth_f = <<<EOSQL
INSERT INTO auth_user
	VALUES(%d, '%s', '', '', '%s', '', 0, 1, 0, 0, 0);
EOSQL;

		$season_id = SEASON_ID;
		$insert_assn_f = <<<EOSQL
INSERT INTO survey_assignment
	VALUES(%d, {$season_id}, 'a', %d, %d, 0, 1);
EOSQL;

		$max_user_id = end($this->all_workers);
		foreach($overrides as $username=>$jobs) {
			if (empty($username)) {
				echo "empty username\n";
				exit;
			}

			if (empty($jobs)) {
				echo "empty username\n";
				exit;
			}

			$max_user_id++;
			$sql = sprintf($insert_auth_f, $max_user_id, $username, $username);
			$result = $this->dbh->query($sql);
			if ($result === FALSE) {
				echo "Failed to execute: $sql\n";
				exit;
			}

			$this->max_assign_id++;
			foreach($jobs as $job_id=>$num_shifts) {
				$sql = sprintf($insert_assn_f, $this->max_assign_id,
					$max_user_id, $job_id, $num_shifts);
				$result = $this->dbh->query($sql);

				if ($result === FALSE) {
					echo "Failed to execute: $sql\n";
					exit;
				}
			}
		}
	}
}

$dbi = new AddExternalWorkers($workers);
$dbi->run();
?>
