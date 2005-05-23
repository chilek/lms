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

if(!$LMS->UserExists($customerid))
{
	$layout['pagetitle'] = trans('Accounts Clear With Customer ID: $0',sprintf("%04d", $customerid));
	$body = '<H1>'.$layout['pagetitle'].'</H1><P>'.trans('Incorrect Customer ID.').'</P>';
	
	$SMARTY->assign('body',$body);
	$SMARTY->assign('customerid',$customerid);
	$SMARTY->display('header.html');
	$SMARTY->display('dialog.html');
	$SMARTY->display('footer.html');
}

if($covenantlist = $DB->GetAll('SELECT invoiceid, itemid, taxvalue,
			SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 AS value
			FROM cash 
			WHERE customerid = ? 
			GROUP BY invoiceid, itemid, taxvalue
			HAVING SUM(CASE type WHEN 3 THEN value ELSE value*-1 END)*-1 > 0
			ORDER BY invoiceid', array($customerid)))
{
	foreach($covenantlist as $row)
	{
		$DB->Execute('INSERT INTO cash (time, adminid, type, value, taxvalue, customerid, comment, invoiceid, itemid)
				VALUES (?NOW?, ?, 3, ?, ?, ?, ?, ?, ?)', 
				array($AUTH->id, 
					$row['value'],
					$row['taxvalue'],
					$customerid, 
					trans('Accounted'), 
					$row['invoiceid'], 
					$row['itemid']));
	}
	$LMS->SetTS('cash');
}

header('Location: ?'.$SESSION->get('backto'));

?>
