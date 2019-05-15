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

function r_stripslashes($string)
{
    if (isset($string)) {
        foreach ($string as $key => $value) {
            if (is_array($value)) {
                $string[$key] = r_stripslashes($value);
            } else {
                $string[$key] = stripslashes($value);
            }
        }

        return $string;
    } else {
        return false;
    }
}

if (get_magic_quotes_gpc()) {
    $_POST = r_stripslashes($_POST);
}

$_SERVER['REMOTE_ADDR'] = str_replace("::ffff:", "", $_SERVER['REMOTE_ADDR']);
