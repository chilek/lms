<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

check_file_uploads();

function getMessageTemplate($tmplid, $subjectelem, $messageelem)
{
    global $DB;

    $result = new xajaxResponse();
    $row = $DB->GetRow('SELECT subject, message FROM templates WHERE id = ?', array($tmplid));
    $result->call('messageTemplateReceived', $subjectelem, $row['subject'], $messageelem, $row['message']);

    return $result;
}

function getMessageTemplates($tmpltype)
{
    global $LMS;

    $result = new xajaxResponse();
    $templates = $LMS->GetMessageTemplates($tmpltype);
    $result->call('messageTemplatesReceived', $templates);

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getMessageTemplate', 'getMessageTemplates'));
$SMARTY->assign('xajax', $LMS->RunXajax());

function GetRecipients($filter, $type = MSG_MAIL)
{
    global $LMS;

    $group = intval($filter['group']);
    $network = intval($filter['network']);
    if (is_array($filter['customergroup'])) {
        $customergroup = implode(',', Utils::filterIntegers($filter['customergroup']));
    } else {
        $customergroup = intval($filter['customergroup']);
    }
    $nodegroup = intval($filter['nodegroup']);
    $linktype = intval($filter['linktype']);
    $tarifftype = intval($filter['tarifftype']);
    $consent = isset($filter['consent']);
    $netdevices = isset($filter['netdevices']) ? $filter['netdevices'] : null;

    if ($group == 50) {
        $deleted = 1;
        $network = null;
        $customergroup = null;
    } else {
        $deleted = 0;
    }

    $disabled = ($group == 51) ? 1 : 0;
    $indebted = ($group == 52) ? 1 : 0;
    $notindebted = ($group == 53) ? 1 : 0;
    $indebted2 = ($group == 57) ? 1 : 0;
    $indebted3 = ($group == 58) ? 1 : 0;
    $opened_documents = ($group == 59) ? 1 : 0;

    $expired_indebted = ($group == 61) ? 1 : 0;
    $expired_notindebted = ($group == 60) ? 1 : 0;
    $expired_indebted2 = ($group == 62) ? 1 : 0;
    $expired_indebted3 = ($group == 63) ? 1 : 0;

    if ($group >= 50) {
        $group = 0;
    }

    if ($network) {
        $net = $LMS->GetNetworkParams($network);
    }

    if ($type == MSG_SMS) {
        $smstable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('contact') . ' AS phone, customerid
				FROM customercontacts
				WHERE ((type & ' . (CONTACT_MOBILE | CONTACT_DISABLED) . ') = ' . CONTACT_MOBILE . ' )
				GROUP BY customerid
			) x ON (x.customerid = c.id) ';
    } elseif ($type == MSG_MAIL) {
        $mailtable = 'JOIN (SELECT ' . $LMS->DB->GroupConcat('contact') . ' AS email, customerid
				FROM customercontacts
				WHERE ((type & ' . (CONTACT_EMAIL | CONTACT_DISABLED) . ') = ' . CONTACT_EMAIL . ')
				GROUP BY customerid
			) cc ON (cc.customerid = c.id) ';
    }

    if ($tarifftype) {
        $tarifftable = 'JOIN (
			SELECT DISTINCT a.customerid FROM assignments a
			JOIN tariffs t ON t.id = a.tariffid
			WHERE a.suspended = 0
				AND (a.datefrom = 0 OR a.datefrom < ?NOW?)
				AND (a.dateto = 0 OR a.dateto > ?NOW?)
				AND t.type = ' . $tarifftype . '
		) a ON a.customerid = c.id ';
    }

    $deadline = ConfigHelper::getConfig('payments.deadline', ConfigHelper::getConfig('invoices.paytime', 0));

    if ($expired_indebted || $expired_indebted2 || $expired_indebted3 || $expired_notindebted) {
        $expired_debt_table = "
			LEFT JOIN (
				SELECT SUM(value) AS value, cash.customerid
				FROM cash
				JOIN customers c ON c.id = cash.customerid
				LEFT JOIN divisions ON divisions.id = c.divisionid
				LEFT JOIN documents d ON d.id = cash.docid
				WHERE (cash.docid IS NULL AND ((cash.type <> 0 AND cash.time < ?NOW?)
						OR (cash.type = 0 AND cash.time + ((CASE c.paytime WHEN -1 THEN
							(CASE WHEN divisions.inv_paytime IS NULL THEN $deadline ELSE divisions.inv_paytime END) ELSE c.paytime END)) * 86400 < ?NOW?)))
					OR (cash.docid IS NOT NULL AND ((d.type IN (" . DOC_RECEIPT . ',' . DOC_CNOTE . ") AND cash.time < ?NOW?
						OR (d.type IN (" . DOC_INVOICE . ',' . DOC_DNOTE . ") AND d.cdate + d.paytime * 86400 < ?NOW?))))
				GROUP BY cash.customerid
			) b2 ON (b2.customerid = c.id)";
    } else {
        $expired_debt_table = '';
    }

    if (!empty($netdevices)) {
        $netdevtable = ' JOIN (
				SELECT DISTINCT n.ownerid FROM nodes n
				WHERE n.ownerid IS NOT NULL AND netdev IN (' . implode(',', $netdevices) . ')
			) nd ON nd.ownerid = c.id ';
    }

    $suspension_percentage = f_round(ConfigHelper::getConfig('finances.suspension_percentage'));

    $recipients = $LMS->DB->GetAll('SELECT c.id, pin, '
        . ($type == MSG_MAIL ? 'cc.email, ' : '')
        . ($type == MSG_SMS ? 'x.phone, ' : '')
        . $LMS->DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername,
		COALESCE(b.value, 0) AS balance
		FROM customerview c 
		LEFT JOIN (
			SELECT SUM(value) AS value, customerid
			FROM cash GROUP BY customerid
		) b ON (b.customerid = c.id)
		' . $expired_debt_table . '
		LEFT JOIN (SELECT a.customerid,
			SUM((CASE a.suspended
				WHEN 0 THEN (((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount)
				ELSE ((((100 - a.pdiscount) * (CASE WHEN t.value IS null THEN l.value ELSE t.value END) / 100) - a.vdiscount) * ' . $suspension_percentage . ' / 100) END)
			* (CASE t.period
				WHEN ' . MONTHLY . ' THEN 1
				WHEN ' . YEARLY . ' THEN 1/12.0
				WHEN ' . HALFYEARLY . ' THEN 1/6.0
				WHEN ' . QUARTERLY . ' THEN 1/3.0
				ELSE (CASE a.period
					WHEN ' . MONTHLY . ' THEN 1
					WHEN ' . YEARLY . ' THEN 1/12.0
					WHEN ' . HALFYEARLY . ' THEN 1/6.0
					WHEN ' . QUARTERLY . ' THEN 1/3.0
					ELSE 0 END)
				END)
			) AS value 
			FROM assignments a
			LEFT JOIN tariffs t ON (t.id = a.tariffid)
			LEFT JOIN liabilities l ON (l.id = a.liabilityid AND a.period != ' . DISPOSABLE . ')
			WHERE a.datefrom <= ?NOW? AND (a.dateto > ?NOW? OR a.dateto = 0) 
			GROUP BY a.customerid
		) t ON (t.customerid = c.id) '
        . (isset($netdevtable) ? $netdevtable : '')
        . (isset($mailtable) ? $mailtable : '')
        . (isset($smstable) ? $smstable : '')
        . ($tarifftype ? $tarifftable : '')
        .'WHERE deleted = ' . $deleted
        . ($consent ? ' AND c.mailingnotice = 1' : '')
        . ($type == MSG_WWW ? ' AND c.id IN (SELECT DISTINCT ownerid FROM nodes)' : '')
        .($group!=0 ? ' AND status = '.$group : '')
        .($network ? ' AND c.id IN (SELECT ownerid FROM vnodes WHERE 
			(netid = ' . $net['id'] . ' AND ipaddr > ' . $net['address'] . ' AND ipaddr < ' . $net['broadcast'] . ')
			OR (ipaddr_pub > '.$net['address'].' AND ipaddr_pub < '.$net['broadcast'].'))' : '')
        .($customergroup ? ' AND c.id IN (SELECT customerid FROM customerassignments
			WHERE customergroupid IN (' . $customergroup . '))' : '')
        .($nodegroup ? ' AND c.id IN (SELECT ownerid FROM vnodes
			JOIN nodegroupassignments ON (nodeid = vnodes.id)
			WHERE nodegroupid = ' . $nodegroup . ')' : '')
        .($linktype != '' ? ' AND c.id IN (SELECT ownerid FROM vnodes
			WHERE linktype = ' . $linktype . ')' : '')
        .($disabled ? ' AND EXISTS (SELECT 1 FROM vnodes WHERE ownerid = c.id
			GROUP BY ownerid HAVING (SUM(access) != COUNT(access)))' : '')
        . ($indebted ? ' AND COALESCE(b.value, 0) < 0' : '')
        . ($indebted2 ? ' AND COALESCE(b.value, 0) < -t.value' : '')
        . ($indebted3 ? ' AND COALESCE(b.value, 0) < -t.value * 2' : '')
        . ($notindebted ? ' AND COALESCE(b.value, 0) >= 0' : '')
        . ($expired_indebted ? ' AND COALESCE(b2.value, 0) < 0' : '')
        . ($expired_indebted2 ? ' AND COALESCE(b2.value, 0) < -t.value' : '')
        . ($expired_indebted3 ? ' AND COALESCE(b2.value, 0) < -t.value * 2' : '')
        . ($expired_notindebted ? ' AND COALESCE(b2.value, 0) >= 0' : '')
        . ($opened_documents ? ' AND c.id IN (SELECT DISTINCT customerid FROM documents
			WHERE documents.closed = 0
				AND documents.type NOT IN (' . DOC_INVOICE . ',' . DOC_CNOTE . ',' . DOC_DNOTE . '))' : '')
        . ($tarifftype ? ' AND NOT EXISTS (SELECT id FROM assignments
			WHERE customerid = c.id AND tariffid IS NULL AND liabilityid IS NULL
				AND (datefrom = 0 OR datefrom < ?NOW?)
				AND (dateto = 0 OR dateto > ?NOW?))' : '')
        .' ORDER BY customername');

    return $recipients;
}

function GetRecipient($customerid)
{
    global $DB;

    return $DB->GetRow('SELECT c.id, pin, '
        . $DB->Concat('c.lastname', "' '", 'c.name') . ' AS customername,
		COALESCE((SELECT SUM(value) FROM cash WHERE customerid = c.id), 0) AS balance
		FROM customerview c WHERE c.id = ?', array($customerid));
}

function BodyVars(&$body, $data)
{
    global $LMS, $LANGDEFS;

    $body = str_replace('%customer', $data['customername'], $body);
    $body = str_replace('%balance', $data['balance'], $body);
    $body = str_replace('%cid', $data['id'], $body);
    $body = str_replace('%pin', $data['pin'], $body);
    if (strpos($body, '%bankaccount') !== false) {
        $body = str_replace('%bankaccount', format_bankaccount(bankaccount($data['id'])), $body);
    }

    if (preg_match('/%last_(?<number>[0-9]+)_in_a_table/', $body, $m)) {
        $lN = '';
        $lastN = $LMS->GetCustomerShortBalanceList($data['id'], $m['number']);
        if (!empty($lastN)) {
            foreach ($lastN as $r) {
                $lN .= date("Y/m/d | ", $r['time']);
                $lN .= sprintf("%20s | ", sprintf($LANGDEFS[$LMS->ui_lang]['money_format'], $r['value']));
                $lN .= $r['comment']."\n";
            }
        }
        $body = preg_replace('/%last_[0-9]+_in_a_table/', $lN, $body);
    }

    $hook_data = $LMS->ExecuteHook('messageadd_variable_parser', array(
        'body' => $body,
        'data' => $data,
    ));
    $body = $hook_data['body'];
}

function FindNetDeviceUplink($netdevid)
{
    static $uplink_netdev = null;
    static $visited = array();
    static $root_netdevid = null;
    static $netdev_links = null;
    static $DB = null;

    if (is_null($DB)) {
        $DB = LMSDB::getInstance();
    }

    if (empty($root_netdevid)) {
        $root_netdevid = ConfigHelper::getConfig('phpui.root_netdevice_id');
    }

    if (is_null($netdev_links)) {
        $netlinks = $DB->GetAll('SELECT id, src, dst FROM netlinks');
        if (!empty($netlinks)) {
            foreach ($netlinks as $netlink) {
                if (!isset($netdev_links[$netlink['src']])) {
                    $netdev_links[$netlink['src']] = array();
                }
                $netdev_links[$netlink['src']][] = $netlink['dst'];
                if (!isset($netdev_links[$netlink['dst']])) {
                    $netdev_links[$netlink['dst']] = array();
                }
                $netdev_links[$netlink['dst']][] = $netlink['src'];
            }
        } else {
            $netdev_links = array();
        }
    }

    $visited[$netdevid] = true;

    if ($root_netdevid == $netdevid) {
        return $uplink_netdev;
    }

    if (!isset($netdev_links[$netdevid])) {
        return null;
    }

    foreach ($netdev_links[$netdevid] as $netdev) {
        if (isset($visited[$netdev])) {
            continue;
        }

        if ($netdev == $root_netdevid) {
            return $netdev;
        } else {
            $uplink_netdev = FindNetDeviceUplink($netdev);
        }
        if (!empty($uplink_netdev)) {
            return $netdev;
        }
    }

    return $uplink_netdev;
}

function GetNetDevicesInSubtree($netdevid)
{
    static $uplink_netdev = null;
    static $netdevices = array();
    static $visited = array();
    static $netdev_links = null;
    static $DB = null;

    if (is_null($uplink_netdev)) {
        $uplink_netdev = FindNetDeviceUplink($netdevid);
        if (empty($uplink_netdev)) {
            $uplink_netdev = 0;
        }
    }

    if (is_null($DB)) {
        $DB = LMSDB::getInstance();
    }

    if (is_null($netdev_links)) {
        $netlinks = $DB->GetAll('SELECT id, src, dst FROM netlinks');
        if (!empty($netlinks)) {
            foreach ($netlinks as $netlink) {
                if (!isset($netdev_links[$netlink['src']])) {
                    $netdev_links[$netlink['src']] = array();
                }
                $netdev_links[$netlink['src']][] = $netlink['dst'];
                if (!isset($netdev_links[$netlink['dst']])) {
                    $netdev_links[$netlink['dst']] = array();
                }
                $netdev_links[$netlink['dst']][] = $netlink['src'];
            }
        } else {
            $netdev_links = array();
        }
    }

    $netdevices[] = $netdevid;
    $visited[$netdevid] = true;

    if (!isset($netdev_links[$netdevid])) {
        return array();
    }

    foreach ($netdev_links[$netdevid] as $netdev) {
        if ($netdev == $uplink_netdev || isset($visited[$netdev])) {
            continue;
        }
        GetNetDevicesInSubtree($netdev);
    }

    return $netdevices;
}

$layout['pagetitle'] = trans('Message Add');

if (isset($_POST['message']) && !isset($_GET['sent'])) {
    $message = $_POST['message'];

    $message['netdevices'] = array();
    if (!empty($message['netdev'])) {
        if (isset($message['wholesubtree'])) {
            $message['netdevices'] = GetNetDevicesInSubtree($message['netdev']);
        } else {
            $message['netdevices'][] = $message['netdev'];
        }
    }

    if (!in_array($message['type'], array(MSG_MAIL, MSG_SMS, MSG_ANYSMS, MSG_WWW, MSG_USERPANEL))) {
        $message['type'] = MSG_USERPANEL_URGENT;
    }

    if (empty($message['customerid']) && ($message['group'] < 0 || $message['group'] > 63
        || ($message['group'] > CSTATUS_LAST && $message['group'] < 50))) {
        $error['group'] = trans('Incorrect customers group!');
    }

    if ($message['type'] == MSG_MAIL) {
        $message['body'] = $message['mailbody'];
        if ($message['sender'] == '') {
            $error['sender'] = trans('Sender e-mail is required!');
        } elseif (!check_email($message['sender'])) {
            $error['sender'] = trans('Specified e-mail is not correct!');
        }
        if ($message['from'] == '') {
            $error['from'] = trans('Sender name is required!');
        }
    } elseif ($message['type'] == MSG_WWW || $message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
        $message['body'] = $message['mailbody'];
    } else {
        $message['body'] = $message['smsbody'];
        $message['sender'] = '';
        $message['from'] = '';
        $phonenumbers = array();
        if ($message['type'] == MSG_ANYSMS) {
            $message['phonenumber'] = preg_replace('/[ \t]/', '', $message['phonenumber']);
            if (preg_match('/^[\+]?[0-9]+(,[\+]?[0-9]+)*$/', $message['phonenumber'])) {
                $phonenumbers = preg_split('/,/', $message['phonenumber']);
            }
            if (count($message['users'])) {
                $phonenumbers = array_merge($phonenumbers, $message['users']);
            }
            if (empty($phonenumbers)) {
                $error['phonenumber'] = trans('Specified phone number is not correct!');
            }
        }
    }

    $msgtmplid = intval($message['tmplid']);
    $msgtmploper = intval($message['tmploper']);
    $msgtmplname = $message['tmplname'];
    if (!isset($_GET['count_recipients']) && $msgtmploper > 1) {
        switch ($message['type']) {
            case MSG_MAIL:
                $msgtmpltype = TMPL_MAIL;
                break;
            case MSG_SMS:
            case MSG_ANYSMS:
                $msgtmpltype = TMPL_SMS;
                break;
            case MSG_WWW:
                $msgtmpltype = TMPL_WWW;
                break;
            case MSG_USERPANEL:
                $msgtmpltype = TMPL_USERPANEL;
                break;
            case MSG_USERPANEL_URGENT:
                $msgtmpltype = TMPL_USERPANEL_URGENT;
                break;
        }
        switch ($msgtmploper) {
            case 2:
                if (empty($msgtmplid)) {
                    break;
                }
                $LMS->UpdateMessageTemplate($msgtmplid, $msgtmpltype, null, $message['subject'], null, null, $message['body']);
                break;
            case 3:
                if (!strlen($msgtmplname)) {
                    break;
                }
                $LMS->AddMessageTemplate($msgtmpltype, $msgtmplname, $message['subject'], null, null, $message['body']);
                break;
        }
    }

    if ($message['subject']=='') {
        $error['subject'] = trans('Message subject is required!');
    }

    if ($message['body'] == '') {
        if (in_array($message['type'], array(MSG_SMS, MSG_ANYSMS))) {
            $error['smsbody'] = trans('Message body is required!');
        } else {
            $error['mailbody'] = trans('Message body is required!');
        }
    }

    if (!$error) {
        $recipients = array();
        if (empty($message['customerid'])) {
            if ($message['type'] != MSG_ANYSMS) {
                $recipients = GetRecipients($message, $message['type']);
            } else {
                foreach ($phonenumbers as $phone) {
                            $recipients[]['phone'] = $phone;
                }
            }
        } else {
            $recipient = GetRecipient($message['customerid']);
            if (!empty($recipient)) {
                switch ($message['type']) {
                    case MSG_MAIL:
                        if (empty($message['customermails'])) {
                            break;
                        }
                        $recipient['email'] = implode(',', $message['customermails']);
                        $recipients = array($recipient);
                        break;
                    case MSG_SMS:
                        if (empty($message['customerphones'])) {
                            break;
                        }
                        $recipient['phone'] = implode(',', $message['customerphones']);
                        $recipients = array($recipient);
                        break;
                    default:
                        $recipients = array($recipient);
                }
            }
        }

        if (!$recipients) {
            $error['subject'] = trans('Unable to send message. No recipients selected!');
        }
    }

    if (isset($_GET['count_recipients'])) {
        header('Content-Type: application/json');
        die(json_encode(array(
            'recipients' => empty($error) ? count($recipients) : -1,
        )));
    }

    if ($message['type'] == MSG_MAIL) {
        $result = handle_file_uploads('files', $error);
        extract($result);
        $SMARTY->assign('fileupload', $fileupload);
    }

    if (!$error) {
        set_time_limit(0);

        $message['body'] = str_replace("\r", '', $message['body']);

        $html_format = isset($message['wysiwyg']) && isset($message['wysiwyg']['mailbody']) && ConfigHelper::checkValue($message['wysiwyg']['mailbody']);

        if ($message['type'] == MSG_MAIL) {
            if (!$html_format) {
                $message['body'] = wordwrap($message['body'], 76, "\n");
            }
            $dsn_email = ConfigHelper::getConfig('mail.dsn_email', '', true);
            $mdn_email = ConfigHelper::getConfig('mail.mdn_email', '', true);
        }

        $SMARTY->assign('message', $message);
        $SMARTY->assign('recipcount', count($recipients));
        $SMARTY->display('message/messagesend.html');

        $DB->BeginTrans();

        $DB->Execute('INSERT INTO messages (type, cdate, subject, body, userid, sender)
			VALUES (?, ?NOW?, ?, ?, ?, ?)', array(
                $message['type'],
                $message['subject'],
                $message['body'],
                Auth::GetCurrentUser(),
                $message['type'] == MSG_MAIL ? '"' . $message['from'] . '" <' . $message['sender'] . '>' : '',
            ));

        $msgid = $DB->GetLastInsertID('messages');

        foreach ($recipients as $key => $row) {
            if ($message['type'] == MSG_MAIL) {
                $recipients[$key]['destination'] = explode(',', $row['email']);
            } elseif ($message['type'] == MSG_WWW) {
                $recipients[$key]['destination'] = array(trans('www'));
            } elseif ($message['type'] == MSG_USERPANEL) {
                $recipients[$key]['destination'] = array(trans('userpanel'));
            } elseif ($message['type'] == MSG_USERPANEL_URGENT) {
                $recipients[$key]['destination'] = array(trans('userpanel urgent'));
            } else {
                $recipients[$key]['destination'] = explode(',', $row['phone']);
            }

            $customerid = isset($row['id']) ? $row['id'] : 0;
            foreach ($recipients[$key]['destination'] as $destination) {
                $DB->Execute('INSERT INTO messageitems (messageid, customerid,
					destination, status)
					VALUES (?, ?, ?, ?)', array($msgid, empty($customerid) ? null : $customerid, $destination, MSG_NEW));
                if ($message['type'] == MSG_MAIL && (!empty($dsn_email) || !empty($mdn_email))) {
                    $msgitemid = $DB->GetLastInsertID('messageitems');
                    if (!isset($msgitems[$customerid])) {
                        $msgitems[$customerid] = array();
                    }
                    $msgitems[$customerid][$destination] = $msgitemid;
                }
            }
        }

        $DB->CommitTrans();

        if ($message['type'] == MSG_MAIL) {
            $attachments = null;
            if (!empty($files)) {
                foreach ($files as $file) {
                    $attachments[] = array(
                        'content_type' => $file['type'],
                        'filename' => $file['name'],
                        'data' => file_get_contents($tmppath . DIRECTORY_SEPARATOR . $file['name']),
                    );
                }

                // deletes uploaded files
                if (!empty($tmppath)) {
                    rrmdir($tmppath);
                }
            }

            $debug_email = ConfigHelper::getConfig('mail.debug_email');
            if (!empty($debug_email)) {
                echo '<B>'.trans('Warning! Debug mode (using address $a).', ConfigHelper::getConfig('mail.debug_email')).'</B><BR>';
            }

            $headers['From'] = '"' . qp_encode($message['from']) . '"' . ' <' . $message['sender'] . '>';
            $headers['Subject'] = $message['subject'];

            $reply_email = ConfigHelper::getConfig('mail.reply_email');
            $headers['Reply-To'] = empty($reply_email) ? $message['sender'] : $reply_email;

            if (isset($message['copytosender'])) {
                $headers['Cc'] = $headers['From'];
            }
            if ($html_format) {
                $headers['X-LMS-Format'] = 'html';
            }
            if (!empty($dsn_email)) {
                $headers['From'] = $dsn_email;
                $headers['Delivery-Status-Notification-To'] = true;
            }
            if (!empty($mdn_email)) {
                $headers['Return-Receipt-To'] = $mdn_email;
                $headers['Disposition-Notification-To'] = $mdn_email;
            }
        } elseif ($message['type'] != MSG_WWW) {
            $debug_phone = ConfigHelper::getConfig('sms.debug_phone');
            if (!empty($debug_phone)) {
                echo '<B>'.trans('Warning! Debug mode (using phone $a).', $debug_phone).'</B><BR>';
            }
        }

        foreach ($recipients as $key => $row) {
            $body = $message['body'];

            BodyVars($body, $row);

            $customerid = isset($row['id']) ? $row['id'] : 0;

            foreach ($row['destination'] as $destination) {
                $orig_destination = $destination;
                if ($message['type'] == MSG_MAIL) {
                    $headers['To'] = '<' . $destination . '>';
                    echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> ';
                } elseif ($message['type'] == MSG_WWW) {
                    echo '<img src="img/network.gif" border="0" align="absmiddle" alt=""> ';
                } elseif ($message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
                    echo '<img src="img/cms.gif" border="0" align="absmiddle" alt=""> ';
                } else {
                    $destination = preg_replace('/[^0-9]/', '', $destination);
                    echo '<img src="img/sms.gif" border="0" align="absmiddle" alt=""> ';
                }

                echo trans(
                    '$a of $b ($c) $d:',
                    ($key + 1),
                    count($recipients),
                    sprintf('%02.1f%%', round((100 / count($recipients)) * ($key + 1), 1)),
                    $row['customername'] . ' &lt;' . $destination . '&gt;'
                );
                flush();

                if ($message['type'] == MSG_MAIL) {
                    if (isset($message['copytosender'])) {
                        $destination .= ',' . $message['sender'];
                    }
                    if (!empty($dsn_email) || !empty($mdn_email)) {
                        $headers['X-LMS-Message-Item-Id'] = $msgitems[$customerid][$orig_destination];
                        $headers['Message-ID'] = '<messageitem-' . $msgitems[$customerid][$orig_destination] . '@rtsystem.' . gethostname() . '>';
                    }
                    $result = $LMS->SendMail($destination, $headers, $body, $attachments);
                } elseif ($message['type'] == MSG_WWW || $message['type'] == MSG_USERPANEL || $message['type'] == MSG_USERPANEL_URGENT) {
                    $result = MSG_SENT;
                } else {
                    $result = $LMS->SendSMS($destination, $body, $msgid);
                }

                if (is_string($result)) {
                    echo " <font color=red>$result</font>";
                } else if ($result == MSG_SENT) {
                    echo ' ['.trans('sent').']';
                } else {
                    echo ' ['.trans('added').']';
                }

                echo "<BR>\n";

                if (!is_int($result) || $result == MSG_SENT) {
                    $DB->Execute(
                        'UPDATE messageitems SET status = ?, lastdate = ?NOW?,
						error = ? WHERE messageid = ? AND '
                            . (empty($customerid) ? 'customerid IS NULL' : 'customerid = ' . intval($customerid)) . '
							AND destination = ?',
                        array(
                            is_int($result) ? $result : MSG_ERROR,
                            is_int($result) ? null : $result,
                            $msgid,
                            $orig_destination,
                        )
                    );
                }
            }
        }

        echo '<script type="text/javascript">';
        echo "history.replaceState({}, '', location.href.replace(/&sent=1/gi, '') + '&sent=1');";
        echo '</script>';

        $SMARTY->display('footer.html');
        $SESSION->close();
        die;
    } else if (!empty($message['customerid'])) {
        $message['customer'] = $DB->GetOne('SELECT '
            .$DB->Concat('UPPER(lastname)', "' '", 'name').'
			FROM customerview
			WHERE id = ?', array($message['customerid']));

        $message['phones'] = $DB->GetAll(
            'SELECT contact, name FROM customercontacts
			WHERE customerid = ? AND (type & ?) = 0 AND (type & ?) > 0',
            array($message['customerid'], CONTACT_DISABLED, CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE)
        );
        if (is_null($message['phones'])) {
            $message['phones'] = array();
        }

        $message['emails'] = $DB->GetAll(
            'SELECT contact, name FROM customercontacts
			WHERE customerid = ? AND (type & ?) = ?',
            array($message['customerid'], CONTACT_EMAIL | CONTACT_DISABLED, CONTACT_EMAIL)
        );
        if (is_null($message['emails'])) {
            $message['emails'] = array();
        }
    }

    $SMARTY->assign('error', $error);
    $SMARTY->assign('message', $message);
} else if (!empty($_GET['customerid'])) {
    $message = $DB->GetRow('SELECT id AS customerid, '
        .$DB->Concat('UPPER(lastname)', "' '", 'name').' AS customer
		FROM customerview
		WHERE id = ?', array($_GET['customerid']));

    $message['phones'] = $DB->GetAll(
        'SELECT contact, name, type FROM customercontacts
		WHERE customerid = ? AND (type & ?) = 0 AND (type & ?) > 0',
        array($_GET['customerid'], CONTACT_DISABLED, CONTACT_MOBILE | CONTACT_FAX | CONTACT_LANDLINE)
    );
    if (is_null($message['phones'])) {
        $message['phones'] = array();
    }
    $message['customerphones'] = array();
    foreach ($message['phones'] as $idx => $phone) {
        if ($phone['type'] & CONTACT_MOBILE) {
            $message['customerphones'][$idx] = $phone['contact'];
        }
    }

    $message['emails'] = $DB->GetAll(
        'SELECT contact, name FROM customercontacts
		WHERE customerid = ? AND (type & ?) = ?',
        array($_GET['customerid'], CONTACT_EMAIL | CONTACT_DISABLED, CONTACT_EMAIL)
    );
    if (is_null($message['emails'])) {
        $message['emails'] = array();
    }
    $message['customermails'] = array();
    foreach ($message['emails'] as $idx => $email) {
        $message['customermails'][$idx] = $email['contact'];
    }

    $message['type'] = empty($message['emails']) ? (empty($message['phones']) ? MSG_WWW : MSG_SMS) : MSG_MAIL;

    $SMARTY->assign('message', $message);
}

if (isset($message['type'])) {
    switch ($message['type']) {
        case MSG_MAIL:
            $msgtmpltype = TMPL_MAIL;
            break;
        case MSG_SMS:
        case MSG_ANYSMS:
            $msgtmpltype = TMPL_SMS;
            break;
        case MSG_WWW:
            $msgtmpltype = TMPL_WWW;
            break;
        case MSG_USERPANEL:
            $msgtmpltype = TMPL_USERPANEL;
            break;
        case MSG_USERPANEL_URGENT:
            $msgtmpltype = TMPL_USERPANEL_URGENT;
            break;
    }
} else {
    $msgtmpltype = TMPL_MAIL;
}
$SMARTY->assign('messagetemplates', $LMS->GetMessageTemplates($msgtmpltype));
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('nodegroups', $LMS->GetNodeGroupNames());
$SMARTY->assign('userinfo', $LMS->GetUserInfo(Auth::GetCurrentUser()));
$SMARTY->assign('users', $DB->GetAll('SELECT name, phone FROM vusers WHERE phone <> \'\' ORDER BY name'));

$netdevices = $LMS->GetNetDevList();
unset($netdevices['total'], $netdevices['order'], $netdevices['direction']);
$SMARTY->assign('netdevices', $netdevices);

$SMARTY->display('message/messageadd.html');
