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

$search = trim($_GET['what']);

switch($_GET['mode'])
{
	case 'user':
		if($_GET['ajax']==1) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT id, lastname, name, email, phone1, phone2, phone3 FROM users WHERE id ?LIKE? \''.$search.'%\' OR lastname ?LIKE? \''.$search.'%\' OR name ?LIKE? \''.$search.'%\' OR email ?LIKE? \'%'.$search.'%\' OR phone1 ?LIKE? \''.$search.'%\' OR phone2 ?LIKE? \''.$search.'%\' OR phone3 ?LIKE? \''.$search.'%\' ORDER by lastname, name, email, phone1 LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			foreach($candidates as $idx => $row) {
				$actions[$row['id']]='?m=userinfo&id='.$row['id'];
				$eglible[$row['id']]=$row['name'].' '.$row['lastname'];
				if (preg_match("/^$search/",$row['id'])) $descriptions[$row['id']]=trans('Id').': '.$row['id'];
				if (preg_match("/^$search/",$row['lastname'])) $descriptions[$row['id']]=trans('First/last name').': '.$row['lastname'];
				if (preg_match("/^$search/",$row['name'])) $descriptions[$row['id']]=trans('First/last name').': '.$row['name'];
				if (preg_match("/$search/",$row['email'])) $descriptions[$row['id']]=trans('E-mail').': '.$row['email'];
				if (preg_match("/^$search/",$row['phone1'])) $descriptions[$row['id']]=trans('Phone').': '.$row['phone1'];
				if (preg_match("/^$search/",$row['phone2'])) $descriptions[$row['id']]=trans('Phone').': '.$row['phone2'];
				if (preg_match("/^$search/",$row['phone3'])) $descriptions[$row['id']]=trans('Phone').': '.$row['phone3'];
			}
			header('Content-type: text/plain');
			print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
			print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
			print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
		}

		if(is_numeric($search)) // maybe it's customer ID
		{
			if($customerid = $DB->GetOne('SELECT id FROM users WHERE id = '.$search))
			{
				$target = '?m=userinfo&id='.$customerid;
				break;
			}
		}

		// use usersearch module to find all customers
		$s['customername'] = $search;
		$s['address'] = $search;
		$s['phone'] = $search;
		$s['email'] = $search;
		$SESSION->save('usersearch', $s);
		$SESSION->save('uslk', 'OR');
		
		$SESSION->remove('uslp');
		$SESSION->remove('usln');
		$SESSION->remove('uslg');
		$SESSION->remove('usls');
		
		$target = '?m=usersearch&search=1';
	break;

	case 'node':
		if($_GET['ajax']==1) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT id, lower(name) as name, inet_ntoa(ipaddr) as ipaddr, lower(mac) as mac FROM nodes WHERE id ?LIKE? \''.$search.'%\' OR lower(name) ?LIKE? lower(\''.$search.'%\') OR ipaddr ?LIKE? \'%'.$search.'%\' OR lower(mac) ?LIKE? lower(\'%'.$search.'%\') ORDER BY name, ipaddr, mac LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			foreach($candidates as $idx => $row) {
				$actions[$row['id']]='?m=nodeinfo&id='.$row['id'];
				$eglible[$row['id']]=$row['name'].' '.$row['lastname'];
				if (preg_match("/^$search/",$row['name'])) $descriptions[$row['id']]=trans('Name').': '.$row['name'];
				if (preg_match("/$search/",$row['ipaddr'])) $descriptions[$row['id']]=trans('IP').': '.$row['ipaddr'];
				if (preg_match("/$search/",$row['mac'])) $descriptions[$row['id']]=trans('MAC address').': '.$row['mac'];
			}
			header('Content-type: text/plain');
			print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
			print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
			print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
		}

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

		$SESSION->remove('nslp');

		$target = '?m=nodesearch&search=1';
	break;
	
	case 'ticket':
		if($_GET['ajax']==1) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT id, subject FROM rttickets WHERE id ?LIKE? \''.$search.'%\' OR lower(subject) ?LIKE? lower(\'%'.$search.'%\') ORDER BY subject, id LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			foreach($candidates as $idx => $row) {
				$actions[$row['id']]='?m=rtticketview&id='.$row['id'];
				$eglible[$row['id']]=$row['subject'];
				if (preg_match("/$search/",$row['id'])) $descriptions[$row['id']]=trans('Id').': '.$row['id'];
				if (preg_match("/$search/",$row['subject'])) $descriptions[$row['id']]=trans('Subject:').' '.$row['subject'];
			}
			header('Content-type: text/plain');
			print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
			print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
			print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
		}

		if(intval($search))
			$target = '?m=rtticketview&id='.$search;
		else
		{
			$SESSION->save('rtsearch', array('name' => $search));
			$target = '?m=rtsearch&search=1';
		}
	break;
}

$SESSION->redirect($target ? $target : '?m=welcome');

?>
