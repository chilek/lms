<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$id = intval($_GET['id']);

if ($id) {
    $DB->BeginTrans();
    $document = $DB->GetRow(
        'SELECT id, customerid, cancelled FROM documents
        WHERE id = ? AND type IN ?
            AND divisionid IN (SELECT divisionid FROM userdivisions WHERE userid = ?)
            AND (customerid IS NULL OR customerid IN (SELECT id FROM customerview))
        FOR UPDATE',
        array($id, array(DOC_INVOICE, DOC_CNOTE), Auth::GetCurrentUser())
    );
    if (!$document) {
        $DB->RollbackTrans();
        access_denied();
    }
    if (isset($_GET['recover'])) {
        if (!$document['cancelled'] || !$DB->Execute('UPDATE documents SET cancelled = 0 WHERE id = ?', array($id))) {
            $DB->RollbackTrans();
            $SESSION->redirect('?m=invoicelist');
        }

        $invoice = $LMS->GetInvoiceContent($id);
        if (empty($invoice['content'])) {
            $DB->RollbackTrans();
            $SESSION->redirect('?m=invoicelist');
        }

        foreach ($invoice['content'] as $idx => $content) {
            if ($invoice['doctype'] == DOC_CNOTE) {
                $value = $content['total'] - ($invoice['invoice']['content'][$idx]['total'] ?? 0);
            } else {
                $value = $content['total'];
            }
            if (!$LMS->AddBalance(array(
                'time' => $invoice['cdate'],
                'value' => $value * -1,
                'currency' => $invoice['currency'],
                'currencyvalue' => $invoice['currencyvalue'],
                'taxid' => $content['taxid'],
                'customerid' => $invoice['customerid'],
                'comment' => $content['description'],
                'docid' => $id,
                'itemid' => $content['itemid'],
            ))) {
                $DB->RollbackTrans();
                $SESSION->redirect('?m=invoicelist');
            }
        }
    } else {
        if ($LMS->isDocumentPublished($id) && !ConfigHelper::checkPrivilege('published_document_modification')) {
            $DB->RollbackTrans();
            return;
        }

        if ($LMS->isDocumentReferenced($id)) {
            $DB->RollbackTrans();
            return;
        }

        if ($LMS->isArchiveDocument($id)) {
            $DB->RollbackTrans();
            return;
        }

        if ($LMS->isKsefDocument($id)) {
            $DB->RollbackTrans();
            return;
        }

        $hook_data = $LMS->executeHook('invoicecancel_before_cancel', array(
            'id' => $id,
        ));
        if (isset($hook_data['continue']) && empty($hook_data['continue'])) {
            $DB->RollbackTrans();
            return;
        }

        if ($document['cancelled'] || !$DB->Execute('UPDATE documents SET cancelled = 1 WHERE id = ?', array($id))
            || $DB->Execute('DELETE FROM cash WHERE docid = ?', array($id)) === false) {
            $DB->RollbackTrans();
            $SESSION->redirect('?m=invoicelist');
        }
    }
    if ($SYSLOG) {
        $args = array(
            SYSLOG::RES_DOC => $document['id'],
            SYSLOG::RES_CUST => $document['customerid'],
            SYSLOG::RES_USER => Auth::GetCurrentUser()
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
    }
    $DB->CommitTrans();
}

$SESSION->redirect('?m=invoicelist');
