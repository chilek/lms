<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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
	if($ts==0)
		return 'n/a';
	$min= $ts / 60;
	$hours = $min / 60;
	$days  = floor($hours / 24);
	$hours = floor($hours - ($days * 24));
	$min= floor($min - ($days * 60 * 24) - ($hours * 60));
	if ($days != 0)
	{
		$result = $days;
		if($days==1)
			$result .= ' dzie� ';
		else
			$result .= ' dni ';
	}
	if ($hours != 0) 
	{
		$result .= $hours;
		if($hours==1)
			$result .= ' godzina ';
		elseif(in_array($hours, array(2,3,4,22,23)))
			$result .= ' godziny ';
		else	
			$result .= ' godzin ';
	}
	if($min != 0)
	{
		$result .= $min;
		if($min==1)
			$result .= ' minuta ';
		elseif(in_array($min, array(2,3,4,22,23,24,32,33,34,42,43,44,52,53,54)))
			$result .= ' minuty ';
		else
			$result .= ' minut ';
	}
	return $result;
}

function check_fin($fin)
{
	$steps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);

	$fin = str_replace('-', '', $fin);
	$fin = str_replace(' ', '', $fin);

	if (strlen($fin) != 10) return FALSE;

	for ($x = 0; $x < 9; $x++) $sum_nb += $steps[$x] * $fin[$x];

	if ($sum_nb % 11 == $fin[9]) return TRUE;

	return FALSE;
}

function check_ssn($ssn)
{
	// AFAIR This doesn't cover people born after Y2k, they have month+20
	// Be warned.
	
	if (!eregi('^[0-9]{11}$',$ssn))
		return FALSE;
	
	$steps = array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3);
	
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
	return eregi('^[0-9]{2}-[0-9]{3}$', $zip);
}

