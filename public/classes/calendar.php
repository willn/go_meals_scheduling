<?php
require_once 'globals.php';
require_once 'WorkersList.php';
require_once 'mysql_api.php';

class Calendar {
	const BLANK_DAY_HTML = '<td class="blank"></td>';
	const FARM_MSG = '<p class="notice">Farm meal night</p>';

	protected $web_display = TRUE;
	protected $key_filter = 'all';

	protected $assignments = [];

	protected $holidays;

	protected $is_report = FALSE;

	protected $num_shifts = [
		'meeting' => 0,
		'sunday' => 0,
		'weekday' => 0,
		'brunch' => 0,
	];

	protected $special_prefs = [
		'avoids',
		'prefers',
		'clean_after_self',
		'bunch_shifts',
		'bundle_shifts',
	];

	protected $season_months = [];

	public function __construct($season_months=[]) {
		// 'all' is the default, so only change if it's numeric
		if (isset($_GET['key']) && is_numeric($_GET['key'])) {
			$this->key_filter = $_GET['key'];
		}

		$this->holidays = get_holidays();
		$this->season_months = !empty($season_months) ?
			$season_months : get_current_season_months();
	}

	/**
	 * Set the season's months.
	 *
	 * @param array $season list of months in the season to be used.
	 */
	public function setSeasonMonths($season) {
		$this->season_months = $season;
	}

	public function setIsReport($setting=TRUE) {
		$this->is_report = $setting;
	}

	/**
	 * Don't build a display for the web, just get the info needed.
	 * NOTE: This is used by the auto-assignment routine, to get the needed
	 * dates.
	 */
	public function disableWebDisplay() {
		$this->web_display = FALSE;
	}

	public function enableWebDisplay() {
		$this->web_display = TRUE;
	}

	/**
	 * Load the assignments from disk into memory.
	 */
	public function loadAssignments($file) {
		if (!file_exists($file)) {
			return FALSE;
		}

		$this->assignments = json_decode(file_get_contents($file), true);
	}

	/**
	 * Render the "quick links" section listing the various months in the season for
	 * quick navigation.
	 *
	 * @param array $current_season string list of the month numbers and names.
	 */
	public function renderMonthsOverlay($current_season) {
		$out = '';
		foreach($current_season as $month_num=>$month_name) {
			$out .= <<<EOHTML
				<li><a href="#{$month_name}">{$month_name}</a></li>
EOHTML;
		}

		// add admin-only options
		if (!isset($_SESSION['access_type']) ||
			($_SESSION['access_type'] != 'guest')) {
			$out .= <<<EOHTML
				<li><a href="#worker_comments">comments</a></li>
				<li><a href="#confirm_checks">confirm checks</a></li>
EOHTML;
		}

		return <<<EOHTML
			<ul id="summary_overlay">
				<li>Quick links:</li>
				{$out}
				<li><a href="#end">end</a></li>
			</ul>
EOHTML;
	}


	/**
	 * Get the weekly spacer html.
	 * @return string html for a spacer.
	 */
	public function getWeeklySpacerHtml() {
		$preferences = get_pref_names();
		$options = '';
		foreach($preferences as $val => $label) {
			$options .= "\t\t\t\t<a class=\"{$label}\">{$label}</a>\n";
		}

		return <<<EOHTML
			<td class="week_selector">
				This week:
{$options}
			</td>
EOHTML;
	}

	/**
	 * Get the weekday selector html
	 * @param int $day_num the meal number for the season, for debugging.
	 * @param string $day_of_week the short name for the day of the week, e.g. 'Tue'.
	 * @return string the rendered html.
	 */
	public function getWeekdaySelectorHtml($day_num, $day_of_week) {
		if (!is_numeric($day_num)) {
			return '';
		}
		if (empty($day_of_week)) {
			return '';
		}

		$preferences = get_pref_names();
		$options = '';
		foreach($preferences as $val => $label) {
			$options .= "\t\t\t\t<a class=\"{$label}\">{$label}</a>\n";
		}

		$short_day = substr($day_of_week, 0, 3);
		return <<<EOHTML
			<td class="weekday_selector weekday_num_{$day_num}">
				{$short_day}:<br>
{$options}
			</td>
EOHTML;
	}

