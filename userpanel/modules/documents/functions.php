<?php

/*
 *  LMS version 1.11-git
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

function module_main()
{
    global $SESSION;

    $op = isset($_GET['op']) ? $_GET['op'] : '';

    $DB = LMSDB::getInstance();
    $LMS = LMS::getInstance();
    $SMARTY = LMSSmarty::getInstance();

    $sms_contacts = $LMS->GetCustomerContacts($SESSION->id, CONTACT_MOBILE);
    if (!empty($sms_contacts)) {
        foreach ($sms_contacts as $sms_contact) {
            if (($sms_contact['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS) {
                if (!isset($sms_recipients)) {
                    $sms_recipients = array();
                }
                $sms_recipients[] = $sms_contact['contact'];
            }
        }
    }

    $sms_options = $LMS->getCustomerSMSOptions();
    $sms_onetime_password_body = ConfigHelper::getConfig('userpanel.document_approval_customer_onetime_password_sms_body', '', true);
    $sms_active = !empty($sms_options) && isset($sms_options['service']) && !empty($sms_options['service'])
        && !empty($sms_onetime_password_body);
    if (!$sms_active) {
        $sms_service = ConfigHelper::getConfig('sms.service', '', true);
        $sms_active = !empty($sms_service);
    }
    $sms_active = $sms_active && isset($sms_recipients);
    $SMARTY->assign('sms_active', $sms_active);

    if (isset($_POST['documentid'])) {
        $documentid = intval($_POST['documentid']);
        if ($documentid && $DB->GetOne(
            'SELECT id FROM documents
            WHERE id = ? AND customerid = ? AND closed = 0 AND confirmdate > ?NOW?',
            array($documentid, $SESSION->id)
        )) {
            if (isset($_GET['smsauth'])) {
                if ($sms_active) {
                    if (isset($_GET['send'])) {
                        if (!isset($_SESSION['session_smsauthcode']) || time() - $_SESSION['session_smsauthcode_timestamp'] > 60) {
                            $_SESSION['session_smsauthcode'] = $sms_authcode = strval(rand(10000000, 99999999));
                            $_SESSION['session_smsauthcode_timestamp'] = time();
                            $sms_body = str_replace('%password%', $sms_authcode, $sms_onetime_password_body);
                            $error = array();
                            foreach ($sms_recipients as $sms_recipient) {
                                $res = $LMS->SendSMS($sms_recipient, $sms_body, null, $sms_options);
                                if (is_string($res)) {
                                    $error[] = $res;
                                }
                            }
                            if ($error) {
                                echo implode('<br>', $error);
                            }
                        } else {
                            if (isset($_SESSION['session_smsauthcode'])) {
                                echo trans('Your previous authorization code is still valid. Please wait a minute until it expires.');
                            } else {
                                unset($_SESSION['session_smsauthcode'], $_SESSION['session_smsauthcode_timestamp']);
                            }
                        }
                    } elseif (isset($_GET['check'])) {
                        if (isset($_SESSION['session_smsauthcode']) && time() - $_SESSION['session_smsauthcode_timestamp'] < 5 * 60) {
                            if ($_POST['code'] == $_SESSION['session_smsauthcode']) {
                                unset($_SESSION['session_smsauthcode'], $_SESSION['session_smsauthcode_timestamp']);

                                // commit customer document only if it's owned by this customer
                                // and is prepared for customer action
                                $LMS->CommitDocuments(array($documentid));
                            } else {
                                echo trans('Authorization code you entered is invalid!');
                            }
                        } else {
                            echo trans('Your authorization code has expired! Try again in a moment.');
                        }
                    }
                }
                die;
            } else {
                $files = array();
                $error = null;

                if (isset($_FILES['files'])) {
                    foreach ($_FILES['files']['name'] as $fileidx => $filename) {
                        if (!empty($filename)) {
                            if (is_uploaded_file($_FILES['files']['tmp_name'][$fileidx]) && $_FILES['files']['size'][$fileidx]) {
                                $files[] = array(
                                    'tmpname' => null,
                                    'filename' => $filename,
                                    'name' => $_FILES['files']['tmp_name'][$fileidx],
                                    'type' => $_FILES['files']['type'][$fileidx],
                                    'md5sum' => md5($_FILES['files']['tmp_name'][$fileidx]),
                                    'attachmenttype' => -1,
                                );
                            } else { // upload errors
                                if (isset($error['files'])) {
                                    $error['files'] .= "\n";
                                } else {
                                    $error['files'] = '';
                                }
                                switch ($_FILES['files']['error'][$fileidx]) {
                                    case 1:
                                    case 2:
                                        $error['files'] .= trans('File is too large: $a', $filename);
                                        break;
                                    case 3:
                                        $error['files'] .= trans('File upload has finished prematurely: $a', $filename);
                                        break;
                                    case 4:
                                        $error['files'] .= trans('Path to file was not specified: $a', $filename);
                                        break;
                                    default:
                                        $error['files'] .= trans('Problem during file upload: $a', $filename);
                                        break;
                                }
                            }
                        }
                    }
                    if (!$error) {
                        $error = $LMS->AddDocumentFileAttachments($files);
                        if (!$error) {
                            $attachmentids = $LMS->AddDocumentScans($documentid, $files);
                            if ($attachmentids) {
                                $mail_dsn = ConfigHelper::getConfig('userpanel.document_notification_mail_dsn_address', '', true);
                                $mail_mdn = ConfigHelper::getConfig('userpanel.document_notification_mail_mdn_address', '', true);
                                $mail_sender_name = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_name', '', true);
                                $mail_sender_address = ConfigHelper::getConfig('userpanel.document_notification_mail_sender_address', ConfigHelper::getConfig('mail.smtp_username'));
                                $mail_reply_address = ConfigHelper::getConfig('userpanel.document_notification_mail_reply_address', '', true);
                                $mail_recipient = ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_recipient');
                                $mail_format = ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_format', 'text');
                                $mail_subject = ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_subject');
                                $mail_body = ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_body');

                                if (!empty($mail_sender_address)) {
                                    $customerinfo = $LMS->GetCustomer($SESSION->id);

                                    if (!empty($mail_recipient) && !empty($mail_subject) && !empty($mail_body)) {
                                        // operator notification
                                        $mail_subject = $LMS->customerNotificationReplaceSymbols(
                                            $mail_subject,
                                            array(
                                                'customerinfo' => $customerinfo,
                                                'document' => array(
                                                    'id' => $documentid,
                                                    'attachmentids' => $attachmentids,
                                                ),
                                            )
                                        );
                                        $mail_body = $LMS->customerNotificationReplaceSymbols(
                                            $mail_body,
                                            array(
                                                'customerinfo' => $customerinfo,
                                                'document' => array(
                                                    'id' => $documentid,
                                                    'attachmentids' => $attachmentids,
                                                ),
                                            )
                                        );
                                        $LMS->SendMail($mail_recipient, array(
                                            'From' => ($mail_sender_name ? '"' . $mail_sender_name . '" ' : '') . '<' . $mail_sender_address . '>',
                                            'To' => $mail_recipient,
                                            'Subject' => $mail_subject,
                                            'X-LMS-Format' => $mail_format,
                                        ), $mail_body);
                                    }

                                    $mail_format = ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_format', 'text');
                                    $mail_subject = ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_subject');
                                    $mail_body = ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_body');
                                    if (!empty($mail_recipient) && !empty($mail_subject) && !empty($mail_body)) {
                                        // customer notification
                                        $mail_subject = $LMS->customerNotificationReplaceSymbols(
                                            $mail_subject,
                                            array(
                                                'customerinfo' => $customerinfo,
                                                'document' => array(
                                                    'id' => $documentid,
                                                    'attachmentids' => $attachmentids,
                                                ),
                                            )
                                        );
                                        $mail_body = $LMS->customerNotificationReplaceSymbols(
                                            $mail_body,
                                            array(
                                                'customerinfo' => $customerinfo,
                                                'document' => array(
                                                    'id' => $documentid,
                                                    'attachmentids' => $attachmentids,
                                                ),
                                            )
                                        );
                                        $mail_recipients = $LMS->GetCustomerContacts($SESSION->id, CONTACT_EMAIL);
                                        if (!empty($mail_recipients)) {
                                            $destinations = array();
                                            foreach ($mail_recipients as $mail_recipient) {
                                                if (($mail_recipient['type'] & (CONTACT_NOTIFICATIONS | CONTACT_DISABLED)) == CONTACT_NOTIFICATIONS) {
                                                    $destinations[] = $mail_recipient['contact'];
                                                }
                                            }
                                            if (!empty($destinations)) {
                                                $recipients = array(
                                                    array(
                                                        'id' => $SESSION->id,
                                                        'email' => implode(',', $destinations),
                                                    )
                                                );
                                                $sender = ($mail_sender_name ? '"' . $mail_sender_name . '" ' : '') . '<' . $mail_sender_address . '>';
                                                $message = $LMS->addMessage(array(
                                                    'type' => MSG_MAIL,
                                                    'subject' => $mail_subject,
                                                    'body' => $mail_body,
                                                    'sender' => array(
                                                        'name' => $mail_sender_name,
                                                        'mail' => $mail_sender_address,
                                                    ),
                                                    'contenttype' => $mail_format == 'text' ? 'text/plain' : 'text/html',
                                                    'recipients' => $recipients,
                                                ));
                                                $headers = array(
                                                    'From' => $sender,
                                                    'Recipient-Name' => $customerinfo['customername'],
                                                    'Subject' => $mail_subject,
                                                    'X-LMS-Format' => $mail_format,
                                                );
                                                if (!empty($mail_reply_address) && $mail_reply_address != $mail_sender_address) {
                                                    $headers['Reply-To'] = $mail_reply_address;
                                                }
                                                if (!empty($mail_mdn)) {
                                                    $headers['Return-Receipt-To'] = $mail_mdn;
                                                    $headers['Disposition-Notification-To'] = $mail_mdn;
                                                }
                                                if (!empty($mail_dsn)) {
                                                    $headers['Delivery-Status-Notification-To'] = true;
                                                }
                                                foreach ($destinations as $destination) {
                                                    if (!empty($mail_dsn) || !empty($mail_mdn)) {
                                                        $headers['X-LMS-Message-Item-Id'] = $message['items'][$SESSION->id][$destination];
                                                        $headers['Message-ID'] = '<messageitem-' . $message['items'][$SESSION->id][$destination] . '@rtsystem.' . gethostname() . '>';
                                                    }
                                                    $LMS->SendMail($destination, $headers, $mail_body);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $SMARTY->assign('error', $error);
                        }
                    } else {
                        $SMARTY->assign('error', $error);
                    }
                }
                $op = '';
            }
        }
    } else {
        $documentid = 0;
    }

    $documents = $DB->GetAll('SELECT d.id, d.number, d.type, c.title, c.fromdate, c.todate, 
		    c.description, n.template, d.closed, d.cdate, d.confirmdate
		FROM documentcontents c
		JOIN documents d ON (c.docid = d.id)
		LEFT JOIN numberplans n ON (d.numberplanid = n.id)
		WHERE d.customerid = ?'
            . (ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only')
                ? ' AND (d.closed > 0 OR d.confirmdate >= ?NOW? OR d.confirmdate = -1)': '')
            . (ConfigHelper::checkConfig('userpanel.hide_archived_documents') ? ' AND d.archived = 0': '')
            . ' ORDER BY cdate', array($SESSION->id));

    if (!empty($documents)) {
        foreach ($documents as &$doc) {
            $doc['attachments'] = $DB->GetAllBykey('SELECT * FROM documentattachments WHERE docid = ?
				ORDER BY type DESC, filename', 'id', array($doc['id']));
        }
    }

    $unit_multipliers = array(
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
        'T' => 1024 * 1024 * 1024 * 1024,
    );
    foreach (array('post_max_size', 'upload_max_filesize') as $var) {
        preg_match('/^(?<number>[0-9]+)(?<unit>[kKmMgGtT]?)$/', ini_get($var), $m);
        $unit_multiplier = isset($m['unit']) ? $unit_multipliers[strtoupper($m['unit'])] : 1;
        if ($var == 'post_max_size') {
            $unit_multiplier *= 1/1.33;
        }
        if (empty($m['number'])) {
            $val['bytes'] = 0;
            $val['text'] = trans('(unlimited)');
        } else {
            $val['bytes'] = round($m['number'] * $unit_multiplier);
            $res = setunits($val['bytes']);
            $val['text'] = round($res[0]) . ' ' . $res[1];
        }
        $SMARTY->assign($var, $val);
    }

    $SMARTY->assign('documents', $documents);
    $SMARTY->assign('documentid', $documentid);
    $SMARTY->assign('op', $op);
    $SMARTY->display('module:documents.html');
}

function module_docview()
{
    include 'docview.php';
}

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        $SMARTY = LMSSmarty::getInstance();

        $SMARTY->assign(
            'moduleconfig',
            array(
                'hide_documentbox' => ConfigHelper::getConfig('userpanel.hide_documentbox'),
                'show_confirmed_documents_only' => ConfigHelper::checkConfig('userpanel.show_confirmed_documents_only'),
                'hide_archived_documents' => ConfigHelper::checkConfig('userpanel.hide_archived_documents'),
                'document_notification_mail_dsn_address' =>
                    ConfigHelper::getConfig('userpanel.document_notification_mail_dsn_address', '', true),
                'document_notification_mail_mdn_address' =>
                    ConfigHelper::getConfig('userpanel.document_notification_mail_mdn_address', '', true),
                'document_notification_mail_sender_name' =>
                    ConfigHelper::getConfig('userpanel.document_notification_mail_sender_name', '', true),
                'document_notification_mail_sender_address' =>
                    ConfigHelper::getConfig('userpanel.document_notification_mail_sender_address', '', true),
                'document_notification_mail_reply_address' =>
                    ConfigHelper::getConfig('userpanel.document_notification_mail_reply_address', '', true),
                'signed_document_scan_operator_notification_mail_recipient' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_recipient', '', true),
                'signed_document_scan_operator_notification_mail_format' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_format', 'text'),
                'signed_document_scan_operator_notification_mail_subject' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_subject', '', true),
                'signed_document_scan_operator_notification_mail_body' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_operator_notification_mail_body', '', true),
                'signed_document_scan_customer_notification_mail_format' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_format', 'text'),
                'signed_document_scan_customer_notification_mail_subject' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_subject', '', true),
                'signed_document_scan_customer_notification_mail_body' =>
                    ConfigHelper::getConfig('userpanel.signed_document_scan_customer_notification_mail_body', '', true),
                'document_approval_customer_notification_mail_format' =>
                    ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_format', 'text'),
                'document_approval_customer_notification_mail_subject' =>
                    ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_subject', '', true),
                'document_approval_customer_notification_mail_body' =>
                    ConfigHelper::getConfig('userpanel.document_approval_customer_notification_mail_body', '', true),
                'document_approval_customer_onetime_password_sms_body' =>
                    ConfigHelper::getConfig('userpanel.document_approval_customer_onetime_password_sms_body', '', true),
            )
        );

        $SMARTY->display('module:documents:setup.html');
    }

    function module_submit_setup()
    {
        if (!isset($_POST['moduleconfig'])) {
            die;
        }

        $DB = LMSDB::getInstance();

        $variables = array(
            'hide_documentbox' => CONFIG_TYPE_BOOLEAN,
            'show_confirmed_documents_only' => CONFIG_TYPE_BOOLEAN,
            'hide_archived_documents' => CONFIG_TYPE_BOOLEAN,
            'document_notification_mail_dsn_address' => CONFIG_TYPE_RICHTEXT,
            'document_notification_mail_mdn_address' => CONFIG_TYPE_RICHTEXT,
            'document_notification_mail_sender_name' => CONFIG_TYPE_RICHTEXT,
            'document_notification_mail_sender_address' => CONFIG_TYPE_RICHTEXT,
            'document_notification_mail_reply_address' => CONFIG_TYPE_RICHTEXT,
            'signed_document_scan_operator_notification_mail_recipient' => CONFIG_TYPE_RICHTEXT,
            'signed_document_scan_operator_notification_mail_format' => CONFIG_TYPE_NONE,
            'signed_document_scan_operator_notification_mail_subject' => CONFIG_TYPE_RICHTEXT,
            'signed_document_scan_operator_notification_mail_body' => CONFIG_TYPE_RICHTEXT,
            'signed_document_scan_customer_notification_mail_format' => CONFIG_TYPE_NONE,
            'signed_document_scan_customer_notification_mail_subject' => CONFIG_TYPE_RICHTEXT,
            'signed_document_scan_customer_notification_mail_body' => CONFIG_TYPE_RICHTEXT,
            'document_approval_customer_notification_mail_format' => CONFIG_TYPE_NONE,
            'document_approval_customer_notification_mail_subject' => CONFIG_TYPE_RICHTEXT,
            'document_approval_customer_notification_mail_body' => CONFIG_TYPE_RICHTEXT,
            'document_approval_customer_onetime_password_sms_body' => CONFIG_TYPE_RICHTEXT,
        );

        $moduleconfig = $_POST['moduleconfig'];

        foreach ($variables as $variable => $type) {
            switch ($type) {
                case CONFIG_TYPE_BOOLEAN:
                    $value = isset($moduleconfig[$variable]) ? 1 : 0;
                    break;
                case CONFIG_TYPE_RICHTEXT:
                    $value = $moduleconfig[$variable];
                    break;
                case CONFIG_TYPE_NONE:
                    $mail_format = str_replace('_mail_format', '_mail_body', $variable);
                    if (isset($moduleconfig['wysiwyg'][$mail_format]) && $moduleconfig['wysiwyg'][$mail_format] == 'true') {
                        $value = 'html';
                    } else {
                        $value = 'text';
                    }
                    break;
            }
            $DB->Execute(
                'UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
                array($value, 'userpanel', $variable,)
            );
        }

        header('Location: ?m=userpanel&module=documents');
    }
}
