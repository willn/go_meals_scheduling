<?php

class Respondents {
	protected $ids_clause;
	protected $dbh;


	public function __construct() {
		global $dbh;
		$this->dbh = $dbh;

		$this->ids_clause = get_job_ids_clause();
	}

	/**
	 * Return an array of all workers usernames in the system
	 */
	public function getWorkers() {
		// all the workers who should respond:
		$sid = SEASON_ID;
		$assn_table = ASSIGN_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.id, a.username
				FROM {$assn_table} as sa, {$auth_user_table} as a
				WHERE sa.season_id={$sid}
					AND a.id=sa.worker_id
					AND ({$this->ids_clause})
				GROUP BY sa.worker_id
EOSQL;
		$all_workers = [];
		foreach ($this->dbh->query($sql) as $row) {
			$all_workers[$row['id']] = $row['username'];
		}

		$num_shift_overrides = get_num_shift_overrides();
		$all_workers += array_keys($num_shift_overrides);

		sort($all_workers);
		return $all_workers;
	}


	/**
	 * Return an array of the people who have responded to the
	 * survey already.
	 */
	public function getResponders() {
		// list of workers who are assigned.
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.id as id, a.username as username
				FROM {$auth_user_table} as a, {$prefs_table} as p
				WHERE a.id=p.worker_id
				GROUP BY p.worker_id;
EOSQL;
		$responders = [];
		foreach ($this->dbh->query($sql) as $row) {
			$responders[$row['id']] = $row['username'];
		}

		return $responders;
	}


	public function getTimeRemaining() {
		$seconds = DEADLINE - time();
		$deadline = date('g:ia l, F j', DEADLINE);

		if ($seconds <= 0) {
			return <<<EOHTML
				<div class="remaining">closed at {$deadline}</div>
EOHTML;
		}

		$days = floor($seconds/60/60/24);
		$day_string = '';
		if ($days > 0) {
			$day_string = $days . ' days, ';
		}

		$parts = array();
		$hours = $seconds/60/60%24;
		if ($hours > 0) {
			$parts[] = $hours;
		}

		$mins = $seconds/60%60;
		$parts[] = sprintf('%02d', $mins);

		$secs = $seconds%60;
		$parts[] = sprintf('%02d', $secs);


		// warning
		$color = '#f00';
		// 3 hours
		if ($seconds > 10800) {
			$color = '#fc0';
		}
		if ($seconds > 86400) {
			$color = '#eee';
		}

		$out = implode(':', $parts);
		return <<<EOHTML
			<div class="remaining">
				countdown: {$day_string} {$out} until <b>{$deadline}</b>
			</div>
EOHTML;
	}


	/**
	 * Display the menu of worker names so to choose which name to save preferences.
	 */
	public function renderWorkerMenu() {
		$workers_list = new WorkersList();
		if (empty($workers_list)) {
			echo "<h2>No workers configured</h2>\n";
			return;
		}

		$slackers = $this->getNonResponders();

		// display names for "login"
		print <<<EOHTML
			<div class="workers_list">
				{$workers_list->getWorkersListAsLinks($slackers)}
			</div>
EOHTML;
	}

	/**
	 * Return a brief statement of how many people have responded, percentage,
	 * and then an SVG percentage bar graph.
	 *
	 * @param[in] display_usernames boolean default FALSE. If true, then the
	 *     email addresses of the slackers are shown as well.
	 * #!# move this to workerslist?
	 */
	public function getSummary($display_usernames=FALSE) {
		$all_workers = $this->getWorkers();
		$num_workers = count($all_workers);

		$responders = $this->getResponders();
		$num_responders = count($responders);

		// generate summary data
		$slackers = $this->getNonResponders();
error_log(__FILE__ . ' ' . __LINE__ . " slackers: " . var_export($slackers, TRUE));
		$num_slackers = count($slackers);
		$percentage = number_format(($num_responders / $num_workers) * 100, 1);
		$non_respond = 100 - $percentage;

		$missing_emails = '';
		// optionally display the email addresses of the slackers
		if ($display_usernames) {
			$emails = [];
			foreach($slackers as $id=>$name) {
				if (strstr($name, '@')) {
					$emails[] = "<b>{$name}</b>";
					continue;
				}
				$emails[] = $name . DOMAIN;
			}

			$list = implode(', ', $emails);
			$missing_emails = <<<EOHTML
				<h4>Non-responders:</h4>
				<div class="subtle_text">{$list}</div>
EOHTML;
		}

		return <<<EOHTML
			<div>
				Responses: {$num_responders} / {$num_workers}
				<span style="color:#999;">({$percentage}%)</span>
				{$this->renderPercentageBar($percentage)}
				{$missing_emails}
			</div>
EOHTML;
	}

	/**
	 * Get the list of people who have not submitted their survey.
	 * @return array list of usernames.
	 */
	public function getNonResponders() {
		$all_workers = $this->getWorkers();
		$responders = $this->getResponders();
		return array_diff($all_workers, $responders);
	}

	/**
	 * Render the percentage bar to display how many people have done the survey.
	 * @param[in] percentage float (0-100)
	 * @return svg for displaying the percentage bar.
	 */
	public function renderPercentageBar($percentage) {
		$bar_width = 300;
		$bar_height = 30;
		$respond_share = $bar_width * ($percentage / 100);
		$ticks = range(($bar_width / 5), $bar_width, ($bar_width / 5));

		$tick_lines = '';
		foreach($ticks as $t) {
			$tick_lines .= <<<EOSVG
<rect x="{$t}" y="0" width="1" height="{$bar_height}" style="fill:#99c;"/>
EOSVG;
		}

		return <<<EOSVG
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="{$bar_width}px" height="{$bar_height}px">
	<rect x="0" y="0" width="{$bar_width}" height="{$bar_height}" style="fill:#C6D9FD;"/>
	<rect x="0" y="0" width="{$respond_share}" height="{$bar_height}" style="fill:#4D89F9;"/>
	{$tick_lines}
</svg>
EOSVG;

	}
}
