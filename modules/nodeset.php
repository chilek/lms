<?

	if($LMS->UserExists($_GET[ownerid]))
	{
		$LMS->NodeSetU($_GET[ownerid],$_GET[access]);
	}
	if($LMS->NodeExists($_GET[id]))
	{
		$LMS->NodeSet($_GET[id]);
	}

	header("Location: ?".$_SESSION[backto]);

?>
