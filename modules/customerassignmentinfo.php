<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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
 *  $Id: 057973c06cb75a3348be6172466bc1bce55a4cb4 $
 */

$a = $DB->GetRow('SELECT a.invoice, a.settlement,
        a.numberplanid, a.paytype, n.template, n.period, a.attribute,
        d.number AS docnumber, d.type AS doctype, d.cdate,
        n2.template AS numtemplate, a.customerid, a.separatedocument,
        (CASE WHEN l.splitpayment IS NULL THEN t.splitpayment ELSE l.splitpayment END) AS splitpayment
    FROM assignments a
    LEFT JOIN numberplans n ON (n.id = a.numberplanid)
    LEFT JOIN documents d ON d.id = a.docid
    LEFT JOIN numberplans n2 ON n2.id = d.numberplanid
    LEFT JOIN tariffs t ON t.id = a.tariffid
    LEFT JOIN liabilities l ON l.id = a.liabilityid
    WHERE a.id = ?', array($_GET['id']));

if ($a['template']) {
    $a['numberplan'] = $a['template'].' ('.$NUM_PERIODS[$a['period']].')';
}

if (!empty($a['docnumber'])) {
    $a['docnumber'] = docnumber(array(
       'number' => $a['docnumber'],
       'template' => $a['numtemplate'],
       'cdate' => $a['cdate'],
       'customerid' => empty($a['customerid']) ? null : $a['customerid'],
    ));
    $a['document'] = trans(
        '$a no. $b issued on $c',
        $DOCTYPES[$a['doctype']],
        $a['docnumber'],
        date('Y/m/d', $a['cdate'])
    );
}

$a['paytypename'] = $PAYTYPES[$a['paytype']];

$SMARTY->assign('assignment', $a);
$SMARTY->display('customer/customerassignmentinfoshort.html');
