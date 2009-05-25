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
	
	if(!empty($account) && $acclen < 21 && $acclen >= 8)
	{
		$cc = '2521';	// Kod kraju - Polska
		$format = '%0'.(24 - $acclen) .'d';
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
			$result .= ' dzień ';
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
	if (!eregi('^[0-9]{11}$',$ssn))
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
	return eregi('^[0-9]{2}-[0-9]{3}$', $zip);
}

function check_gg($im)
{
	return eregi('^[0-9]{0,32}$', $im);  // gadu-gadu ID check
}

function check_yahoo($im)
{
	return eregi('^[-_.a-z0-9]{0,32}$', $im);
}

function check_skype($im)
{
	return eregi('^[-_.a-z0-9]{0,32}$', $im);
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

	// poprawny format numeru dowodu osobistego to 9 znakow w tym:
	//    - 2 litery i 7 cyfr lub
	//    - 3 litery i 6 cyfr

	return preg_match('/^[A-Z]{2}[0-9]{7}$/i', $icn) || preg_match('/^[A-Z]{3}[0-9]{6}$/i', $icn);
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
    	        $replacements[5] = "pię ";
	        $replacements[6] = "sze ";
	        $replacements[7] = "sie ";
    		$replacements[8] = "osi ";
		$replacements[9] = "dzi ";

	        return trim(preg_replace($patterns, $replacements, $num));
	}

	$ret = '';
	$_sep = ' ';
	$_minus = 'minus';
	$_digits = array(0 => 'zero', 'jeden', 'dwa', 'trzy', 'cztery', 'pięć', 'sześć', 'siedem', 'osiem', 'dziewięć');		
	$_exponent = array(
			0 => array('','',''),
			3 => array('tysiąc','tysiące','tysięcy'),
			6 => array('milion','miliony','milionów'),
			9 => array('miliard','miliardy','miliardów'),
			12 => array('bilion','biliony','bilionów'),
			15 => array('biliard','biliardy','biliardów'),
			18 => array('trylion','tryliony','trylionów'),
			21 => array('tryliard','tryliardy','tryliardów'),
			24 => array('kwadrylion','kwadryliony','kwadrylionów'),
			27 => array('kwadryliard','kwadryliardy','kwadryliardów'),
			30 => array('kwintylion','kwintyliony','kwintylionów'),
			33 => array('kwintyliiard','kwintyliardy','kwintyliardów'),
			36 => array('sekstylion','sekstyliony','sekstylionów'),
			39 => array('sekstyliard','sekstyliardy','sekstyliardów'),
			42 => array('septylion','septyliony','septylionów'),
			45 => array('septyliard','septyliardy','septyliardów'),
			48 => array('oktylion','oktyliony','oktylionów'),
			51 => array('oktyliard','oktyliardy','oktyliardów'),
			54 => array('nonylion','nonyliony','nonylionów'),
			57 => array('nonyliard','nonyliardy','nonyliardów'),
			60 => array('decylion','decyliony','decylionów'),
			63 => array('decyliard','decyliardy','decyliardów'),
			100 => array('centylion','centyliony','centylionów'),
			103 => array('centyliard','centyliardy','centyliardów'),
			120 => array('wicylion','wicylion','wicylion'),
			123 => array('wicyliard','wicyliardy','wicyliardów'),
			180 => array('trycylion','trycylion','trycylion'),
			183 => array('trycyliard','trycyliardy','trycyliardów'),
			240 => array('kwadragilion','kwadragilion','kwadragilion'),
			243 => array('kwadragiliard','kwadragiliardy','kwadragiliardów'),
			300 => array('kwinkwagilion','kwinkwagilion','kwinkwagilion'),
			303 => array('kwinkwagiliard','kwinkwagiliardy','kwinkwagiliardów'),
			360 => array('seskwilion','seskwilion','seskwilion'),
			363 => array('seskwiliard','seskwiliardy','seskwiliardów'),
			420 => array('septagilion','septagilion','septagilion'),
			423 => array('septagiliard','septagiliardy','septagiliardów'),
			480 => array('oktogilion','oktogilion','oktogilion'),
			483 => array('oktogiliard','oktogiliardy','oktogiliardów'),
			540 => array('nonagilion','nonagilion','nonagilion'),
			543 => array('nonagiliard','nonagiliardy','nonagiliardów'),
			600 => array('centylion','centyliony','centylionów'),
			603 => array('centyliard','centyliardy','centyliardów'),
			6000018 => array('milinilitrylion','milinilitryliony','milinilitrylionów')
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
					$ret .=' ';
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
			$ret .= $_sep . 'dziewięćset';
			break;

		case 8:
			$ret .= $_sep . 'osiemset';
			break;

		case 7:
			$ret .= $_sep . 'siedemset';
			break;

		case 6:
			$ret .= $_sep . 'sześćset';
			break;

		case 5:
			$ret .= $_sep . 'pięćset';
			break;

		case 4:
			$ret .= $_sep . 'czterysta';
			break;

		case 3:
			$ret .= $_sep . 'trzysta';
			break;

		case 2:
			$ret .= $_sep . 'dwieście';
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
			$ret .= $_sep . $_digits[$t] . 'dziesiąt';
			break;

		case 4:
			$ret .= $_sep . 'czterdzieści';
			break;

		case 3:
			$ret .= $_sep . 'trzydzieści';
			break;

		case 2:
			$ret .= $_sep . 'dwadzieścia';
			break;

		case 1:
			switch ($d)
			{
				case 0:
					$ret .= $_sep . 'dziesięć';
					break;

				case 1:
					$ret .= $_sep . 'jedenaście';
					break;

				case 2:
				case 3:
				case 7:
				case 8:
					$ret .= $_sep . $_digits[$d] . 'naście';
					break;

				case 4:
					$ret .= $_sep . 'czternaście';
					break;

				case 5:
					$ret .= $_sep . 'piętnaście';
					break;

				case 6:
					$ret .= $_sep . 'szesnaście';
					break;

				case 9:
					$ret .= $_sep . 'dziewiętnaście';
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
