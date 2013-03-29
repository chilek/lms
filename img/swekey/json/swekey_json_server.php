<?php

global $DB, $AUTH, $LMS;

if (empty($_POST)) {
	echo "This is the swekey JSON server.<br />It should be called using a http POST request.";
	exit;
}

include_once(dirname(__FILE__) . '/../../../lib/swekey/lms_integration.php');

if (session_id() == '')
	session_start();

$SWEKEY = new LmsSwekeyIntegration($DB, $AUTH, $LMS);
$result = $SWEKEY->AjaxHandler($_POST);

echo json_encode($result);
exit;

?>