	/**
	 * Figure out which dates have which shifts applied to them.
	 *
	 * @param Worker $worker (optional) either NULL or instance of Worker
	 *     If set, then this calendar will act in survey mode, presenting
	 *     the worker with the list of shifts they need to fill for each day
	 *     that the shift is available. If not set, then use report mode and show
	 *     a summary of all available workers for that date.
	 * @param array $availability a structured array of when people
	 *     are available to work. The first level is the date, then the job ID,
	 *     then the preference level (2 - prefer, 1 - OK) which points to an
	 *     array listing the usernames who fit into that preference
	 *     level. Currently only used for reporting.
	 *     Example:
	 * '11/11/2018' => [
	 *		4597 => [
	 *				2 => ['alice', 'bob'],
	 *				1 => ['charlie', 'doug', 'edward', 'fred'],
	 *		],
 	 * ]
	 * @param array $non_respondents list of usernames who did not
	 *     respond to the survey, filtered to only include the list of
	 *     workers for the currently viewed job.
	 * @return array the dates_and_shifts array or a single entry
	 *     with the html to render all the calendar months of the season.
	 */
	public function evalDates($worker=NULL, $availability=NULL,
		$non_respondents=[]) {

		$sunday_jobs = get_sunday_jobs();
		$brunch_jobs = get_brunch_jobs();
		$mtg_nights = get_mtg_nights();

		$mtg_day_count = [];
		foreach(array_keys($mtg_nights) as $dow) {
			$mtg_day_count[$dow] = 0;
		}

		$weekly_selector = '';
		$saved_prefs = [];
		if (!is_null($worker)) {
			// XXX is worker an object or an array?
			$saved_prefs = $this->getSavedPrefs($worker->getId());
			$weekly_selector = $this->getWeeklySpacerHtml();
		}

		$selectors = $this->renderDaySelectors(!is_null($worker));

		$meal_days = get_weekday_meal_days();

		$day_of_week = NULL;
		$out = '';
		$dates_and_shifts = [];
		// for each month in the season
		$month_count = 0;
		$season_year = SEASON_YEAR;
		foreach($this->season_months as $month_num=>$month_name) {
			$month_count++;

			// if this season wraps around to another year
			if (($month_count !== 1) && ($month_name === 'January')) {
				$season_year++;	
			}

			$month_entries = [];
			$month_week_count = 1;

			// get unix ts
			$start_ts = strtotime("{$month_name} 1, " . $season_year);
			$days_in_month = date('t', $start_ts);

			// figure out the first day of the starting month
			if (is_null($day_of_week)) {
				// pull out the dow
				$day_of_week = date('w', $start_ts);
			}

			$week_num = date('W', $start_ts);
			$week_id = "week_{$week_num}_{$month_num}";
			$table = <<<EOHTML
				<tr class="week" id="{$week_id}">
					{$weekly_selector}
EOHTML;
			for($dw = 0; $dw < $day_of_week; $dw++) {
				$table .= self::BLANK_DAY_HTML;
			}

			foreach(array_keys($mtg_day_count) as $key) {
				$mtg_day_count[$key] = 0;
			}

			// for each day in the current month
			for ($day_num=1; $day_num<=$days_in_month; $day_num++) {
				$tally = '';

				// if this is sunday... add the row start
				if (($day_of_week == 0) && ($day_num != 1)) {
					$week_num++;
					$week_id = "week_{$week_num}_{$month_num}";
					$table .= <<<EOHTML
						<tr class="week" id="{$week_id}">
							{$weekly_selector}
EOHTML;
				}

				#!# need to fix the DOM validity of this id value
				$date_string = "{$month_num}/{$day_num}/{$season_year}";
				$cell = '';

				$meal_type = get_meal_type_by_date($date_string);
				$is_done = FALSE;
				$type = '';
				$jobs = [];
				switch($meal_type) {
					case HOLIDAY_NIGHT:
						$cell = '<span class="skip">holiday</span>';
						$is_done = TRUE;
						break;

					/*
					 * Skip dates. Don't display selection options
					 * for this date if we know in advance that we'll be cancelling
					 * a meal on this date. Similar functionality to holidays.
					 * https://github.com/willn/go_meals_scheduling/issues/17
					 */
					case SKIP_NIGHT:
						$cell = '<span class="skip">skip</span>';
						$is_done = TRUE;
						break;

					case BRUNCH_MEAL:
						// skip if not first saturday
						if (!is_first_saturday($date_string)) {
							$is_done = TRUE;
						}
						else {
							$type = 'brunch';
							$this->num_shifts[$type]++;
							$jobs = get_brunch_jobs();
							$day_of_week = date('w', strtotime($date_string));
						}
						break;

					case SUNDAY_MEAL:
						$type = 'sunday';
						$this->num_shifts[$type]++;
						$jobs = get_sunday_jobs();
						break;

					case MEETING_NIGHT_MEAL:
						$type = 'meeting';
						$this->num_shifts[$type]++;
						$jobs = get_mtg_jobs();
						break;

					case WEEKDAY_MEAL:
						$type = 'weekday';
						$this->num_shifts[$type]++;
						$jobs = get_weekday_jobs();
						break;

					case NOT_A_MEAL:
						$is_done = TRUE;
						break;
				}

				$message = '';
				if (!$is_done) {
					if (!$this->web_display) {
						$dates_and_shifts = $this->addJobsToDatesAndShifts(
							$jobs, $dates_and_shifts, $date_string);
					}
					else if (is_null($worker)) {
						$cell = $this->generateReportCell($date_string, $availability,
							$tally, $type, $non_respondents);
					}
					else {
						$cell = $this->generateReportCellForWorker($worker,
							$date_string, $type);
					}

					$message = empty($cell) ? '' : 
						$this->addMessage($day_of_week, $month_num);
				}

				// #!# suppress this display unless it's report mode...
				// list the auto-assigned workers
				if (isset($this->assignments[$date_string])) {
					$assn = '';
					if ($this->key_filter == 'all') {
						$assn = $this->assignments[$date_string];
					}
					else if (isset($this->assignments[$date_string][$this->key_filter])) {
						$assn = $this->assignments[$date_string][$this->key_filter];
					}

					if ($assn != '') {
						$cell .= '<pre>' . print_r($assn, true) . '</pre>';
					}
				}

				// render an individual calendar day table cell
				$table .= <<<EOHTML
				<td class="dow_{$day_of_week}">
					<div class="date_number">{$day_num}{$tally}</div>{$cell}{$message}
				</td>

EOHTML;

				// close the row at end of week (saturday)
				if ($day_of_week == 6 || $day_num == $days_in_month) {
					$table .= "\n</tr>\n";
					$month_week_count++;
				}

				$day_of_week++;
				// wrap-around
				if ($day_of_week == 7) {
					$day_of_week = 0;
				}
			}

			if (!$this->web_display) {
				continue;
			}

			$survey = ($this->is_report) ? '' : 'survey';
			$quarterly_month_ord = ($month_num % 4);
			$out .= <<<EOHTML
			<div id="{$month_name}" class="month_wrapper">
				<h3 class="month {$survey}">
					{$month_name} {$season_year}</h3>
				<div class="surround month_{$quarterly_month_ord}">
					<table>
						{$selectors}
						{$table}
					</table>
				</div>
			</div>
EOHTML;
		}

		if (!$this->web_display) {
			return $dates_and_shifts;
		}

		return [$out];
	}

