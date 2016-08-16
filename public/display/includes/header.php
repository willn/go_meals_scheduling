<!doctype html>
<html>
<head>
	<title>Meals Scheduling Survey and Reporting</title>
	<link rel="stylesheet" href="display/styles/default.css" type="text/css">
	<link rel="stylesheet" href="select2/select2.min.css" type="text/css">

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.3/jquery.dataTables.js"></script>
	<script type="text/javascript" src="select2/select2.full.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$('#per_worker').dataTable({
				'bPaginate': false
			});
		} );
	</script>

<?php
if (isset($_REQUEST['worker'])) {
	echo <<<EOHTML
	<script type="text/javascript" src="js/utils.js"></script>
	<script type="text/javascript" src="js/survey_library.js"></script>
EOHTML;
}
?>

</head>

<body>
