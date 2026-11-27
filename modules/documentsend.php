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

if (isset($_GET['action']) && $_GET['action'] == 'preview' && isset($_GET['api'])) {
    header('Content-Type: application/json');

    $documentSendCustomerId = intval($_POST['customerid'] ?? 0);
    $documentSendIds = isset($_POST['marks']) ? Utils::filterIntegers(array_values($_POST['marks'])) : array();

    if (empty($documentSendIds)) {
        die(json_encode(array(
            'error' => trans("Fatal error: No documents were selected!"),
        )));
    }

    $userId = Auth::GetCurrentUser();

    $docs = $DB->GetAll(
        'SELECT
            d.id,
            d.number,
            d.cdate,
            d.type,
            d.customerid,
            d.name,
            d.divisionid,
            c.pin,
            n.template,
            dc.title AS content_title
        FROM documents d
        JOIN documentcontents dc ON dc.docid = d.id
        JOIN customers c ON c.id = d.customerid
        JOIN docrights r ON r.doctype = d.type AND r.userid = ' . $userId . '
        LEFT JOIN numberplans n ON n.id = d.numberplanid
        WHERE d.id IN ?'
            . ($documentSendCustomerId ? ' AND d.customerid = ' . $documentSendCustomerId : '') . '
            AND d.cancelled = 0
            AND (r.rights & ?) > 0
        ORDER BY d.id',
        array(
            $documentSendIds,
            DOCRIGHT_VIEW,
        )
    );

    if (empty($docs)) {
        die(json_encode(array(
            'error' => trans("Fatal error: No documents were selected!"),
        )));
    }

    $customerIds = array_unique(array_map('intval', array_column($docs, 'customerid')));
    $mixedCustomers = count($customerIds) > 1;
    if (!$mixedCustomers) {
        $documentSendCustomerId = reset($customerIds);
    }

    $documentSendContacts = array();
    if (!$mixedCustomers && $documentSendCustomerId) {
        foreach ($LMS->GetCustomerContacts($documentSendCustomerId, CONTACT_EMAIL) as $contact) {
            $contactDisabled = ($contact['type'] & CONTACT_DISABLED) == CONTACT_DISABLED;
            $contactDocuments = ($contact['type'] & CONTACT_DOCUMENTS) == CONTACT_DOCUMENTS;
            $documentSendContacts[] = array(
                'contact' => $contact['contact'],
                'fullname' => $contact['fullname'],
                'disabled' => $contactDisabled,
                'checked' => $contactDocuments && !$contactDisabled,
            );
        }
    }

    $doc = reset($docs);

    ConfigHelper::setFilter($doc['divisionid'], $userId);

    $docTypeAlias = $DOCTYPE_ALIASES[$doc['type']];
    $subject = ConfigHelper::getConfig('documents-' . $docTypeAlias . '.mail_subject', ConfigHelper::getConfig('documents.mail_subject', '%document'));
    $body = ConfigHelper::getConfig('documents-' . $docTypeAlias . '.mail_body', ConfigHelper::getConfig('documents.mail_body', '%document'));
    $mail_format = ConfigHelper::getConfig('documents.mail_format', 'text');

    $mixedTemplates = false;
    foreach ($docs as $docItem) {
        if ($docItem['id'] == $doc['id']) {
            continue;
        }
        ConfigHelper::setFilter($docItem['divisionid'], $userId);
        $itemTypeAlias = $DOCTYPE_ALIASES[$docItem['type']];
        $itemSubject = ConfigHelper::getConfig('documents-' . $itemTypeAlias . '.mail_subject', ConfigHelper::getConfig('documents.mail_subject', '%document'));
        $itemBody = ConfigHelper::getConfig('documents-' . $itemTypeAlias . '.mail_body', ConfigHelper::getConfig('documents.mail_body', '%document'));
        $itemFormat = ConfigHelper::getConfig('documents.mail_format', 'text');
        if ($itemSubject !== $subject || $itemBody !== $body || $itemFormat !== $mail_format) {
            $mixedTemplates = true;
            break;
        }
    }
    ConfigHelper::setFilter($doc['divisionid'], $userId);

    if (count($docs) == 1) {
        $doc['fullnumber'] = docnumber(array(
            'number' => $doc['number'],
            'template' => $doc['template'],
            'cdate' => $doc['cdate'],
            'customerid' => $doc['customerid'],
        ));

        $user = $LMS->GetUserInfo($userId, false);

        $mailPlaceholders = $LMS->substituteDocumentMailPlaceholders(
            $subject,
            $body,
            $doc,
            array(
                'currtime' => time(),
                'username' => $user['name'],
                'rusername' => $user['rname'],
            )
        );
        $subject = $mailPlaceholders['subject'];
        $body = $mailPlaceholders['body'];
    }

    die(json_encode(array(
        'subject' => $subject,
        'body' => $body,
        'format' => $mail_format,
        'mixedTemplates' => $mixedTemplates,
        'mixedCustomers' => $mixedCustomers,
        'customerid' => $mixedCustomers ? 0 : $documentSendCustomerId,
        'contacts' => $documentSendContacts,
    )));
}

$SMARTY->display('header.html');

