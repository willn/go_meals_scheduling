
CREATE TABLE schedule_shifts (
	id INTEGER PRIMARY KEY autoincrement,
	string varchar(12),
	job_id int
);

CREATE TABLE schedule_prefs (
	date_id int,
	worker_id int,
	pref int,
	primary key(date_id, worker_id)
);

CREATE TABLE schedule_comments (
	worker_id int,
	timestamp datetime,
	comments text,
	avoids varchar(500),
	prefers varchar(500),
	clean_after_self varchar(2),
	bunch_shifts varchar(2),
	bundle_shifts varchar(2),
	primary key(worker_id)
);

