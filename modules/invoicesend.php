<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

use \Lms\KSeF\KSeF;

$layout['pagetitle'] = trans('Invoice send');

if (isset($_POST['marks'])) {
    if (!empty($_POST['marks']['invoice']) || !empty($_POST['marks']['note'])) {
        $marks = [];
        if (!empty($_POST['marks']['invoice'])) {
            $marks = array_merge($marks, $_POST['marks']['invoice']);
        }
        if (!empty($_POST['marks']['note'])) {
            $marks = array_merge($marks, $_POST['marks']['note']);
        }
    } else {
        if ($_GET['marks'] == 'invoice' || $_GET['marks'] == 'note' || $_GET['marks'] == 'cash') {
            $marks = $_POST['marks'];
        }
    }

    $ids = Utils::filterIntegers($marks);

    if ($_GET['marks'] == 'cash' && !empty($ids)) {
        $ids = $LMS->GetDocumentsForBalanceRecords($ids, array(DOC_INVOICE, DOC_CNOTE, DOC_INVOICE_PRO, DOC_DNOTE));
    }
} elseif (isset($_GET['id']) && intval($_GET['id'])) {
    $ids = array(intval($_GET['id']));
} else {
    $ids = array();
}

$invoiceSendPreview = isset($_GET['action']) && $_GET['action'] == 'preview' && (isset($_GET['api']) || isset($_GET['ajax']));

if ($invoiceSendPreview) {
    header('Content-Type: application/json');

    $invoiceSendCustomerId = intval($_POST['customerid'] ?? $_GET['customerid'] ?? 0);

    if (empty($ids) || empty($invoiceSendCustomerId)) {
        die(json_encode(array(
            'error' => trans("Fatal error: No invoices nor debit notes were selected!"),
        )));
    }

    $docs = $DB->GetAll(
        "SELECT
            d.id,
            d.number,
            d.cdate,
            d.name,
            d.customerid,
            d.type AS doctype,
            d.archived,
            n.template,
            d.divisionid,
            d.div_name,
            d.div_shortname,
            d.div_account,
            (CASE WHEN EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = d.id AND d2.type < 0) THEN 1 ELSE 0 END) AS documentreferenced,
            kd.ksefnumber,
            kd.status AS ksefstatus,
            kd.hash AS ksefhash,
            kbs.environment AS ksefenvironment,
            d.div_ten AS kseften
        FROM documents d
        LEFT JOIN customers c ON c.id = d.customerid
        LEFT JOIN ksefdocuments kd ON kd.docid = d.id AND kd.status IN ?
        LEFT JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
        LEFT JOIN ksefconfig kc ON kc.divisionid = d.divisionid
        LEFT JOIN numberplans n ON n.id = d.numberplanid
        WHERE (
                d.type IN ?
                AND (
                    d.cdate < kc.boundarydate
                    OR kc.boundarydate IS NULL
                    OR c.type = ? AND (COALESCE(kc.allconsumers, 0) = ? AND NOT EXISTS (SELECT 1 FROM customerconsents cc WHERE cc.customerid = d.customerid AND cc.type = ?) OR kd.status IS NOT NULL)
                    OR c.type = ? AND kd.status IS NOT NULL
                ) OR d.type IN ?
            ) AND d.id IN (" . implode(',', $ids) . ")
            AND d.customerid = ?
        ORDER BY d.cdate, d.id",
        [
            [
                0,
                200,
            ],
            [
                DOC_INVOICE,
                DOC_CNOTE,
            ],
            CTYPES_PRIVATE,
            0,
            CCONSENT_KSEF_INVOICE,
            CTYPES_COMPANY,
            [
                DOC_DNOTE,
                DOC_INVOICE_PRO,
            ],
            $invoiceSendCustomerId,
        ]
    );

    if (empty($docs)) {
        die(json_encode(array(
            'error' => trans("Fatal error: No invoices nor debit notes were selected!"),
        )));
    }

    $doc = $docs[count($docs) - 1];

    ConfigHelper::setFilter($doc['divisionid']);

    $mail_subject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
    $mail_body = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));
    $mail_format = ConfigHelper::getConfig('sendinvoices.mail_format', 'text');
    $aggregate_documents = ConfigHelper::checkConfig('sendinvoices.aggregate_documents');
    $financial_history_reverse_order = ConfigHelper::checkConfig(
        'sendinvoices.financial_history_reverse_order',
        ConfigHelper::checkConfig(
            'finances.history_reverse_order',
            true
        )
    );
    $financial_history_item_description_format = ConfigHelper::getConfig(
        'sendinvoices.financial_history_item_description_format',
        ConfigHelper::getConfig(
            'finances.history_item_description_format',
            '%comment'
        )
    );

    if (!empty($mail_body) && $mail_body[0] == DIRECTORY_SEPARATOR && is_readable($mail_body)) {
        $mail_body = file_get_contents($mail_body);
    }

    if ($doc['doctype'] == DOC_DNOTE) {
        $document = $LMS->GetNoteContent($doc['id']);
    } else {
        $document = $LMS->GetInvoiceContent($doc['id']);
    }
    if (empty($document) && !empty($doc['archived'])) {
        $tradeDocument = $LMS->GetTradeDocument($doc);
        $document = $tradeDocument['document'] ?? null;
    }
    if (empty($document)) {
        die(json_encode(array(
            'error' => trans("Fatal error: No invoices nor debit notes were selected!"),
        )));
    }

    $mailPlaceholders = $LMS->substituteInvoiceMailPlaceholders(
        $mail_subject,
        $mail_body,
        $doc,
        $document,
        array(
            'currtime' => time(),
            'mail_format' => $mail_format,
            'aggregate_documents' => $aggregate_documents,
            'financial_history_reverse_order' => $financial_history_reverse_order,
            'financial_history_item_description_format' => $financial_history_item_description_format,
        )
    );

    $subject = $mailPlaceholders['subject'];
    $body = $mailPlaceholders['body'];
    if (isset($mail_format) && $mail_format == 'html') {
        $body = str_replace('<eol>', '<br>', $body);
    } else {
        $body = str_replace('<eol>', "\n", $body);
    }

    die(json_encode(array(
        'subject' => $subject,
        'body' => $body,
    )));
}

