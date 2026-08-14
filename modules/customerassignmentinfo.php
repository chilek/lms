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

$a = $DB->GetRow(
    'SELECT a.invoice, a.settlement,
        a.numberplanid, a.paytime, a.paytype, n.template, n.period, a.attribute,
        d.number AS docnumber, d.type AS doctype, d.cdate,
        n2.template AS numtemplate, a.customerid,
        a.separatedocument, a.separateitem,
        t.value, t.flags,
        (CASE WHEN l.flags IS NULL
            THEN (CASE WHEN t.flags & ? > 0 THEN 1 ELSE 0 END)
            ELSE (CASE WHEN l.flags & ? > 0 THEN 1 ELSE 0 END)
        END) AS splitpayment,
        (CASE WHEN l.flags IS NULL
            THEN (CASE WHEN t.flags & ? > 0 THEN 1 ELSE 0 END)
            ELSE (CASE WHEN l.flags & ? > 0 THEN 1 ELSE 0 END)
        END) AS netflag,
        (CASE WHEN l.taxcategory IS NULL THEN t.taxcategory ELSE l.taxcategory END) AS taxcategory
    FROM assignments a
    LEFT JOIN numberplans n ON (n.id = a.numberplanid)
    LEFT JOIN documents d ON d.id = a.docid
    LEFT JOIN numberplans n2 ON n2.id = d.numberplanid
    LEFT JOIN tariffs t ON t.id = a.tariffid
    LEFT JOIN liabilities l ON l.id = a.liabilityid
    WHERE a.id = ?',
    array(
        TARIFF_FLAG_SPLIT_PAYMENT,
        LIABILITY_FLAG_SPLIT_PAYMENT,
        TARIFF_FLAG_NET_ACCOUNT,
        LIABILITY_FLAG_NET_ACCOUT,
        $_GET['id'],
    )
);

if (!empty($a['template'])) {
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

$a['paytypename'] = !empty($a['paytype']) && isset($PAYTYPES[$a['paytype']]) ? $PAYTYPES[$a['paytype']]['label'] : '';

$SMARTY->assign('assignment', $a);
$SMARTY->display('customer/customerassignmentinfoshort.html');
