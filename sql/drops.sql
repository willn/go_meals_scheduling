drop table auth_group;
drop table auth_group_permissions;
drop table auth_permission;
drop table auth_user_groups;
drop table auth_user_user_permissions;
drop table django_admin_log;
drop table django_content_type;
drop table django_migrations;
drop table django_session;
drop table work_app_committee;
drop table work_app_extrainput;
drop table work_app_extrainput_workers;
drop table work_app_log;
drop table work_app_quota;
drop table work_app_quotarequest;
drop table work_app_reason;
drop table work_app_surveycomment;
drop table work_app_surveyresponse;

/*
 * SQLite 3.35.0 introduced support for ALTER TABLE DROP COLUMN, so 
 * do the below SELECT AS FROM as a work-around to skip the password.
 */
CREATE TABLE auth_user_tiny AS
	SELECT
		id,
		first_name,
		last_name,
		email,
		username,
		gather_id
	FROM auth_user
	WHERE is_active=1;

DROP TABLE auth_user;
ALTER TABLE auth_user_tiny
	RENAME TO auth_user;

VACUUM;
