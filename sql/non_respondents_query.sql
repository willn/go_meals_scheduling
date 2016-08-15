--- look to see who should have, but hasn't responded yet
SELECT first_name, last_name
    FROM auth_user 
    WHERE id IN
        (SELECT worker_id
            FROM survey_assignment
			WHERE season_id=8
                AND (job_id='544' or
					job_id='545' or
					job_id='548' or
					job_id='542' or
					job_id='543' or
					job_id='547' or
					job_id='546' or
					job_id='549')
            GROUP BY worker_id)
        AND id NOT IN
            (SELECT worker_id
                FROM schedule_prefs
                GROUP BY worker_id)
    ORDER BY first_name, last_name;

--- find the workers who have responded to the survey
SELECT a.first_name, a.last_name
	FROM auth_user as a, schedule_prefs as p
	WHERE a.id=p.worker_id
	GROUP BY p.worker_id;

