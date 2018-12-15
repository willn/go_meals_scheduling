<?php
global $relative_dir;
if (!isset($relative_dir)) {
	$relative_dir = './';
}
require_once $relative_dir . 'globals.php';

require_once $relative_dir . 'classes/calendar.php';
require_once $relative_dir . 'classes/roster.php';
require_once $relative_dir . 'classes/worker.php';
require_once $relative_dir . 'classes/WorkersList.php';

class Survey {
	protected $worker_obj;
	protected $calendar;
	protected $roster;

	protected $name;
	protected $username;
	protected $worker_id;

	protected $dbh;

	protected $saved = 0;
	protected $summary;
	protected $results = [
		0 => [],
		1 => [],
		2 => [],
	];

	// track the number of jobs marked positively
	protected $positive_count = [];

	protected $request_keys = [
		'bunch_shifts' => NULL,
		'bundle_shifts' => NULL,
		'clean_after_self' => NULL,
		'comments' => NULL,
	];

	protected $is_save_request = FALSE;

	protected $insufficient_prefs_msg;


	public function __construct() {
		$this->calendar = new Calendar();
		$this->roster = new Roster();

		global $dbh;
		$this->dbh = $dbh;
		if (is_null($dbh)) {
			$this->dbh = create_sqlite_connection();
		}
	}

	/**
	 * Set the assigned worker, and 
	 *
	 * @param[in] username string, the username of this user.
	 * @param[in] worker_id int, the unique ID for this worker.
	 */
	public function setWorker($username=NULL, $worker_id=NULL) {
		if (!is_null($username)) {
			$this->username = $username;
		}
		else {
			if (is_null($this->username)) {
				echo "Missing username in setWorker!\n";
				exit;
			}
			$username = $this->username;
		}

		if (!is_null($worker_id)) {
			$this->worker_id = $worker_id;
		}
		else {
			if (is_null($this->worker_id)) {
				echo "Missing worker_id in setWorker!\n";
				exit;
			}
			$worker_id = $this->worker_id;
		}
	}

	/**
	 * Get the current worker's info.
	 */
	public function getCurrentWorkerInfo() {
		return [
			'username' => $this->username,
			'worker_id' => $this->worker_id,
		];
	}

	/**
	 * Load the worker's info from the database / config overrides.
	 *
	 * @param[in] first_name string, the first name of this worker.
	 * @param[in] last_name string, the last name of the worker.
	 */
	public function loadWorkerInfo($first_name=NULL, $last_name=NULL) {
		$this->roster->loadNumShiftsAssigned($this->username);
		$this->worker_obj = $this->roster->getWorker($this->username);
		if (is_null($this->worker_obj)) {
			$this->reportNoShifts();
		}

		$this->worker_obj->setId($this->worker_id);

		if (!is_null($first_name) || !is_null($last_name)) {
			$this->name = "{$first_name} {$last_name}";
			$this->worker_obj->setNames($first_name, $last_name);
		}
	}

	/**
	 * Get the list of workers.
	 * XXX how is this different from getWorkerList?
	 *
	 * @return array list of workers.
	 */
	public function getWorkers() {
		$workers_list = new WorkersList();
		return $workers_list->getWorkers();
	}

	/**
	 * Get a select list of the various workers available.
	 * XXX how is this different from getWorkers?
	 *
	 * @param [in] id string, denotes name of DOM element and form element
	 *     name.
	 * @param[in] first_entry boolean, defaults to FALSE, if true,
	 *     then pre-pend the list with a blank entry.
	 * @param[in] skip_user string (defaults to NULL), if not null, then don't
	 *     display this users' name in the list.
	 * @param[in] chosen_users array specifies as list of chosen usernames.
	 * @param[in] only_user boolean (default FALSE), if true, then instead of a
	 *     "(x) remove" link, display a "clear" link.
	 */
	public function getWorkerList($id, $first_entry=FALSE, $skip_user=NULL,
		$chosen_users=[], $only_user=FALSE) {

		return $this->calendar->getWorkerList($id, $first_entry,
			$skip_user, $chosen_users, $only_user);
	}

