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

$layout['pagetitle'] = trans('Invoice send');

$SMARTY->display('header.html');

if (!isset($_GET['sent']) && isset($_SERVER['HTTP_REFERER']) && !preg_match('/m=invoicesend/', $_SERVER['HTTP_REFERER'])) {
	set_time_limit(0);

	echo '<H1>' . $layout['pagetitle'] . '</H1>';

	$invoice_filetype = ConfigHelper::getConfig('invoices.type', '', true);
	$dnote_filetype = ConfigHelper::getConfig('notes.type', '', true);

	$smtp_host = ConfigHelper::getConfig('sendinvoices.smtp_host');
	$smtp_port = ConfigHelper::getConfig('sendinvoices.smtp_port');
	$smtp_user = ConfigHelper::getConfig('sendinvoices.smtp_user');
	$smtp_pass = ConfigHelper::getConfig('sendinvoices.smtp_pass');
	$smtp_auth = ConfigHelper::getConfig('sendinvoices.smtp_auth');

	$debug_email = ConfigHelper::getConfig('sendinvoices.debug_email', '', true);
	$sender_name = ConfigHelper::getConfig('sendinvoices.sender_name', '', true);
	$sender_email = ConfigHelper::getConfig('sendinvoices.sender_email', '', true);
	$mail_subject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
	$mail_body = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));
	$invoice_filename = ConfigHelper::getConfig('sendinvoices.invoice_filename', 'invoice_%docid');
	$dnote_filename = ConfigHelper::getConfig('sendinvoices.debitnote_filename', 'dnote_%docid');
	$notify_email = ConfigHelper::getConfig('sendinvoices.notify_email', '', true);
	$reply_email = ConfigHelper::getConfig('sendinvoices.reply_email', '', true);
	$add_message = ConfigHelper::checkConfig('sendinvoices.add_message');
	$dsn_email = ConfigHelper::getConfig('sendinvoices.dsn_email', '', true);
	$mdn_email = ConfigHelper::getConfig('sendinvoices.mdn_email', '', true);

	if (empty($sender_email))
		echo '<span class="red">' . trans("Fatal error: sender_email unset! Can't continue, exiting.") . '</span><br>';

	$smtp_auth = empty($smtp_auth) ? ConfigHelper::getConfig('mail.smtp_auth_type') : $smtp_auth;
	if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $smtp_auth))
		echo '<span class="red">' . trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.") . '</span><br>';

	if (isset($_POST['marks']))
		if ($_GET['marks'] == 'invoice')
			$docids = array_map('intval', array_values($_POST['marks']));
		else
			$docids = $DB->GetCol("SELECT docid FROM cash c
				JOIN documents d ON d.id = c.docid
				WHERE d.type IN (?, ?, ?)
					AND c.id IN (" . implode(',', array_map('intval', array_values($_POST['marks']))) . ")",
				array(DOC_INVOICE, DOC_CNOTE, DOC_DNOTE));
	elseif (isset($_GET['id']) && intval($_GET['id']))
		$docids = array(intval($_GET['id']));

	if (empty($docids))
		echo '<span class="red">' . trans("Fatal error: No invoices nor debit notes were selected!") . '</span><br>';
	else {
		$docs = $DB->GetAll("SELECT d.id, d.number, d.cdate, d.name, d.customerid, d.type AS doctype, n.template, m.email
			FROM documents d
			LEFT JOIN customers c ON c.id = d.customerid
			JOIN (SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
				FROM customercontacts WHERE (type & ?) = ? GROUP BY customerid) m ON m.customerid = c.id
			LEFT JOIN numberplans n ON n.id = d.numberplanid
			WHERE d.type IN (?, ?, ?) AND d.id IN (" . implode(',', $docids) . ")
			ORDER BY d.number",
			array(CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_INVOICES,
				DOC_INVOICE, DOC_CNOTE, DOC_DNOTE));

		if (!empty($docs)) {
			$currtime = time();
			$LMS->SendInvoices($docs, 'frontend', compact('SMARTY', 'invoice_filetype', 'dnote_filetype',
				'invoice_filename', 'dnote_filename', 'debug_email',
				'mail_body', 'mail_subject', 'currtime', 'sender_email', 'sender_name', 'extrafile',
				'dsn_email', 'reply_email', 'mdn_email', 'notify_email', 'quiet', 'test', 'add_message',
				'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_auth'));
		}
	}

	echo '<script type="text/javascript">';
	echo "history.replaceState({}, '', location.href.replace(/&(is_sure|sent)=1/gi, '') + '&sent=1');";
	echo '</script>';
}

$SMARTY->display('footer.html');

?>
