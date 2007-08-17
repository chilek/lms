<?php

/*
 * LMS version 1.10-cvs
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

function escape_js($string)
{
        // escape quotes and backslashes, newlines, etc.
        return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
}

function macformat($mac)
{
	$res = str_replace('-', ':', $mac);
	// allow eg. format "::ab:3::12", only whole addresses
	if(preg_match('/^([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2}):([0-9a-f]{0,2})$/i', $mac, $arr))
	{
		$res = '';
		for($i=1; $i<=6; $i++)
		{
			if($i > 1) $res .= ':';
			if(strlen($arr[$i]) == 1) $res .= '0';
			if(strlen($arr[$i]) == 0) $res .= '00';
			
			$res .= $arr[$i];
		}
	}
	else // other formats eg. cisco xxxx.xxxx.xxxx or parts of addresses
	{
		$tmp = eregi_replace('[^0-9a-f]', '', $mac);
	
		if(strlen($tmp) == 12) // we've the whole address
			if(check_mac($tmp)) 
				$res = $tmp;
	}
	return $res;
}

$mode = '';

if(isset($_POST['qscustomer']) && $_POST['qscustomer']) {
	$mode = 'customer'; 
	$search = urldecode(trim($_POST['qscustomer']));
} elseif(isset($_POST['qsnode']) && $_POST['qsnode']) {
	$mode = 'node'; 
	$search = urldecode(trim($_POST['qsnode']));
} elseif(isset($_POST['qsticket']) && $_POST['qsticket']) {
	$mode = 'ticket'; 
	$search = urldecode(trim($_POST['qsticket']));
} else {
	$search = urldecode(trim($_GET['what']));
	$mode = $_GET['mode'];
}

switch($mode)
{
	case 'customer':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT id, lastname, name, email, address, deleted 
					    FROM customersview 
					    WHERE id ?LIKE? \''.$search.'%\' 
						    OR LOWER(lastname) ?LIKE? LOWER(\'%'.$search.'%\') 
						    OR LOWER(name) ?LIKE? LOWER(\'%'.$search.'%\') 
						    OR LOWER(address) ?LIKE? LOWER(\'%'.$search.'%\') 
						    OR LOWER(email) ?LIKE? LOWER(\'%'.$search.'%\') 
						    ORDER by deleted, lastname, name, email, address LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=customerinfo&id='.$row['id'];
				$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '').$row['lastname'].' '.$row['name'].($row['deleted'] ? '</font>' : ''));
				if (preg_match("/^$search/i",$row['id'])) 	{ $descriptions[$row['id']] = escape_js(trans('Id:').' '.$row['id']); continue; }
				if (preg_match("/$search/i",$row['lastname'])) 	{ $descriptions[$row['id']] = escape_js(trans('Name/Surname:').' '.$row['lastname']); continue; }
				if (preg_match("/$search/i",$row['name'])) 	{ $descriptions[$row['id']] = escape_js(trans('First name:').' '.$row['name']); continue; }
				if (preg_match("/$search/i",$row['address'])) 	{ $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['address']); continue; }
				if (preg_match("/$search/i",$row['email'])) 	{ $descriptions[$row['id']] = escape_js(trans('E-mail:').' '.$row['email']); continue; }
				if (!$descriptions[$row['id']]) $descriptions[$row['id']]='-';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
				print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
				print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
			} else {
				print "false;\n";
			}
			exit;
		}

		if(is_numeric($search)) // maybe it's customer ID
		{
			if($customerid = $DB->GetOne('SELECT id FROM customersview WHERE id = '.$search))
			{
				$target = '?m=customerinfo&id='.$customerid;
				break;
			}
		}

		// use customersearch module to find all customers
		$s['customername'] = $search;
		$s['address'] = $search;
		$s['zip'] = $search;
		$s['city'] = $search;
		$s['phone'] = $search;
		$s['email'] = $search;
		$SESSION->save('customersearch', $s);
		$SESSION->save('uslk', 'OR');
		
		$SESSION->remove('uslp');
		$SESSION->remove('usln');
		$SESSION->remove('uslg');
		$SESSION->remove('usls');
		
		$target = '?m=customersearch&search=1';
	break;

	case 'node':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT n.id, n.name, INET_NTOA(ipaddr) as ip, INET_NTOA(ipaddr_pub) AS ip_pub, mac 
				    FROM nodes n
				    JOIN customersview c ON (c.id = n.ownerid)
				    WHERE n.id ?LIKE? \''.$search.'%\' 
					    OR LOWER(n.name) ?LIKE? LOWER(\'%'.$search.'%\') 
					    OR INET_NTOA(ipaddr) ?LIKE? \'%'.$search.'%\' 
					    OR INET_NTOA(ipaddr_pub) ?LIKE? \'%'.$search.'%\' 
					    OR LOWER(mac) ?LIKE? LOWER(\'%'.macformat($search).'%\') 
				    ORDER BY n.name LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=nodeinfo&id='.$row['id'];
				$eglible[$row['id']] = escape_js($row['name']);
				if (preg_match("/^$search/i",$row['id'])) $descriptions[$row['id']] = escape_js(trans('Id').': '.$row['id']);
				if (preg_match("/$search/i",$row['name'])) $descriptions[$row['id']] = escape_js(trans('Name').': '.$row['name']);
				if (preg_match("/$search/i",$row['ip'])) $descriptions[$row['id']] = trans('IP').': '.$row['ip'];
				if (preg_match("/$search/i",$row['ip_pub'])) $descriptions[$row['id']] = trans('IP').': '.$row['ip_pub'];
				if (preg_match("/".macformat($search)."/i",$row['mac'])) $descriptions[$row['id']] = trans('MAC').': '.$row['mac'];
				if (!$descriptions[$row['id']]) $descriptions[$row['id']]='-';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
				print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
				print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
			} else {
				print "false;\n";
			}
			exit;
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
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll('SELECT rttickets.id, rttickets.subject, rttickets.requestor, customers.name, customers.lastname FROM rttickets LEFT JOIN customers on rttickets.customerid=customers.id WHERE rttickets.id ?LIKE? \''.$search.'%\' OR lower(rttickets.subject) ?LIKE? lower(\'%'.$search.'%\') OR lower(rttickets.requestor) ?LIKE? lower(\'%'.$search.'%\') OR lower(customers.name) ?LIKE? lower(\''.$search.'%\') OR lower(customers.lastname) ?LIKE? lower(\''.$search.'%\') ORDER BY rttickets.subject, rttickets.id, customers.lastname, customers.name, rttickets.requestor LIMIT 15');
			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=rtticketview&id='.$row['id'];
				$eglible[$row['id']] = escape_js($row['subject']);
				if (preg_match("/$search/i",$row['id'])) $descriptions[$row['id']] = trans('Id').': '.$row['id'];
				if (preg_match("/$search/i",$row['subject'])) $descriptions[$row['id']] = escape_js(trans('Subject:').' '.$row['subject']);
				if (preg_match("/$search/i",$row['requestor'])) $descriptions[$row['id']] = escape_js(trans('First/last name').': '.preg_replace('/ <.*/','',$row['requestor']));
				if (preg_match("/^$search/i",$row['name'])) $descriptions[$row['id']] = escape_js(trans('First/last name').': '.$row['name']);
				if (preg_match("/^$search/i",$row['lastname'])) $descriptions[$row['id']] = escape_js(trans('First/last name').': '.$row['lastname']);
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print preg_replace('/$/',"\");\n","this.eligible = new Array(\"".implode('","',$eglible));
				print preg_replace('/$/',"\");\n","this.descriptions = new Array(\"".implode('","',$descriptions));
				print preg_replace('/$/',"\");\n","this.actions = new Array(\"".implode('","',$actions));
			} else {
				print "false;\n";
			}
			exit;
		}

		if(is_numeric($search) && intval($search)>0)
			$target = '?m=rtticketview&id='.intval($search);
		else
		{
			$SESSION->save('rtsearch', array('name' => $search));
			$target = '?m=rtsearch&search=1';
		}
	break;
}

$SESSION->redirect($target ? $target : '?m=welcome');

?>
