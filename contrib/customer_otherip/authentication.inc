<?php

// $Id$

function GetUserIDByPhone1AndPIN($phone1, $pin)
{
	return $this->DB->GetOne('SELECT id from users WHERE pin=? AND phone1=?', array($pin, $phone1));
}



function GetUserIDByContractAndPIN($contract, $pin)
// It requires "contract" field.
{
	return $this->DB->GetOne('SELECT id FROM users WHERE pin=? AND contract=?', array($pin, $contract));
}

?>