	/**
	 * Get the list of tasks by id, their name and the number of instances this
	 * worker was assigned.
	 */
	public function getShifts() {
		$tasks = $this->worker_obj->getTasks();
		$shifts = $this->worker_obj->getNumShiftsToFill();

		$out = [];
		foreach($tasks as $id=>$name) {
			$out[$id] = [
				'name' => $name,
				'instances' => $shifts[$id],
			];
		}
		return $out;
	}

	/**
	 * Render the shifts summary to html
	 * @return string the summary of shifts to work.
	 */
	public function renderShiftsSummaryHtml($shifts) {
		if (empty($shifts)) {
			return NULL;
		}

		$out = '';
		foreach($shifts as $id=>$info) {
			$short_name = preg_replace("/ ?\(.*/", '', $info['name']);
			$out .= "<div>{$info['instances']} meal(s) of {$short_name}</div>";
		}
		return <<<EOHTML
			<div class="shift_instances">
				<h4>Assigned Meals:</h4>
				{$out}
			</div>
EOHTML;
	}

	public function toString() {
		if (is_null($this->worker_obj)) {
			$this->reportNoShifts();
		}

		// if the user is trying to save, display results and get out
		if ($this->is_save_request) {
			$out = $this->renderSaved();
			$this->sendEmail($this->worker_obj->getUsername(), $out);
			return <<<EOHTML
<div class="saved_notification">{$out}</div>
EOHTML;
		}

		// query for this worker's tasks.
		$shifts = $this->getShifts();
		$shifts_summary = $this->renderShiftsSummaryHtml($shifts);
		if (is_null($shifts_summary)) {
			$this->reportNoShifts();
		}

		$current_season = get_current_season_months();
		return <<<EOHTML
		<h2>Welcome, {$this->worker_obj->getName()}</h2>
		{$this->calendar->renderMonthsOverlay($current_season)}
		<form method="POST" action="process.php">
			<input type="hidden" name="username" value="{$_GET['worker']}">
			<input type="hidden" name="posted" value="1">
			{$this->renderRequests()}
			{$shifts_summary}
			{$this->calendar->toString($this->worker_obj)}
			<button class="pill" type="submit" value="Save" id="end">Save</button>
		</form>
EOHTML;
	}

	public function reportNoShifts() {
		$dir = BASE_DIR;
		echo <<<EOHTML
			<div style="padding: 50px;">
				<div class="highlight">Sorry {$_GET['worker']} - you don't
				have any meals shifts for the upcoming season</div>
				<a href="{$dir}" class="pill">&larr; go back</a>
			</div>
EOHTML;
		exit;
	}

	/**
	 * Render the form inputs for asking if the worker wants to clean after
	 * doing their cook shift.
	 *
	 * @param[in] requests key-value pairs of preferences saved to database.
	 * @return string rendered html for the clean after cook form input option.
	 */
	protected function renderCleanAfter($requests) {
		$tasks = $this->worker_obj->getTasks();

		$sunday_cook = $sunday_clean = FALSE;
		$weekday_cook = $weekday_clean = FALSE;
		$mtg_cook = $mtg_clean = FALSE;
		foreach($tasks as $task_id=>$unused) {
			if (is_a_cook_job($task_id)) {
				if (is_a_sunday_job($task_id)) {
					$sunday_cook = TRUE;
				}
				else if (is_a_mtg_night_job($task_id)) {
					$mtg_cook = TRUE;
				}
				else {
					$weekday_cook = TRUE;
				}
			}
			else {
				if (is_a_sunday_job($task_id)) {
					$sunday_clean = TRUE;
				}
				else if (is_a_mtg_night_job($task_id)) {
					$mtg_clean = TRUE;
				}
				else {
					$weekday_clean = TRUE;
				}
			}
		}

		$clean_after_cook =
			(($sunday_cook && $sunday_clean) ||
			($weekday_cook && $weekday_clean) ||
			($mtg_cook && $mtg_clean));

		if (!$clean_after_cook) {
			return '';
		}

		return <<<EOHTML
			<li>
				I prefer to:<br>
				<div class="radio_buttons">
					<label>
						<input type="radio" name="clean_after_self" value="yes"
							{$requests['clean_after_self_yes']}>
						<span>Clean the same day as cooking</span>
					</label>

					<label>
						<input type="radio" name="clean_after_self" value="dc"
							{$requests['clean_after_self_dc']}>
						<span>Don't Care</span>
					</label>

					<label>
						<input type="radio" name="clean_after_self" value="no"
							{$requests['clean_after_self_no']}>
						<span>Avoid cleaning the same day as cooking</span>
					</label>
				</div>
			</li>
EOHTML;
	}

