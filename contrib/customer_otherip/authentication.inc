<?php

// $Id$

function GetUserIDByPhone1AndPIN($phone1, $pin)
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id from users WHERE pin=? AND phone1=?', array($pin, $phone1));
}

function GetUserIDByIDAndPIN($id, $pin)
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id from users WHERE pin=? AND id=?', array($pin, $id));
}

function GetUserIDByContractAndPIN($contract, $pin)
// It requires "contract" field.
{
	global $LMS;
	return $LMS->DB->GetOne('SELECT id FROM users WHERE pin=? AND contract=?', array($pin, $contract));
}

?>
