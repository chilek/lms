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
    return preg_match('/^CZ[0-9]{8,10}$/', $ten);
}

function check_ssn($ssn)
{
    return preg_match('/^[0-9]{6}\/[0-9]{3,4}$/', $ssn);
}

function check_regon($regon)
{
    return preg_match('/^[0-9]{8,10}$/', $regon);
}

function check_icn($icn)
{
    return preg_match('/^[1-9][0-9]{8}$/', $icn);
}

function bankaccount($id, $account = null)
{
    return iban_account('CZ', 22, $id, $account);
}

function check_bankaccount($account)
{
    return iban_check_account('CZ', 22, $account);
}

function format_bankaccount($account)
{
    return preg_replace('/(..)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
}

function getHolidays($year = null)
{
    return array();
}

/*!
 * \brief Generate random postcode
 *
 * \return string
 */
function generateRandomPostcode()
{
    return rand(1, 9) . sprintf("%04d", rand(0, 9999));
}

function get_currency_value($currency, $date = null)
{
    return exchangeratesapi_get_currency_value($currency, $date);
}