	public function renderRequests() {
		$comments_text = $this->worker_obj->getCommentsText();
		$comments_info = $this->worker_obj->getComments();

		$questions = ['clean_after_self', 'bunch_shifts'];
		$answers = ['yes', 'dc', 'no'];
		foreach($questions as $q) {
			$found = FALSE;
			foreach($answers as $a) {
				$choice = $q . '_' . $a;
				$requests[$choice] = '';
				if (array_key_exists($q, $comments_info) &&
					($comments_info[$q] == $a)) {
					$requests[$choice] = ' checked';
					$found = TRUE;
					break;
				}
			}

			// set defaults
			if (!$found) {
				$requests[$q . '_dc'] = ' checked';
			}
		}

		// generate list of workers
		$avoids = explode(',', array_get($comments_info, 'avoids', ''));
		$avoids = array_flip($avoids);
		$avoids = array_fill_keys(array_keys($avoids), 1);
		$avoid_worker_selector = $this->getWorkerList('avoid_worker', FALSE,
			$this->worker_obj->getUsername(), $avoids);

		$prefers = explode(',', array_get($comments_info, 'prefers', ''));
		$prefers = array_flip($prefers);
		$prefers = array_fill_keys(array_keys($prefers), 1);
		$prefer_worker_selector = $this->getWorkerList('prefer_worker', FALSE,
			$this->worker_obj->getUsername(), $prefers);

		$bundle_checked = (array_get($comments_info, 'bundle_shifts') == 'on') ?
				' checked' : '';

		return <<<EOHTML
			<div class="d_table">
				<div class="d_cell pad">
					<label id="avoid_workers">
						<span>Avoid scheduling with: (e.g. housemates)</span>
						{$avoid_worker_selector}
					</label>
				</div>
				<div class="d_cell pad">
					<label id="prefer_workers">
						<span>Prefer to schedule with: (e.g. housemates)</span>
						{$prefer_worker_selector}
					</label>
				</div>
			</div>

			{$this->renderCleanAfter($requests)}

			<h3>Special Requests:</h3>
			<label class="explain">
				Please use the calendar below to mark your availability
				and only place special requests not addressed above here:
				<textarea name="comments" rows="7" cols="100">{$comments_text}</textarea>
			</label>
EOHTML;
	}

	/**
	 * #!#
	 */
	public function run($post) {
		if (isset($_POST['posted'])) {
			$this->is_save_request = TRUE;
		}

		$this->setUsername($post);
		$this->lookupWorkerId();
		$this->setWorker();
		$this->loadWorkerInfo();

		$this->processDates($post);
		$this->confirmWorkLoad();

		// save the survey
		$this->saveRequests($post);
		$this->savePreferences();
	}

	/**
	 * Set the username from POST.
	 * Display an error and exit if username is not set.
	 */
	protected function setUsername($post) {
		if (!isset($post['username'])) {
			echo "<p class=\"error\">Missing username</p>\n";
			exit;
		}
		$this->username = $post['username'];
	}

	/**
	 * Lookup the worker's ID from the database, set that to the worker_id
	 * member variable.
	 */
	protected function lookupWorkerId() {
		$auth_user_table = AUTH_USER_TABLE;
		$sql = <<<EOSQL
select id from {$auth_user_table} where username='{$this->username}'
EOSQL;

		// get this worker's ID
		$this->worker_id = NULL;
		foreach($this->dbh->query($sql) as $row) {
			$this->worker_id = $row['id'];
			return;
		}
	}

