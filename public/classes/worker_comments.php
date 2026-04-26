<?php

class WorkerComments {
	protected $special_prefs = [
		'avoids',
		'prefers',
		'clean_after_self',
		'bunch_shifts',
		'bundle_shifts',
	];

	/**
	 * Get the comments saved by the workers.
	 * First, load the data, then render it.
	 *
	 * @param string $job_key_clause Fragment of a SQL query
	 */
	public function getWorkerComments($job_key_clause) {
		$data = $this->loadWorkerComments($job_key_clause);
		return $this->renderWorkerComments($data);
	}

	/**
	 * Load all worker's preferences from the database.
	 *
	 * @param string $job_key_clause Fragment of a SQL query
	 */
	public function loadWorkerComments($job_key_clause) {
		// render the comments
		$comments_table = SCHEDULE_COMMENTS_TABLE;
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.username, c.*
				FROM {$auth_user_table} as a, {$comments_table} as c
				WHERE c.worker_id=a.id
					AND a.username in (SELECT u.username
							FROM {$auth_user_table} as u, {$prefs_table} as p,
								{$shifts_table} as s
							WHERE u.id=p.worker_id
								AND p.date_id=s.id
								{$job_key_clause}
							GROUP BY u.username)
				ORDER BY a.username, c.timestamp
EOSQL;

		$request_keys = [
			'username',
			'comments',
			'timestamp',
		];
		$request_keys = array_merge($request_keys, $this->special_prefs);

		$data = [];
		$mysql_api = get_mysql_api();
		foreach($mysql_api->get($sql) as $row) {
			$entry = [];
			foreach($request_keys as $key) {
				$entry[$key] = $row[$key];
			}
			$data[] = $entry;
		}
		return $data;
	}

	/**
	 * Render the "admin" comments section.
	 * This displays a summary of all of the workers who submitted a survey.
	 * This includes the username, the time/date of their last submission,
	 * and preferences such as avoid/prefer to work with people, prefer/avoid
	 * to clean after cooking and then any other loose comments they've made.
	 */
	public function renderWorkerComments($data) {
		$checks = [];
		$check_separator = 'echo "-----------";';

		$out = "<h2 id=\"worker_comments\">Comments</h2>\n";
		foreach($data as $row) {
			$username = $row['username'];

			$requests = '';
			foreach($this->special_prefs as $req) {
				if (empty($row[$req])) {
					continue;
				}
				if ($row[$req] === 'dc') {
					continue;
				}

				$requests .= "{$req}: {$row[$req]}<br>\n";

				// generate check script lines
				switch($req) {
				case 'avoids':
					$avoid_workers = explode(',', $row[$req]);
					foreach($avoid_workers as $av) {
						$checks[] = $check_separator;
						$checks[] = "echo '{$username}' avoid workers '{$av}'";
						$checks[] = "grep '{$username}' " . RESULTS_FILE .
							" | grep '{$av}'";
					}
					break;

				case 'prefers':
					$prefers = explode(',', $row[$req]);
					foreach($prefers as $pr) {
						$checks[] = $check_separator;
						$checks[] = "echo '{$username}' prefers '{$pr}'";
						$checks[] = "grep '{$username}' " . RESULTS_FILE .
							" | grep '{$pr}'";
					}
					break;

				case 'clean_after_self':
					$checks[] = $check_separator;
					$checks[] = "echo '{$username}' clean after self: '{$row[$req]}'";
					$checks[] = "grep '{$username}.*{$username}' " . RESULTS_FILE;
					break;

				/* These aren't currently used...
				case 'bunch_shifts':
				case 'bundle_shifts':
				*/
				}
			}

			$freeform_remark = stripslashes($row['comments']);
			$content = (empty($requests) && empty($freeform_remark)) ? '' :
				"<p>{$requests}<br>{$freeform_remark}</p>\n";

			$out .= <<<EOHTML
		<fieldset>
			<legend>{$username} - {$row['timestamp']}</legend>
			{$content}
		</fieldset>
EOHTML;
		}

		$check_script = implode("\n", $checks);
		$check_script = <<<EOHTML
<h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results">{$check_script}</div>
EOHTML;
		return $out . $check_script;
	}
}

?>