	/**
	 * Create the short code for recording which type of meal this is, and the
	 * count of how many we have observed.
	 * @param string $type the type of meal this is servicing. Possible types
	 *     are: 'sunday', 'meeting', 'weekday', 'brunch'
	 * @return string of html to be rendered at the bottom of each calendar
	 *     date which contains a meal. Example: '[W13]'
	 */
	function generateDateCount($type) {
		$code = '';
		switch($type) {
			case 'sunday':
				$code = 'S';
				break;
			case 'brunch':
				$code = 'B';
				break;
			case 'meeting':
				$code = 'M';
				break;
			case 'weekday':
				$code = 'W';
				break;
		}

		return <<<EOHTML
<span class="type_count">[{$code}{$this->num_shifts[$type]}]</span>
EOHTML;
	}

	/**
	 * Generate the date cell for a report
	 *
	 * @param string $date_string text representing a date, i.e. '12/6/2009'
	 * @param array $availability a structured array of when people
	 *     are available to work.
	 * @param string $tally a count of each meal-type instance.
	 * @param string $type the type of meal this is servicing. Possible types
	 *     are: 'sunday', 'meeting', 'weekday', 'brunch'
	 * @param array $non_respondents list of usernames who did not
	 *     respond to the survey, filtered to only include the list of
	 *     workers for the currently viewed job.
	 * @return string the content to be rendered in the cell variable.
	 */
	function generateReportCell($date_string, $availability, &$tally, $type,
		$non_respondents) {

		if (empty($availability)) {
			return '';
		}

		$tally .= $this->generateDateCount($type);

		$date_availability = array_key_exists($date_string, $availability) ?
			$availability[$date_string] : [];

		// report the available workers
		return $this->list_available_workers_for_date($date_availability,
			($type === 'brunch'), $non_respondents);
	}


