<?php

/**
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

$layout['pagetitle'] = trans('Document send');

$SMARTY->display('header.html');

if (!isset($_GET['sent']) && isset($_SERVER['HTTP_REFERER']) && !preg_match('/m=documentsend/', $_SERVER['HTTP_REFERER'])) {
	set_time_limit(0);

	echo '<H1>' . $layout['pagetitle'] . '</H1>';

	$smtp_options = array(
		'host' => ConfigHelper::getConfig('documents.smtp_host'),
		'port' => ConfigHelper::getConfig('documents.smtp_port'),
		'user' => ConfigHelper::getConfig('documents.smtp_user'),
		'pass' => ConfigHelper::getConfig('documents.smtp_pass'),
		'auth' => ConfigHelper::getConfig('documents.smtp_auth'),
		'ssl_verify_peer' => ConfigHelper::checkValue(ConfigHelper::getConfig('documents.smtp_ssl_verify_peer', true)),
		'ssl_verify_peer_name' => ConfigHelper::checkValue(ConfigHelper::getConfig('documents.smtp_ssl_verify_peer_name', true)),
		'ssl_allow_self_signed' => ConfigHelper::checkConfig('documents.smtp_ssl_allow_self_signed'),
	);

	$debug_email = ConfigHelper::getConfig('documents.debug_email', '', true);
	$sender_name = ConfigHelper::getConfig('documents.sender_name', '', true);
	$sender_email = ConfigHelper::getConfig('documents.sender_email', '', true);
	$mail_subject = ConfigHelper::getConfig('documents.mail_subject', '%document');
	$mail_body = ConfigHelper::getConfig('documents.mail_body', '%document');
	$mail_format = ConfigHelper::getConfig('documents.mail_format', 'text');
	$notify_email = ConfigHelper::getConfig('documents.notify_email', '', true);
	$reply_email = ConfigHelper::getConfig('documents.reply_email', '', true);
	$add_message = ConfigHelper::checkConfig('documents.add_message');
	$dsn_email = ConfigHelper::getConfig('documents.dsn_email', '', true);
	$mdn_email = ConfigHelper::getConfig('documents.mdn_email', '', true);

	if (empty($sender_email))
		echo '<span class="red">' . trans("Fatal error: sender_email unset! Can't continue, exiting.") . '</span><br>';

	$smtp_auth = empty($smtp_auth) ? ConfigHelper::getConfig('mail.smtp_auth_type') : $smtp_auth;
	if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $smtp_auth))
		echo '<span class="red">' . trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.") . '</span><br>';

	if (isset($_POST['marks']))
		$docids = $DB->GetCol("SELECT id FROM documents
			WHERE id IN (" . implode(',', Utils::filterIntegers(array_values($_POST['marks']))) . ")");
	elseif (isset($_GET['id']) && intval($_GET['id']))
		$docids = array(intval($_GET['id']));

	if (empty($docids))
		echo '<span class="red">' . trans("Fatal error: No documents were selected!") . '</span><br>';
	else {
		$docs = $DB->GetAll("SELECT d.id, d.customerid, d.name, m.email
			FROM documents d
			JOIN (SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
				FROM customercontacts WHERE (type & ?) = ? GROUP BY customerid) m ON m.customerid = d.customerid
			WHERE d.id IN (" . implode(',', $docids) . ")
			ORDER BY d.id",
			array(CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED, CONTACT_EMAIL | CONTACT_DOCUMENTS));

		if (!empty($docs)) {
			$currtime = time();
			$LMS->SendDocuments($docs, 'frontend', compact(
				'debug_email', 'mail_body', 'mail_subject', 'mail_format', 'currtime', 'sender_email',
				'sender_name', 'extrafile', 'dsn_email', 'reply_email', 'mdn_email', 'notify_email',
				'quiet', 'test', 'add_message', 'smtp_options'));
		}
	}

	echo '<script type="text/javascript">';
	echo "history.replaceState({}, '', location.href.replace(/&(is_sure|sent)=1/gi, '') + '&sent=1');";
	echo '</script>';
}

$SMARTY->display('footer.html');

?>
