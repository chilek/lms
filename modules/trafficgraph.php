<?php

/*
 *  LMS version 1.11-git
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

if (!function_exists('imagecreate')) {
    die;
}

define('GRAPH_HEIGHT', 320);
define('GRAPH_WIDTH', 750);

function TrafficGraph($nodeid, $net = null, $customer = null, $bar = null, $fromdate = null, $todate = null, $add = null)
{
    function imagestringcenter($img, $font, $x, $y, $w, $h, $text, $color)
    {
        $text = iconv("UTF-8", "ISO-8859-2//TRANSLIT", $text);
        $tw = strlen($text) * imagefontwidth($font);
        $th = imagefontheight($font);

        if ($w == -1) {
            $x -= ($tw/2);
        } elseif ($w != 0) {
            $x += ($w - $tw) / 2;
        }

        if ($h == -1) {
            $y -= ($th/2);
        } elseif ($h != 0) {
            $y += ($h - $th) / 2;
        }

        imagestring($img, $font, $x, $y, $text, $color);
    }

    function value2string($value, $base = 1024)
    {
        $units = array('', 'k', 'M', 'G', 'T');

        $basek = $base;
        $baseM = $basek * $basek;
        $baseG = $baseM * $basek;

        if ($value > $baseG) {
            $value /= $baseG;
            $unit = 3;
        } elseif ($value > $baseM) {
            $value /= $baseM;
            $unit = 2;
        } elseif ($value > $basek) {
            $value /= $basek;
            $unit = 1;
        } else {
            $unit = 0;
        }

        if ($value >= 1000) {
            $value /= $basek;
            $width = 4;
            $precision = 2;
            $unit++;
        } elseif ($value >= 100) {
            $width = 3;
            $precision = 0;
        } elseif ($value >= 10) {
            $width = 4;
            $precision = 1;
        } else {
            $width = 4;
            $precision = 2;
        }

        $string = sprintf("%".$width.".".$precision."f", $value).$units[$unit];

        return $string;
    }

    global $LMS;

    $imgW = GRAPH_WIDTH;
    $imgH = GRAPH_HEIGHT;

    $tto = ($todate ? $todate : time());
    $tfrom = $fromdate;

    if ($tfrom > $tto) {
        $temp = $tfrom;
        $tfrom = $tto;
        $tto = $temp;
    }

    switch ($bar) {
        case 'hour':
            $tfrom = $tto - 60 * 60;
            break;
        case 'day':
            $tfrom = $tto - 60 * 60 * 24;
            break;
        case 'week':
            $tfrom = $tto - 60 * 60 * 24 * 7;
            break;
        case 'month':
            $tfrom = $tto - 60 * 60 * 24 * 30;
            break;
        case 'year':
            $tfrom = $tto - 60 * 60 * 24 * 365;
            break;
        default:
            $tfrom = $tfrom ? $tfrom : $tto - 60 * 60 * 24 * 30;
            break;
    }

    if ($add) {
        $tfrom += $add;
        $tto += $add;
    }

    $freq = ConfigHelper::getConfig('phpui.stat_freq', 12);

    if ($nodeid) {
        $node = $LMS->DB->GetRow('SELECT name, INET_NTOA(ipaddr) AS ip
			FROM nodes WHERE id = ?', array($nodeid));

        $traffic = $LMS->DB->GetAll(
            'SELECT upload, download, dt
			FROM stats WHERE nodeid = ? AND dt >= ? AND dt <= ?
			ORDER BY dt',
            array($nodeid, $tfrom, $tto)
        );
    } else {
        if ($net) {
            $params = $LMS->GetNetworkParams($net);
            if ($params) {
                $params['address']++;
                $params['broadcast']--;
                $net = ' AND (( ipaddr > '.$params['address'].' AND ipaddr < '.$params['broadcast'].')
					OR ( ipaddr_pub > '.$params['address'].' AND ipaddr_pub < '.$params['broadcast'].')) ';
            } else {
                $net = '';
            }
        } else {
            $net = '';
        }

        $traffic = $LMS->DB->GetAll(
            'SELECT SUM(x.upload) AS upload,
			SUM(x.download) AS download, (x.dts * ?) As dt
			FROM (
				SELECT SUM(upload) AS upload, SUM(download) AS download, CEIL(dt / ?) AS dts
				FROM stats '
                .($customer || $net ? 'JOIN nodes ON stats.nodeid = nodes.id ' : '')
                .'WHERE dt >= ? AND dt <= ? '
                .($customer ? ' AND ownerid = '.intval($customer).' ' : '')
                .$net
                .'GROUP BY CEIL(dt / ?), nodeid) x
			GROUP BY dts, x.dts
			ORDER BY x.dts',
            array($freq, $freq, $tfrom - $freq, $tto + $freq, $freq)
        );
    }

    $ftrText['total'] = trans('Total');
    $ftrText['upload'] = trans('Upload');
    $ftrText['download'] = trans('Download');
    $ftrText['t_put'] = trans('Transmitted [B]');
    $ftrText['average'] = trans('Avg [bit/s]');
    //$ftrText['min'] = trans('Min [bit/s]');
    $ftrText['max'] = trans('Max [bit/s]');
    $ftrText['last'] = trans('Last [bit/s]');

    //$str_len = strlen($ftrText['t_put'].$ftrText['average'].$ftrText['min'].$ftrText['max'].$ftrText['last']) + 11;
    $str_len = strlen($ftrText['t_put'].$ftrText['average'].$ftrText['max'].$ftrText['last']) + 11;
    $str_len += max(strlen($ftrText['total']), strlen($ftrText['upload']), strlen($ftrText['download']));

    /* DEFINE SIZES, DIMENSIONS AND COORDINATES */
    /********************************************/
    /* Fonts */
    $hdrFont = 3;       /* header */
    $crtFont = 2;       /* chart (axis legend) */
    $ftrFont = 3;       /* footer */
    if ($str_len * imagefontwidth($ftrFont) > $imgW) {
        $ftrFont = 2;
        if ($str_len * imagefontwidth($ftrFont) > $imgW) {
            $ftrFont = 1;
        }
    }

    /* Inter line spacing and chart padding */
    $spacing = 1.1;
    $padding = $imgW / 45;      // 15
    //$ftrSpacing = $imgW / 60; // 20
    $ftrSpacing = 0;        // 20
    /* Line heights in each section (respect to spacing) */
    $hdrSPC = $spacing * imagefontheight($hdrFont);
    $crtSPC = $spacing * imagefontheight($crtFont);
    $ftrSPC = $spacing * imagefontheight($ftrFont);
    /* Heights of each section */
    $hdrH = 2 * $hdrSPC;        /* 2 lines */
    $ftrH = 4 * $ftrSPC;        /* 4 lines (header plus in, out, total */
    $crtH = $imgH - $hdrH - $ftrH;  /* what's left - for chart */
    /* Start Y positions of each section */
    $crtY = $hdrH;
    $ftrY = $crtY + $crtH;
    /* Coordinates of chart section */
    $crtX1 = $padding + 5 * imagefontwidth($crtFont);
    $crtX2 = $imgW - $padding;
    $crtY1 = $crtY + $padding;
    //$crtY2 = $crtY + $crtH - $padding - $crtSPC;
    $crtY2 = $crtY + $crtH - $crtSPC;

    $image = imagecreate($imgW, $imgH); /* create image */

    /* CREATE COLORS OF EACH ELEMENT OF IMAGE */
    /******************************************/
    $clrBG = imagecolorallocate($image, 0xEB, 0xE4, 0xD6);  /* image background */
    $clrCBG = imagecolorallocate($image, 240, 240, 240);    /* chart background */
    $clrBLK = imagecolorallocate($image, 0, 0, 0);      /* black */
    $clrTXT = imagecolorallocate($image, 0, 0, 0);      /* text */
    $clrGRD = imagecolorallocate($image, 128, 128, 128);    /* chart grid */
    $clrGRM = imagecolorallocate($image, 144, 24, 24);  /* chart grid major */
    $clrBRD = imagecolorallocate($image, 128, 128, 128);    /* chart border */
    $clrIN = imagecolorallocate($image, 0, 204, 0);     /* inbound */
    $clrOUT = imagecolorallocate($image, 0, 0, 255);    /* outbound */
    $clrTOT = imagecolorallocate($image, 192, 0, 0);    /* total */

    /* PREPARE DATA TO BE PLACED ON THE CHART */
    $range = $tto - $tfrom;

    $total_up = $total_down = 0;    /* total and max up/down */
    $max_up = $max_down = 0;
    //$min_up = $min_down = 1000000000000;

    $upt = $dnt = 0;

    $dtime = $range / ($crtX2 - $crtX1); /* time of 1 pixel */
    $ctime = $tfrom + $dtime;
    $dt_prev = $freq;

    $loop_max = ($crtX2 - $crtX1) / ($range / $freq);
    if ($loop_max < 2) {
        $loop_max = 2;
    }

    if (isset($traffic)) {
        foreach ($traffic as $idx => $elem) {
            $total_up += $elem['upload'];
            $total_down += $elem['download'];

            if ($elem['dt'] < $ctime) {
                $upt = $elem['upload'];
                $dnt = $elem['download'];
                $dt_prev = $elem['dt'];
            } else {
                if ($dtime > $freq) {
                    $coeff = ($elem['dt'] - $ctime) / ($elem['dt'] - $dt_prev);
                } else {
                    $coeff = 1.0;
                }

                $upt += $elem['upload'] * (1.0 - $coeff);
                $dnt += $elem['download'] * (1.0 - $coeff);

                $upt *= 8;  /* convert bytes to bits per second */
                $dnt *= 8;
                if ($dtime > $freq) {
                    $upt /= $dtime;
                    $dnt /= $dtime;
                } else {
                    $upt /= $freq;
                    $dnt /= $freq;
                }

                if ($max_up < $upt) {
                    $max_up = $upt;
                }
                if ($max_down < $dnt) {
                    $max_down = $dnt;
                }

                //if ($min_up > $upt) { $min_up = $upt; }
                //if ($min_down > $dnt) { $min_down = $dnt; }

                $loop_i = 0;
                do {
                    $chart[] = array('upload' => $upt, 'download' => $dnt);
                    //if ($loop_i++ > $loop_max) { $upt = $dnt = 0; $min_up = $min_down = 0; }
                    if ($loop_i++ > $loop_max) {
                        $upt = $dnt = 0;
                    }
                } while ($elem['dt'] > ($ctime += $dtime));

                $upt = $elem['upload'] * $coeff;
                $dnt = $elem['download'] * $coeff;
                $dt_prev = $elem['dt'];
            }
        }
    }
    if ($tto - $ctime >= $freq) {
        //if ($ctime + $dtime <= $tto) $min_up = $min_down = 0;
        while (($ctime += $dtime) <= $tto) {
            $chart[] = array('upload' => 0, 'download' => 0);
        }
    }
    $last_chart = end($chart);
    $last_up = $last_chart['upload'];
    $last_down = $last_chart['download'];

    /* DRAW CHART HEADER */
    /*********************/
    // title
    if ($nodeid) {
        if ($node) {
            $title = $node['name'].' - '.$node['ip'];
        } else {
            $title = iconv('UTF-8', 'ISO-8859-2//TRANSLIT', trans('unknown')).' (ID: '.$nodeid.')';
        }
    } else {
        $title =  iconv('UTF-8', 'ISO-8859-2//TRANSLIT', trans('Network Statistics'));
    }

    imagestringcenter($image, $hdrFont, 0, 0, $imgW, $hdrSPC, $title, $clrTXT);

    // time period title
    $title = date('Y/m/d H:i', $tfrom).' - '.date('Y/m/d H:i', $tto);
    imagestringcenter($image, $hdrFont, 0, $hdrSPC, $imgW, $hdrSPC, $title, $clrTXT);

    /* DRAW CHART FOOTER */
    /*********************/
    $rectSize = $ftrSPC * 0.5;
    $rectPad = ($ftrSPC - $rectSize) / 2;

    $start = $padding;

    /* Rectangles of color with respect to graph lines */
    imagefilledrectangle($image, $start, $ftrY + 1 * $ftrSPC + $rectPad, $start + $rectSize, $ftrY + 1 * $ftrSPC + $rectSize + $rectPad, $clrTOT);
    imagefilledrectangle($image, $start, $ftrY + 2 * $ftrSPC + $rectPad, $start + $rectSize, $ftrY + 2 * $ftrSPC + $rectSize + $rectPad, $clrIN);
    imagefilledrectangle($image, $start, $ftrY + 3 * $ftrSPC + $rectPad, $start + $rectSize, $ftrY + 3 * $ftrSPC + $rectSize + $rectPad, $clrOUT);

    for ($i = 1; $i < 4; $i++) {
        imagerectangle($image, $start, $ftrY + $i * $ftrSPC + $rectPad, $start + $rectSize, $ftrY + $i * $ftrSPC + $rectSize + $rectPad, $clrBLK);
    }

    $start += $ftrSPC;

    /* Legend - Total, Input, Output */
    imagestringcenter($image, $ftrFont, $start, $ftrY + 1 * $ftrSPC, 0, $ftrSPC, $ftrText['total'], $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, 0, $ftrSPC, $ftrText['download'], $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, 0, $ftrSPC, $ftrText['upload'], $clrTXT);

    $start += (max(strlen($ftrText['total']), strlen($ftrText['upload']), strlen($ftrText['download'])) + 1) * imagefontwidth($ftrFont) + $ftrSpacing;

    /* Total transfer */
    $text = $ftrText['t_put'];
    $width = (strlen($text) + 1) * imagefontwidth($ftrFont);
    imagestringcenter($image, $ftrFont, $start, $ftrY, $width, $ftrSPC, $text, $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + $ftrSPC, $width, $ftrSPC, value2string($total_up + $total_down), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, $width, $ftrSPC, value2string($total_down), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, $width, $ftrSPC, value2string($total_up), $clrTXT);

    /* Average transfer */
    $start += $width + $ftrSpacing;
    $text = $ftrText['average'];
    $width = (strlen($text) + 1) * imagefontwidth($ftrFont);
    imagestringcenter($image, $ftrFont, $start, $ftrY, $width, $ftrSPC, $text, $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + $ftrSPC, $width, $ftrSPC, value2string(($total_up + $total_down)*8/$range, 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, $width, $ftrSPC, value2string($total_down * 8 / $range, 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, $width, $ftrSPC, value2string($total_up * 8 / $range, 1000), $clrTXT);

    /* Minimum transfer */
    //$start += $width + $ftrSpacing;
    //$text = $ftrText['min'];
    //$width = (strlen($text) + 1) * imagefontwidth($ftrFont);
    //imagestringcenter($image, $ftrFont, $start, $ftrY, $width, $ftrSPC, $text, $clrTXT);
    //imagestringcenter($image, $ftrFont, $start, $ftrY + $ftrSPC, $width, $ftrSPC, value2string(($min_up + $min_down), 1000), $clrTXT);
    //imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, $width, $ftrSPC, value2string($min_down, 1000), $clrTXT);
    //imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, $width, $ftrSPC, value2string($min_up, 1000), $clrTXT);

    /* Maximum transfer */
    $start += $width + $ftrSpacing;
    $text = $ftrText['max'];
    $width = (strlen($text) + 1) * imagefontwidth($ftrFont);
    imagestringcenter($image, $ftrFont, $start, $ftrY, $width, $ftrSPC, $text, $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + $ftrSPC, $width, $ftrSPC, value2string(($max_up + $max_down), 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, $width, $ftrSPC, value2string($max_down, 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, $width, $ftrSPC, value2string($max_up, 1000), $clrTXT);

    /* Last transfer */
    $start += $width + $ftrSpacing;
    $text = $ftrText['last'];
    $width = (strlen($text) + 1) * imagefontwidth($ftrFont);
    imagestringcenter($image, $ftrFont, $start, $ftrY, $width, $ftrSPC, $text, $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + $ftrSPC, $width, $ftrSPC, value2string(($last_up + $last_down), 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 2 * $ftrSPC, $width, $ftrSPC, value2string($last_down, 1000), $clrTXT);
    imagestringcenter($image, $ftrFont, $start, $ftrY + 3 * $ftrSPC, $width, $ftrSPC, value2string($last_up, 1000), $clrTXT);

    /* DRAW CHART */
    /**************/
    /* Draw chart border */
    imagefilledrectangle($image, $crtX1, $crtY1, $crtX2, $crtY2, $clrCBG);
    imagerectangle($image, $crtX1, $crtY1, $crtX2, $crtY2, $clrBRD);

    $style1 = array($clrGRD, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT);
    $style2 = array($clrGRD, $clrGRD, IMG_COLOR_TRANSPARENT);

    //$max_val = max($max_up, $max_down, $max_up $max_down);
    $max_val = max($max_up, $max_down);
    if ($max_val < 1000) {
        $max_val = 1000;
    }
    $max_pow = floor(pow(10, floor(log10($max_val))));
    $max_v = ceil($max_val / $max_pow);
    $max_step = ($crtY2 - $crtY1) / ($max_v);
    if ($max_v < 2) {
        $subs = 20;
    } elseif ($max_v < 4) {
        $subs = 5;
    } elseif ($max_v < 8) {
        $subs = 2;
    } else {
        $subs = 1;
    }
    $max_substep = $max_step / $subs;

    /* Draw entire chart */
    $pixel = ($crtY2 - $crtY1) / ($max_v * $max_pow);

    $Xpos = $crtX1 + 1;
    foreach ($chart as $elem) {
        //imagesetthickness($image, 1);
        //imageline($image, $Xpos, $crtY2-1, $Xpos, $crtY2 - (($elem['upload']+$elem['download'])*$pixel), $clrTOT);
        imageline($image, $Xpos, $crtY2-1, $Xpos, $crtY2 - ($elem['download'] * $pixel), $clrIN);
        //imagesetthickness($image, 1);
        if ($Xpos > ($crtX1 + 1)) {
            //imageline($image, $Xpos-1, $crtY2 - $Ypos_down, $Xpos, $crtY2 - ($elem['download']*$pixel), $clrIN);
            imageline($image, $Xpos-1, $crtY2 - $Ypos_up, $Xpos, $crtY2 - ($elem['upload'] * $pixel), $clrOUT);
        }
        $Ypos_up = $elem['upload'] * $pixel;
        $Ypos_down = $elem['download'] * $pixel;

        $Xpos++;
    }

    /* Draw Y-axis grid */
    for ($i = 1; $i <= $max_v; $i++) {
        imagesetstyle($image, $style2);
        if ($i != $max_v) {
            imageline($image, $crtX1+1, $crtY2 - $i*$max_step, $crtX2-1, $crtY2 - $i * $max_step, IMG_COLOR_STYLED);
        }
        imagesetstyle($image, $style1);
        for ($j = 1; $j < $subs; $j++) {
            $Ypos = $i * $max_step - $j * $max_substep;
            imageline($image, $crtX1 + 1, $crtY2 - $Ypos, $crtX2 - 1, $crtY2 - $Ypos, IMG_COLOR_STYLED);
        }
        $text = value2string($i * $max_pow, 1000);
        imagestringcenter($image, $crtFont, $crtX1 - (strlen($text) + 1) * imagefontwidth($crtFont), $crtY2 - $i * $max_step, 0, -1, $text, $clrTXT);
    }

    /* Draw X-axis grid */
    /* Calculate one unit - number of seconds between two pixels */
    $min_unit = 6 * imagefontwidth($crtFont) * $range / ($crtX2 - $crtX1);
    if ($min_unit < 900) {
        $unit = 900;
        $subunits = 3;
    } elseif ($min_unit < 3600) {
        $unit = 3600;
        $subunits = 4;
    } elseif ($min_unit < 3 * 3600) {
        $unit = 3 * 3600;
        $subunits = 3;
    } elseif ($min_unit < 24 * 3600) {
        $unit = 24 * 3600;
        $subunits = 6;
    } elseif ($min_unit < 7 * 24 * 3600) {
        $unit = 7 * 24 * 3600;
        $subunits = 7;
    } else {
        $unit = 30 * 24 * 3600;
        $subunits = 6;
    }

    $units = floor($range / $unit);

    /* Correction - quarter hour, full hour or day */
    if ($unit == 900) {
        $corr = $tto % 900;
    } elseif ($unit < 24 * 3600) {
        $corr = $tto % 3600;
    } else {
        $result = getdate($tto);
        $corr = $tto - mktime(0, 0, 0, $result['mon'], $result['mday'], $result['year']);
    }

    for ($i = 0; $i <= $units + 1; $i++) {
        imagesetstyle($image, $style1);
        for ($j = 0; $j < $subunits; $j++) {
            $Tpos = $tto - $corr - ($i * $unit) + ($j * $unit/$subunits);
            if (($Tpos < $tto) && ($Tpos > $tfrom)) {
                $Xpos = $crtX1 + ($Tpos - $tfrom) * ($crtX2 - $crtX1) / $range;
                imageline($image, $Xpos, $crtY1 + 1, $Xpos, $crtY2 - 1, IMG_COLOR_STYLED);
            }
        }

        imagesetstyle($image, $style2);
        $Tpos = $tto - $corr - ($i * $unit);
        if ($Tpos > $tfrom) {
            $Xpos = $crtX1 + ($Tpos - $tfrom) * ($crtX2 - $crtX1) / $range;
            imageline($image, $Xpos, $crtY1+1, $Xpos, $crtY2 - 1, IMG_COLOR_STYLED);

            if ($unit >= 24 * 3600) {
                $utxt = strftime('%e/%m', $Tpos);
            } else {
                $utxt = strftime('%H:%M', $Tpos);
            }
            imagestringcenter($image, $crtFont, $Xpos, $crtY2, -1, $crtSPC, $utxt, $clrTXT);
        }
    }

    imagepng($image);
    imagedestroy($image);
}

$nodeid = isset($_GET['nodeid']) ? $_GET['nodeid'] : 0;
$bar = isset($_GET['bar']) ? $_GET['bar'] : null;
$from = isset($_GET['from']) ? $_GET['from'] : null;
$to = isset($_GET['to']) ? $_GET['to'] : null;
$customer = !empty($_GET['customer']) ? $_GET['customer'] : null;
$net = !empty($_GET['net']) ? $_GET['net'] : null;
$add = !empty($_GET['add']) ? $_GET['add'] : null;

if (empty($_GET['popup'])) {
    header('Content-type: image/png');
    TrafficGraph($nodeid, $net, $customer, $bar, $from, $to, $add);
    die;
}

$SMARTY->assign('nodeid', $nodeid);
$SMARTY->assign('bar', $bar);
$SMARTY->assign('to', $to);
$SMARTY->assign('from', $from);
$SMARTY->assign('add', $add);
$SMARTY->assign('customer', $customer);
$SMARTY->assign('net', $net);
$SMARTY->display('traffic/trafficgraph.html');
