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

$layout['pagetitle'] = trans('New Balance');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$last = $DB->GetRow('SELECT cash.id AS id, cash.value AS value, cash.currency, cash.currencyvalue,
        taxes.label AS tax, customerid, time, comment, '.$DB->Concat('UPPER(c.lastname)', "' '", 'c.name').' AS customername,
		s.name AS sourcename
		FROM cash 
		LEFT JOIN customers c ON (customerid = c.id)
		LEFT JOIN taxes ON (taxid = taxes.id)
		LEFT JOIN cashsources s ON (cash.sourceid = s.id)
		WHERE NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = cash.customerid)
		ORDER BY cash.id DESC LIMIT 1');

$SMARTY->assign('last', $last);
$SMARTY->assign('currency', LMS::$default_currency);
$SMARTY->assign('operation', $SESSION->get('addtype'));
$SMARTY->assign('sourceid', $SESSION->get('addsource'));
$SMARTY->assign('comment', $SESSION->get('addbc'));
$SMARTY->assign('taxid', $SESSION->get('addbtax'));
$SMARTY->assign('time', $SESSION->get('addbt'));
$SMARTY->assign('taxeslist', $LMS->GetTaxes());
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('sourcelist', $DB->GetAll('SELECT id, name FROM cashsources WHERE deleted = 0 ORDER BY name'));
$SMARTY->display('balance/balancenew.html');
