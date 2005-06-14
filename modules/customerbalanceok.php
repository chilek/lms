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

$customerid = $_GET['id'];

if(!$LMS->CustomerExists($customerid))
{
	$layout['pagetitle'] = trans('Accounts Clear With Customer ID: $0',sprintf("%04d", $customerid));
	$body = '<H1>'.$layout['pagetitle'].'</H1><P>'.trans('Incorrect Customer ID.').'</P>';
	
	$SMARTY->assign('body',$body);
	$SMARTY->assign('customerid',$customerid);
	$SMARTY->display('header.html');
	$SMARTY->display('dialog.html');
	$SMARTY->display('footer.html');
}

$covenants = $DB->GetAll('SELECT a.id AS cashid,
			ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) AS value
			FROM cash a 
			LEFT JOIN cash b ON (a.id = b.reference)
			WHERE a.customerid = ? AND a.docid>0
			GROUP BY a.docid, a.itemid, a.id
			HAVING ROUND(SUM(CASE a.type WHEN 3 THEN a.value*-1 ELSE a.value END)/(CASE COUNT(b.id) WHEN 0 THEN 1 ELSE COUNT(b.id) END),2)
			+ COALESCE(SUM(CASE b.type WHEN 3 THEN b.value*-1 ELSE b.value END),0) > 0', array($customerid));

$balance = $LMS->GetCustomerBalance($customerid);

if($covenants)
{
	foreach($covenants as $row)
	{
		if($balance>=0) break;
	
		$value = -$balance>=$row['value'] ? $row['value'] : -$balance;
		$balance += $value;
		
		$DB->Execute('INSERT INTO cash (time, userid, type, value, customerid, comment, reference)
				VALUES (?NOW?, ?, 3, ?, ?, ?, ?)', 
				array($AUTH->id, 
					$value,
					$customerid,
					trans('Accounted'),
					$row['cashid']));
	}
}

if($balance<0)
{
	$DB->Execute('INSERT INTO cash (time, userid, type, value, customerid, comment)
			VALUES (?NOW?, ?, 3, ?, ?, ?)', 
			array($AUTH->id, 
				-$balance,
				$customerid,
				trans('Accounted')));
}


header('Location: ?'.$SESSION->get('backto'));

?>
