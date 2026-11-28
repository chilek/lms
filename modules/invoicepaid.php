<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

$SESSION->restore('ilm', $marks);
$SESSION->remove('ilm');
$ids = Utils::filterIntegers(array_replace((array) $marks, (array) ($_POST['marks'] ?? array())));

foreach (array_unique($ids) as $invoiceid) {
    $DB->BeginTrans();
    $document = $DB->GetRow(
        'SELECT customerid, closed, type, reference, currency, currencyvalue FROM documents
        WHERE id = ? AND type IN ? AND cancelled = 0
            AND divisionid IN (SELECT divisionid FROM userdivisions WHERE userid = ?)
            AND (customerid IS NULL OR customerid IN (SELECT id FROM customerview))
        FOR UPDATE',
        array($invoiceid, array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO), Auth::GetCurrentUser())
    );
    if (!$document) {
        $DB->RollbackTrans();
        continue;
    }

    if (ConfigHelper::checkConfig('phpui.invoice_check_payment') && $document['customerid'] && !$document['closed']) {
        $value = $DB->GetOne(
            'SELECT COALESCE(SUM(grossvalue), 0) - COALESCE(
                (SELECT SUM(grossvalue) FROM vinvoicecontents WHERE docid = ?), 0)
            FROM vinvoicecontents WHERE docid = ?',
            array($document['type'] == DOC_CNOTE ? $document['reference'] : null, $invoiceid)
        );
        if ($value === false || $value === null) {
            $DB->RollbackTrans();
            continue;
        }
        if ($value != 0 && !$LMS->AddBalance(array(
            'type' => 1,
            'time' => time(),
            'value' => $value,
            'currency' => $document['currency'],
            'currencyvalue' => $document['currencyvalue'],
            'customerid' => $document['customerid'],
            'comment' => trans('Accounted'),
        ))) {
            $DB->RollbackTrans();
            continue;
        }
    }

    $closed = intval(!$document['closed']);
    if (!$DB->Execute('UPDATE documents SET closed = ? WHERE id = ?', array($closed, $invoiceid))) {
        $DB->RollbackTrans();
        continue;
    }
    if ($SYSLOG) {
        $args = array(
            SYSLOG::RES_DOC => $invoiceid,
            SYSLOG::RES_CUST => $document['customerid'],
            'closed' => $closed,
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
    }
    $DB->CommitTrans();
}

$SESSION->redirect_to_history_entry();
