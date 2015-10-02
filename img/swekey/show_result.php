<?php

if (get_magic_quotes_gpc())
	$_GET['result'] = stripslashes(@$_GET['result']);

echo "/*SWEKEY-BEGIN*/" . htmlentities(@$_GET['result']) . "/*SWEKEY-END*/";

?>
