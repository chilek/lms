<?php

/*
 * LMS version 1.4-cvs
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

$search = trim($_GET['what']);

switch($_GET['mode'])
{
	case 'user':
		if(($userid = $DB->GetOne('SELECT id FROM users WHERE id = '.intval($search))) ||
		   ($userid = $DB->GetOne('SELECT id FROM users WHERE lastname ?LIKE? ? OR address ?LIKE? ? OR phone1 = ? OR phone2 = ? OR phone3 = ? OR email = ? LIMIT 1', array('%'.$search.'%', '%'.$search.'%', $search, $search, $search, $search)))
		   )
			$target = '?m=userinfo&id='.$userid;
		else
			$target = '?m=userlist';
	break;

	case 'node':
		if(($nodeid = $DB->GetOne('SELECT id FROM nodes WHERE id = '.ip2long($search))) ||
		   ($nodeid = $DB->GetOne('SELECT id FROM nodes WHERE ipaddr = ? OR name = UPPER(?) OR mac = UPPER(?) LIMIT 1', array(ip_long($search), $search, $search)))
		   )
			$target = '?m=nodeinfo&id='.$nodeid;
		else
			$target = '?m=nodelist';
	break;
	
	case 'ticket':
		if(intval($search))
			$target = '?m=rtticketview&id='.$search;
		else
		{
			$_SESSION['rtsearch']['username'] = $search;
			$target = '?m=rtsearch&search=1';
		}
	break;
	
	

}

if($target == '')
	$target = '?m=welcome';

header('Location: '.$target);

?>