$SMARTY->display('header.html');

if (!isset($_GET['sent']) && isset($_SERVER['HTTP_REFERER']) && !preg_match('/m=invoicesend/', $_SERVER['HTTP_REFERER'])) {
    set_time_limit(0);

    echo '<H1>' . $layout['pagetitle'] . '</H1>';

    if (isset($_POST['invoicesend']) && is_array($_POST['invoicesend'])) {
        $invoiceSend = $_POST['invoicesend'];
        $invoiceSendCustomerId = intval($invoiceSend['customerid'] ?? 0);

        $invoiceSendEmails = empty($invoiceSend['recipients']) ? array() : (array) $invoiceSend['recipients'];
        if (!empty($invoiceSend['extraemail'])) {
            $extraEmail = is_string($invoiceSend['extraemail']) ? trim($invoiceSend['extraemail']) : '';
            if (check_email($extraEmail)) {
                $invoiceSendEmails[] = $extraEmail;
            } else {
                $invoiceSendInvalidExtraEmail = true;
            }
        }
        $invoiceSendRecipients = array();
        foreach ($invoiceSendEmails as $email) {
            $email = is_string($email) ? trim($email) : '';
            if (check_email($email)) {
                $invoiceSendRecipients[$email] = $email;
            }
        }

        if (isset($invoiceSend['subject']) && is_string($invoiceSend['subject']) && strlen(trim($invoiceSend['subject']))) {
            $invoiceSendSubject = trim($invoiceSend['subject']);
        }
        if (isset($invoiceSend['body']) && is_string($invoiceSend['body'])) {
            $invoiceSendBody = str_replace("\r\n", "\n", $invoiceSend['body']);
            if (strlen($invoiceSendBody) && $invoiceSendBody[0] == DIRECTORY_SEPARATOR && is_readable($invoiceSendBody)) {
                unset($invoiceSendBody);
            }
        }
    }

    if (empty($ids)) {
        echo '<span class="red">' . trans("Fatal error: No invoices nor debit notes were selected!") . '</span><br>';
    } elseif (!empty($invoiceSendInvalidExtraEmail)) {
        echo '<span class="red">' . trans('Unable to send message. Invalid e-mail address: $a', htmlspecialchars($extraEmail)) . '</span><br>';
    } elseif (isset($invoiceSendRecipients) && (empty($invoiceSendRecipients) || empty($invoiceSendCustomerId))) {
        echo '<span class="red">' . trans("Unable to send message. No recipients selected!") . '</span><br>';
    } else {
        $docs = $DB->GetAll(
            "SELECT
                d.id,
                d.number,
                d.cdate,
                d.name,
                d.customerid,
                d.type AS doctype,
                d.archived,
                n.template,
                m.email,
                d.divisionid,
                d.div_name,
                d.div_shortname,
                d.div_account,
                (CASE WHEN EXISTS (SELECT 1 FROM documents d2 WHERE d2.reference = d.id AND d2.type < 0) THEN 1 ELSE 0 END) AS documentreferenced,
                kd.ksefnumber,
                kd.status AS ksefstatus,
                kd.hash AS ksefhash,
                kbs.environment AS ksefenvironment,
                d.div_ten AS kseften
            FROM documents d
            LEFT JOIN customers c ON c.id = d.customerid
            LEFT JOIN ksefdocuments kd ON kd.docid = d.id AND kd.status IN ?
            LEFT JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
            LEFT JOIN ksefconfig kc ON kc.divisionid = d.divisionid
            " . (isset($invoiceSendRecipients) ? 'LEFT ' : '') . "JOIN (
                SELECT customerid, " . $DB->GroupConcat('contact') . " AS email
                FROM customercontacts
                WHERE (type & ?) = ?
                GROUP BY customerid
            ) m ON m.customerid = c.id
            LEFT JOIN numberplans n ON n.id = d.numberplanid
            WHERE (
                    d.type IN ?
                    AND (
                        d.cdate < kc.boundarydate
                        OR kc.boundarydate IS NULL
                        OR c.type = ? AND (COALESCE(kc.allconsumers, 0) = ? AND NOT EXISTS (SELECT 1 FROM customerconsents cc WHERE cc.customerid = d.customerid AND cc.type = ?) OR kd.status IS NOT NULL)
                        OR c.type = ? AND kd.status IS NOT NULL
                    ) OR d.type IN ?
                ) AND d.id IN (" . implode(',', $ids) . ")"
            . (isset($invoiceSendRecipients) ? ' AND d.customerid = ' . $invoiceSendCustomerId : '') . "
            ORDER BY d.number",
            [
                [
                    0,
                    200,
                ],
                CONTACT_EMAIL | CONTACT_INVOICES | CONTACT_DISABLED, CONTACT_EMAIL | CONTACT_INVOICES,
                [
                    DOC_INVOICE,
                    DOC_CNOTE,
                ],
                CTYPES_PRIVATE,
                0,
                CCONSENT_KSEF_INVOICE,
                CTYPES_COMPANY,
                [
                    DOC_DNOTE,
                    DOC_INVOICE_PRO,
                ],
            ]
        );

        if (!empty($docs) && isset($invoiceSendRecipients)) {
            foreach ($docs as &$doc) {
                $doc['email'] = implode(',', $invoiceSendRecipients);
            }
            unset($doc);
        }

        if (!empty($docs)) {
            $which = isset($_GET['which']) ? intval($_GET['which']) : 0;
            if ($which & DOC_ENTITY_DUPLICATE) {
                $duplicate_date = isset($_GET['duplicate-date']) ? intval($_GET['duplicate-date']) : 0;
            } else {
                $duplicate_date = 0;
            }
            if (!$which) {
                $which = DOC_ENTITY_ORIGINAL;
            }

            $currtime = time();

            $combine_documents = isset($invoiceSendRecipients);
            $skip_mail_substitution = $combine_documents && isset($invoiceSendSubject) && isset($invoiceSendBody);

            $divisiondocs = array();
            if ($combine_documents) {
                usort($docs, function ($a, $b) {
                    if ($a['cdate'] == $b['cdate']) {
                        return $a['id'] <=> $b['id'];
                    }
                    return $a['cdate'] <=> $b['cdate'];
                });
                $divisiondocs[$docs[count($docs) - 1]['divisionid']] = $docs;
            } else {
                foreach ($docs as $doc) {
                    $divisionid = $doc['divisionid'];
                    if (!isset($divisiondocs[$divisionid])) {
                        $divisiondocs[$divisionid] = array();
                    }
                    $divisiondocs[$divisionid][] = $doc;
                }
            }

            foreach ($divisiondocs as $divisionid => $docs) {
                ConfigHelper::setFilter($divisionid);

                $ksefOfflineSupport = ConfigHelper::checkConfig('ksef.offline_support');
                $docs = array_filter(
                    $docs,
                    function ($doc) use ($ksefOfflineSupport) {
                        return empty($doc['ksefhash']) || $doc['ksefstatus'] == 200 || $ksefOfflineSupport && empty($doc['ksefstatus']);
                    }
                );

                $smtp_options = array(
                    'host' => ConfigHelper::getConfig('sendinvoices.smtp_host'),
                    'port' => ConfigHelper::getConfig('sendinvoices.smtp_port'),
                    'user' => ConfigHelper::getConfig('sendinvoices.smtp_username', ConfigHelper::getConfig('sendinvoices.smtp_user')),
                    'pass' => ConfigHelper::getConfig('sendinvoices.smtp_password', ConfigHelper::getConfig('sendinvoices.smtp_pass')),
                    'auth' => ConfigHelper::getConfig('sendinvoices.smtp_auth_type', ConfigHelper::getConfig('sendinvoices.smtp_auth')),
                    'ssl_verify_peer' => ConfigHelper::checkConfig('sendinvoices.smtp_ssl_verify_peer', true),
                    'ssl_verify_peer_name' => ConfigHelper::checkConfig('sendinvoices.smtp_ssl_verify_peer_name', true),
                    'ssl_allow_self_signed' => ConfigHelper::checkConfig('sendinvoices.smtp_ssl_allow_self_signed'),
                );

                $debug_email = ConfigHelper::getConfig('sendinvoices.debug_email', '', true);
                $sender_name = ConfigHelper::getConfig('sendinvoices.sender_name', '', true);
                $sender_email = ConfigHelper::getConfig('sendinvoices.sender_email', '', true);
                $mail_subject = ConfigHelper::getConfig('sendinvoices.mail_subject', 'Invoice No. %invoice');
                $mail_body = ConfigHelper::getConfig('sendinvoices.mail_body', ConfigHelper::getConfig('mail.sendinvoice_mail_body'));
                if (isset($invoiceSendSubject)) {
                    $mail_subject = $invoiceSendSubject;
                }
                if (isset($invoiceSendBody)) {
                    $mail_body = $invoiceSendBody;
                }
                $mail_format = ConfigHelper::getConfig('sendinvoices.mail_format', 'text');
                $invoice_filename = ConfigHelper::getConfig('sendinvoices.invoice_filename', 'invoice_%docid');
                $dnote_filename = ConfigHelper::getConfig('sendinvoices.debitnote_filename', 'dnote_%docid');
                $notify_email = ConfigHelper::getConfig('sendinvoices.notify_email', '', true);
                $reply_email = ConfigHelper::getConfig('sendinvoices.reply_email', '', true);
                $add_message = ConfigHelper::checkConfig('sendinvoices.add_message');
                $message_attachments = ConfigHelper::checkConfig('sendinvoices.message_attachments');
                $aggregate_documents = ConfigHelper::checkConfig('sendinvoices.aggregate_documents');
                $financial_history_reverse_order = ConfigHelper::checkConfig(
                    'sendinvoices.financial_history_reverse_order',
                    ConfigHelper::checkConfig(
                        'finances.history_reverse_order',
                        true
                    )
                );
                $financial_history_item_description_format = ConfigHelper::getConfig(
                    'sendinvoices.financial_history_item_description_format',
                    ConfigHelper::getConfig(
                        'finances.history_item_description_format',
                        '%comment'
                    )
                );
                $dsn_email = ConfigHelper::getConfig('sendinvoices.dsn_email', '', true);
                $mdn_email = ConfigHelper::getConfig('sendinvoices.mdn_email', '', true);

                $extrafile = ConfigHelper::getConfig('sendinvoices.extra_file', null, true);
                if ($extrafile && !is_readable($extrafile)) {
                    $extrafile = null;
                }

                if (empty($sender_email)) {
                    echo '<span class="red">' . trans("Fatal error: sender_email unset! Can't continue, exiting.") . '</span><br>';
                }

                $smtp_auth = empty($smtp_auth) ? ConfigHelper::getConfig('mail.smtp_auth_type') : $smtp_auth;
                if (!empty($smtp_auth) && !preg_match('/^LOGIN|PLAIN|CRAM-MD5|NTLM$/i', $smtp_auth)) {
                    echo '<span class="red">' . trans("Fatal error: smtp_auth value not supported! Can't continue, exiting.") . '</span><br>';
                }

                $quiet = false;
                $test = false;

                $LMS->SendInvoices(
                    $docs,
                    'frontend',
                    compact(
                        'SMARTY',
                        'invoice_filename',
                        'dnote_filename',
                        'debug_email',
                        'mail_body',
                        'mail_subject',
                        'mail_format',
                        'currtime',
                        'sender_email',
                        'sender_name',
                        'extrafile',
                        'dsn_email',
                        'reply_email',
                        'mdn_email',
                        'notify_email',
                        'quiet',
                        'test',
                        'add_message',
                        'message_attachments',
                        'aggregate_documents',
                        'financial_history_reverse_order',
                        'financial_history_item_description_format',
                        'which',
                        'duplicate_date',
                        'smtp_options',
                        'combine_documents',
                        'skip_mail_substitution'
                    )
                );
            }
        }
    }

    echo '<script type="text/javascript">';
    echo "history.replaceState({}, '', location.href.replace(/&(is_sure|sent)=1/gi, '') + '&sent=1');";
    echo '</script>';
}

$SMARTY->display('footer.html');
