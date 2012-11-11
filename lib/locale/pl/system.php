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
	$steps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
	$sum_nb = 0;

	$ten = str_replace('-', '', $ten);
	$ten = str_replace(' ', '', $ten);

	if (strlen($ten) != 10) return FALSE;

	for ($x = 0; $x < 9; $x++) $sum_nb += $steps[$x] * $ten[$x];

	if ($sum_nb % 11 == $ten[9]) return TRUE;

	return FALSE;
}

function check_ssn($ssn)
{
	// AFAIR This doesn't cover people born after Y2k, they have month+20
	// Be warned.
	if (!preg_match('/^[0-9]{11}$/', $ssn))
		return FALSE;
	
	$steps = array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3);
	$sum_nb = 0;
	
	for ($x = 0; $x < 10; $x++)
	{
		$sum_nb += $steps[$x] * $ssn[$x];
	}
	
	$sum_m = 10 - $sum_nb % 10;
	
	if ($sum_m == 10)
		$sum_c = 0;
	else
		$sum_c = $sum_m;
	
	if ($sum_c == $ssn[10])
		return TRUE;
	return FALSE;
}

function check_zip($zip)
{
	return preg_match('/^[0-9]{2}-[0-9]{3}$/', $zip);
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
	$sum_nb = 0;

        if(strlen($regon) == 14)
	{
		$steps = array(2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8);
	
		for($x = 0; $x < 13; $x++) $sum_nb += $steps[$x] * $regon[$x];
	
		$mod = $sum_nb % 11;
		
		if($mod == 10) $mod = 0;
	
		if($mod == $regon[13]) return true;
	}
        else if(strlen($regon) == 9)
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

	// poprawny format numeru dowodu osobistego to 9 znakow w tym:
	//    - 2 litery i 7 cyfr lub
	//    - 3 litery i 6 cyfr

	return preg_match('/^[A-Z]{2}[0-9]{7}$/i', $icn) || preg_match('/^[A-Z]{3}[0-9]{6}$/i', $icn);
}

function bankaccount($id, $account=NULL)
{
	global $DB;

	if($account === NULL)
		$account = $DB->GetOne('SELECT account FROM divisions
			WHERE id IN (SELECT divisionid
                    		FROM customers WHERE id = ?)', array($id));	

	$acclen = strlen($account);
	
	if(!empty($account) && $acclen < 21 && $acclen >= 8)
	{
		$cc = '2521';	// Kod kraju - Polska
		$format = '%0'.(24 - $acclen) .'d';
		$account .= sprintf($format, $id);
		return sprintf('%02d', 98-bcmod($account.$cc.'00', 97)).$account;
	} 

	return $account;
}

function format_bankaccount($account)
{
	return preg_replace('/(..)(....)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6} ${7}', $account);
}

function phone_format($string)
{
    $stacjonarne = array(
			12, 13, 14, 15, 16, 17, 18, 22, 23, 24, 25, 26, 29, 30, 32, 33, 34, 39, 40, 41, 42, 43, 44, 46, 47,
			48, 52, 54, 55, 56, 58, 59, 61, 62, 63, 64, 65, 67, 68, 70, 71, 74, 75, 76, 77, 80, 81, 82, 83, 84,
			85, 86, 87, 89, 91, 94, 95
		);
    $format = 0;
    $t = array();
    
    if ( strlen($string)===7 )
	$format = 1;
    else {
	$t[0] = substr($string,0,2);
	if (in_array($t[0],$stacjonarne))
	    $format = 2;
	else 
	    $format = 3;
    }
    
    $string = str_replace(',','',$string);
    $string = str_replace(' ','',$string);
    $string = str_replace('(','',$string);
    $string = str_replace(')','',$string);
    $string = str_replace('-','',$string);
    $string = str_replace('/','',$string);
    $str = '';
    $t = array();
    
    switch ($format)
    {
	
	case'1': 
		$t[0] = substr($string,0,3);
		$t[1] = substr($string,3,2);
		$t[2] = substr($string,5,2);
	break;
	
	case'2':
		$t[0] = substr($string,0,2);
		$t[1] = substr($string,2,3);
		$t[2] = substr($string,5,2);
		$t[3] = substr($string,7,2);
	break;
	
	case'3':
		$t[0] = substr($string,0,3);
		$t[1] = substr($string,3,3);
		$t[2] = substr($string,6,3);
	break;
	
	default:
		$t[0] = $string;
    }
    
    if (count($t)!==0)
	$str = implode(' ',$t);
    else 
	$str = $string;
    
    return $str;
}

?>
