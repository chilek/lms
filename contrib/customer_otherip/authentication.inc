<?php

// $Id$

function GetUserIDByPhone1AndPIN($phone1, $pin)
{
	$tmp = $this->ADB->GetRow("SELECT id,phone1,pin from users WHERE pin=? AND phone1=?", array($pin, $phone1));
	return $tmp[id];
}



function GetUserIDByContractAndPIN($contract, $pin)
// It requires "contract" field.

{
	    $tmp = $this->ADB->GetRow("SELECT id,contract,pin from users WHERE pin=? AND contract=?", array($pin, $contract));
		    return $tmp[id];
}


?>
