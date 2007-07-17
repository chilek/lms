<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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

switch($CONFIG['database']['type'])
{
	case 'postgres':
		$DB->Execute('CREATE TEMP VIEW customersview AS
				SELECT c.* FROM customers c
				WHERE NOT EXISTS (
		    			SELECT 1 FROM customerassignments a
			    		JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
				        WHERE e.userid = ? AND a.customerid = c.id
					)', array($AUTH->id));
	break;
		
	case 'mysql':
	case 'mysqli':
		$DB->Execute('SET @lms_current_user=?', array($AUTH->id));
	break;
}

?>
