<?php

/*
 * LMS version 1.11-cvs
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

$a = $DB->GetRow('SELECT a.invoice, a.settlement,
        a.numberplanid, a.paytype, n.template, n.period
		FROM assignments a
		LEFT JOIN numberplans n ON (n.id = a.numberplanid)
		WHERE a.id = ?',array(intval($_GET['id'])));

if ($a['template']) {
    $a['numberplan'] = $a['template'].' ('.$NUM_PERIODS[$a['period']].')';
}

$a['paytypename'] = $PAYTYPES[$a['paytype']];

$a['locks'] = array();
$locks = $DB->GetAll('SELECT days, fromsec, tosec FROM assignmentlocks WHERE assignmentid = ?',
	array(intval($_GET['id'])));
if (!empty($locks))
	foreach ($locks as $lock) {
		$from = intval($lock['fromsec']);
		$to = intval($lock['tosec']);
		$days = intval($lock['days']);
		$lockdays = array();
		for ($i = 0; $i < 7; $i++)
			if ($days & (1 << $i))
				$lockdays[$i] = 1;
		$a['locks'][] = array('days' => $lockdays, 'fhour' => intval($from / 3600), 'fminute' => intval(($from % 3600) / 60),
			'thour' => intval($to / 3600), 'tminute' => intval(($to % 3600) / 60));
	}

$SMARTY->assign('assignment', $a);
$SMARTY->display('customerassignmentinfoshort.html');

?>
