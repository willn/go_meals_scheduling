<h2>sqlite test...</h2>

<?php
try {
    /*** connect to SQLite database ***/
    $dbh = new PDO("sqlite:sqlite_data/work_allocation.db");

	$auth_user_table = AUTH_USER_TABLE;
	$sql = "select * from {$auth_user_table} limit 5";
	foreach ($dbh->query($sql) as $row) {
		echo "<p>ID: {$row['id']}, username: {$row['username']}</p>\n";
	}
}
catch(PDOException $e)
{
    echo $e->getMessage();
}
?>
