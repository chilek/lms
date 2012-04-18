<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

function macformat($mac, $escape=false)
{
	global $DB;

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
		$tmp = preg_replace('/[^0-9a-f]/i', '', $mac);

		if(strlen($tmp) == 12) // we've the whole address
			if(check_mac($tmp))
				$res = $tmp;
	}

	if ($escape)
		$res = $DB->Escape("%$res%");
	return $res;
}

$mode = '';

if(!empty($_POST['qscustomer'])) {
	$mode = 'customer'; 
	$search = urldecode(trim($_POST['qscustomer']));
} elseif(!empty($_POST['qsnode'])) {
	$mode = 'node'; 
	$search = urldecode(trim($_POST['qsnode']));
} elseif(!empty($_POST['qsticket'])) {
	$mode = 'ticket'; 
	$search = urldecode(trim($_POST['qsticket']));
} elseif(!empty($_POST['qsaccount'])) {
	$mode = 'account'; 
	$search = urldecode(trim($_POST['qsaccount']));
} elseif(!empty($_GET['what'])) {
	$search = urldecode(trim($_GET['what']));
	$mode = $_GET['mode'];
}

$sql_search = $DB->Escape("%$search%");

switch($mode)
{
	case 'customer':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll("SELECT id, email, address, post_name, post_address, deleted,
			    ".$DB->Concat('UPPER(lastname)',"' '",'name')." AS username
				FROM customersview
				WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'id = '.intval($search).' OR ' : '')."
					LOWER(".$DB->Concat('lastname',"' '",'name').") ?LIKE? LOWER($sql_search)
					OR LOWER(address) ?LIKE? LOWER($sql_search)
					OR LOWER(post_name) ?LIKE? LOWER($sql_search)
					OR LOWER(post_address) ?LIKE? LOWER($sql_search)
					OR LOWER(email) ?LIKE? LOWER($sql_search)
				ORDER by deleted, username, email, address
				LIMIT 15");

			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=customerinfo&id='.$row['id'];
				$eglible[$row['id']] = escape_js(($row['deleted'] ? '<font class="blend">' : '')
				    .truncate_str($row['username'], 50).($row['deleted'] ? '</font>' : ''));

				if (preg_match("~^$search\$~i",$row['id'])) {
				    $descriptions[$row['id']] = escape_js(trans('Id:').' '.$row['id']);
				    continue;
				}
				if (preg_match("~$search~i",$row['username'])) {
				    $descriptions[$row['id']] = '';
				    continue;
				}
				if (preg_match("~$search~i",$row['address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['address']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_name'])) {
				    $descriptions[$row['id']] = escape_js(trans('Name:').' '.$row['post_name']);
				    continue;
				}
				else if (preg_match("~$search~i",$row['post_address'])) {
				    $descriptions[$row['id']] = escape_js(trans('Address:').' '.$row['post_address']);
				    continue;
				}
				if (preg_match("~$search~i",$row['email'])) {
				    $descriptions[$row['id']] = escape_js(trans('E-mail:').' '.$row['email']);
				    continue;
				}
				$descriptions[$row['id']] = '';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
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
		$s['email'] = $search;

		$SESSION->save('customersearch', $s);
		$SESSION->save('cslk', 'OR');

		$SESSION->remove('cslp');
		$SESSION->remove('csln');
		$SESSION->remove('cslg');
		$SESSION->remove('csls');

		$target = '?m=customersearch&search=1';
	break;

	case 'node':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
		    // Build different query for each database engine,
		    // MySQL is slow here when vnodes view is used
		    if ($CONFIG['database']['type'] == 'postgres')
			    $sql_query = 'SELECT n.id, n.name, INET_NTOA(ipaddr) as ip,
			        INET_NTOA(ipaddr_pub) AS ip_pub, mac
				    FROM vnodes n
				    WHERE %where
    				ORDER BY n.name LIMIT 15';
            else
			    $sql_query = 'SELECT n.id, n.name, INET_NTOA(ipaddr) as ip,
			        INET_NTOA(ipaddr_pub) AS ip_pub, mac
				    FROM nodes n
				    JOIN (
                        SELECT nodeid, GROUP_CONCAT(mac SEPARATOR \',\') AS mac
                        FROM macs
                        GROUP BY nodeid
                    ) m ON (n.id = m.nodeid)
				    WHERE %where
    				ORDER BY n.name LIMIT 15';

            $sql_where = '('.(preg_match('/^[0-9]+$/',$search) ? "n.id = $search OR " : '')."
				LOWER(n.name) ?LIKE? LOWER($sql_search)
				OR INET_NTOA(ipaddr) ?LIKE? $sql_search
				OR INET_NTOA(ipaddr_pub) ?LIKE? $sql_search
				OR LOWER(mac) ?LIKE? LOWER(".macformat($search, true)."))
			    AND NOT EXISTS (
                    SELECT 1 FROM customerassignments a
                    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			        WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)";

			$candidates = $DB->GetAll(str_replace('%where', $sql_where,	$sql_query));

			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=nodeinfo&id='.$row['id'];
				$eglible[$row['id']] = escape_js($row['name']);

				if (preg_match("~^$search\$~i", $row['id'])) {
				    $descriptions[$row['id']] = escape_js(trans('Id').': '.$row['id']);
				    continue;
				}
				if (preg_match("~$search~i", $row['name'])) {
				    $descriptions[$row['id']] = escape_js(trans('Name').': '.$row['name']);
				    continue;
				}
				if (preg_match("~$search~i", $row['ip'])) {
				    $descriptions[$row['id']] = trans('IP').': '.$row['ip'];
				    continue;
				}
				if (preg_match("~$search~i", $row['ip_pub'])) {
				    $descriptions[$row['id']] = trans('IP').': '.$row['ip_pub'];
				    continue;
				}
				if (preg_match("~".macformat($search)."~i", $row['mac'])) {
				    $macs = explode(',', $row['mac']);
				    foreach ($macs as $mac) {
    				    if (preg_match("~".macformat($search)."~i", $mac)) {
        				    $descriptions[$row['id']] = trans('MAC').': '.$mac;
	                    }
			        }
			        if (count($macs) > 1) {
			            $descriptions[$row['id']] .= ',...';
			        }
				    continue;
				}
				$descriptions[$row['id']] = '';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
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

		$target = '?m=nodesearch&search';
	break;

	case 'ticket':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$categories = $LMS->GetCategoryListByUser($AUTH->id);
			foreach($categories as $category)
				$catids[] = $category['id'];
			$candidates = $DB->GetAll("SELECT t.id, t.subject, t.requestor, c.name, c.lastname 
				FROM rttickets t
				LEFT JOIN rtticketcategories tc ON t.id = tc.ticketid
				LEFT JOIN customersview c on (t.customerid = c.id)
				WHERE ".(is_array($catids) ? "tc.categoryid IN (".implode(',', $catids).")" : "tc.categoryid IS NULL")
					." AND (".(preg_match('/^[0-9]+$/',$search) ? 't.id = '.intval($search).' OR ' : '')."
					LOWER(t.subject) ?LIKE? LOWER($sql_search)
					OR LOWER(t.requestor) ?LIKE? LOWER($sql_search)
					OR LOWER(c.name) ?LIKE? LOWER($sql_search)
					OR LOWER(c.lastname) ?LIKE? LOWER($sql_search))
					ORDER BY t.subject, t.id, c.lastname, c.name, t.requestor
					LIMIT 15");

			$eglible=array(); $actions=array(); $descriptions=array();
			if ($candidates)
			foreach($candidates as $idx => $row) {
				$actions[$row['id']] = '?m=rtticketview&id='.$row['id'];
				$eglible[$row['id']] = escape_js($row['subject']);
				if (preg_match("~^$search\$~i",$row['id'])) 	{ $descriptions[$row['id']] = trans('Id').': '.$row['id']; continue; }
				if (preg_match("~$search~i",$row['subject'])) 	{ $descriptions[$row['id']] = escape_js(trans('Subject:').' '.$row['subject']); continue; }
				if (preg_match("~$search~i",$row['requestor'])) { $descriptions[$row['id']] = escape_js(trans('First/last name').': '.preg_replace('/ <.*/','',$row['requestor'])); continue; }
				if (preg_match("~^$search~i",$row['name'])) 	{ $descriptions[$row['id']] = escape_js(trans('First/last name').': '.$row['name']); continue; }
				if (preg_match("~^$search~i",$row['lastname'])) { $descriptions[$row['id']] = escape_js(trans('First/last name').': '.$row['lastname']); continue; }
				$descriptions[$row['id']] = '';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
			exit;
		}

		if(is_numeric($search) && intval($search)>0)
			$target = '?m=rtticketview&id='.intval($search);
		else
		{
			$SESSION->save('rtsearch', array('name' => $search, 
					'subject' => $search,
					'operator' => 'OR'));

			$target = '?m=rtsearch&s=1';
		}
	break;

	case 'account':
		$ac = explode('@', $search);

		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
		    $username = $DB->Escape('%'.$ac[0].'%');
		    $domain   = $DB->Escape('%'.$ac[1].'%');

			$candidates = $DB->GetAll("(SELECT p.id, p.login, d.name AS domain, 0 AS type
					FROM passwd p
					JOIN domains d ON (p.domainid = d.id)
					WHERE p.login ?LIKE? LOWER($username)
					".($domain ? "AND d.name ?LIKE? LOWER($domain)" : '').")
					UNION 
					(SELECT a.id, a.login, d.name AS domain, 1 AS type 
					FROM aliases a
					JOIN domains d ON (a.domainid = d.id)
					WHERE a.login ?LIKE? LOWER($username)
					".($domain ? "AND d.name ?LIKE? LOWER($domain)" : '').")
					ORDER BY login, domain
					LIMIT 15");

			$eglible=array(); $actions=array(); $descriptions=array();

			if ($candidates) foreach($candidates as $idx => $row)
			{
				if($row['type'])
					$actions[$row['id']] = '?m=aliasinfo&id='.$row['id'];
				else
					$actions[$row['id']] = '?m=accountinfo&id='.$row['id'];

				$eglible[$row['id']] = escape_js($row['login'].'@'.$row['domain']);
				$descriptions[$row['id']] = '';
			}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
			exit;
		}

		$search = array();
		$search['login'] = $ac[0];
		if(!empty($ac[1])) $search['domain'] = $ac[1];

		$SESSION->save('accountsearch', $search);
		$target = '?m=accountsearch&s=1';
	break;
}

$SESSION->redirect(!empty($target) ? $target : '?'.$SESSION->get('backto'));

?>
