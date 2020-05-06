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

class Utils
{
    public static function filterIntegers(array $params)
    {
        return array_filter($params, function ($value) {
            $string = strval($value);
            if ($string[0] == '-') {
                $string = ltrim($string, '-');
            }
            return ctype_digit($string);
        });
    }

    // taken from RoundCube
    /**
     * Generate a random string
     *
     * @param int  $length String length
     * @param bool $raw    Return RAW data instead of ascii
     *
     * @return string The generated random string
     */
    public static function randomBytes($length, $raw = false)
    {
        $hextab  = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $tabsize = strlen($hextab);

        // Use PHP7 true random generator
        if ($raw && function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (!$raw && function_exists('random_int')) {
            $result = '';
            while ($length-- > 0) {
                $result .= $hextab[random_int(0, $tabsize - 1)];
            }

            return $result;
        }

        $random = openssl_random_pseudo_bytes($length);

        if ($random === false && $length > 0) {
            throw new Exception("Failed to get random bytes");
        }

        if (!$raw) {
            for ($x = 0; $x < $length; $x++) {
                $random[$x] = $hextab[ord($random[$x]) % $tabsize];
            }
        }

        return $random;
    }

    public static function isAllowedIP($ip, $allow_from)
    {
        if (empty($allow_from)) {
            return true;
        }

        $allowedlist = explode(',', $allow_from);

        foreach ($allowedlist as $value) {
            $mask = '';

            if (strpos($value, '/') === false) {
                $net = $value;
            } else {
                list ($net, $mask) = explode('/', $value);
            }

            $net = trim($net);
            $mask = trim($mask);

            if ($mask == '') {
                $mask = '255.255.255.255';
            } elseif (is_numeric($mask)) {
                $mask = prefix2mask($mask);
            }

            if (isipinstrict($ip, $net, $mask)) {
                return true;
            }
        }

        return false;
    }

    public static function docEntityCount($entities)
    {
        return substr_count(sprintf("%b", $entities), '1');
    }

    public static function triggerError($error_msg, $error_type = E_USER_NOTICE, $context = 1)
    {
        $stack = debug_backtrace();
        for ($i = 0; $i < $context; $i++) {
            if (false === ($frame = next($stack))) {
                break;
            }
            $error_msg .= ", from " . $frame['function'] . ':' . $frame['file'] . ' line ' . $frame['line'];
        }
        return trigger_error($error_msg, $error_type);
    }

    public static function LoadMarkdownDocumentation($variable_name = null)
    {
        $markdown_documentation_file = ConfigHelper::getConfig(
            'phpui.markdown_documentation_file',
            SYS_DIR . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . 'Zmienne-konfiguracyjne-LMS-Plus.md'
        );

        if (isset($variable_name)) {
            $content = file_get_contents($markdown_documentation_file);
            if (($startpos = strpos($content, '## ' . $variable_name)) === false) {
                return null;
            }
            $endpos = strpos($content, '## ', $startpos + 1);
            if ($endpos === false) {
                $chunk = substr($content, $startpos);
            } else {
                $chunk = substr($content, $startpos, $endpos - $startpos);
            }
            $lines = explode("\n", $chunk);
            array_shift($lines);
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            unset($line);
            return implode("\n", $lines);
        }

        $result = array();

        if (empty($markdown_documentation_file) || !file_exists($markdown_documentation_file)) {
            return $result;
        }

        $content = file($markdown_documentation_file);
        if (empty($content)) {
            return $result;
        }

        $variable = null;
        $buffer = '';
        foreach ($content as $line) {
            if (preg_match('/^##\s+(?<variable>.+)\r?\n/', $line, $m)) {
                if ($variable && $buffer) {
                    list ($section, $var) = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = $m['variable'];
                $buffer = '';
            } elseif (preg_match('/^\*\*\*/', $line)) {
                if ($variable && $buffer) {
                    list ($section, $var) = explode('.', $variable);
                    if (!isset($result[$section])) {
                        $result[$section] = array();
                    }
                    $result[$section][$var] = $buffer;
                }
                $variable = null;
                $buffer = '';
            } elseif ($variable) {
                $buffer .= $line;
            }
        }
        if ($variable && $buffer) {
            list ($section, $var) = explode('.', $variable);
            if (!isset($result[$section])) {
                $result[$section] = array();
            }
            $result[$section][$var] = $buffer;
        }

        return $result;
    }

    public static function MarkdownToHtml($markdown)
    {
        static $markdown_parser = null;
        if (!isset($markdown_parser)) {
            $markdown_parser = new Parsedown();
        }
        return $markdown_parser->Text($markdown);
    }

    public static function getDefaultCustomerConsents()
    {
        global $CCONSENTS;

        $result = array();

        $value = ConfigHelper::getConfig('phpui.default_customer_consents', 'data_processing', true);
        if (!empty($value)) {
            $values = array_flip(preg_split('/[\s\.,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY));
            foreach ($CCONSENTS as $consent_id => $consent) {
                if (isset($values[$consent['name']])) {
                    $result[$consent_id] = $consent_id;
                }
            }
        }

        return $result;
    }

    public static function checkZip($zip, $country = null)
    {
        if (!isset($country) || empty($country)) {
            $country = $GLOBALS['_language'];
        } else if (preg_match('/^[0-9]+$/', $country)) {
            $LMS = LMS::getInstance();
            $country = $LMS->getCountryCodeById($country);
        }
        if (isset($GLOBALS['LANGDEFS'][$country]['check_zip'])
            && !ConfigHelper::checkConfig('phpui.skip_zip_validation')) {
            return $GLOBALS['LANGDEFS'][$country]['check_zip']($zip);
        } else {
            return true;
        }
    }
}