function to_words($num, $power = 0, $powsuffix = '', $short_version = 0)
{
	// Extracted from lang.pl.php by Piotr Klaban <makler at man dot torun dot pl>
	// from PEAR package Number_Words-0.3.1
	// 'short_version' added by alec/kubatyszko
	// added leading space trim's by alec
	
	if($short_version)
	{
	        $patterns[0] = "/0/";
    		$patterns[1] = "/1/";
	        $patterns[2] = "/2/";
    		$patterns[3] = "/3/";
		$patterns[4] = "/4/";
	        $patterns[5] = "/5/";
    		$patterns[6] = "/6/";
	        $patterns[7] = "/7/";
    		$patterns[8] = "/8/";
	        $patterns[9] = "/9/";

    		$replacements[0] = "zer ";
                $replacements[1] = "jed ";
		$replacements[2] = "dwa ";
	        $replacements[3] = "trz ";
    		$replacements[4] = "czt ";
    	        $replacements[5] = "pi� ";
	        $replacements[6] = "sze ";
	        $replacements[7] = "sie ";
    		$replacements[8] = "osi ";
		$replacements[9] = "dzi ";

	        return trim(preg_replace($patterns, $replacements, $num));
	}

	$ret = '';
	$_sep = ' ';
	$_minus = 'minus';
	$_digits = array(0 => 'zero', 'jeden', 'dwa', 'trzy', 'cztery', 'pi��', 'sze��', 'siedem', 'osiem', 'dziewi��');		
	$_exponent = array(
			0 => array('','',''),
			3 => array('tysi�c','tysi�ce','tysi�cy'),
			6 => array('milion','miliony','milion�w'),
			9 => array('miliard','miliardy','miliard�w'),
			12 => array('bilion','biliony','bilion�w'),
			15 => array('biliard','biliardy','biliard�w'),
			18 => array('trylion','tryliony','trylion�w'),
			21 => array('tryliard','tryliardy','tryliard�w'),
			24 => array('kwadrylion','kwadryliony','kwadrylion�w'),
			27 => array('kwadryliard','kwadryliardy','kwadryliard�w'),
			30 => array('kwintylion','kwintyliony','kwintylion�w'),
			33 => array('kwintyliiard','kwintyliardy','kwintyliard�w'),
			36 => array('sekstylion','sekstyliony','sekstylion�w'),
			39 => array('sekstyliard','sekstyliardy','sekstyliard�w'),
			42 => array('septylion','septyliony','septylion�w'),
			45 => array('septyliard','septyliardy','septyliard�w'),
			48 => array('oktylion','oktyliony','oktylion�w'),
			51 => array('oktyliard','oktyliardy','oktyliard�w'),
			54 => array('nonylion','nonyliony','nonylion�w'),
			57 => array('nonyliard','nonyliardy','nonyliard�w'),
			60 => array('decylion','decyliony','decylion�w'),
			63 => array('decyliard','decyliardy','decyliard�w'),
			100 => array('centylion','centyliony','centylion�w'),
			103 => array('centyliard','centyliardy','centyliard�w'),
			120 => array('wicylion','wicylion','wicylion'),
			123 => array('wicyliard','wicyliardy','wicyliard�w'),
			180 => array('trycylion','trycylion','trycylion'),
			183 => array('trycyliard','trycyliardy','trycyliard�w'),
			240 => array('kwadragilion','kwadragilion','kwadragilion'),
			243 => array('kwadragiliard','kwadragiliardy','kwadragiliard�w'),
			300 => array('kwinkwagilion','kwinkwagilion','kwinkwagilion'),
			303 => array('kwinkwagiliard','kwinkwagiliardy','kwinkwagiliard�w'),
			360 => array('seskwilion','seskwilion','seskwilion'),
			363 => array('seskwiliard','seskwiliardy','seskwiliard�w'),
			420 => array('septagilion','septagilion','septagilion'),
			423 => array('septagiliard','septagiliardy','septagiliard�w'),
			480 => array('oktogilion','oktogilion','oktogilion'),
			483 => array('oktogiliard','oktogiliardy','oktogiliard�w'),
			540 => array('nonagilion','nonagilion','nonagilion'),
			543 => array('nonagiliard','nonagiliardy','nonagiliard�w'),
			600 => array('centylion','centyliony','centylion�w'),
			603 => array('centyliard','centyliardy','centyliard�w'),
			6000018 => array('milinilitrylion','milinilitryliony','milinilitrylion�w')
	);

	if (substr($num, 0, 1) == '-')
	{
		$ret = $_minus;
		$num = substr($num, 1);
	}

	// strip excessive zero signs and spaces
	$num = trim($num);
	$num = preg_replace('/^0+/','',$num);

	if (strlen($num) > 3)
	{
		$maxp = strlen($num)-1;
		$curp = $maxp;
		for ($p = $maxp; $p > 0; --$p)
		{ // power

			// check for highest power
			if (isset($_exponent[$p]))
			{ // send substr from $curp to $p
				$snum = substr($num, $maxp - $curp, $curp - $p + 1);
				$snum = preg_replace('/^0+/','',$snum);
				if ($snum !== '')
				{
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
		$ret = trim($ret);
		if ($num == 0)
		{
			return $ret;
		}
	}
	elseif ($num == 0 || $num == '')
	{
		return $_digits[0];
	}

	$h = $t = $d = 0;

	switch(strlen($num))
	{
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

	switch ($h)
	{
		case 9:
			$ret .= $_sep . 'dziewi��set';
			break;

		case 8:
			$ret .= $_sep . 'osiemset';
			break;

		case 7:
			$ret .= $_sep . 'siedemset';
			break;

		case 6:
			$ret .= $_sep . 'sze��set';
			break;

		case 5:
			$ret .= $_sep . 'pi��set';
			break;

		case 4:
			$ret .= $_sep . 'czterysta';
			break;

		case 3:
			$ret .= $_sep . 'trzysta';
			break;

		case 2:
			$ret .= $_sep . 'dwie�cie';
			break;

		case 1:
			$ret .= $_sep . 'sto';
			break;
	}
	
	switch ($t)
	{
		case 9:
		case 8:
		case 7:
		case 6:
		case 5:
			$ret .= $_sep . $_digits[$t] . 'dziesi�t';
			break;

		case 4:
			$ret .= $_sep . 'czterdzie�ci';
			break;

		case 3:
			$ret .= $_sep . 'trzydzie�ci';
			break;

		case 2:
			$ret .= $_sep . 'dwadzie�cia';
			break;

		case 1:
			switch ($d)
			{
				case 0:
					$ret .= $_sep . 'dziesi��';
					break;

				case 1:
					$ret .= $_sep . 'jedena�cie';
					break;

				case 2:
				case 3:
				case 7:
				case 8:
					$ret .= $_sep . $_digits[$d] . 'na�cie';
					break;

				case 4:
					$ret .= $_sep . 'czterna�cie';
					break;

				case 5:
					$ret .= $_sep . 'pi�tna�cie';
					break;

				case 6:
					$ret .= $_sep . 'szesna�cie';
					break;

				case 9:
					$ret .= $_sep . 'dziewi�tna�cie';
					break;
			}
			break;
	}

	if ($t != 1 && $d > 0)
		$ret .= $_sep . $_digits[$d];

	if ($t == 1)
		$d = 0;

	if (( $h + $t ) > 0 && $d == 1)
		$d = 0;

	if ($power > 0)
	{
		if (isset($_exponent[$power]))
			$lev = $_exponent[$power];

		if (!isset($lev) || !is_array($lev))
			return null;

		switch ($d)
		{
			case 1:
				$suf = $lev[0];
				break;
			case 2:
			case 3:
			case 4:
				$suf = $lev[1];
				break;
			case 0:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
				$suf = $lev[2];
				break;
		}
		$ret .= $_sep . $suf;
	}

	if ($powsuffix != '')
		$ret .= $_sep . $powsuffix;

	return trim($ret);
}

?>
