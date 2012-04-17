<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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
	return preg_match('/^CZ[0-9]{8,10}$/', $ten);
}

function check_ssn($ssn)
{
	return preg_match('/^[0-9]{6}\/[0-9]{3,4}$/', $ssn);
}

function check_zip($zip)
{
	return preg_match('/^[1-9][0-9]{4}$/', $zip);
}

function check_gg($im)
{
	return preg_match('/^[0-9]{0,32}$/', $im);  // gadu-gadu ID check
}

function check_yahoo($im)
{
	return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_skype($im)
{
	return preg_match('/^[-_.a-z0-9]{0,32}$/i', $im);
}

function check_regon($regon)
{
	return preg_match('/^[0-9]{8,10}$/', $regon);
}

function check_icn($icn)
{
	return preg_match('/^[1-9][0-9]{8}$/', $icn);
}

function bankaccount($id, $account=NULL)
{
	global $DB;

	if($account === NULL)
		$account = $DB->GetOne('SELECT account FROM divisions WHERE id IN (SELECT divisionid
			FROM customers WHERE id = ?)', array($id));

        $acclen = strlen($account);
				
	if(!empty($account) && $acclen < 17 || $acclen >= 8)
	{
		$cc = '2820';	// Kod kraju - Slovensko
		$format = '%0'.(20 - $acclen) .'d';
		return sprintf('%02d',98-bcmod($account.sprintf($format,$id).$cc.'00',97)).$account.sprintf($format,$id);
	}

	return $account;
}

function format_bankaccount($account)
{
	return preg_replace('/(..)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
}

?>
