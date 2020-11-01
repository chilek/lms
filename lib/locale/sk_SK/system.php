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
            return preg_match('/^[0-9]{3}[\-\s]?[0-9]{2}$/', $zip);
        },
        'check_ten' => function ($ten) {
            $steps = array(6, 5, 7, 2, 3, 4, 5, 6, 7);
            $sum_nb = 0;

            $ten = str_replace('-', '', $ten);
            $ten = str_replace(' ', '', $ten);

            if (strlen($ten) != 10) {
                return false;
            }

            for ($x = 0; $x < 9; $x++) {
                $sum_nb += $steps[$x] * $ten[$x];
            }

            if ($sum_nb % 11 == $ten[9]) {
                return true;
            }

            return false;
        },
        'check_ssn' => function ($ssn) {
            // AFAIR This doesn't cover people born after Y2k, they have month+20
            // Be warned.
            if (!preg_match('/^[0-9]{11}$/', $ssn)) {
                return false;
            }

            $steps = array(1, 3, 7, 9, 1, 3, 7, 9, 1, 3);
            $sum_nb = 0;

            for ($x = 0; $x < 10; $x++) {
                $sum_nb += $steps[$x] * $ssn[$x];
            }

            $sum_m = 10 - $sum_nb % 10;

            if ($sum_m == 10) {
                $sum_c = 0;
            } else {
                $sum_c = $sum_m;
            }

            if ($sum_c == $ssn[10]) {
                return true;
            }
            return false;
        },
        'check_regon' => function ($regon) {
            $regon = str_replace('-', '', $regon);
            $regon = str_replace(' ', '', $regon);
            $sum_nb = 0;

            if (strlen($regon) == 9) {
                $steps = array(8, 9, 2, 3, 4, 5, 6, 7);

                for ($x = 0; $x < 8; $x++) {
                    $sum_nb += $steps[$x] * $regon[$x];
                }

                $mod = $sum_nb % 11;

                if ($mod == 10) {
                    $mod = 0;
                }

                if ($mod == $regon[8]) {
                    return true;
                }
            } elseif (strlen($regon) == 7) {
                $steps = array(2, 3, 4, 5, 6, 7);

                for ($x = 0; $x < 6; $x++) {
                    $sum_nb += $steps[$x] * $regon[$x];
                }

                $mod = $sum_nb % 11;

                if ($mod == 10) {
                    $mod = 0;
                }

                if ($mod == $regon[6]) {
                    return true;
                }
            }

            return false;
        },
        'check_icn' => function ($icn) {
            $icn = str_replace(' ', '', $icn);

            // poprawny format numeru dowodu osobistego to 9 znakow w tym:
            //    - 2 litery i 7 cyfr lub
            //    - 3 litery i 6 cyfr

            return preg_match('/^[A-Z]{2}[0-9]{7}$/i', $icn) || preg_match('/^[A-Z]{3}[0-9]{6}$/i', $icn);
        },
        'bankaccount' => function ($id, $account = null, $country_code = false) {
            return ($country_code && strpos($account, 'SK') !== 0 ? 'SK' : '') . iban_account('SK', 22, $id, $account);
        },
        'check_bankaccount' => function ($account) {
            if (strpos($account, 'SK') === 0) {
                $account = substr($account, 2);
            }
            return iban_check_account('SK', 22, $account);
        },
        'format_bankaccount' => function ($account, $country_code = false) {
            if ($country_code) {
                return preg_replace('/(....)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
            } else {
                return preg_replace('/(..)(....)(....)(....)(....)(....)/i', '${1} ${2} ${3} ${4} ${5} ${6}', $account);
            }
        },
        'format_ten' => function ($ten, $country_code = false) {
            if ($country_code) {
                $ten = preg_replace('/[ \-]/', '', $ten);
            }
            if (strpos($ten, 'SK') === 0) {
                $ten = substr($ten, 2);
            }
            return ($country_code ? 'SK' : '') . $ten;
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
            return sprintf("%03d", rand(0, 999)) . ' ' . sprintf("%02d", rand(0, 99));
        },
        'get_currency_value' => function ($currency, $date = null) {
            return exchangeratesapi_get_currency_value($currency, $date);
        },
    )
);