	/**
	 * Generate the date cell for a single worker's report
	 *
	 * @param object $worker An instance of a Worker object.
	 * @param string $date_string text representing a date, i.e. '12/6/2009'
	 * @param string $type The type of meal ('weekday', 'meeting', 'sunday')
	 * @return string the content to be rendered in the cell variable.
	 */
	public function generateReportCellForWorker($worker, $date_string, $type) {
		$cell = '';
		$jobs = [];

		switch($type) {
			case 'sunday':
				$jobs = get_sunday_jobs();
				break;
			case 'brunch':
				$jobs = get_brunch_jobs();
				break;
			case 'meeting':
				$jobs = get_mtg_jobs();
				break;
			case 'weekday':
				$jobs = get_weekday_jobs();
				break;
		}

		// for report
		if (!is_object($worker)) {
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " null worker sent");
			return '';
		}

		$saved_prefs = $this->getSavedPrefs($worker->getId());
		foreach($jobs as $job_id=>$name) {
			// if this worker doesn't have this job, then skip
			if (!array_key_exists($job_id, $worker->getTasks())) {
				continue;
			}

			// recall the value that was saved already - if any exists
			$saved_pref_val =
				isset($saved_prefs[$job_id][$date_string]) ?
					$saved_prefs[$job_id][$date_string] : DEFAULT_AVAIL;

			$cell .= $this->renderSurveyJob($date_string, $name, $job_id,
				$saved_pref_val);
		}

