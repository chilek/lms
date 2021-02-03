<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
    self::UI_FUNCTION,
    array(
        'uptimef' => function ($ts) {
            if ($ts < 60) {
                return trans('less than one minute ago');
            }

            $min= $ts / 60;
            $hours = $min / 60;
            $days  = floor($hours / 24);
            $hours = floor($hours - ($days * 24));
            $min= floor($min - ($days * 60 * 24) - ($hours * 60));

            $result = '';
            if ($days != 0) {
                $result = $days;
                if ($days==1) {
                    $result .= ' zi ';
                } else {
                    $result .= ' zile ';
                }
            }
            if ($hours != 0) {
                $result .= $hours;
                if ($hours==1) {
                    $result .= ' ora ';
                } else {
                    $result .= ' ore ';
                }
            }
            if ($min != 0) {
                $result .= $min;
                if ($min==1) {
                    $result .= ' minut ';
                } else {
                    $result .= ' minute ';
                }
            }
            return trim($result);
        },
        'to_words' => function ($num, $power = 0, $powsuffix = '', $short_version = 0) {
            // Extracted from lang.pl.php by Piotr Klaban <makler at man dot torun dot pl>

            if ($short_version) {
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

                    $replacements[0] = 'zer ';
                    $replacements[1] = 'unu ';
                    $replacements[2] = 'doi ';
                    $replacements[3] = 'tre ';
                    $replacements[4] = 'pat ';
                    $replacements[5] = 'cin ';
                    $replacements[6] = 'şas ';
                    $replacements[7] = 'şap ';
                    $replacements[8] = 'opt ';
                    $replacements[9] = 'nou ';

                    return trim(preg_replace($patterns, $replacements, $num));
            }

            $ret = '';
            $_sep = ' ';
            $_minus = 'minus';
            $_digits = array(0 => 'zero', 'unu', 'doi', 'trei', 'patru', 'cinci', 'şase', 'şapte', 'opt', 'nouă');
            $_exponent = array(
                0 => array(''),
                3 => array('mii'),
                6 => array('milioane'),
                9 => array('bilioane'),
                12 => array('trillioane'),
                15 => array('quadrillion'),
                18 => array('quintillion'),
                21 => array('sextillion'),
                24 => array('septillion'),
                27 => array('octillion'),
                30 => array('nonillion'),
                33 => array('decillion'),
                36 => array('undecillion'),
                39 => array('duodecillion'),
                42 => array('tredecillion'),
                45 => array('quattuordecillion'),
                48 => array('quindecillion'),
                51 => array('sexdecillion'),
                54 => array('septendecillion'),
                57 => array('octodecillion'),
                60 => array('novemdecillion'),
                63 => array('vigintillion'),
                66 => array('unvigintillion'),
                69 => array('duovigintillion'),
                72 => array('trevigintillion'),
                75 => array('quattuorvigintillion'),
                78 => array('quinvigintillion'),
                81 => array('sexvigintillion'),
                84 => array('septenvigintillion'),
                87 => array('octovigintillion'),
                90 => array('novemvigintillion'),
                93 => array('trigintillion'),
                96 => array('untrigintillion'),
                99 => array('duotrigintillion'),
                // 100 => array('googol') - not latin name
                // 10^googol = 1 googolplex
                102 => array('trestrigintillion'),
                105 => array('quattuortrigintillion'),
                108 => array('quintrigintillion'),
                111 => array('sextrigintillion'),
                114 => array('septentrigintillion'),
                117 => array('octotrigintillion'),
                120 => array('novemtrigintillion'),
                123 => array('quadragintillion'),
                126 => array('unquadragintillion'),
                129 => array('duoquadragintillion'),
                132 => array('trequadragintillion'),
                135 => array('quattuorquadragintillion'),
                138 => array('quinquadragintillion'),
                141 => array('sexquadragintillion'),
                144 => array('septenquadragintillion'),
                147 => array('octoquadragintillion'),
                150 => array('novemquadragintillion'),
                153 => array('quinquagintillion'),
                156 => array('unquinquagintillion'),
                159 => array('duoquinquagintillion'),
                162 => array('trequinquagintillion'),
                165 => array('quattuorquinquagintillion'),
                168 => array('quinquinquagintillion'),
                171 => array('sexquinquagintillion'),
                174 => array('septenquinquagintillion'),
                177 => array('octoquinquagintillion'),
                180 => array('novemquinquagintillion'),
                183 => array('sexagintillion'),
                186 => array('unsexagintillion'),
                189 => array('duosexagintillion'),
                192 => array('tresexagintillion'),
                195 => array('quattuorsexagintillion'),
                198 => array('quinsexagintillion'),
                201 => array('sexsexagintillion'),
                204 => array('septensexagintillion'),
                207 => array('octosexagintillion'),
                210 => array('novemsexagintillion'),
                213 => array('septuagintillion'),
                216 => array('unseptuagintillion'),
                219 => array('duoseptuagintillion'),
                222 => array('treseptuagintillion'),
                225 => array('quattuorseptuagintillion'),
                228 => array('quinseptuagintillion'),
                231 => array('sexseptuagintillion'),
                234 => array('septenseptuagintillion'),
                237 => array('octoseptuagintillion'),
                240 => array('novemseptuagintillion'),
                243 => array('octogintillion'),
                246 => array('unoctogintillion'),
                249 => array('duooctogintillion'),
                252 => array('treoctogintillion'),
                255 => array('quattuoroctogintillion'),
                258 => array('quinoctogintillion'),
                261 => array('sexoctogintillion'),
                264 => array('septoctogintillion'),
                267 => array('octooctogintillion'),
                270 => array('novemoctogintillion'),
                273 => array('nonagintillion'),
                276 => array('unnonagintillion'),
                279 => array('duononagintillion'),
                282 => array('trenonagintillion'),
                285 => array('quattuornonagintillion'),
                288 => array('quinnonagintillion'),
                291 => array('sexnonagintillion'),
                294 => array('septennonagintillion'),
                297 => array('octononagintillion'),
                300 => array('novemnonagintillion'),
                303 => array('centillion'),
                309 => array('duocentillion'),
                312 => array('trecentillion'),
                366 => array('primo-vigesimo-centillion'),
                402 => array('trestrigintacentillion'),
                603 => array('ducentillion'),
                624 => array('septenducentillion'),
                // bug on a earthlink page: 903 => array('trecentillion'),
                2421 => array('sexoctingentillion'),
                3003 => array('millillion'),
                3000003 => array('milli-millillion')
                );

            if (substr($num, 0, 1) == '-') {
                $ret = $_sep . $_minus;
                $num = substr($num, 1);
            }

            // strip excessive zero signs and spaces
            $num = trim($num);
            $num = preg_replace('/^0+/', '', $num);

            if (strlen($num) > 3) {
                $maxp = strlen($num)-1;
                $curp = $maxp;
                for ($p = $maxp; $p > 0; --$p) { // power
                // check for highest power
                    if (isset($_exponent[$p])) {  // send substr from $curp to $p
                            $snum = substr($num, $maxp - $curp, $curp - $p + 1);
                            $snum = preg_replace('/^0+/', '', $snum);
                        if ($snum !== '') {
                            $cursuffix = $_exponent[$power][count($_exponent[$power])-1];
                            if ($powsuffix != '') {
                                $cursuffix .= $this->_sep . $powsuffix;
                            }
                            $ret .= to_words($snum, $p, $cursuffix);
                        }
                            $curp = $p - 1;
                            continue;
                    }
                }

                $num = substr($num, $maxp - $curp, $curp - $p + 1);
                $ret = trim($ret);
                if ($num == 0) {
                    return $ret;
                }
            } elseif ($num == 0 || $num == '') {
                return $_digits[0];
            }

            $h = $t = $d = 0;

            switch (strlen($num)) {
                // phpcs:disable PSR2.ControlStructures.SwitchDeclaration
                case 3:
                    $h = (int)substr($num, -3, 1);
                case 2:
                    $t = (int)substr($num, -2, 1);
                // phpcs:enable PSR2.ControlStructures.SwitchDeclaration
                case 1:
                    $d = (int)substr($num, -1, 1);
                    break;
                case 0:
                    return;
                break;
            }

            if ($h) {
                $ret .= $_sep . $_digits[$h] . $_sep . 'hundred';

                // in English only - add ' and' for [1-9]01..[1-9]99
                // (also for 1001..1099, 10001..10099 but it is harder)
                // for now it is switched off, maybe some language purists
                // can force me to enable it, or to remove it completely
                // if (($t + $d) > 0)
                //   $ret .= $_sep . 'and';
            }

            // ten, twenty etc.
            switch ($t) {
                case 9:
                case 8:
                case 7:
                case 5:
                case 4:
                case 3:
                    $ret .= $_sep . $_digits[$t] . 'zeci';
                    break;
                case 6:
                    $ret .= $_sep . 'şaizeci';
                    break;
                case 2:
                    $ret .= $_sep . 'douăzeci';
                    break;
                case 1:
                    switch ($d) {
                        case 0:
                            $ret .= $_sep . 'zece';
                            break;
                        case 1:
                            $ret .= $_sep . 'unsprezece';
                            break;
                        case 9:
                        case 8:
                        case 7:
                        case 5:
                        case 3:
                        case 2:
                            $ret .= $_sep . $_digits[$d] . 'sprezece';
                            break;
                        case 6:
                            $ret .= $_sep . 'şaisprezece';
                            break;
                        case 4:
                            $ret .= $_sep . 'paisprezece';
                            break;
                    }
                    break;
            }

            if ($t != 1 && $d > 0) { // add digits only in <0>,<1,9> and <21,inf>
            // add minus sign between [2-9] and digit
                if ($t > 1) {
                    $ret .= '-' . $_digits[$d];
                } else {
                    $ret .= $_sep . $_digits[$d];
                }
            }

            if ($power > 0) {
                if (isset($_exponent[$power])) {
                    $lev = $_exponent[$power];
                }

                if (!isset($lev) || !is_array($lev)) {
                    return null;
                }

                $ret .= $_sep . $lev[0];
            }

            if ($powsuffix != '') {
                $ret .= $_sep . $powsuffix;
            }

            return trim($ret);
        },
    )
);
