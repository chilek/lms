<?php

// $Id$

function GetUserIDByPhone1AndPIN($phone1, $pin)
{
	$tmp = $this->ADB->GetRow("SELECT id,phone1,pin from users WHERE pin=? AND phone1=?", array($pin, $phone1));
	return $tmp[id];
}

?>
