<?php
global $relative_dir;
if (!isset($relative_dir)) {
	$relative_dir = './';
}
require_once $relative_dir . 'globals.php';
require_once 'WorkersList.php';

class Calendar {
	const BLANK_DAY_HTML = '<td class="blank"></td>';

	protected $web_display = TRUE;
	protected $key_filter = 'all';

	protected $assignments = array();

	protected $holidays;

	protected $is_report = FALSE;

	protected $num_shifts = array(
		'sunday' => 0,
		'weekday' => 0,
		'meeting' => 0,
	);

	public function __construct($season_months=[]) {
		// 'all' is the default, so only change if it's numeric
		if (isset($_GET['key']) && is_numeric($_GET['key'])) {
			$this->key_filter = $_GET['key'];
		}

		$this->holidays = get_holidays(SEASON_NAME);

		if (empty($season_months)) {
			$this->season_months = get_current_season_months();
		}
	}

	/**
	 * Set the season's months.
	 *
	 * @param[in] season array list of months in the season to be used.
	 */
	public function setSeasonMonths($season) {
		$this->season_months = $season;
	}

	public function setIsReport($setting=TRUE) {
		$this->is_report = $setting;
	}

	/**
	 * Don't build a display for the web, just get the info needed.
	 * XXX This is used by the auto-assignment routine, to get the needed
	 * dates.
	 */
	public function disableWebDisplay() {
		$this->web_display = FALSE;
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
	 * @param[in] current_season array string list of the month numbers and names.
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
	 */
	public function getWeeklySpacerHtml() {
		return <<<EOHTML
			<td class="week_selector">
				This week:
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;
	}

	/**
	 * Get the weekday selector html
	 * @param[in] day_num int the meal number for the season, for debugging.
	 * @param[in] day_of_week string, the short name for the day of the week,
	 *     e.g. 'Tue'.
	 * @return string the rendered html.
	 */
	public function getWeekdaySelectorHtml($day_num, $day_of_week) {
		if (!is_numeric($day_num)) {
			return;
		}
		if (empty($day_of_week)) {
			return;
		}

		$short_day = substr($day_of_week, 0, 3);
		return <<<EOHTML
			<td class="weekday_selector weekday_num_{$day_num}">
				{$short_day}:<br>
				<a class="prefer">prefer</a>
				<a class="OK">OK</a>
				<a class="avoid">avoid</a>
			</td>
EOHTML;
	}

	/**
	 * Figure out which dates have which shifts applied to them.
	 *
	 * @param[in] worker array (optional) 
	 *     If set, then this calendar will act in survey mode, presenting
	 *     the worker with the list of shifts they need to fill for each day
	 *     that the shift is available. If not set, then use report mode and show
	 *     a summary of all available workers for that date.
	 * @param[in] availability array a structured array of when people
	 *     are available to work. The first level is the date, then the job ID,
	 *     then the preference level (2 - prefer, 1 - OK) which points to an
	 *     array listing the usernames who fit into that preference
	 *     level. Currently only used for reporting.
	 *     Example:
	 * '11/11/2018' => [
	 *		4597 => [
	 *				2 => ['alice', 'bob' ],
	 *				1 => ['charlie', 'doug', 'edward', 'fred'],
	 *		],
 	 * ]
	 * @return string html to render all the calendar months of the
	 *     season... or to just return the dates_and_shifts array if
	 *     this is not for web_display.
	 */
	public function evalDates($worker=NULL, $availability=NULL) {
		$sunday_jobs = get_sunday_jobs();
		$mtg_nights = get_mtg_nights();

		$mtg_day_count = array();
		foreach(array_keys($mtg_nights) as $dow) {
			$mtg_day_count[$dow] = 0;
		}

		$weekly_selector = '';
		if (!is_null($worker)) {
			$saved_prefs = $this->getSavedPrefs($worker->getId());
			$weekly_selector = $this->getWeeklySpacerHtml();
		}

		$selectors = $this->renderDaySelectors(!is_null($worker));

		$meal_days = get_weekday_meal_days();

		$skip_dates = get_skip_dates();
		$reg_day_overrides = get_regular_day_overrides();

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

			$month_entries = array();
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

				#!# need to fix the validity of this id value
				$date_string = "{$month_num}/{$day_num}/{$season_year}";
				$cell = '';

				$meal_type = get_meal_type_by_date($date_string);
				switch($meal_type) {
					case HOLIDAY_NIGHT:
						$cell = '<span class="skip">holiday</span>';
						break;

					case SKIP_NIGHT:
						$cell = '<span class="skip">skip</span>';
						break;

					#-----------------------------------------
					case SUNDAY_MEAL:
						$this->num_shifts['sunday']++;

						if (!$this->web_display) {
							$dates_and_shifts = $this->addJobsToDatesAndShifts(
								$sunday_jobs, $dates_and_shifts, $date_string);
						}
						else if (!is_null($worker)) {
							foreach($sunday_jobs as $key=>$name) {
								$saved_pref_val =
									isset($saved_prefs[$key][$date_string]) ?
										$saved_prefs[$key][$date_string] : NULL;

								// if this job is in the list of assigned tasks.
								if (array_key_exists($key, $worker->getTasks())) {
									$cell .= $this->renderday($date_string, $name, $key,
										$saved_pref_val);
								}
							}
						}
						// generate the date cell for the report
						else if (!empty($availability) &&
							array_key_exists($date_string, $availability)) {
							// report the available workers
							$tally = <<<EOHTML
<span class="type_count">[S{$this->num_shifts['sunday']}]</span>
EOHTML;
							$cell = $this->list_available_workers(
								$availability[$date_string], TRUE);
						}
						break;

					#-----------------------------------------
					case MEETING_NIGHT_MEAL:
						$this->num_shifts['meeting']++;
						$jobs = get_mtg_jobs();

						if (!$this->web_display) {
							$dates_and_shifts = $this->addJobsToDatesAndShifts(
								$jobs, $dates_and_shifts, $date_string);
						}
						else if (!is_null($worker)) {
							foreach($jobs as $key=>$name) {
								$saved_pref_val =
									isset($saved_prefs[$key][$date_string]) ?
										$saved_prefs[$key][$date_string] : NULL;

								if (array_key_exists($key, $worker->getTasks())) {
									// is this preference saved already?

									$cell .= $this->renderday($date_string, $name,
										$key, $saved_pref_val);
								}
							}
						}
						else {
							$tally = <<<EOHTML
<span class="type_count">[M{$this->num_shifts['meeting']}]</span>
EOHTML;
							$cell .= '<span class="note">meeting night</span>';
							// report the available workers
							$cell .= $this->list_available_workers(
								$availability[$date_string]);
						}

						break;

					#-----------------------------------------
					case WEEKDAY_MEAL:
						$this->num_shifts['weekday']++;

						/*
						 * Confirm that the day of week for the current date is an
						 * approved day of week for this meal type.
						 */
						if (in_array($day_of_week, $meal_days)) {
							$jobs = get_weekday_jobs();

							// just add the dates and shifts, don't render the calendar
							if (!$this->web_display) {
								$dates_and_shifts = $this->addJobsToDatesAndShifts(
									$jobs, $dates_and_shifts, $date_string);
							}
							// if this is for a specific worker
							else if (!is_null($worker)) {
								foreach($jobs as $key=>$name) {
									$saved_pref_val =
										isset($saved_prefs[$key][$date_string]) ?
											$saved_prefs[$key][$date_string] : NULL;

									if (array_key_exists($key, $worker->getTasks())) {
										// is this preference saved already?

										$cell .= $this->renderday($date_string, $name,
											$key, $saved_pref_val);
									}
								}
							}
							// generate the date cell for the report
							else if (!empty($availability) &&
								array_key_exists($date_string, $availability)) {
								// report the available workers
								$tally = <<<EOHTML
	<span class="type_count">[S{$this->num_shifts['sunday']}]</span>
EOHTML;
								$cell = $this->list_available_workers(
									$availability[$date_string], TRUE);
							}
						}
						/*
						 * XXX Why does this exist? This would only happen if
						 * there's available preferences for a non-scheduled
						 * day of week. Would this be an override? (e.g. thursday?)
						 */
						else if (array_key_exists($date_string, $availability)) {
							// generate the date cell for the report
							$tally = <<<EOHTML
<span class="type_count">[W{$this->num_shifts['weekday']}]</span>
EOHTML;

							// report the available workers
							$cell = $this->list_available_workers(
								$availability[$date_string]);
						}
						break;

					case NOT_A_MEAL:
						break;
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
					<div class="date_number">{$day_num}{$tally}</div>
					{$cell}
					{$this->getMessage($month_num, $day_of_week, $cell)}
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
					<table cellpadding="8" cellspacing="1" border="0" width="100%">
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

		return $out;
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
	 * Get a notice message to display on certain dates.
	 */
	public function getMessage($month_num, $day_of_week, $cell) {
		if (empty($cell)) {
			return '';
		}

		$notice = '';
		if (($day_of_week === 2) && (($month_num > 5) && ($month_num < 11)))  {
			$notice = '<p class="notice">Farm meal night</p>';
		}
		return $notice;
	}

	/**
	 * Render the HTML for the header of each month table in the calendar.
	 * To be specific, this includes the colored row with the day names
	 * and then the next row with the quick links to apply "prefer",
	 * "OK", or "avoid" to all of that day of the week for this month.
	 *
	 * @param[in] has_worker boolean (optional, default FALSE) If TRUE,
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
				if (in_array($day_num, array_merge(array(0), $meal_days))) {
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
	 * @param[in] worker_id the ID number of the current worker
	 * @return array of already-saved preferences for this worker. If empty,
	 *     then this worker has not taken the survey yet.
	 */
	private function getSavedPrefs($worker_id) {
		if (!is_numeric($worker_id)) {
			return array();
		}

		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$sql = <<<EOJS
			select s.id, s.string, s.job_id, p.pref
				FROM {$shifts_table} as s, {$prefs_table} as p
				WHERE s.id=p.date_id
					AND worker_id={$worker_id}
					ORDER BY s.string, s.job_id
EOJS;

		global $dbh;
		$data = array();
		foreach ($dbh->query($sql) as $row) {
			if (!array_key_exists($row['job_id'], $data)) {
				$data[$row['job_id']] = array();
			}
			$data[$row['job_id']][$row['string']] = $row['pref'];
		}

		return $data;
	}


	/*
	 * Draw an individual survey table cell for one day.
	 *
	 * @param[in] date_string string of text representing a date, i.e. '12/6/2009'
	 * @param[in] name string name of the job
	 * @param[in] key int the job ID
	 * @param[in] saved_pref number the preference score previously saved
	 */
	private function renderday($date_string, $name, $key, $saved_pref) {
		$pref_names = get_pref_names();

		$sel = ['', '', ''];
		if (!is_numeric($saved_pref)) {
			$saved_pref = 1;
		}
		$sel[$saved_pref] = 'selected';

		$id = "{$date_string}_{$key}";
		return <<<EOHTML
			<div class="choice">
			{$this->renderJobNameForDay($name)}
			<select name="date_{$date_string}_{$key}" class="preference_selection">
				<option value="2" {$sel[2]}>{$pref_names[2]}</option>
				<option value="1" {$sel[1]}>{$pref_names[1]}</option>
				<option value="0" {$sel[0]}>{$pref_names[0]}</option>
			</select>
			</div>
EOHTML;
	}

	/**
	 * Shorten meal names in the survey calendar for a date entry.
	 *
	 * @param[in] name string name of the job
	 * @return string name to be rendered.
	 */
	public function renderJobNameForDay($name) {
		$name = preg_replace('/^.*meal /i', '', $name);
		$drop = array(
			' (twice a season)',
			// 'Meeting night ',
			'Sunday ',
			' (two meals/season)',
			' (2 meals/season)',
			'Weekday meal ',
			'Weekday ',
		);
		return str_replace($drop, '', $name);
	}

	/**
	 * Return the list of jobs as special links to filter the results.
	 *
	 * @param[in] job_key string Either an int representing the unique ID
	 *     for the job to report on, or 'all' to show all jobs.
	 * @return string HTML for displaying the list of job/links.
	 */
	public function getJobsIndex($job_key) {
		global $all_jobs;

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
		return $jobs_html;
	}


	/**
	 * Load which dates the workers have marked as being available.
	 */
	function getWorkerDates() {
		// grab all the preferences for every date
		$prefs_table = SCHEDULE_PREFS_TABLE;
		$shifts_table = SCHEDULE_SHIFTS_TABLE;
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
			SELECT s.string, s.job_id, a.username, p.pref
				FROM {$auth_user_table} as a, {$prefs_table} as p,
					{$shifts_table} as s
				WHERE p.pref>0
					AND a.id=p.worker_id
					AND s.id = p.date_id
				ORDER BY s.string ASC,
					p.pref DESC,
					a.username ASC;
EOSQL;
		$data = array();
		global $dbh;
		foreach($dbh->query($sql) as $row) {
			$data[] = $row;
		}

		$dates = [];
		foreach($data as $d) {
			if (!array_key_exists($d['string'], $dates)) {
				$dates[$d['string']] = array();
			}
			if (!array_key_exists($d['job_id'], $dates[$d['string']])) {
				$dates[$d['string']][$d['job_id']] = array();
			}
			$dates[$d['string']][$d['job_id']][$d['pref']][] = $d['username'];
		}

		return $dates;
	}

	public function getWorkerComments($job_key_clause) {
		$special_prefs = array(
			'avoids',
			'prefers',
			'clean_after_self',
			'bunch_shifts',
			'bundle_shifts',
		);

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
		$comments = array();
		$out = "<h2 id=\"worker_comments\">Comments</h2>\n";
		$checks = array();
		$check_separator = 'echo "-----------";';
		global $dbh;
		foreach($dbh->query($sql) as $row) {
			$username = $row['username'];

			$requests = '';
			foreach($special_prefs as $req) {
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
					$avoids = explode(',', $row[$req]);
					foreach($avoids as $av) {
						$checks[] = $check_separator;
						$checks[] = "echo '{$username}' avoids '{$av}'";
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

				/* not sure if these are used right now
				case 'bunch_shifts':
				case 'bundle_shifts':
				*/
				}
			}

			$comments[] = $row;
			$remark = stripslashes($row['comments']);
			$content = (empty($requests) && empty($remark)) ? '' :
				"<p>{$requests}<br>{$remark}</p>\n";

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
	 * @param [in] id string, denotes name of DOM element and form element
	 *     name.
	 * @param[in] first_entry boolean, defaults to FALSE, if true,
	 *     then pre-pend the list with a blank entry.
	 * @param[in] skip_user string (defaults to NULL), if not null, then don't
	 *     display this users' name in the list.
	 * @param[in] chosen array specifies as list of chosen usernames.
	 * @param[in] only_user boolean (default FALSE), if true, then instead of a
	 *     "(x) remove" link, display a "clear" link.
	 */
	public function getWorkerList($id, $first_entry=FALSE, $skip_user=NULL,
		$chosen=array(), $only_user=FALSE) {

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
	 * reporting feature - list the workers available for this day
	 */
	private function list_available_workers($cur_date, $is_sunday=FALSE) {
		$cell = '';

		if (is_null($cur_date)) {
			error_log('no date supplied for ' . __FUNCTION__);
			return;
		}

		$job_titles = array();
		if ($is_sunday) {
			$job_titles = get_sunday_jobs();
		}
		else {
			$mtg_jobs = get_mtg_jobs();
			$job_titles = get_weekday_jobs() + $mtg_jobs;
		}

		if ($this->key_filter != 'all') {
			// don't figure out a listing for a non-supported day of week
			if (!isset($cur_date[$this->key_filter])) {
				return;
			}

			$cur_date = array($this->key_filter =>
				$cur_date[$this->key_filter]);
		}

		foreach($cur_date as $job=>$info) {
			// don't report anything for an empty day
			if (empty($info)) {
				if (isset($job_titles[$job])) {
					$cell .= '<div class="warning">empty!<div>';
				}
				continue;
			}

			// include a title if not filtered
			if ($this->key_filter == 'all') {
				$cell .= "<h3 class=\"jobname\">{$job_titles[$job]}</h3>\n";
			}

			// list people who prefer the job first
			if (array_key_exists(2, $info)) {
				$cell .= '<div class="highlight">prefer:<ul><li>' . 
					implode("</li>\n<li>\n", $info[2]) . 
					"</li></ul></div>\n";
			}

			// next, list people who would be OK with it
			if (array_key_exists(1, $info)) {
				$cell .= '<div class="OK">OK:<ul><li>' . 
					implode("</li>\n<li>\n", $info[1]) . 
					"</li></ul></div>\n";
			}
		}

		return $cell;
	}

	public function getNumShifts() {
		return $this->num_shifts;
	}

	/**
	 * Show the number of shifts to assign:
	 */
	public function getShiftCounts() {
		$sum = 0;
		$jobs = $this->num_shifts;
		$jobs['total'] = 0;

		// compute the total row
		foreach($this->num_shifts as $job=>$count) {
			$jobs['total'] += $count;
		}

		return $jobs;
	}

	/**
	 * Output this calendar to a string
	 *
	 * @param[in] worker XXX ???
	 * @param[in] availability array a structured array of when people
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
	 * @return string html to display.
	 */
	public function toString($worker=NULL, $availability=NULL) {
		if (is_null($worker) && empty($availability)) {
			return;
		}

		return $this->evalDates($worker, $availability);
	}


	/**
	 * Count the number of times each shift appears.
	 * @param[in] dates_and_shifts associative array of date to array of shifts
	 *     needed for that meal.
	 */
	function getShiftsPerDate($dates_and_shifts) {
		$summary = [];

		foreach($dates_and_shifts as $date => $shifts) {
			foreach($shifts as $job_id) {
				$summary[$job_id]++;
			}
		}

		return $summary;
	}

	/**
	 * Get the number of assignments per job id.
	 * We're looking for the number of day-types needed for the season.
	 *
	 * Let M = number of meals we're trying to cover (for Sundays, 12)
	 * Let W = number of workers of this type assigned to each meal (e.g. 3 cleaners)
	 * Let S = number of meals per assigned shift (2 for cooks, 4 for cleaners)
	 * The formula would then be: (M * W) / S
	 *
	 * @param[summary] associative array, key is the job id, value is the number
	 *    of meals during the season when this shift is assigned.
	 */
	function getNumberAssignmentsPerJobId($summary) {
		$num_days = [];

		foreach($summary as $job_id => $meals) {
			$workers = get_job_instances($job_id);
			$shifts = get_num_dinners_per_assignment($this->season_months, $job_id);
			if ($shifts != 0) {
				$num_days[$job_id] = ceil(($meals * $workers) / $shifts);
			}
		}

		return $num_days;
	}

	/**
	 * Render the number of assignments, make it human readable.
	 *
	 * @param[in] num_assignments #!#
	 */
	function renderNumberAssignments($num_assignments) {
		$out = [];
		foreach($num_assignments as $job_id => $assignments) {
			$out[] = get_job_name($job_id) . " {$assignments}\n";
		}

		return "<p>" . implode($out, '<br>') . "</p>";
	}

	/**
	 * Render the season and date summary.
	 */
	function renderSeasonDateSummary() {
		$this->disableWebDisplay();
		$dates_and_shifts = $this->evalDates();
		$summary = $this->getShiftsPerDate($dates_and_shifts);
		$num_days = $this->getNumberAssignmentsPerJobId($summary);
		$assns = $this->renderNumberAssignments($num_days);

		$current_season = $this->season_months;
		return "<h2>season: " . SEASON_YEAR . ' ' .
			array_shift($current_season) . ' - ' .
			array_pop($current_season) . '</h2>' .
			$assns;
	}
}
?>