		return $cell;
	}

	/**
	 * #!#
	 */
	public function addJobsToDatesAndShifts($list_of_jobs,
		$dates_and_shifts, $date_string) {

		$jobs_list = array_keys($list_of_jobs);
		if (!empty($jobs_list)) {
			$dates_and_shifts[$date_string] = $jobs_list;
		}

		return $dates_and_shifts;
	}


	/**
	 * Add a notice message to display on certain dates.
	 *
	 * @param int $day_of_week the number of the current day of the week.
	 * @param int $month_num the number of the current month.
	 * @return string if applicable, an html message about what
	 *     happens on this calendar date.
	 */
	public function addMessage($day_of_week, $month_num) {
		if (!doing_csa_farm_meals()) {
			return '';
		}

		// XXX
		if (($day_of_week === TUESDAY) && (($month_num > MAY) && ($month_num < NOVEMBER)))  {
			return self::FARM_MSG;
		}
		return '';
	}

	/**
	 * Render the HTML for the header of each month table in the calendar.
	 * To be specific, this includes the colored row with the day names
	 * and then the next row with the quick links to apply "prefer",
	 * "OK", or "avoid_shift" to all of that day of the week for this month.
	 *
	 * @param bool $has_worker boolean (optional, default FALSE) If TRUE,
	 *     then this calendar is in survey mode, not report mode.
	 * @return string HTML the html to display the calendar headers.
	 */
	public function renderDaySelectors($has_worker=FALSE) {
		$day_labels = '';
		$day_num = 0;
		$day_selectors = '';
		$meal_days = get_weekday_meal_days();

		foreach(get_days_of_week() as $dow) {
			$day_labels .= <<<EOHTML
				<th class="day_of_week">{$dow}</th>
EOHTML;

			// only create the selectors when a worker is specified, meaning
			// for survey mode
			if ($has_worker) {
				if (in_array($day_num, array_merge([0], $meal_days))) {
					$day_selectors .= $this->getWeekdaySelectorHtml($day_num, $dow);
				}
				else {
					$day_selectors .= self::BLANK_DAY_HTML;
				}
			}
			$day_num++;
		}

		$weekly_spacer = !$has_worker ? '' :
			'<td width="1%"><!-- weekly spacer --></td>';

		$day_labels = <<<EOHTML
			<tr class="day_labels">
				{$weekly_spacer}
				{$day_labels}
			</tr>
EOHTML;

		$selectors = '';
		if ($has_worker) {
			$selectors =<<<EOHTML
				<tr class="weekdays">
					{$weekly_spacer}
					{$day_selectors}
				</tr>
EOHTML;
		}

		return $day_labels . "\n" . $selectors;
	}

	/*
	 * Find the saved preferences for this worker
	 *
	 * @param string $worker_id the ID number of the current worker
	 * @return array of already-saved preferences for this worker. If empty,
	 *     then this worker has not taken the survey yet.
	 */
	private function getSavedPrefs($worker_id) {
		if (!is_numeric($worker_id)) {
			return [];
		}

		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$sql = <<<EOJS
			select s.id, s.date_shift_string, s.job_id, p.pref
				FROM {$shifts_table} as s, {$prefs_table} as p
				WHERE s.id=p.date_id
					AND worker_id={$worker_id}
					ORDER BY s.date_shift_string, s.job_id
EOJS;

		$mysql_api = get_mysql_api();
		$data = [];
		foreach ($mysql_api->get($sql) as $row) {
			if (!array_key_exists($row['job_id'], $data)) {
				$data[$row['job_id']] = [];
			}
			$data[$row['job_id']][$row['date_shift_string']] = $row['pref'];
		}

		return $data;
	}


	/*
	 * Draw an individual entry for one job survey (select list) on a given date.
	 *
	 * @param string $date_string text representing a date, i.e. '12/6/2009'
	 * @param string $name string name of the job
	 * @param int $key the job ID
	 * @param int $saved_pref the preference score previously saved
	 * @return string the rendered HTML
	 */
	public function renderSurveyJob($date_string, $name, $key, $saved_pref) {
		$preferences = get_pref_names();

		if (!isset($saved_pref)) {
			$saved_pref = DEFAULT_AVAIL;
		}

		$options = '';
		foreach($preferences as $val => $label) {
			$selected = ($saved_pref == $val) ? ' selected' : '';
			$options .= "\t\t\t\t<option value=\"{$val}\"{$selected}>{$label}</option>\n";
		}

		$id = "{$date_string}_{$key}";
		return <<<EOHTML
			<div class="choice">
			{$this->renderJobNameForDay($name)}
			<select name="date_{$date_string}_{$key}" class="preference_selection">
{$options}
			</select>
			</div>
EOHTML;
	}

	/**
	 * Shorten meal names in the survey calendar for a date entry.
	 *
	 * @param string $name of the job
	 * @return string name to be rendered.
	 */
	public function renderJobNameForDay($name) {
		$name = preg_replace('/^.*meal /i', '', $name);
		$drop = [
			' (twice a season)',
			// 'Meeting night ',
			'Sunday ',
			' (two meals/season)',
			' (2 meals/season)',
			'Weekday meal ',
			'Weekday ',
		];
		return str_replace($drop, '', $name);
	}

	/**
	 * Return the list of jobs as special links to filter the results.
	 *
	 * @param string $job_key Either an int representing the unique ID
	 *     for the job to report on, or 'all' to show all jobs.
	 * @return string HTML for displaying the list of job/links.
	 */
	public function getJobsIndex($job_key) {
		$all_jobs = get_all_jobs();

		$jobs_html = '';
		foreach($all_jobs as $key=>$label) {
			if (isset($_GET['key']) && ($key == $job_key)) {
				$jobs_html .= "<li><b>{$label}</b></li>\n";
				continue;
			}
			$dir = BASE_DIR;
			$jobs_html .= <<<EOHTML
<li><a href="{$dir}/report.php?key={$key}">{$label}</a></li>
EOHTML;
		}
		return <<<EOHTML
<ul id="filter_overlay">{$jobs_html}</ul>
EOHTML;
	}


	/**
	 * Load which dates the workers have marked as being available.
	 * @return array associative array of date-string -> [ job_id -> preferences]
	 */
	function getWorkerDates() {
		// grab all the preferences for every date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT s.date_shift_string, s.job_id, a.username, p.pref
				FROM {$auth_user_table} as a, {$prefs_table} as p,
					{$shifts_table} as s
				WHERE a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY s.date_shift_string ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;
		$data = [];
		$mysql_api = get_mysql_api();
		foreach($mysql_api->get($sql) as $row) {
			$data[] = $row;
		}

		$dates = [];
		foreach($data as $d) {
			if (!array_key_exists($d['date_shift_string'], $dates)) {
				$dates[$d['date_shift_string']] = [];
			}
			if (!array_key_exists($d['job_id'], $dates[$d['date_shift_string']])) {
				$dates[$d['date_shift_string']][$d['job_id']] = [];
			}
			$dates[$d['date_shift_string']][$d['job_id']][$d['pref']][] = $d['username'];
		}

		return $dates;
	}

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

	/**
	 * Get a select list of the various workers available.
	 *
	 * @param string $id denotes name of DOM element and form element
	 *     name.
	 * @param bool $first_entry defaults to FALSE, if true,
	 *     then pre-pend the list with a blank entry.
	 * @param string $skip_user (defaults to NULL), if not null, then don't
	 *     display this users' name in the list.
	 * @param array $chosen specifies as list of chosen usernames.
	 * @param bool $only_user (default FALSE), if true, then instead of a
	 *     "(x) remove" link, display a "clear" link.
	 */
	public function getWorkerList($id, $first_entry=FALSE, $skip_user=NULL,
		$chosen=[], $only_user=FALSE) {

		$worker_list = new WorkersList();
		$workers = $worker_list->getWorkers();
		$options = ($first_entry) ? '<option value="none"></option>' : '';
		foreach($workers as $username=>$info) {
			if (!is_null($skip_user) && $username == $skip_user) {
				continue;
			}

			$visible_name = <<<EOTXT
{$info['first_name']} {$info['last_name']} ({$username})
EOTXT;

			$selected = isset($chosen[$username]) ? ' selected' : '';
			$options .= <<<EOHTML
			<option value="{$username}"{$selected}>{$visible_name}</option>
EOHTML;
		}

		return <<<EOHTML
		<select name="{$id}[]" id="{$id}" multiple="multiple">
			{$options}
		</select>
EOHTML;
	}

	/*
	 * Reporting feature - list the workers available for this day
	 *
	 * @param array $cur_date_jobs associative array, keys are the job IDs,
	 *     the value is an associative array. That array consists of
	 *     keys which are the positive preferences and (2 or 1) and the list
	 *     of usernames who left that preference in alphabetical order.
	 * @param bool $is_brunch IF this date is a brunch or not.
	 * @param array $non_respondents list of usernames who did not
	 *     respond to the survey, filtered to only include the list of
	 *     workers for the currently viewed job.
	 */
	public function list_available_workers_for_date($cur_date_jobs,
		$is_brunch=FALSE, $non_respondents=[]) {

		if (is_null($cur_date_jobs)) {
			return;
		}

		$cell = '';
		$job_titles = get_brunch_jobs() + get_mtg_jobs() +
			get_weekday_jobs() + get_sunday_jobs();

		if ($this->key_filter != 'all') {
			// don't figure out a listing for a non-supported day of week
			if (!isset($cur_date_jobs[$this->key_filter])) {
				return;
			}

			$cur_date_jobs = [$this->key_filter =>
				$cur_date_jobs[$this->key_filter]];
		}

		foreach($cur_date_jobs as $job_id=>$info) {
			// don't report anything for an empty day
			if (empty($info)) {
				if (isset($job_titles[$job_id])) {
					$cell .= '<div class="warning">empty!<div>';
				}
				continue;
			}

			// include a title if not filtered
			if ($this->key_filter == 'all') {
				$cell .= "<h3 class=\"jobname\">{$job_titles[$job_id]}</h3>\n";
			}

			// list people who prefer the job first
			if (array_key_exists(2, $info)) {
				$cell .= '<div class="worker_avail_preference highlight">prefer:<ul><li>' . 
					implode("</li>\n<li>\n", $info[2]) . 
					"</li></ul></div>\n";
			}

			// next, list people who would be OK with it
			if (array_key_exists(1, $info)) {
				$cell .= '<div class="worker_avail_preference OK">OK:<ul><li>' . 
					implode("</li>\n<li>\n", $info[1]) . 
					"</li></ul></div>\n";
			}

			$cell .= '<div class="worker_avail_preference non_respond">non-respond:<ul><li>' . 
				implode("</li>\n<li>\n", $non_respondents) . 
				"</li></ul></div>\n";
		}

		return $cell;
	}

	/**
	 * Show the number of shifts to assign:
	 */
	public function getShiftCounts() {
		$sum = 0;
		$jobs = $this->num_shifts;
		$jobs['total'] = 0;

		// compute the total row
		foreach($this->num_shifts as $job_name=>$count) {
			// shift down to an even count
			$jobs[$job_name] = get_nearest_even($count);
			$jobs['total'] += $jobs[$job_name];
		}

		return $jobs;
	}

	/**
	 * Output this calendar to a string
	 *
	 * @param Worker $worker (optional)
	 *     If set, then this calendar will act in survey mode, presenting
	 *     the worker with the list of shifts they need to fill for each day
	 *     that the shift is available. If not set, then use report mode and show
	 *     a summary of all available workers for that date.
	 * @param array $availability a structured array of when people
	 *     are available to work. The first level is the date, then the job ID,
	 *     then the preference level (2 - prefer, 1 - OK) which points to an
	 *     array listing the usernames who fit into that preference level.
	 *     Example:
	 * '11/11/2018' => [
	 *		4597 => [
	 *				2 => ['alice', 'bob' ],
	 *				1 => ['charlie', 'doug', 'edward', 'fred'],
	 *		],
 	 * ]
	 * @param array $non_respondents list of usernames who did not
	 *     respond to the survey, filtered to only include the list of
	 *     workers for the currently viewed job.
	 * @return string html to display.
	 */
	public function toString($worker=NULL, $availability=NULL,
			$non_respondents=[]) {

		if (is_null($worker) && empty($availability)) {
			return <<<EOHTML
				<h2>No worker specified and no availability saved.</h2>
EOHTML;
		}

		$out = $this->evalDates($worker, $availability, $non_respondents);
		return $out[0];
	}


	/**
	 * Count the number of times each shift appears.
	 * @param array $dates_and_job_ids associative array of date to array of job
	 *     IDs needed for that meal.
	 *     Ex: ['11/1/2023'] => [0 => 7054, 1 => 7057]
	 */
	function getShiftsPerDate($dates_and_job_ids) {
		$summary = [];

		foreach($dates_and_job_ids as $date => $jobs) {
			foreach($jobs as $job_id) {
				if (!isset($summary[$job_id])) {
					$summary[$job_id] = 0;
				}
				$summary[$job_id]++;
			}
		}

		return $summary;
	}

	/**
	 * Convert number of meals to number of assignments per job ID.
	 * We're looking for the number of day-types needed for the season.
	 *
	 * meals = number of meals we're trying to cover (for Sundays, 12)
	 * workers = number of workers of this type assigned to
	 *   each meal (e.g. 3 cleaners)
	 * shifts = number of meals per assigned shift (2 for
	 *   cooks, 1 per month for cleaners)
	 * The formula would then be: assignments = ((meals * workers) / shifts)
	 *
	 * @param array $summary associative array, key is the job id, value is the number
	 *    of dates during the season when this shift is assigned.
	 * @param float $sub_season_factor number (default 1) if the jobs were assigned
	 *     across an entire season, but we're only scheduling part of it,
	 *     then this would be a fractional number (<1). Split the number of
	 *     jobs according to the factor.
	 * return array int the number of assignments needed.
	 */
	function getNumberAssignmentsPerJobId($summary, $sub_season_factor) {
		$num_assns = [];

		foreach($summary as $job_id => $dates) {
			// force all meal counts to be even
			$date_count = get_nearest_even($dates);

			$num_workers = get_num_workers_per_job_per_meal($job_id);
			$shifts = get_num_meals_per_assignment($this->season_months,
				$job_id, $sub_season_factor);
			if ($shifts != 0) {
				$num_assns[$job_id] = 
					intval(floor((($date_count * $num_workers) / $shifts)));
			}
		}

		return $num_assns;
	}

	/**
	 * Render the number of assignments, make it human readable.
	 *
	 * @param array $num_assignments associative where the keys are
	 *     job IDs and the values are the number of shifts needed for the season.
	 * @return string The rendered lines of html summarizing the number
	 *     of assignments per job.
	 */
	function renderNumberAssignments($num_assignments) {
		$list = [];
		foreach($num_assignments as $job_id => $assignments) {
			$name = get_job_name($job_id);
			$list[$name] = $name . " {$assignments}\n";
		}

		ksort($list);
		return "<p>" . implode('<br>', $list) . "</p>";
	}

	/**
	 * Get the number of shifts needed for the season.
	 *
	 * @return array associative where the keys are job IDs and the
	 *     values are the number of shifts needed for the season.
	 */
	public function getNumShiftsNeeded() {
		$this->disableWebDisplay();
		$dates_and_shifts = $this->evalDates();
		$shifts_per_date = $this->getShiftsPerDate($dates_and_shifts);

		$workers = get_num_workers_per_job_per_meal();

		$out = [];
		foreach($shifts_per_date as $job_id => $count) {
			$num_workers = $workers[$job_id];
			$out[$job_id] = ($num_workers * $count);
		}
		return $out;
	}


	/**
	 * Get the current number of assignments for each job id for the current
	 * season.
	 *
	 * @return array associative where they keys are job IDs and the values are
	 *     the number of work bundle / assignments needed.
	 */
	public function getAssignmentsNeededForCurrentSeason() {
		$this->disableWebDisplay();
		$dates_and_shifts = $this->evalDates();
		$num_meals = $this->getShiftsPerDate($dates_and_shifts);
		return $this->getNumberAssignmentsPerJobId(
			$num_meals, SUB_SEASON_FACTOR);
	}


	/**
	 * Render the season and date summary.
	 * This is in the report summary footer, summarizing the number of
	 * assignments needed.
	 * NOTE: This is needed when calculating the number of shifts for
	 * the work committee for an upcoming work season.
	 *
	 * @return string HTML to render a summary of the number of needed
	 *     work assignments per job.
	 */
	public function renderSeasonDateSummary() {
		$num_assns = $this->getAssignmentsNeededForCurrentSeason();
		$assns = $this->renderNumberAssignments($num_assns);

		$current_season = $this->season_months;
		return "<h2>season: " . SEASON_YEAR . ' ' .
			array_shift($current_season) . ' - ' .
			array_pop($current_season) . "</h2>\n" .
			$assns;
	}
}
?>
