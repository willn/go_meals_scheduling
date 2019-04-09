<?php
require_once 'respondents.php';

/**
 * Track the list of workers.
 */
class WorkersList {
	private $workers = [];

	/*
	 * Find all of the worker names. We need even those who don't have shifts
	 * this season in case they have overrides.
	 * XXX isn't this redundant...?
	 */
	public function load() {
		$sid = SEASON_ID;
		$assn_table = ASSIGN_TABLE;
		$auth_user_table = AUTH_USER_TABLE;

		// request the first and last name for the select2 avoid/prefer workers
		$sql = <<<EOSQL
			SELECT id, username, first_name, last_name
				FROM {$auth_user_table}
				WHERE id IN
					(SELECT worker_id
						FROM {$assn_table}
						WHERE season_id={$sid}
						GROUP BY worker_id)
				ORDER BY username
EOSQL;

		// global $dbh;
		$dbh = create_sqlite_connection();
		$this->workers = [];
		foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
			$this->workers[$row['username']] = $row;
		}
	}

	/**
	 * Get the list of workers.
	 */
	public function getWorkers() {
		if (empty($this->workers)) {
			$this->load();
		}

		return $this->workers;
	}

	/**
	 * Display the list of workers as links in order to select their survey.
	 */
	public function getWorkersListAsLinks() {
		$workers = $this->getWorkers();

		$out = $lines = '';
		$count = 0;
		ksort($workers);
		$dir = BASE_DIR;
		foreach($workers as $name=>$unused) {
			$lines .= <<<EOHTML
				<li><a href="{$dir}/index.php?worker={$name}">{$name}</a></li>
EOHTML;

			$count++;
			if (($count % 10) == 0) {
				$out .= "<ol>{$lines}</ol>\n";
				$lines = '';
			}
		}

		if ($lines != '') {
			$out .= "<ol>{$lines}</ol>\n";
		}

		return $out;
	}
}
