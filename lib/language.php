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


$LANGDEFS = array(
    'pl_PL' => array(
        'name' => 'Polish',
        'orig' => 'Polski',
        'locale' => 'pl_PL.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'pl',
        'money_format' => '%01.2f zł',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'PLN',
//        'mobile' => '(88[0-9]|5[01][0-9]|6[069][0-9]|7[2789][0-9])[0-9]{6}',
    ),
    'pl' => 'pl_PL',
    'lt_LT' => array(
        'name' => 'Lithuanian',
        'orig' => 'Litewski',
        'locale' => 'lt_LT.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'lt',
        'money_format' => '%01.2f EUR',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'EUR',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'lt' => 'lt_LT',
    'en_US' => array(
        'name' => 'English',
        'orig' => 'English',
        'locale' => 'en_US.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'en',
        'money_format' => '$ %01.2f',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'USD',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'en' => 'en_US',
    'en_GY' => array(
        'name' => 'English (Guyana)',
        'orig' => 'English (Guyana)',
        'locale' => 'en_GY.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'en',
        'money_format' => '$ %01.2f',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'GYD',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'sk_SK' => array(
        'name' => 'Slovak',
        'orig' => 'Slovenský',
        'locale' => 'sk_SK.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'sk',
        'money_format' => '%01.2f EUR',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'EUR',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'sk' => 'sk_SK',
    'ro_RO' => array(
        'name' => 'Romanian',
        'orig' => 'Romana',
        'locale' => 'ro_RO.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'ro',
        'money_format' => '%01.2f RON',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'RON',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'ro' => 'ro_RO',
    'cs_CZ' => array(
        'name' => 'Czech',
        'orig' => 'Česky',
        'locale' => 'cs_CZ.UTF-8',
        'charset' => 'UTF-8',
        'html' => 'cs',
        'money_format' => '%01.2f Kč',
        'money_format_in_words' => '%s %s %s/100',
        'currency' => 'CZK',
//        'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
    ),
    'cs' => 'cs_CZ',
);

// UI language
if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
} else {
    $langs = '';
}

$langs = explode(',', $langs);

foreach ($langs as $val) {
    if (strlen($val) >= 5) {
        $val = str_replace('-', '_', substr($val, 0, 5));
        if (isset($LANGDEFS[$val])) {
            $_ui_language = $val;
            break;
        }
    } else {
        $val = substr($val, 0, 2);
        if (isset($LANGDEFS[$val]) && is_string($LANGDEFS[$val])) {
            $_ui_language = $LANGDEFS[$val];
            break;
        }
    }
}

// System language
$lang = ConfigHelper::getConfig('phpui.lang');
if (!empty($lang)) {
    if (isset($LANGDEFS[$lang])) {
        if (is_string($LANGDEFS[$lang])) {
            $_language = $LANGDEFS[$lang];
        } else {
            $_language = $lang;
        }
    } else {
        $_language = 'en_US';
    }
} else if (isset($_ui_language)) {
    $_language = $_ui_language;
} else {
    $_language = 'en_US'; // default language
}

$_currency = $LANGDEFS[$_language]['currency'];

// Use system lang for UI if any of browser langs isn't supported
// or browser langs aren't set
if (!isset($_ui_language)) {
    $_ui_language = $_language;
}

$_LANG = array();
if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_ui_language . DIRECTORY_SEPARATOR . 'strings.php')) {
    include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_ui_language . DIRECTORY_SEPARATOR . 'strings.php');
}
if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_ui_language . DIRECTORY_SEPARATOR . 'ui.php')) {
    include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_ui_language . DIRECTORY_SEPARATOR . 'ui.php');
}
if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_language . DIRECTORY_SEPARATOR . 'system.php')) {
    include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_language . DIRECTORY_SEPARATOR . 'system.php');
}

setlocale(LC_COLLATE, $LANGDEFS[$_language]['locale']);
setlocale(LC_CTYPE, $LANGDEFS[$_language]['locale']);
setlocale(LC_TIME, $LANGDEFS[$_language]['locale']);
setlocale(LC_NUMERIC, $LANGDEFS[$_language]['locale']);

mb_internal_encoding('UTF-8');

$_current_ui_language = $_ui_language;

function refresh_ui_language($lang)
{
    global $_current_ui_language;
    global $_LANG;
    global $LANGDEFS;
    if (!empty($lang)) {
        if (strlen($lang) >= 5) {
            $language = substr($lang, 0, 5);
            if (!isset($LANGDEFS[$language])) {
                $language = 'en_US';
            }
        } else {
            $language = substr($lang, 0, 2);
            if (isset($LANGDEFS[$language]) && is_string($LANGDEFS[$language])) {
                $language = $LANGDEFS[$language];
            } else {
                $language = 'en_US';
            }
        }
        if ($language != $_current_ui_language) {
            $_current_ui_language = $language;
            $_LANG = array();
            if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_current_ui_language . DIRECTORY_SEPARATOR . 'strings.php')) {
                include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_current_ui_language . DIRECTORY_SEPARATOR . 'strings.php');
            }
        }
    }
}

function reset_ui_language()
{
    global $_current_ui_language;
    global $_ui_language;
    global $_LANG;
    if ($_current_ui_language != $_ui_language) {
        $_current_ui_language = $_ui_language;
        $_LANG = array();
        if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_current_ui_language . DIRECTORY_SEPARATOR . 'strings.php')) {
            include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . $_current_ui_language . DIRECTORY_SEPARATOR . 'strings.php');
        }
    }
}
