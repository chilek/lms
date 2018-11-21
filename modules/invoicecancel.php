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
 *  $Id$
 */

$id = intval($_GET['id']);

if($id && $_GET['is_sure'] == '1') {
	if (isset($_GET['recover'])) {
		$DB->Execute('UPDATE documents SET cancelled = 0 WHERE id = ?', array($id));

		$invoice = $LMS->GetInvoiceContent($id);

		foreach ($invoice['content'] as $idx => $content) {
			if ($invoice['doctype'] == DOC_CNOTE)
				$value = $content['total'] - $invoice['invoice']['content'][$idx]['total'];
			else
				$value = $content['total'];
			$LMS->AddBalance(array(
				'time' => $invoice['cdate'],
				'value' => $value * -1,
				'taxid' => $content['taxid'],
				'customerid' => $invoice['customerid'],
				'comment' => $content['description'],
				'docid' => $id,
				'itemid' => $content['itemid'],
			));
		}
		if ($SYSLOG) {
			$args = array(
				SYSLOG::RES_DOC => $document['id'],
				SYSLOG::RES_CUST => $document['customerid'],
				SYSLOG::RES_USER => Auth::GetCurrentUser()
			);
			$SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
		}
	} else {
		if ($LMS->isDocumentPublished($id) && !ConfigHelper::checkConfig('privileges.superuser'))
			return;
		$DB->Execute('UPDATE documents SET cancelled = 1 WHERE id = ?', array($id));
		$DB->Execute('DELETE FROM cash WHERE docid = ?', array($id));
		$document = $DB->GetRow('SELECT * FROM documents WHERE id = ?', array($id));
		if ($SYSLOG) {
			$args = array(
				SYSLOG::RES_DOC => $document['id'],
				SYSLOG::RES_CUST => $document['customerid'],
				SYSLOG::RES_USER => Auth::GetCurrentUser()
			);
			$SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
		}
	}
}

$SESSION->redirect('?m=invoicelist');

?>
