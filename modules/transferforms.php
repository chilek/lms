<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if ($kind == 2)
	$list = $DB->GetAll('SELECT c.id, c.address, c.zip, c.city, d.account,
		d.name AS d_name, d.shortname AS d_shortname, d.address AS d_address, d.zip AS d_zip, d.city AS d_city, '
		. $DB->Concat('UPPER(lastname)', "' '", 'c.name') . ' AS customername,
		COALESCE(SUM(cash.value), 0.00) AS balance
		FROM customerview c 
		LEFT JOIN cash ON (c.id = cash.customerid)
		LEFT JOIN vdivisions d ON (d.id = c.divisionid)
		WHERE deleted = 0'
		. ($customer ? ' AND c.id = '.$customer : '')
		. ($group ?
		' AND '.($exclgroup ? 'NOT' : '').'
			EXISTS (SELECT 1 FROM customerassignments a
			WHERE a.customergroupid = '.$group.' AND a.customerid = c.id)' : '')
		.' GROUP BY c.id, c.lastname, c.name, c.address, c.zip, c.city, d.account, d.name,
			d.shortname, d.address, d.zip, d.city
		HAVING COALESCE(SUM(cash.value), 0.00) < ? ORDER BY c.id',
		array(str_replace(',', '.', $balance)));
else
	$list = $DB->GetCol('SELECT id FROM documents d
		WHERE cdate >= ? AND cdate <= ? AND type = ?'
		.(!empty($_GET['customerid']) ? ' AND d.customerid = '.intval($_GET['customerid']) : '')
		.(!empty($_GET['numberplanid']) ? ' AND d.numberplanid = '.intval($_GET['numberplanid']) : '')
		.(!empty($_GET['groupid']) ?
		' AND '.(!empty($_GET['groupexclude']) ? 'NOT' : '').'
			EXISTS (SELECT 1 FROM customerassignments a
				WHERE a.customergroupid = '.intval($_GET['groupid']).'
				AND a.customerid = d.customerid)' : '')
		.' AND NOT EXISTS (
			SELECT 1 FROM customerassignments a
			JOIN excludedgroups e ON (a.customergroupid = e.customergroupid)
			WHERE e.userid = lms_current_user() AND a.customerid = d.customerid)'
		.' ORDER BY CEIL(cdate/86400), id',
		array($_GET['from'], $_GET['to'], DOC_INVOICE));

if (!$list) {
	$SESSION->close();
	die;
}

if ($kind == 2)
	$document = new LMSEzpdfTransferForm(trans('Form of Cash Transfer'), 'A4', 'landscape');
else
	$document = new LMSEzpdfMipTransferForm(trans('Form of Cash Transfer'), 'A4', 'portrait');

$count = count($list);
$i = 0;

foreach ($list as $row) {
	if ($kind == 1) {
		$row = $LMS->GetInvoiceContent($row);
		$row['t_number'] = docnumber(array(
			'number' => $row['number'],
			'template' => $row['template'],
			'cdate' => $row['cdate'],
		));
	}
	$i++;
	$row['last'] = ($i == $count);
	$document->Draw($row);
}

$document->WriteToBrowser();

?>