	/**
	 * Process the dates which have been submitted.
	 *
	 * @param[in] post associative array of key-value pairs which were submitted.
	 */
	protected function processDates($post) {
		foreach($post as $key=>$choice) {
			// skip anything that's not a date
			if (empty(strstr($key, 'date_'))) {
				continue;
			}

			list($prefix, $date_string, $task) = explode('_', $key);
			$this->results[$choice][$task][] = $date_string;
			if ($choice > 0) {
				if (!isset($this->positive_count[$task])) {
					$this->positive_count[$task] = 0;
				}
				$this->positive_count[$task]++;
			}
		}
	}

	/**
	 * Used by the save process to figure out if the user has selected enough
	 * shifts to fulfill their assignment.
	 */
	protected function confirmWorkLoad() {
		global $all_jobs;
		$insufficient_prefs = [];

		$num_shifts_to_fill = $this->worker_obj->getNumShiftsToFill();
		foreach($num_shifts_to_fill as $job_id => $num_instances) {
			$pos_count = !array_key_exists($job_id, $this->positive_count) ? 0 :
				$this->positive_count[$job_id];

			// if they haven't filled out enough preferences to fulfill this
			// shift, then warn them.
			if ($pos_count < $num_instances) {
				$insufficient_prefs[] = 
					"({$pos_count} avail / <b>{$num_instances} needed</b>) for {$all_jobs[$job_id]}\n";
			}
		}

		if (!empty($insufficient_prefs)) {
			$missing = implode('<br>', $insufficient_prefs);
			$dir = BASE_DIR;
			$out = <<<EOHTML
			<div class="warning">
				<h2>Warning:</h2>
				<p>You haven't marked enough preferences for the jobs assigned. Your
				choices will be saved and you can come back add more later. However, without
				enough available dates, assignments will be more difficult.</p>
			</div>

			<div class="attention">
				<h3>Jobs which need more availability:</h3>
				<p>{$missing}</p>
				<p>Perhaps you can trade a shift, or <a
					href="{$dir}/index.php?worker={$this->username}">
						add more availability</a>.</p>
			</div>
EOHTML;
			$this->insufficient_prefs_msg = $out;
			echo $out;
		}
	}

	/**
	 * Pick out the number of instances someone has been assigned from this
	 * database.
	 */
	protected function getNumInstances($row) {
		$job_id = $row['job_id'];
		$season = get_current_season_months();
		$num_dinners = get_num_dinners_per_assignment($season, $job_id);

		// otherwise, look for an entry in from the db
		$num_instances = 0;
		$num_shifts_assigned = $row['instances'];

		// how many shifts are needed?
		return $num_dinners * $num_shifts_assigned;
	}

	/**
	 * Save the special requests to the db
	 */
	protected function saveRequests($post) {
		$sql = $this->getSaveRequestsSQL($post);
		$this->dbh->exec($sql);
	}

	/**
	 * Generate the SQL needed to save the special requests to the database.
	 *
	 * @param[in] requests associative array with key-value pairs of
	 *     the various special requests that workers can make.
	 * @return string the SQL generated.
	 */
	public function getSaveRequestsSQL($post) {
		// deal with special requests first
		foreach(array_keys($this->request_keys) as $r) {
			if (!isset($post[$r])) {
				continue;
			}

			$this->request_keys[$r] = $post[$r];
		}

		$avoid_list = implode(',', $post['avoid_worker']);
		$prefer_list = implode(',', $post['prefer_worker']);

		$bundle = array_get($post, 'bundle_shifts', '');
		$table = SCHEDULE_COMMENTS_TABLE;
		$comments = $this->dbh->quote($post['comments']);
		return <<<EOSQL
replace into {$table}
	values(
		{$this->worker_id},
		datetime('now'),
		{$comments},
		'{$avoid_list}',
		'{$prefer_list}',
		'{$post['clean_after_self']}',
		'{$post['bunch_shifts']}',
		'{$bundle}'
	)
EOSQL;
	}

