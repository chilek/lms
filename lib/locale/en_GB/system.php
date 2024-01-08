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

self::addLanguageFunctions(
    self::SYSTEM_FUNCTION,
    array(
        'check_zip' => function ($zip) {
            return preg_match('/^[A-Z][0-9A-Z]{1,3}\s+[0-9][0-9A-Z]{2}$/', $zip);
        },
        'check_ten' => function ($ten) {
            return true;
        },
        'check_ssn' => function ($ssn) {
            $ssn = str_replace(array('-','/',' ',"\t","\n"), '', $ssn);

            if (!preg_match('/^[A-Z]{2}[0-9]{6}[A-Z]$/', $ssn)) {
                return false;
            }

            return true;
        },
        'check_regon' => function ($regon) {
            return true;
        },
        'check_icn' => function ($icn) {
            return true;
        },
        'bankaccount' => function ($id, $account = null, $country_code = false) {
            return iban_account('GB', 20, $id, $account);
        },
        'check_bankaccount' => function ($account) {
            return iban_check_account('GB', 20, $account);
        },
        'format_bankaccount' => function ($account, $country_code = false) {
            if ($country_code) {
                return preg_replace('/(....)(....)(....)(....)(....)(..)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
            } else {
                return preg_replace('/(..)(....)(....)(....)(....)(..)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
            }
        },
        'format_ten' => function ($ten, $country_code = false) {
            if ($country_code) {
                $ten = preg_replace('/[ \-]/', '', $ten);
            }
            if (strpos($ten, 'GB') === 0) {
                $ten = substr($ten, 2);
            }
            return ($country_code ? 'GB' : '') . $ten;
        },
        'getHolidays' => function ($year = null) {
            return array();
        },
        /*!
         * \brief Generate random postcode
         *
         * \return string
         */
        'generateRandomPostcode' => function () {
            return sprintf("%05d", rand(0, 99999)) . '-' . sprintf("%04d", rand(0, 9999));
        },
        'get_currency_value' => function ($currency, $date = null) {
            return exchangeratesapi_get_currency_value($currency, $date);
        },
    )
);
