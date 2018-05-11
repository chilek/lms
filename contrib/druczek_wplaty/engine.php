<?php

/*
 * LMS version 1.11.8 Belus
 *
 *  (C) Copyright 2001-2009 LMS Developers
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
 *  $Id: engine.php,v 1.10 2009/01/13 07:45:33 alec Exp $
 */

$cid = $document['customerid'];

$suma = round($suma,2);
$sumap = $suma * 100 % 100;


$customerinfo = $LMS->GetCustomer($cid);
$assignments = $LMS->GetCustomerAssignments($cid);
$customernodes = $LMS->GetCustomerNodes($cid);
$tariffs = $LMS->GetTariffs();

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
		$customeraccounts[$idx]['aliases'] = $DB->GetCol('SELECT login FROM aliases WHERE accountid=?', array($account['id']));
		/*// create random password
		$pass = '';
		for ($i = 0; $i < 8; $i++)
		       $pass .= substr('0123456789abcdefghijklmnopqrstuvwxyz', rand(0,36), 1);
		$customeraccounts[$idx]['password'] = $pass;
		*/
	}

$document['template'] = $DB->GetOne('SELECT template FROM numberplans WHERE id=?', array($document['numberplanid']));
$document['nr'] = docnumber($document['number'], $document['template']);


$daneumow = $DB->GetAll("SELECT  number, from_unixtime(cdate,'%d-%m-%Y') as date, from_unixtime(cdate,'%Y') as year, name, address, zip, city, ten, ssn  from documents where type = '-1' and customerid = ?",array($cid));

foreach($daneumow as $daneumowy) {
   $umowa['date'] = $daneumowy['date'];
   $umowa['year'] = $daneumowy['year'];
   $umowa['name'] = $daneumowy['name'];
   $umowa['address'] = $daneumowy['address'];
   $umowa['zip'] = $daneumowy['zip'];
   $umowa['city'] = $daneumowy['city'];
   $umowa['number'] = $daneumowy['number'];
   $umowa['ssn'] = $daneumowy['ssn'];
   $umowa['ten'] = $daneumowy['ten'];
}

$division = $DB->GetRow('SELECT * FROM ' . ($vaddresses_exists ? 'v' : '') . 'divisions WHERE id = ?',
	        array($customerinfo['divisionid']));

$division_address = $LMS->GetAddress($division['address_id']);
$USER_TY = "Abonament - ID: $cid";

$barcode = new \Com\Tecnick\Barcode\Barcode();
$bobj = $barcode->getBarcodeObj('C128', iconv('UTF-8', 'ASCII//TRANSLIT', $USER_TY), -1, -15, 'black');
$barcode_image = base64_encode($bobj->getPngData());

$SMARTY->assign(
		array(
			'customernodes' => $customernodes,
			'assignments' => $assignments,
			'customerinfo' => $customerinfo,
			'tariffs' => $tariffs,
			'customeraccounts' => $customeraccounts,
			'document' => $document,
                        'engine' => $engine,
			'umowa' => $umowa,
			'division' => $division,
			'division_address' => $division_address,
			'barcode_image' => $barcode_image
		     )
		);

$output = $SMARTY->fetch(DOC_DIR.'/templates/'.$engine['name'].'/'.$engine['template']);

?>
