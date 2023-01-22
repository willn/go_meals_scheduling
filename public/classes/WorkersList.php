<?php
require_once 'respondents.php';
require_once 'mysql_api.php';

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
		$sid = get_season_id();
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

		$mysql_api = get_mysql_api();
		$this->workers = [];
		$query = $mysql_api->get($sql);
		foreach ($query as $row) {
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
	 *
	 * @param array $slackers list of usernames for people who have
	 *     not yet filled out their survey.
	 * @param array $assigned_workers list of strings, usernames of
	 *     folks who have been assigned a meals job.
	 */
	public function getWorkersListAsLinks($slackers, $assigned_workers=[]) {
		$workers = $this->getWorkers();
		$slackers_flip = array_flip($slackers);
		$assigned_flip = array_flip($assigned_workers);

		$out = $lines = '';
		$count = 0;
		ksort($workers);
		$dir = BASE_DIR;
		foreach($workers as $name=>$unused) {
			// figure out who has submitted already...
			$extra = isset($slackers_flip[$name]) ? '' : ' &check;';

			if (isset($assigned_flip[$name])) {
				$lines .= <<<EOHTML
<li><a href="{$dir}/index.php?worker={$name}">{$name}</a>{$extra}</li>
EOHTML;
			}
			else {
				// disable links for people who have no shifts
				$lines .= "<li><span>{$name}</span></li>";
			}

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
