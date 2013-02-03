<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

function check_ten($ten)
{
	return TRUE;
}

function check_ssn($ssn)
{
	$ssn = str_replace(array('-','/',' ',"\t","\n"), '', $ssn);
	
	if(!is_numeric($ssn) || strlen($ssn) != 9)
		return FALSE;
	
	return TRUE;
}

function check_zip($zip)
{
	return preg_match('/^[0-9]{5}$|^[0-9]{5}-[0-9]{4}$/', $zip);
}

function check_gg($im)
{
	return preg_match('/^[0-9]{0,32}$/', $im);
}

function check_skype($im)
{
	return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_yahoo($im)
{
	return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_regon($regon) // business registration number
{
	return true;
}

function check_icn($icn) // identity card number
{
	return true;
}

function bankaccount($id, $account=NULL)
{
	global $DB;
	
	if($account === NULL)
		$account = $DB->GetOne('SELECT account FROM divisions WHERE id IN (SELECT divisionid
			FROM customers WHERE id = ?)', array($id));

	// This function is for demonstration only, coz US don't support IBAN
	if(!empty($account) && strlen($account) < 21 && strlen($account) >= 8) // mass-payments IBAN
	{
	        $cc = '3028';	// Country code - US
		$account = 'US'.sprintf('%02d',98-bcmod($account.sprintf('%012d',$id).$cc.'00',97)).$account.sprintf('%012d', $id);
	} 

	return $account;
}

function format_bankaccount($account)
{
	return $account;
}

?>
