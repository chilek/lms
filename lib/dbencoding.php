<?php

/*
 * LMS version 1.7-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

if(strtolower($_CONFIG['database']['server_encoding']) != 'unicode')
{
	switch($_CONFIG['database']['type'])
	{
		case 'postgres':
			$DB->Execute("SET CLIENT_ENCODING TO 'UNICODE'");
		break;
		
		case 'mysql':
			$DB->iconv = $_CONFIG['database']['server_encoding'];
			if(!function_exists('iconv'))
				die('Iconv support is required by \'server_encoding\' option!');
			if(!iconv($DB->iconv, $DB->iconv, 'test'))
				die('Wrong \'server_encoding\' value or encoding not supported by iconv!');
		break;
	}
}

?>