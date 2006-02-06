<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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

$p = $_GET['p'];

if(!isset($p))
	$js = 'var targetfield = window.opener.targetfield;';
if($p == 'main')
	$js = 'var targetfield = parent.targetfield;';

if($_POST['search'])
{
	$search = $_POST['search'];

	if($customerlist = $DB->GetAll('SELECT customers.id AS id, '.$DB->Concat('UPPER(lastname)',"' '",'customers.name').' AS customername, address, zip, city, email, phone1, ssn, 
				COALESCE(SUM(value), 0.00)/(CASE COUNT(DISTINCT nodes.id) WHEN 0 THEN 1 ELSE COUNT(DISTINCT nodes.id) END) AS balance 
				FROM customers 
				LEFT JOIN cash ON (customers.id = cash.customerid)
				LEFT JOIN nodes ON (customers.id = ownerid)
				WHERE deleted = 0 
				AND (customers.id = '.intval($search)
				    .' OR ten LIKE \'%'.$search.'%\''
				    .' OR ssn LIKE \'%'.$search.'%\''
				    .' OR phone1 LIKE \'%'.$search.'%\''
				    .' OR UPPER(email) LIKE UPPER(\'%'.$search.'%\')'
				    .' OR UPPER('.$DB->Concat('lastname',"' '",'customers.name').') ?LIKE? UPPER(\'%'.$search.'%\')'
				    .' OR UPPER(address) ?LIKE? UPPER(\'%'.$search.'%\')'
				    .' OR UPPER(nodes.name) ?LIKE? UPPER(\'%'.$search.'%\')
				) 
				GROUP BY customers.id, lastname, customers.name, address, zip, city, email, phone1, ssn
				ORDER BY customername LIMIT 15'))
	{
		foreach($customerlist as $idx => $row)
			$customerlist[$idx]['nodes'] = $LMS->GetCustomerNodes($row['id']);
	}

	$SMARTY->assign('customerlist', $customerlist);
	$SMARTY->assign('search', $search);
}

$SMARTY->assign('part', $p);
$SMARTY->assign('js', $js);
$SMARTY->display('choosecustomer.html');

?>
