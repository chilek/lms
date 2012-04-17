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
	$steps = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4);
	$sum_nb = 0;

	$ten = strtoupper(preg_replace('/[^[:digit:]\?]/', '', $ten));
	if (!preg_match('/(^[0-9]{11})([0-9]{1}$|\?{1}$)/', $ten, $regs))
		if (!preg_match('/(^[0-9]{8})([0-9]{1}$|\?{1}$)/', $ten, $regs))
			return FALSE;
	$num = $regs[1];
	$ctr = $regs[2];
	$len = strlen($num);

	for ($x = 0; $x < $len; $x++)
		$sum_nb += $steps[$x] * $num[$x];
	if ($sum_nb % 11 == 10) {
		$sum_nb = 0;
		for ($x = 0; $x < $len; $x++)
			$sum_nb += $steps[$x + 2] * $num[$x];
	}

	$sum_nb = $sum_nb % 11;
	if ($sum_nb == 10)
		$sum_nb = 0;
	if ($sum_nb == $ctr)
		return TRUE;
	return FALSE;
}

function check_ssn($ssn)
{
	if (!preg_match('/^[0-9]{11}$/', $ssn))
		return FALSE;
	
	$sum_nb = 0;
	for($x = 0; $x < 10; $x++)
		if ($x == 9)
			$sum_nb = $sum_nb + $ssn[$x] * 1;
		else
			$sum_nb = $sum_nb + ($ssn[$x] * ($x + 1));
	if(($sum_nb % 11) == $ssn[10])
		return TRUE;
	return FALSE;
}

function check_zip($zip)
{
	return preg_match('/^[0-9]{5}$/', $zip);
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
	$regon = str_replace('-', '', $regon);
	$regon = str_replace(' ', '', $regon);

	return check_ten($regon);

	$sum_nb = 0;

        if(strlen($regon) == 9)
	{
		$steps = array(8, 9, 2, 3, 4, 5, 6, 7);
	
		for($x = 0; $x < 8; $x++) $sum_nb += $steps[$x] * $regon[$x];
	
		$mod = $sum_nb % 11;
		
		if($mod == 10) $mod = 0;
	
		if($mod == $regon[8]) return true;
	}
	elseif(strlen($regon) == 7)
	{
		$steps = array(2, 3, 4, 5, 6, 7);
	
		for ($x = 0; $x < 6; $x++) $sum_nb += $steps[$x] * $regon[$x];

		$mod = $sum_nb % 11;
		
		if($mod == 10) $mod = 0;
	
		if ($mod == $regon[6]) return true;
	}
	
	return false;
}

function check_icn($icn)
{
	$icn = str_replace(' ', '', $icn);

	// proper format of identity card number - 9 digits

	return preg_match('/^[0-9]{8}$/i', $icn);
}

function bankaccount($id, $account=NULL)
{
	global $DB;

	if($account === NULL)
		$account = $DB->GetOne('SELECT account FROM divisions WHERE id IN (SELECT divisionid
                        FROM customers WHERE id = ?)', array($id));	
	
	$acclen = strlen($account);
	
	if(!empty($account) && $acclen < 13 && $acclen >= 5)
	{
		$cc = '2129';	// Country Code - Lithuania
		$format = '%0'.(16 - $acclen) .'d';
		return 'LT'.sprintf('%02d',98-bcmod($account.sprintf($format,$id).$cc.'00',97)).$account.sprintf($format,$id);
	}

	return $account;
}

function format_bankaccount($account)
{
	return preg_replace('/(..)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5}', $account);
}

?>
