<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	if (ConfigHelper::checkConfig('phpui.skip_zip_validation')) {
		return true;
	} else {
		return preg_match('/^[0-9]{2}-[0-9]{3}$/', $zip);
	}
}

function check_regon($regon) {
	$regon = str_replace('-', '', $regon);
	$regon = str_replace(' ', '', $regon);
	$regon = preg_replace('/[0]{5}$/', '', $regon);
	$sum_nb = 0;

	if (strlen($regon) == 14) {
		$steps = array(2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8);
	
		for($x = 0; $x < 13; $x++) $sum_nb += $steps[$x] * $regon[$x];
	
		$mod = $sum_nb % 11;
		
		if($mod == 10) $mod = 0;
	
		if($mod == $regon[13]) return true;
	} else if (strlen($regon) == 9) {
		$steps = array(8, 9, 2, 3, 4, 5, 6, 7);
	
		for($x = 0; $x < 8; $x++) $sum_nb += $steps[$x] * $regon[$x];
	
		$mod = $sum_nb % 11;
		
		if($mod == 10) $mod = 0;
	
		if($mod == $regon[8]) return true;
	} elseif (strlen($regon) == 7) {
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

function bankaccount($id, $account = NULL) {
	return iban_account('PL', 26, $id, $account);
}

function check_bankaccount($account) {
	return iban_check_account('PL', 26, $account);
}

function format_bankaccount($account) {
	return preg_replace('/(..)(....)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6} ${7}', $account);
}

function getHolidays($year = null) {
	if(!$year) $year = date("Y");
	$easterDay = easter_date($year);

	$days[mktime(0,0,0,1,1,$year)] = 'Nowy Rok';
	$days[mktime(0,0,0,1,6,$year)] = 'Objawienie Pańskie (Trzech Króli)';
	$days[$easterDay] = 'Pierwszy dzień Wielkiej Nocy (Niedziela Wielkanocna)';
	$days[strtotime('+1 day', $easterDay)] = 'Drugi dzień Wielkiej Nocy (Poniedziałek Wielkanocny)';
	$days[mktime(0,0,0,5,1,$year)] = 'Święto Państwowe (Święto Pracy)';
	$days[mktime(0,0,0,5,3,$year)] = 'Święto Narodowe Trzeciego Maja (Święto Konstytucji Trzeciego Maja)';
	$days[strtotime('+49 day', $easterDay)] = 'Zesłanie Ducha Świętego (Zielone Świątki)';
	$days[strtotime('+60 day', $easterDay)] = 'Ciała i Krwi Pańskiej (Boże Ciało)';
	$days[mktime(0,0,0,8,15,$year)] = 'Wniebowzięcie Najświętszej Maryi Panny';
	$days[mktime(0,0,0,11,1,$year)] = 'Wszystkich Świętych (Dzień Zmarłych)';
	$days[mktime(0,0,0,11,11,$year)] = 'Narodowe Święto Niepodległości (Dzień Niepodległości)';
	$days[mktime(0,0,0,12,25,$year)] = 'Pierwszy dzień Bożego Narodzenia';
	$days[mktime(0,0,0,12,26,$year)] = 'Drugi dzień Bożego Narodzenia';

	return $days;
}

/*!
 * \brief Generate random postcode
 *
 * \return string
 */
function generateRandomPostcode() {
    return sprintf("%02d", rand(0, 99)) . '-' . sprintf("%03d", rand(0, 999));
}

?>