if (!isset($_GET['sent']) && isset($_SERVER['HTTP_REFERER']) && !preg_match('/m=documentsend/', $_SERVER['HTTP_REFERER'])) {
    set_time_limit(0);

    echo '<H1>' . $layout['pagetitle'] . '</H1>';

    $mailSubject = $mailBody = null;

    if (isset($_POST['documentsend']) && is_array($_POST['documentsend'])) {
        $documentSend = $_POST['documentsend'];
        $documentSendCustomerId = intval($documentSend['customerid'] ?? 0);

        if (!empty($documentSendCustomerId)) {
            $documentSendEmails = empty($documentSend['recipients']) ? array() : (array) $documentSend['recipients'];
            if (!empty($documentSend['extraemail'])) {
                $extraEmail = is_string($documentSend['extraemail']) ? trim($documentSend['extraemail']) : '';
                if (check_email($extraEmail)) {
                    $documentSendEmails[] = $extraEmail;
                } else {
                    $documentSendInvalidExtraEmail = true;
                }
            }
            $documentSendRecipients = array();
            foreach ($documentSendEmails as $email) {
                $email = is_string($email) ? trim($email) : '';
                if (check_email($email)) {
                    $documentSendRecipients[$email] = $email;
                }
            }
        }

        if (isset($documentSend['commontext']) && ConfigHelper::checkValue($documentSend['commontext'])) {
            if (isset($documentSend['subject']) && is_string($documentSend['subject']) && strlen(trim($documentSend['subject']))) {
                $mailSubject = trim($documentSend['subject']);
            }
            if (isset($documentSend['body']) && is_string($documentSend['body'])) {
                $mailBody = str_replace("\r\n", "\n", $documentSend['body']);
            }
            if (isset($documentSend['wysiwyg']['body'])) {
                $mailFormat = ConfigHelper::checkValue($documentSend['wysiwyg']['body']) ? 'html' : 'text';
            }
        }
    }

    if (isset($_POST['marks'])) {
        $docids = $DB->GetCol(
            "SELECT id
            FROM documents
            WHERE id IN (" . implode(',', Utils::filterIntegers(array_values($_POST['marks']))) . ")"
        );
    } elseif (isset($_GET['id']) && intval($_GET['id'])) {
        $docids = array(intval($_GET['id']));
    }

    if (empty($docids)) {
        echo '<span class="red">' . trans("Fatal error: No documents were selected!") . '</span><br>';
    } elseif (!empty($documentSendInvalidExtraEmail)) {
        echo '<span class="red">' . trans('Unable to send message. Invalid e-mail address: $a', htmlspecialchars($extraEmail)) . '</span><br>';
    } elseif (isset($documentSendRecipients) && (empty($documentSendRecipients) || empty($documentSendCustomerId))) {
        echo '<span class="red">' . trans("Unable to send message. No recipients selected!") . '</span><br>';
    } else {
        $docs = $DB->GetAll(
            "SELECT
                d.id,
                d.type,
                d.customerid,
                d.name,
                m.email,
                p.phone
            FROM documents d
            " . (isset($documentSendRecipients) ? 'LEFT ' : '') . "JOIN (
                SELECT
                    customerid, "
                    . $DB->GroupConcat('contact') . " AS email
                FROM customercontacts
                WHERE (type & ?) = ?
                GROUP BY customerid
            ) m ON m.customerid = d.customerid
            LEFT JOIN (
                SELECT
                    customerid, "
                     . $DB->GroupConcat('contact') . " AS phone
                FROM customercontacts
                WHERE (type & ?) = ?
                GROUP BY customerid
            ) p ON p.customerid = d.customerid
            WHERE d.id IN ?
                AND d.cancelled = 0"
                . (isset($documentSendRecipients) ? ' AND d.customerid = ' . $documentSendCustomerId : '') . "
            ORDER BY d.id",
            array(
                CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                CONTACT_EMAIL | CONTACT_DOCUMENTS,
                CONTACT_MOBILE | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                CONTACT_MOBILE | CONTACT_DOCUMENTS,
                $docids,
            )
        );

        if (!empty($docs) && isset($documentSendRecipients)) {
            foreach ($docs as &$doc) {
                $doc['email'] = implode(',', $documentSendRecipients);
            }
            unset($doc);
        }

        if (!empty($docs)) {
            $currtime = time();
            if (!isset($quiet)) {
                $quiet = false;
            }
            if (!isset($test)) {
                $test = false;
            }

            $attachments = [];
            if (!empty($_POST['attachment-marks'])) {
                foreach ($_POST['attachment-marks'] as $documentId => $documentAttachments) {
                    $attachments = array_merge($attachments, array_keys($documentAttachments));
                }
                $attachments = Utils::filterIntegers($attachments);
            }

            $sendParams = compact(
                'currtime',
                'quiet',
                'test',
                'attachments',
                'mailSubject',
                'mailBody'
            );
            if (isset($mailFormat)) {
                $sendParams['mailFormat'] = $mailFormat;
            }
            $LMS->SendDocuments(
                $docs,
                'frontend',
                $sendParams
            );
        }
    }

    echo '<script type="text/javascript">';
    echo "history.replaceState({}, '', location.href.replace(/&(is_sure|sent)=1/gi, '') + '&sent=1');";
    echo '</script>';
}

$SMARTY->display('footer.html');
