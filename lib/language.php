<?php

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$language = 'en';
$langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
foreach ($langs as $val) {
    switch (substr($val, 0, 2)) {
	case 'pl':
            $language = 'pl';
            break 2;
        case 'en':
	    $language = 'en';
	    break 2;
    }
}

switch ($language) {
    case 'pl':
	require_once($_LIB_DIR.'/lang/polish.php');
	break;
    case 'en':
	require_once($_LIB_DIR.'/lang/english.php');
        break;
}

?>