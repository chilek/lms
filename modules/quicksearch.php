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

$search = trim($_GET['what']);

switch($_GET['mode'])
{
	case 'user':
		if($userid = $DB->GetOne('SELECT id FROM users WHERE id = ? OR lastname ?LIKE? ? OR address ?LIKE? ? OR ? IN (phone1, phone2, phone3, email)', array(intval($search), '%'.$search.'%', '%'.$search.'%', $search)))
			$target = '?m=userinfo&id='.$userid;
		else
			$target = '?m=userlist';
	break;

	case 'node':
		if($nodeid = $DB->GetOne('SELECT id FROM nodes WHERE id = ? OR ipaddr = ? OR UPPER(?) IN (name, mac)', array(intval($search), ip_long($search), $search)))
			$target = '?m=nodeinfo&id='.$nodeid;
		else
			$target = '?m=nodelist';
	break;
}

if($target == '')
	$target = '?m=welcome';

header('Location: '.$target);

?>
