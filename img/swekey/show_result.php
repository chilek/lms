<?php

if (get_magic_quotes_gpc())
	$_GET['result'] = stripslashes(@$_GET['result']);

echo htmlentities(@$_GET['result']);

?>
