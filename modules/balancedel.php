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

$ids = array();
$docitems = array();
$invoices = array();
$receipts = array();
$notes = array();

if (!empty($_GET['id']) && intval($_GET['id'])) {
    $ids = array($_GET['id']);
} elseif (count($_POST['marks'])) {
    foreach ($_POST['marks'] as $markid => $mark) {
        switch ($markid) {
            case 'invoice':
                foreach ($mark as $docid => $items) {
                    $invoices[] = $docid;
                }
                break;
            case 'receipt':
                foreach ($mark as $docid => $items) {
                    $receipts[] = $docid;
                }
                break;
            case 'note':
                foreach ($mark as $docid => $items) {
                    $notes[] = $docid;
                }
                break;
            case 'proforma':
                foreach ($mark as $docid => $items) {
                    $docid = intval($docid);
                    if (!isset($docitems[$docid])) {
                        $docitems[$docid] = array();
                    }
                    foreach ($items as $item) {
                        $docitems[$docid][] = $item;
                    }
                }
                break;
            default:
                if ($mark) {
                    $ids[] = $markid;
                }
                break;
        }
    }
}

$hook_data = $LMS->executeHook('balancedel_before_delete', array(
    'ids' => $ids,
    'docitems' => $docitems,
    'invoices' => $invoices,
    'receipts' => $receipts,
    'notes' => $notes,
));
$ids = $hook_data['ids'];
$docitems = $hook_data['docitems'];
$invoices = $hook_data['invoices'];
$receipts = $hook_data['receipts'];
$notes = $hook_data['notes'];

$DB->BeginTrans();

sort($ids);

if (!empty($ids)) {
    $refdocs = array();

    foreach ($ids as $cashid) {
        $refdocs = array_merge($refdocs, $LMS->getDocumentReferences(null, $cashid));

        $LMS->DelBalance($cashid);
    }

    if (!empty($refdocs)) {
        foreach ($refdocs as $refdoc) {
            $LMS->DeleteDocument($refdoc['docid']);
        }
    }
}

if (!empty($docitems)) {
    foreach ($docitems as $docid => $items) {
        if (isset($_GET['documents'])) {
            $refdocs = $LMS->getDocumentReferences($docid);
        } else {
            $refdocs = null;
        }

        foreach ($items as $itemid) {
            $LMS->InvoiceContentDelete($docid, $itemid);
        }

        if (!empty($refdocs)) {
            foreach ($refdocs as $refdoc) {
                $LMS->DeleteDocument($refdoc['docid']);
            }
        }
    }
}

if (!empty($invoices)) {
    foreach ($invoices as $invoiceid) {
        if (isset($_GET['documents'])) {
            $refdocs = $LMS->getDocumentReferences($invoiceid);
        } else {
            $refdocs = null;
        }

        $LMS->InvoiceDelete($invoiceid);

        if (!empty($refdocs)) {
            foreach ($refdocs as $refdoc) {
                $LMS->DeleteDocument($refdoc['docid']);
            }
        }
    }
}

if (!empty($receipts)) {
    foreach ($receipts as $receiptid) {
        $LMS->ReceiptDelete($receiptid);
    }
}

if (!empty($notes)) {
    foreach ($notes as $noteid) {
        $LMS->DebitNoteDelete($noteid);
    }
}

$DB->CommitTrans();

$SESSION->redirect_to_history_entry();
