<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2009 LMS Developers
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
		return sprintf('%02d',98-bcmod($account.sprintf($format,$id).$cc.'00',97)).$account.sprintf($format,$id);
	}

	return $account;
}
		
function uptimef($ts)
{
	if($ts==0)
		return 'n/a';
	$min= $ts / 60;
	$hours = $min / 60;
	$days  = floor($hours / 24);
	$hours = floor($hours - ($days * 24));
	$min= floor($min - ($days * 60 * 24) - ($hours * 60));
	
	$result = '';
	if ($days != 0)
	{
		$result = $days;
		if($days==1)
			$result .= ' diena ';
		else
			$result .= ' dienas ';
	}
	if ($hours != 0) 
	{
		$result .= $hours;
		if(in_array($hours, array(1,21)))
			$result .= ' valanda ';
		elseif(in_array($hours, array(2,3,4,5,6,7,8,9,22,23)))
			$result .= ' valandas ';
		else	
			$result .= ' valandu ';
	}
	if($min != 0)
	{
		$result .= $min;
		if(in_array($min, array(1,21,31,41,51)))
			$result .= ' minute ';
		elseif(in_array($min, array(2,3,4,5,6,7,8,9,22,23,24,25,26,27,28,29,32,33,34,35,36,37,38,39,42,43,44,
				45,46,47,48,49,52,53,54,55,56,57,58,59)))
			$result .= ' minutes ';
		else
			$result .= ' minučiu ';
	}
	return $result;
}

function check_ten($ten)
{
	$steps = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4);
	$sum_nb = 0;

	$ten = strtoupper(preg_replace('/[^[:alnum:]\?]/', '', $ten));
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

function to_words($num, $power = 0, $powsuffix = '', $short_version = 0)
{
	// Extracted from lang.lt.php by Piotr Klaban <makler at man dot torun dot pl>
	// from PEAR package Number_Words-0.15
	// added leading space trim's by alec

	$ret = '';
	$_sep = ' ';
	$_minus = 'minus';
	$_digits = array(0 => 'nulis', 'vienas', 'du', 'trys', 'keturi', 'penki', 'šeši', 'septyni', 'aštuoni', 'devyni');
	$_exponent = array(
		0 => array(''),
		3 => array('tūkstantis','tūkstančiai','tūkstančių'),
		6 => array('milijonas','milijonai','milijonų'),
		9 => array('bilijonas','bilijonai','bilijonų'),
		12 => array('trilijonas','trilijonai','trilijonų'),
		15 => array('kvadrilijonas','kvadrilijonai','kvadrilijonų'),
		18 => array('kvintilijonas','kvintilijonai','kvintilijonų')
		);

	if (substr($num, 0, 1) == '-')
	{
		$ret = $_minus;
		$num = substr($num, 1);
	}

	// strip excessive zero signs and spaces
	$num = trim($num);
	$num = preg_replace('/^0+/','',$num);

	if (strlen($num) > 3) {
		$maxp = strlen($num)-1;
		$curp = $maxp;
		for ($p = $maxp; $p > 0; --$p) { // power
			// check for highest power
			if (isset($_exponent[$p])) {
			// send substr from $curp to $p
				$snum = substr($num, $maxp - $curp, $curp - $p + 1);
				$snum = preg_replace('/^0+/','',$snum);
				if ($snum !== '') {
					$cursuffix = $_exponent[$power][count($_exponent[$power])-1];
					if ($powsuffix != '')
						$cursuffix .= $_sep . $powsuffix;
					$ret .= toWords($snum, $p, $cursuffix);
				}
				$curp = $p - 1;
				continue;
			}
		}
		$num = substr($num, $maxp - $curp, $curp - $p + 1);
		if ($num == 0)
			return $ret;
	}
	elseif ($num == 0 || $num == '')
		return $_sep . $_digits[0];

	$h = $t = $d = 0;

	switch(strlen($num)) {
		case 3:
			$h = (int)substr($num,-3,1);
		case 2:
			$t = (int)substr($num,-2,1);
		case 1:
			$d = (int)substr($num,-1,1);
			break;
		case 0:
			return;
			break;
	}

	if ($h > 1)
		$ret .= $_sep . $_digits[$h] . $_sep . 'šimtai';
	elseif ($h)
		$ret .= $_sep . 'šimtas';

	// ten, twenty etc.
	switch ($t) {
		case 9:
			$ret .= $_sep . 'devyniasdešimt';
			break;
		case 8:
			$ret .= $_sep . 'aštuoniasdešimt';
			break;
		case 7:
			$ret .= $_sep . 'septyniasdešimt';
			break;
		case 6:
			$ret .= $_sep . 'šešiasdešimt';
			break;
		case 5:
			$ret .= $_sep . 'penkiasdešimt';
			break;
		case 4:
			$ret .= $_sep . 'keturiasdešimt';
			break;
		case 3:
			$ret .= $_sep . 'trisdešimt';
			break;
		case 2:
			$ret .= $_sep . 'dvidešimt';
			break;
		case 1:
			switch ($d) {
				case 0:
					$ret .= $_sep . 'dešimt';
					break;
				case 1:
					$ret .= $_sep . 'vienuolika';
					break;
				case 2:
					$ret .= $_sep . 'dvylika';
					break;
				case 3:
					$ret .= $_sep . 'trylika';
					break;
				case 4:
					$ret .= $_sep . 'keturiolika';
					break;
				case 5:
					$ret .= $_sep . 'penkiolika';
					break;
				case 6:
					$ret .= $_sep . 'šešiolika';
					break;
				case 7:
					$ret .= $_sep . 'septyniolika';
					break;
				case 8:
					$ret .= $_sep . 'aštuoniolika';
					break;
				case 9:
					$ret .= $_sep . 'devyniolika';
					break;
			}
			break; 
		}

	if ($t != 1 && $d > 0) { // add digits only in <0>,<1,9> and <21,inf>
		if ($d > 1 || !$power || $t)
			$ret .= $_sep . $_digits[$d];
	}

	if ($power > 0) {
		if (isset($_exponent[$power]))
			$lev = $_exponent[$power];
		if (!isset($lev) || !is_array($lev))
			return null;

		//echo " $t $d  <br>";

		if ($t == 1 || ($t > 0 && $d == 0 ))
			$ret .= $_sep . $lev[2];
		elseif ($d > 1)
			$ret .= $_sep . $lev[1];		
		else
			$ret .= $_sep . $lev[0];		
	}

	if ($powsuffix != '')
		$ret .= $_sep . $powsuffix;

	return trim($ret);
}

?>
