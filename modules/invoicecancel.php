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
    if (isset($_GET['recover'])) {
	$DB->BeginTrans();//Added if for lms-stck
	if (ConfigHelper::getConfig('phpui.stock')) {//Added if for lms-stck
		$stck_ica = $DB->GetAll('SELECT icitemid, stockid FROM stck_invoicecontentsassignments WHERE icdocid = ?', array($id));
		$sold = 0;
		if ($stck_ica) {
			foreach ($stck_ica as $pos) {
				$sold = $sold || $LMSST->StockSoldById($pos['stockid']);
			}
		}

		if ($sold) {
			$body = '<P>'.trans('Unable to recover invoice - stock already sold!').'</P>';
			$SMARTY->assign('body',$body);
			$SMARTY->display('dialog.html');
			exit;
		}
	}

	$DB->Execute('UPDATE documents SET cancelled = 0 WHERE id = ?', array($id));

        $invoice = $LMS->GetInvoiceContent($id);
	
	foreach ($invoice['content'] as $idx => $content) {
            if ($invoice['doctype'] == DOC_CNOTE) {
                $value = $content['total'] - $invoice['invoice']['content'][$idx]['total'];
            } else {
                $value = $content['total'];
            }
            $LMS->AddBalance(array(
                'time' => $invoice['cdate'],
                'value' => $value * -1,
                'taxid' => $content['taxid'],
                'customerid' => $invoice['customerid'],
                'comment' => $content['description'],
                'docid' => $id,
                'itemid' => $content['itemid'],
	    ));
	    if (ConfigHelper::getConfig('phpui.stock')) {//Added if for lms-stck
		    $icid = $DB->GetLastInsertID('cash');
		    $DB->Execute('INSERT INTO stck_cashassignments (cashid, stockid) VALUES(?, ?)', array($icid, $content['stockid']));
		    $LMSST->StockSell($id, $content['stockid'], ($content['value'] * 1/*$invoice['count']*/), time());
	    }
	}
	$DB->CommitTrans();//Added if for lms-stck
    } else {
        if ($LMS->isDocumentPublished($id) && !ConfigHelper::checkConfig('privileges.superuser')) {
            return;
        }

        if ($LMS->isDocumentReferenced($id)) {
            return;
        }

        if ($LMS->isArchiveDocument($id)) {
            return;
        }

        $hook_data = $LMS->executeHook('invoicecancel_before_cancel', array(
            'id' => $id,
        ));
        if (isset($hook_data['continue']) && empty($hook_data['continue'])) {
            return;
        }

	$DB->Execute('UPDATE documents SET cancelled = 1 WHERE id = ?', array($id));
	
	if (ConfigHelper::getConfig('phpui.stock')) {//Added if for lms-stck
		$stck_ica = $DB->GetAll('SELECT icitemid, stockid FROM stck_invoicecontentsassignments WHERE icdocid = ?', array($id));
		if ($stck_ica) {
			$doc = $DB->GetRow('SELECT d.number, d.cdate, n.template, d.extnumber, d.paytime, d.paytype
				FROM documents d
				LEFT JOIN numberplans n ON (d.numberplanid = n.id)
				WHERE d.id = ?', array($id));
			foreach ($stck_ica as $pos) {
				$LMSST->StockUnSell($pos['stockid'], trans('Canceled invoice (nr: $a)', docnumber($doc['number'], $doc['template'], $doc['cdate'], $doc['extnumber'])));
			}
		}
	}

        $DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
        $document = $DB->GetRow('SELECT * FROM documents WHERE id = ?', array($id));
    }
    if ($SYSLOG) {
        $args = array(
            SYSLOG::RES_DOC => $document['id'],
            SYSLOG::RES_CUST => $document['customerid'],
            SYSLOG::RES_USER => Auth::GetCurrentUser()
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
    }
}

$SESSION->redirect('?m=invoicelist');
