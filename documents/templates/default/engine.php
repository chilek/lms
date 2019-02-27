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

$cid = $document['customerid'];

$customerinfo = $LMS->GetCustomer($cid);
$assignments = $LMS->GetCustomerAssignments($cid);
$customernodes = $LMS->GetCustomerNodes($cid);
$tariffs = $LMS->GetTariffs();
$divisionid = $LMS->GetCustomerDivision($cid);
$division = $LMS->GetDivision($divisionid);

unset($customernodes['total']);

if($customernodes)
	foreach($customernodes as $idx => $row)
	{
		$customernodes[$idx]['net'] = $DB->GetRow('SELECT *, inet_ntoa(address) AS ip FROM networks WHERE address = (inet_aton(mask) & ?)', array($row['ipaddr']));
	}

if($customeraccounts = $DB->GetAll('SELECT passwd.*, domains.name AS domain
				FROM passwd LEFT JOIN domains ON (domainid = domains.id)
				WHERE passwd.ownerid = ? ORDER BY login', array($cid)))
	foreach($customeraccounts as $idx => $account)
	{
		$customeraccounts[$idx]['aliases'] = $DB->GetCol('SELECT login FROM aliases a 
			LEFT JOIN aliasassignments aa ON a.id = aa.aliasid WHERE aa.accountid=?', array($account['id']));
		/*// create random password
		$pass = '';
		for ($i = 0; $i < 8; $i++)
		       $pass .= substr('0123456789abcdefghijklmnopqrstuvwxyz', rand(0,36), 1);
		$customeraccounts[$idx]['password'] = $pass;
		*/
	}

$document['template'] = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($document['numberplanid']));
$document['nr'] = docnumber(array(
	'number' => $document['number'],
	'template' => $document['template'],
	'customerid' => $document['customerid'],
));

$SMARTY->assign(
		array(
			'customernodes' => $customernodes,
			'assignments' => $assignments,
			'customerinfo' => $customerinfo,
			'tariffs' => $tariffs,
			'customeraccounts' => $customeraccounts,
			'document' => $document,
			'engine' => $engine,
			'division' => $division,
		     )
		);

$output = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['template']);

?>
