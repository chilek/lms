<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

Localisation::init();

function uptimef($ts)
{
    return Localisation::callUiFunction('uptimef', $ts);
}

function to_words($num, $power = 0, $powsuffix = '', $short_version = 0)
{
    return Localisation::callUiFunction('to_words', $num, $power, $powsuffix, $short_version);
}

function check_zip($zip)
{
    return Localisation::CallSystemFunction('check_zip', $zip);
}

function check_ten($ten)
{
    return Localisation::CallSystemFunction('check_ten', $ten);
}

function check_ssn($ssn)
{
    return Localisation::CallSystemFunction('check_ssn', $ssn);
}

function check_regon($regon)
{
    return Localisation::CallSystemFunction('check_regon', $regon);
}

function check_icn($icn)
{
    return Localisation::CallSystemFunction('check_icn', $icn);
}

function bankaccount($id, $account = null, $country_code = false)
{
    return Localisation::CallSystemFunction('bankaccount', $id, $account, $country_code);
}

function check_bankaccount($account)
{
    return Localisation::CallSystemFunction('check_bankaccount', $account);
}

function format_bankaccount($account, $country_code = false)
{
    return Localisation::CallSystemFunction('format_bankaccount', $account, $country_code);
}

function getHolidays($year = null)
{
    return Localisation::CallSystemFunction('getHolidays', $year);
}

function generateRandomPostcode()
{
    return Localisation::CallSystemFunction('generateRandomPostcode');
}

function get_currency_value($currency, $date = null)
{
    return Localisation::CallSystemFunction('get_currency_value', $currency, $date);
}
