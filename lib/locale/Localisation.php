<?php

/**
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

class Localisation
{
    const UI_FUNCTION = 'ui_functions';
    const SYSTEM_FUNCTION = 'system_functions';

    private static $langDefs = array();
    private static $defaultUiLanguage = null;
    private static $uiLanguage = null;
    private static $systemLanguage = null;
    private static $defaultSystemLanguage = null;
    private static $defaultCurrency = null;
    private static $uiStrings = array();

    public static function init()
    {
        self::$langDefs = array(
            'pl_PL' => array(
                'name' => 'Polish',
                'orig' => 'Polski',
                'locale' => 'pl_PL.UTF-8',
                'charset' => 'UTF-8',
                'html' => 'pl',
                'money_format' => '%01.2f zł',
                'money_format_in_words' => '%s %s %s/100',
                'currency' => 'PLN',
                'vies_code' => 'PL',
                //'mobile' => '(88[0-9]|5[01][0-9]|6[069][0-9]|7[2789][0-9])[0-9]{6}',
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
                'vies_code' => 'LT',
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
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
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
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
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
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
                'vies_code' => 'SK',
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
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
                'vies_code' => 'RO',
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
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
                'vies_code' => 'CZ',
                //'mobile' => '(88[08]|50[0-9]|6[09][0-9])[0-9]{6}',
            ),
            'cs' => 'cs_CZ',
        );

        self::detectUiLanguage();
        self::detectSystemLanguage();
        self::fixUiLanguage();

        self::loadUiLanguage();
        self::loadSystemLanguage();

        mb_internal_encoding('UTF-8');
    }

    private static function checkLanguage($lang)
    {
        if (strlen($lang) >= 5) {
            $lang = str_replace('-', '_', substr($lang, 0, 5));
            if (isset(self::$langDefs[$lang])) {
                return $lang;
            }
        } else {
            $lang = substr($lang, 0, 2);
            if (isset(self::$langDefs[$lang]) && is_string(self::$langDefs[$lang])) {
                return self::$langDefs[$lang];
            }
        }
        return null;
    }

    private static function detectUiLanguage()
    {
        if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $langs = '';
        }

        $langs = explode(',', $langs);

        foreach ($langs as $val) {
            self::$defaultUiLanguage = self::$uiLanguage = self::checkLanguage($val);
            if (isset(self::$defaultUiLanguage)) {
                break;
            }
        }
    }

    private static function detectSystemLanguage()
    {
        $lang = ConfigHelper::getConfig('phpui.lang');
        if (!empty($lang)) {
            if (isset(self::$langDefs[$lang])) {
                if (is_string(self::$langDefs[$lang])) {
                    self::$defaultSystemLanguage = self::$systemLanguage = self::$langDefs[$lang];
                } else {
                    self::$defaultSystemLanguage = self::$systemLanguage = $lang;
                }
            } else {
                self::$defaultSystemLanguage = self::$systemLanguage = 'en_US';
            }
        } else if (isset(self::$defaultUiLanguage)) {
            self::$defaultSystemLanguage = self::$systemLanguage = self::$defaultUiLanguage;
        } else {
            self::$defaultSystemLanguage = self::$systemLanguage = 'en_US'; // default language
        }

        self::setLocales();
    }

    // Use system lang for UI if any of browser langs isn't supported
    // or browser langs aren't set
    private static function fixUiLanguage()
    {
        if (!isset(self::$uiLanguage)) {
            self::$defaultUiLanguage = self::$uiLanguage = self::$systemLanguage;
        }
    }

    public static function initDefaultCurrency()
    {
        self::$defaultCurrency = ConfigHelper::getConfig('phpui.default_currency', '', true);
        if (empty(self::$defaultCurrency) || !isset($GLOBALS['CURRENCIES'][self::$defaultCurrency])) {
            self::$defaultCurrency = self::$langDefs[self::$systemLanguage]['currency'];
        }
    }

    private static function setLocales()
    {
        $locale = self::$langDefs[self::$systemLanguage]['locale'];
        setlocale(LC_COLLATE, $locale);
        setlocale(LC_CTYPE, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_NUMERIC, $locale);
    }

    public static function getCurrentCurrency()
    {
        return self::$langDefs[self::$systemLanguage]['currency'];
    }

    public static function getDefaultCurrency()
    {
        return self::$defaultCurrency;
    }

    public static function getCurrentMoneyFormat()
    {
        return self::$langDefs[self::$systemLanguage]['money_format'];
    }

    public static function getCurrentMoneyFormatInWords()
    {
        return self::$langDefs[self::$systemLanguage]['money_format_in_words'];
    }

    public static function getCurrentViesCode()
    {
        return isset(self::$langDefs[self::$systemLanguage]['vies_code']) ? self::$langDefs[self::$systemLanguage]['vies_code'] : null;
    }

    public static function getViesCodeByCountryCode($countryCode)
    {
        $lang = self::checkLanguage($countryCode);
        return isset(self::$langDefs[$lang]['vies_code']) ? self::$langDefs[$lang]['vies_code'] : null;
    }

    public static function getCurrentHtmlCharset()
    {
        return self::$langDefs[self::$uiLanguage]['charset'];
    }

    public static function getCurrentHtmlLanguage()
    {
        return self::$langDefs[self::$uiLanguage]['html'];
    }

    private static function loadUiLanguage()
    {
        if (isset(self::$uiStrings[self::$uiLanguage])) {
            return;
        }

        $_LANG = array();
        if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'strings.php')) {
            include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'strings.php');
        }
        if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'ui.php')) {
            include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'ui.php');
        }
        self::$uiStrings[self::$uiLanguage] = $_LANG;
    }

    public static function appendUiLanguage($baseDir)
    {
        $_LANG = array();
        if (@is_readable($baseDir . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'strings.php')) {
            include($baseDir . DIRECTORY_SEPARATOR . self::$uiLanguage . DIRECTORY_SEPARATOR . 'strings.php');
        } elseif (@is_readable($baseDir . DIRECTORY_SEPARATOR . substr(self::$uiLanguage, 0, 2) . DIRECTORY_SEPARATOR . 'strings.php')) {
            include($baseDir . DIRECTORY_SEPARATOR . substr(self::$uiLanguage, 0, 2) . DIRECTORY_SEPARATOR . 'strings.php');
        }
        if (!empty($_LANG)) {
            self::$uiStrings[self::$uiLanguage] = array_merge(self::$uiStrings[self::$uiLanguage], $_LANG);
        }
    }

    public static function addLanguageFunctions($type, array $functions)
    {
        $language = $type == self::UI_FUNCTION ? self::$uiLanguage : self::$systemLanguage;

        if (!isset(self::$langDefs[$language])) {
            return;
        }
        if (!isset(self::$langDefs[$language][$type])) {
            self::$langDefs[$language][$type] = array();
        }
        self::$langDefs[$language][$type] = array_merge(
            self::$langDefs[$language][$type],
            $functions
        );
    }

    public static function setUiLanguage($lang)
    {
        if ($lang == self::$uiLanguage || empty($lang) || !isset(self::$langDefs[$lang])) {
            return;
        }

        $uiLanguage = self::checkLanguage($lang);
        if (isset($uiLanguage)) {
            self::$uiLanguage = $uiLanguage;
            if (!isset(self::$uiStrings[$uiLanguage])) {
                self::loadUiLanguage();
            }
        }
    }

    public static function getCurrentUiLanguage()
    {
        return self::$uiLanguage;
    }

    public static function resetUiLanguage()
    {
        self::$uiLanguage = self::$defaultUiLanguage;
    }

    public static function getCurrentSystemLanguage()
    {
        return self::$systemLanguage;
    }

    public static function resetSystemLanguage()
    {
        self::$systemLanguage = self::$defaultSystemLanguage;
        self::setLocales();
    }

    private static function loadSystemLanguage()
    {
        if (isset(self::$langDefs[self::$systemLanguage][self::SYSTEM_FUNCTION])
            && !empty(self::$langDefs[self::$systemLanguage][self::SYSTEM_FUNCTION])) {
            return;
        }

        if (@is_readable(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$systemLanguage . DIRECTORY_SEPARATOR . 'system.php')) {
            include(LIB_DIR . DIRECTORY_SEPARATOR . 'locale' . DIRECTORY_SEPARATOR . self::$systemLanguage . DIRECTORY_SEPARATOR . 'system.php');
        }
    }

    public static function setSystemLanguage($lang)
    {
        if ($lang == self::$systemLanguage || empty($lang) || !isset(self::$langDefs[$lang])) {
            return;
        }

        $systemLanguage = self::checkLanguage($lang);
        if (isset($systemLanguage)) {
            self::$systemLanguage = $systemLanguage;
            if (!isset(self::$langDefs[self::$systemLanguage][self::SYSTEM_FUNCTION])) {
                self::$langDefs[self::$systemLanguage][self::SYSTEM_FUNCTION] = array();
                self::loadSystemLanguage();
            }
            self::setLocales();
        }
    }

    public static function trans()
    {
        $args = func_get_args();
        $content = array_shift($args);

        if (is_array($content)) {
            $args = array_values($content);
            $content = array_shift($args);
        }

        if (isset(self::$uiStrings[self::$uiLanguage][$content])) {
            $content = trim(self::$uiStrings[self::$uiLanguage][$content]);
        }

        for ($i = 1, $len = count($args); $i <= $len; $i++) {
            $content = str_replace('$' . chr(97 + $i - 1), $args[$i - 1], $content);
        }

        $content = preg_replace('/<![^>]+>/', '', $content);

        return $content;
    }

    private static function callFunction()
    {
        $args = func_get_args();
        $type = array_shift($args);
        $name = array_shift($args);
        $language = $type == self::UI_FUNCTION ? self::$uiLanguage : self::$systemLanguage;
        if (isset(self::$langDefs[$language][$type][$name])) {
            return call_user_func_array(self::$langDefs[$language][$type][$name], $args);
        }
        return null;
    }

    public static function callUiFunction()
    {
        $args = func_get_args();
        array_unshift($args, self::UI_FUNCTION);
        return call_user_func_array('Localisation::callFunction', $args);
    }

    public static function callSystemFunction()
    {
        $args = func_get_args();
        array_unshift($args, self::SYSTEM_FUNCTION);
        return call_user_func_array('Localisation::callFunction', $args);
    }

    public static function arraySort(array &$array, $key = null)
    {
        foreach ($array as &$item) {
            if (isset($key)) {
                $item[$key] = self::trans($item[$key]);
            } else {
                $item = self::trans($item);
            }
        }
        unset($item);
        uasort($array, function ($a, $b) use ($key) {
            if (isset($key)) {
                return strcoll($a[$key], $b[$key]);
            } else {
                return strcoll($a, $b);
            }
        });
    }
}
