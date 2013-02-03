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

/*
 * Simple database abstraction layer for LMS. Mainly inspirated by ADOdb,
 * but not so much powerfull. Hope that this bit of code will work stable.
 *
 * This file include required files and do some nasty things ;>
 */

define('LMSDB_DIR', dirname(__FILE__));

require_once(LMSDB_DIR.'/LMSDB_common.class.php');

function DBInit($dbtype, $dbhost, $dbuser, $dbpasswd, $dbname, $debug = false)
{
    $dbtype = strtolower($dbtype);

	if (!file_exists(LMSDB_DIR."/LMSDB_driver_$dbtype.class.php") )
		trigger_error('Unable to load driver for "'.$dbtype.'" database!', E_USER_WARNING);
	else {
		require_once(LMSDB_DIR."/LMSDB_driver_$dbtype.class.php");
		$drvname = "LMSDB_driver_$dbtype";
		$DB = new $drvname($dbhost, $dbuser, $dbpasswd, $dbname);

		if (!$DB->_loaded)
			trigger_error('PHP Driver for "'.$dbtype.'" database doesn\'t seems to be loaded.', E_USER_WARNING);
		else if (!$DB->_dblink)
			trigger_error('Unable to connect to database!', E_USER_WARNING);
		else {
            $DB->debug = $debug;

            // set client encoding
            $DB->SetEncoding('UTF8');

			return $DB;
	    }
    }

	return FALSE;
}

?>
