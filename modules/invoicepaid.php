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

$SESSION->restore('ilm', $ilm);
$SESSION->remove('ilm');

if(sizeof($_POST['marks']))
	foreach($_POST['marks'] as $id => $mark)
		$ilm[$id] = $mark;

if(sizeof($ilm))
	foreach($ilm as $mark)
		$ids[] = $mark;

if(sizeof($ids))
{
	foreach($ids as $invoiceid)
	{
		list ($cid, $closed) = array_values($DB->GetRow('SELECT customerid, closed FROM documents
			WHERE id = ?', array($invoiceid)));
		// add payment
		if (ConfigHelper::checkConfig('phpui.invoice_check_payment') && $cid && !$closed) {
			$value = $DB->GetOne('SELECT CASE reference WHEN 0 THEN SUM(a.value*a.count)
				ELSE SUM((a.value+b.value)*(a.count+b.count)) - SUM(b.value*b.count) END
				FROM documents d
				JOIN invoicecontents a ON (a.docid = d.id)
				LEFT JOIN invoicecontents b ON (d.reference = b.docid AND a.itemid = b.itemid)
				WHERE d.id = ? GROUP BY d.reference', array($invoiceid));

			if ($value != 0)
				$LMS->AddBalance(array(
					'type' => 1,
					'time' => time(),
					'value' => $value,
					'customerid' => $cid,
					'comment' => trans('Accounted'),
				));
		}

		if ($SYSLOG) {
			$args = array(
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC] => $invoiceid,
				$SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST] => $cid,
				'closed' => intval(!$closed),
			);
			$SYSLOG->AddMessage(SYSLOG_RES_DOC, SYSLOG_OPER_UPDATE, $args,
				array($SYSLOG_RESOURCE_KEYS[SYSLOG_RES_DOC], $SYSLOG_RESOURCE_KEYS[SYSLOG_RES_CUST]));
		}
		$DB->Execute('UPDATE documents SET closed = 
			(CASE closed WHEN 0 THEN 1 ELSE 0 END)
			WHERE id = ?', array($invoiceid));
	}
}

$SESSION->redirect('?'.$SESSION->get('backto'));

?>
