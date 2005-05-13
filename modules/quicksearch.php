<?php

/*
 * LMS version 1.5-cvs
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

$search = trim($_GET['what']);

switch($_GET['mode'])
{
	case 'user':
		if(is_numeric($search)) // maybe it's customer ID
		{
			if($userid = $DB->GetOne('SELECT id FROM users WHERE id = '.$search))
			{
				$target = '?m=userinfo&id='.$userid;
				break;
			}
		}

		// use usersearch module to find all customers
		$s['username'] = $search;
		$s['address'] = $search;
		$s['phone'] = $search;
		$s['email'] = $search;
		$SESSION->save('usersearch', $s);
		$SESSION->save('uslk', 'OR');
		$target = '?m=usersearch&search=1';
	break;

	case 'node':
		if(is_numeric($search) && !strstr($search, '.')) // maybe it's node ID
		{
			if($nodeid = $DB->GetOne('SELECT id FROM nodes WHERE id = '.$search))
			{
				$target = '?m=nodeinfo&id='.$nodeid;
				break;
			}
		}

		// use nodesearch module to find all matching nodes
		$s['name'] = $search;
		$s['mac'] = $search;
		$s['ipaddr'] = $search;
		$SESSION->save('nodesearch', $s);
		$SESSION->save('nslk', 'OR');
		$target = '?m=nodesearch&search=1';
	break;
	
	case 'ticket':
		if(intval($search))
			$target = '?m=rtticketview&id='.$search;
		else
		{
			$SESSION->save('rtsearch.username', $search);
			$target = '?m=rtsearch&search=1';
		}
	break;
}

if($target == '')
	$target = '?m=welcome';

header('Location: '.$target);

?>
