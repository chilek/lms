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

$layout['pagetitle'] = trans('Select customer');

$p = isset($_GET['p']) ? $_GET['p'] : '';

if(!$p)
	$SMARTY->assign('js', 'var targetfield = window.opener.targetfield;');
elseif($p == 'main')
	$SMARTY->assign('js', 'var targetfield = parent.targetfield;');

if(isset($_POST['searchcustomer']) && $_POST['searchcustomer'])
{
	$search = $_POST['searchcustomer'];

	$where_cust = 'AND ('.(intval($search) ? 'c.id = '.intval($search).' OR' : '')
			.'    ten LIKE \'%'.$search.'%\''
			.' OR ssn LIKE \'%'.$search.'%\''
			.' OR icn LIKE \'%'.$search.'%\''
			.' OR rbe LIKE \'%'.$search.'%\''
			.' OR regon LIKE \'%'.$search.'%\''
			.' OR UPPER(email) LIKE UPPER(\'%'.$search.'%\')'
			.' OR UPPER('.$DB->Concat('lastname',"' '",'c.name').') LIKE UPPER(\'%'.$search.'%\')'
			.' OR UPPER(address) LIKE UPPER(\'%'.$search.'%\')) ';
	
	$SMARTY->assign('searchcustomer', $search);
}

if(isset($_POST['searchnode']) && $_POST['searchnode'])
{
	$search = $_POST['searchnode'];

	$where_node = 'AND ('.(intval($search) ? 'nodes.id = '.intval($search).' OR ' : '')
			.'    INET_NTOA(ipaddr) LIKE \'%'.$search.'%\''
			.' OR INET_NTOA(ipaddr_pub) LIKE \'%'.$search.'%\''
			.' OR UPPER(mac) LIKE UPPER(\'%'.$search.'%\')'
			.' OR UPPER(location) LIKE UPPER(\'%'.$search.'%\')'
			.' OR UPPER(nodes.name) LIKE UPPER(\'%'.$search.'%\')) ';
	
	$SMARTY->assign('searchnode', $search);
}

if(isset($where_node) || isset($where_cust))
{
	if($customerlist = $DB->GetAll('SELECT DISTINCT c.id AS id, '.$DB->Concat('UPPER(c.lastname)',"' '",'c.name').' AS customername, address, zip, city, email, ssn, 
				(SELECT SUM(value) FROM cash WHERE customerid = c.id) AS balance 
				FROM customersview c ' 
				.(isset($where_node) ? 'LEFT JOIN nodes ON (c.id = ownerid) ' : '')
				.'WHERE deleted = 0 '
				.(isset($where_cust) ? $where_cust : '')
				.(isset($where_node) ? $where_node : '')
				.'ORDER BY customername LIMIT 15'))
	{
		foreach($customerlist as $idx => $row)
			$customerlist[$idx]['nodes'] = $DB->GetAll('SELECT id, name, mac, inet_ntoa(ipaddr) AS ip, inet_ntoa(ipaddr_pub) AS ip_pub FROM nodes WHERE ownerid=? ORDER BY name',array($row['id']));
	}

	$SMARTY->assign('customerlist', $customerlist);
}

$SMARTY->assign('part', $p);
$SMARTY->display('choosecustomer.html');

?>
