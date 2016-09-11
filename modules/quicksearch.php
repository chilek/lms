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

if (!empty($_POST['qs'])) {
        foreach($_POST['qs'] as $key => $value)
                if(!empty($value)){
                        $mode = $key;
                        $search = $value;
                }
	$search = urldecode(trim($search));
} elseif(!empty($_GET['what'])) {
	$search = urldecode(trim($_GET['what']));
	$mode = $_GET['mode'];
}

$sql_search = $DB->Escape("%$search%");

switch ($mode) {
	case 'customer':
		if(isset($_GET['ajax'])) // support for AutoSuggest
		{
			$candidates = $DB->GetAll("SELECT c.id, cc.contact AS email, address, post_name, post_address, deleted,
			    ".$DB->Concat('UPPER(lastname)',"' '",'c.name')." AS username
				FROM customerview c
				LEFT JOIN customercontacts cc ON cc.customerid = c.id AND (cc.type & " . CONTACT_EMAIL . " = " . CONTACT_EMAIL . ")    
				WHERE ".(preg_match('/^[0-9]+$/', $search) ? 'c.id = '.intval($search).' OR ' : '')."
					LOWER(".$DB->Concat('lastname',"' '",'c.name').") ?LIKE? LOWER($sql_search)
					OR LOWER(address) ?LIKE? LOWER($sql_search)
					OR LOWER(post_name) ?LIKE? LOWER($sql_search)
					OR LOWER(post_address) ?LIKE? LOWER($sql_search)
					OR LOWER(cc.contact) ?LIKE? LOWER($sql_search)
				ORDER by deleted, username, cc.contact, address
				LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

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
                        $SESSION->close();
                        $DB->Destroy();
			exit;
		}

		if(is_numeric($search)) // maybe it's customer ID
		{
			if($customerid = $DB->GetOne('SELECT id FROM customerview WHERE id = '.$search))
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
		    if (ConfigHelper::getConfig('database.type') == 'postgres')
			    $sql_query = 'SELECT n.id, n.name, INET_NTOA(ipaddr) as ip,
			        INET_NTOA(ipaddr_pub) AS ip_pub, mac
				    FROM vnodes n
				    WHERE %where
    				ORDER BY n.name LIMIT ?';
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
    				ORDER BY n.name LIMIT ?';

            $sql_where = '('.(preg_match('/^[0-9]+$/',$search) ? "n.id = $search OR " : '')."
				LOWER(n.name) ?LIKE? LOWER($sql_search)
				OR INET_NTOA(ipaddr) ?LIKE? $sql_search
				OR INET_NTOA(ipaddr_pub) ?LIKE? $sql_search
				OR LOWER(mac) ?LIKE? LOWER(".macformat($search, true)."))
			    AND NOT EXISTS (
                    SELECT 1 FROM customerassignments a
                    JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			        WHERE e.userid = lms_current_user() AND a.customerid = n.ownerid)";

			$candidates = $DB->GetAll(str_replace('%where', $sql_where,	$sql_query),
				array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

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
                        $SESSION->close();
                        $DB->Destroy();
			exit;
		}

		if(is_numeric($search) && !strstr($search, '.')) // maybe it's node ID
		{
			if($nodeid = $DB->GetOne('SELECT id FROM vnodes WHERE id = '.$search))
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
				LEFT JOIN customerview c on (t.customerid = c.id)
				WHERE ".(is_array($catids) ? "tc.categoryid IN (".implode(',', $catids).")" : "tc.categoryid IS NULL")
					." AND (".(preg_match('/^[0-9]+$/',$search) ? 't.id = '.intval($search).' OR ' : '')."
					LOWER(t.subject) ?LIKE? LOWER($sql_search)
					OR LOWER(t.requestor) ?LIKE? LOWER($sql_search)
					OR LOWER(c.name) ?LIKE? LOWER($sql_search)
					OR LOWER(c.lastname) ?LIKE? LOWER($sql_search))
					ORDER BY t.subject, t.id, c.lastname, c.name, t.requestor
					LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

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
                        $SESSION->close();
                        $DB->Destroy();
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
					LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

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
                        $SESSION->close();
                        $DB->Destroy();
			exit;
		}

		$search = array();
		$search['login'] = $ac[0];
		if(!empty($ac[1])) $search['domain'] = $ac[1];

		$SESSION->save('accountsearch', $search);
		$target = '?m=accountsearch&s=1';
	break;

	case 'document':
		if (isset($_GET['ajax'])) {
			$candidates = $DB->GetAll("SELECT d.id, d.type, d.fullnumber,
					d.customerid AS cid, d.name AS customername
				FROM documents d
				JOIN customerview c on d.customerid = c.id
				WHERE (LOWER(d.fullnumber) ?LIKE? LOWER($sql_search)
					OR 1 = 0)
					ORDER BY d.fullnumber
					LIMIT ?", array(intval(ConfigHelper::getConfig('phpui.quicksearch_limit', 15))));

			$eglible = array(); $actions = array(); $descriptions = array();
			if ($candidates)
				foreach ($candidates as $idx => $row) {
/*
					switch ($row['type']) {
						case DOC_INVOICE:
							$actions[$row['id']] = '?m=invoice&id=' . $row['id'];
							break;
						case DOC_RECEIPT:
							$actions[$row['id']] = '?m=receipt&id=' . $row['id'];
							break;
						case DOC_CNOTE:
							$actions[$row['id']] = '?m=note&id=' . $row['id'];
							break;
						default:
							$actions[$row['id']] = '?m=documentview&id=' . $row['id'];
					}
*/
					$actions[$row['id']] = '?m=customerinfo&id=' . $row['cid'];
					$eglible[$row['id']] = escape_js($row['fullnumber']);
					$descriptions[$row['id']] = escape_js(truncate_str($row['customername'], 35));
					//$descriptions[$row['id']] = trans('Document id:') . ' ' . $row['id'];
				}
			header('Content-type: text/plain');
			if ($eglible) {
				print "this.eligible = [\"".implode('","',$eglible)."\"];\n";
				print "this.descriptions = [\"".implode('","',$descriptions)."\"];\n";
				print "this.actions = [\"".implode('","',$actions)."\"];\n";
			} else {
				print "false;\n";
			}
                        $SESSION->close();
                        $DB->Destroy();
			exit;
		}

		$docs = $DB->GetAll("SELECT d.id, d.type, d.customerid AS cid, d.name AS customername
			FROM documents d
			JOIN customerview c ON c.id = d.customerid
			WHERE LOWER(fullnumber) ?LIKE? LOWER($sql_search)");
		if (count($docs) == 1) {
			$cid = $docs[0]['cid'];
/*
			$docid = $docs[0]['id'];
			$type = $docs[0]['type'];
			switch ($type) {
				case DOC_INVOICE:
					$target = '?m=invoice&id=' . $docid;
					break;
				case DOC_RECEIPT:
					$target = '?m=receipt&id=' . $docid;
					break;
				case DOC_CNOTE:
					$target = '?m=note&id=' . $docid;
					break;
				default:
					$target = '?m=documentview&id=' . $docid;
			}
*/
			$target = '?m=customerinfo&id=' . $cid;
		}
	break;
}

$quicksearch = $LMS->executeHook('quicksearch_after_submit',
	array(
		'mode' => $mode,
		'search' => $search,
		'sql_search' => $sql_search,
		'session' => $SESSION,
		'target' => '',
	)
);
if (!empty($quicksearch['target']))
	$target = $quicksearch['target'];

$SESSION->redirect(!empty($target) ? $target : '?'.$SESSION->get('backto'));

?>
