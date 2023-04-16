<?php
require_once 'database_config.php';

/*
 * Database connection and interaction library
 */
class MysqlApi {
	private $host;
	private $database;
	private $user;
	private $password;

	private $link;

	/**
	 * Construct a new connection object.
	 *
	 * @param string $host The hostname of the database server.
	 */
	public function __construct($host='localhost', $database, $user='nobody',
		$password) {

		$this->host = $host;
		$this->database = $database;
		$this->user = $user;
		$this->password = $password;
	}

	public function setLink($link) {
		$this->link = $link;
	}

	public function getLink() {
		if (is_null($this->link)) {
			$this->connect();
		}

		return $this->link;
	}

	/**
	 * Establish a connection to a mysql database
	 *
	 * @return boolean. If TRUE, then the connection either previously existed,
	 *     or was established properly.
	 */
	public function connect() {
		if (!is_null($this->link) && ($this->link !== FALSE)) {
			return TRUE;
		}

		if (!extension_loaded('mysqli')) {
			return FALSE;
		}

		$this->link = mysqli_connect($this->host, $this->user, $this->password);
		if (is_null($this->link)) {
			error_log('unable to establish connection with mysql database');
			return FALSE;
		}

		if (!is_null($this->database) && 
			!mysqli_select_db($this->link, $this->database)) { 
			error_log('unable to select mysql database');
			return FALSE;
		}

		return TRUE; 
	}

	/**
	 * Query the database.
	 *
	 * @param string $query A SQL command to be executed.
	 * @return mysqli_result
	 */
	public function query($query) {
		if (is_null($this->link)) {
			$this->connect();
		}
		if (is_null($this->link)) {
			return NULL;
		}

		$mysqli_result = mysqli_query($this->link, $query);
		if (!$mysqli_result) {
			$err = mysqli_error($this->link);
			error_log("Could not get a result from the query, err: {$err}");
			return NULL;
		}
		return $mysqli_result;
	}

	/**
	 * Retrieve data from the database, return an associative array
	 *
	 * @param string $query A SQL command to be executed.
	 * @param string $primary_key (optional, defaults to NULL). If supplied,
	 *     then the results should be indexed by the value found in this column.
	 * @param boolean $do_stripslashes (default TRUE). If TRUE, then apply
	 *     stripslashes to the returned output.
	 */
	public function get($query, $primary_key=NULL, $do_stripslashes=TRUE) {
		$found = [];

		if (DEBUG) {
			error_log(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . " sql:$query ");
		}

		$mysqli_result = $this->query($query);
		if ($mysqli_result == FALSE) {
			return FALSE;
		}

		while($info = mysqli_fetch_array($mysqli_result, MYSQLI_ASSOC)) {
			if ($do_stripslashes) {
				$info = array_map('stripslashes', $info);
			}

			if (is_null($primary_key)) {
				$found[] = $info;
			}
			else {
				$found[$info[$primary_key]] = $info;
			}
		}

		return $found;
	}
}

/**
 * Get the MySQL API
 */
function get_mysql_api() {
	$HDUP = get_hdup();
	return new MysqlApi($HDUP['host'], $HDUP['database'],
		$HDUP['user'], $HDUP['password']);
}

?>
