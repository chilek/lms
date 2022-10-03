<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

function smarty_modifier_duration_format($sec)
{
    $d = floor($sec / 86400);
    $h = floor(($sec - $d * 86400) / 3600);
    $m = floor(($sec - $d * 86400 - $h * 3600) / 60);
    $s = floor($sec - $d * 86400 - $h * 3600 - $m * 60);
    if ($sec < 60) {
        return sprintf("%02ds", $s);
    } elseif (empty($d)) {
        return sprintf("%02d:%02d:%02d", $h, $m, $s);
    } else {
        return sprintf("%dd %02d:%02d:%02d", $d, $h, $m, $s);
    }
}
