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

function uptimef($ts)
{
	if ($ts < 60) {
		return trans('less than one minute ago');
	}

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
					$ret .= to_words($snum, $p, $cursuffix);
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
