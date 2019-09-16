<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

    public static function tip($params, $template)
    {
        $result = '';

        if (isset($params['popup']) && $popup = $params['popup']) {
            if (is_array($params)) {
                foreach ($params as $paramid => $paramval) {
                    $popup = str_replace('$'.$paramid, $paramval, $popup);
                }
            }

            $text = " onclick=\"popup('$popup',1," . (isset($params['sticky']) && $params['sticky'] ? 1 : 0) . ",10,10)\" onmouseout=\"pophide();\"";
            return $text;
        } else {
            if (isset($params['class'])) {
                $class = $params['class'];
                unset($params['class']);
            } else {
                $class = '';
            }
            $tmpl = $template->getTemplateVars('error');
            if (isset($params['trigger']) && isset($tmpl[$params['trigger']])) {
                $error = str_replace("'", '\\\'', $tmpl[$params['trigger']]);
                $error = str_replace('"', '&quot;', $error);
                $error = str_replace("\r", '', $error);
                $error = str_replace("\n", '<BR>', $error);

                $result .= ' title="' . $error . '" ';
                $result .= ' class="' . (empty($class) ? '' : $class) . ($params['bold'] ? ' lms-ui-error bold" ' : ' lms-ui-error" ');
            } else {
                if ($params['text'] != '') {
                    $text = $params['text'];
                    unset($params['text']);
                    $text = trans(array_merge((array)$text, $params));

                    //$text = str_replace('\'', '\\\'', $text);
                    $text = str_replace('"', '&quot;', $text);
                    $text = str_replace("\r", '', $text);
                    $text = str_replace("\n", '<BR>', $text);

                    $result .= ' title="' . $text . '" ';
                }
                $result .= ' class="' . (empty($class) ? '' : $class) . (isset($params['bold']) && $params['bold'] ? ' bold' : '') . '" ';
            }

            return $result;
        }
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
}
