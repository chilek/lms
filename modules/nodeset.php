<?php

	if($LMS->UserExists($_GET[ownerid]))
	{
		$LMS->NodeSetU($_GET[ownerid],$_GET[access]);
		$backid = $_GET[ownerid];
	}
	if($LMS->NodeExists($_GET[id]))
	{
		$LMS->NodeSet($_GET[id]);
		$backid = $_GET[id];
	}

	header("Location: ?".$_SESSION[backto]."#".$backid);

?>
