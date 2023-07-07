<!doctype html>
<html lang="en">
<head>
	<title>Meals Scheduling Survey and Reporting</title>
	<link rel="stylesheet" href="display/styles/default.css" type="text/css">

	<script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>
	<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	<script>
		$(document).ready(function() {
			$('#per_worker').dataTable({
				'bPaginate': false
			});
		} );
	</script>

<?php
if (isset($_REQUEST['worker'])) {
	echo <<<EOHTML
	<script src="js/utils.js"></script>
	<script src="js/survey_library.js"></script>
EOHTML;
}
?>

</head>

<body>