	/**
	 * Save the stated preferences.
	 */
	protected function savePreferences() {
		$pref_names = get_pref_names();
		$shifts_table = SCHEDULE_SHIFTS_TABLE;

		// reverse the array to process the higer priority preferences first,
		// which puts them at the top of the prefs summary listing.
		krsort($this->results);
		foreach($this->results as $pref=>$data) {
			if (empty($data)) {
				continue;
			}

			foreach($data as $task=>$dates) {
				// process each date instance
				$prev_pref = NULL;
				foreach($dates as $d) {

					$shift_id = NULL;
					$sql = "select id from {$shifts_table} where string='{$d}' and job_id={$task}";
					foreach($this->dbh->query($sql) as $row) {
						$shift_id = $row['id'];
						break;
					}

					if (is_null($shift_id)) {
						$insert = <<<EOSQL
REPLACE INTO {$shifts_table} VALUES(NULL, '{$d}', {$task})
EOSQL;
						// XXX add some escaping here
						$this->dbh->exec($insert);

						// now check to make sure that entry was saved...
						foreach($this->dbh->query($sql) as $row) {
							$shift_id = $row['id'];
							break;
						}

						if (is_null($shift_id)) {
							echo <<<EOHTML
								<p class="error">
									Couldn't retrieve shifts data after insert - is
									the database writable?
								</p>
EOHTML;
							exit;
						}
					}

					$prefs_table = SCHEDULE_PREFS_TABLE;
					$replace = <<<EOSQL
REPLACE INTO {$prefs_table} VALUES({$shift_id}, {$this->worker_id}, {$pref})
EOSQL;
					// XXX add some escaping here
					$success = $this->dbh->exec($replace);
					if ($success) {
						$this->saved++;
						if ($prev_pref !== $pref) {
							$this->summary[$task][] = '<hr>';
						}
						$prev_pref = $pref;

						$s = "{$d} {$pref_names[$pref]}";
						if ($pref > 1) {
							$s = "<b>{$s}</b>";
						}
						$this->summary[$task][] = $s;
					}
				}
			}
		}
	}


	/**
	 * Render a notification message when the data has been saved.
	 * @return string HTML of the notification message.
	 */
	public function renderSaved() {
		$summary_text = '';
		$dates = '';

		if (!empty($this->summary)) {
			foreach($this->summary as $job_id=>$info) {
				$name = get_job_name($job_id);
				$dates = implode("\n<br>", $info);
				$summary_text .= <<<EOHTML
<div class="summary_listing">
	<h4>{$name}</h4>
	{$dates}
</div>
EOHTML;
			}

			$summary_text = <<<EOHTML
<div>
	<h3>Preferences Summary</h3>
	<div class="pref_listing">
		{$summary_text}
	</div>
</div>
EOHTML;
		}

		$dir = BASE_DIR;
		return <<<EOHTML
	<h2>Saved!</h2>
	<p>Saved {$this->saved} shift preferences.</p>
	<p>You may <a href="{$dir}/index.php?worker={$this->username}">make changes</a>
		or <a href="{$dir}">take the survey for another person</a>.</p>
	{$summary_text}
EOHTML;
	}

	/**
	 * Send an email message with the results.
	 * @param[in] content string the summary results.
	 */
	protected function sendEmail($user, $content) {
		if (SKIP_EMAIL) {
			return;
		}

		$worker = $user . DOMAIN;
		$sent = mail($worker,
			'Meal Scheduling Survey preferences saved',
			strip_tags($content),
			'From: ' . FROM_EMAIL);

		if (!$sent) {
			error_log("Unable to send email to: $to");
		}

		// if user is under pref level, then send warning email
		if (!is_null($this->insufficient_prefs_msg)) {
			$sent = mail(FROM_EMAIL,
				'Meal Scheduling Survey preferences saved under limit',
				$worker . "\n" . strip_tags($content . "\n" .
					$this->insufficient_prefs_msg),
				'From: ' . FROM_EMAIL);
		}

		return $sent;
	}
}

?>
