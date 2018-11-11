<?php
require_once 'classes/survey.php';
require_once 'display/includes/header.php';

$survey = new Survey();
$survey->run($_POST);
print $survey->toString();

?>
