<?php

/*
 * LMS version 1.2-cvs
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

$langs = explode(',', ($_CONFIG['phpui']['lang'] ? $_CONFIG['phpui']['lang'] : $_SERVER['HTTP_ACCEPT_LANGUAGE']));
foreach ($langs as $val) {
    switch (substr($val, 0, 2)) {
	case 'pl':
            define('LANG', 'pl');
	    define('CHARSET', 'iso-8859-2');
	    setlocale(LC_MESSAGES, 'pl_PL');
	    setlocale(LC_TIME, 'pl_PL');
            break 2;
        case 'en':
	    define('LANG', 'en');
	    define('CHARSET','iso-8859-1');
	    setlocale(LC_ALL, 'en_US');
	    break 2;
    }
}

bindtextdomain("lms", "$_LIB_DIR/locale");
textdomain("lms");

?>