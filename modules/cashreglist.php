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

$layout['pagetitle'] = trans('Cash Registries List');

$reglist = $DB->GetAll('SELECT cashregs.id AS id, cashregs.name AS name, 
			cashregs.description AS description, disabled,
			(SELECT SUM(value) FROM receiptcontents 
				WHERE cashregs.id = regid) AS balance,
			(SELECT template FROM numberplans 
				WHERE in_numberplanid = numberplans.id ) AS in_template,
			(SELECT template FROM numberplans 
				WHERE out_numberplanid = numberplans.id) AS out_template
		FROM cashregs 
		ORDER BY cashregs.name');

$listdata['sum'] = 0;
if($reglist)
	foreach($reglist as $row)
		if(!$row['disabled'])
			$listdata['sum'] += $row['balance'];

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('reglist', $reglist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('cashreglist.html');

?>
