<?php

class Respondents {
	protected $mysql_api;

	protected $job_filter_id;

	/**
	 * @param int $job_filter_id the job ID to filter the database
	 *     query from. IF empty, then do not filter the request.
	 */
	public function __construct($job_filter_id=NULL) {
		$this->mysql_api = get_mysql_api();
		$this->job_filter_id = intval($job_filter_id);
	}

	/**
	 * Return an array of all workers usernames in the system.
	 */
	public function getWorkers() {
		$job_filter_clause = empty($this->job_filter_id) ?
			' AND (' . get_job_ids_clause() . ')' :
			" AND job_id='{$this->job_filter_id}'";

		$sid = get_season_id();
		$assn_table = ASSIGN_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT a.id, a.username
				FROM {$assn_table} as sa, {$auth_user_table} as a
				WHERE sa.season_id={$sid}
					AND a.id=sa.worker_id
					{$job_filter_clause}
				GROUP BY sa.worker_id
EOSQL;
		$all_workers = [];
		foreach ($this->mysql_api->get($sql) as $row) {
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
		foreach ($this->mysql_api->get($sql) as $row) {
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
		$num_hours = ($seconds / 60 / 60) % 24;
		if ($num_hours > 0) {
			$parts[] = $num_hours;
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
		$non_responders = $this->getNonResponders();
		$assigned_workers = $this->getWorkers();

		// display names for "login"
		print <<<EOHTML
			<div class="workers_list">
				{$workers_list->getWorkersListAsLinks($non_responders, $assigned_workers)}
			</div>
EOHTML;
	}

	/**
	 * Return a brief statement of how many people have responded, percentage,
	 * and then an SVG percentage bar graph.
	 *
	 * @param bool $display_usernames default FALSE. If true, then the
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
		$num_slackers = count($slackers);
		$percentage = ($num_responders / $num_workers) * 100;
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

		$percentage_f = number_format($percentage, 1);
		return <<<EOHTML
			<div>
				Responses: {$num_responders} / {$num_workers}
				<span style="color:#999;">({$percentage_f}%)</span>
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
	 * @param float $percentage (0-100)
	 * @return string svg for displaying the percentage bar.
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
