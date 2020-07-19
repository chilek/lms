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

function GetEmails($group, $network = null, $customergroup = null)
{
    global $DB, $LMS;

    if ($group == 4) {
        $deleted = 1;
        $network = null;
        $customergroup = null;
    } else {
        $deleted = 0;
    }

    $disabled = ($group == 5) ? 1 : 0;
    $indebted = ($group == 6) ? 1 : 0;

    if ($group>3) {
        $group = 0;
    }

    if ($network) {
        $net = $LMS->GetNetworkParams($network);
    }

    if ($emails = $DB->GetAll('SELECT customers.id AS id, cc.contact AS email, '.$DB->Concat('lastname', "' '", 'customers.name').' AS customername, pin, '
        .'COALESCE(SUM(value), 0.00) AS balance '
        .'FROM customers
		LEFT JOIN customercontacts cc ON cc.customerid = c.id AND ((cc.type & '.CONTACT_EMAIL | CONTACT_DISABLED.') = ' .CONTACT_EMAIL. ' ) 
		LEFT JOIN cash ON (customers.id=cash.customerid) '
        .($network ? 'LEFT JOIN vnodes ON (customers.id=ownerid) ' : '')
        .($customergroup ? 'LEFT JOIN customerassignments ON (customers.id=customerassignments.customerid) ' : '')
        .' WHERE deleted = '.$deleted
        .' AND email IS NOT NULL'
        .($group!=0 ? ' AND status = '.$group : '')
        .($network ? ' AND (ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].')' : '')
        .($customergroup ? ' AND customergroupid='.$customergroup : '')
        .' GROUP BY cc.contact, lastname, customers.name, customers.id, pin ORDER BY customername')) {
        if ($disabled) {
            $access = $DB->GetAllByKey('SELECT ownerid AS id FROM vnodes GROUP BY ownerid HAVING (SUM(access) != COUNT(access))', 'id');
        }

        foreach ($emails as $idx => $row) {
            if ($disabled && $access[$row['id']]) {
                $emails2[] = $row;
            } elseif ($indebted) {
                if ($row['balance'] < 0) {
                    $emails2[] = $row;
                }
            }
        }

        if ($disabled || $indebted) {
            $emails = $emails2;
        }
    }

    return $emails;
}

$layout['pagetitle'] = trans('Serial Mail');

if (isset($_POST['mailing'])) {
    $mailing = $_POST['mailing'];

    if ($mailing['group'] < 0 || $mailing['group'] > 6) {
        $error['group'] = trans('Incorrect customers group!');
    }

    if ($mailing['sender']=='') {
        $error['sender'] = trans('Sender e-mail is required!');
    } elseif (!check_email($mailing['sender'])) {
        $error['sender'] = trans('Specified e-mail is not correct!');
    }

    if ($mailing['from']=='') {
        $error['from'] = trans('Sender name is required!');
    }

    if ($mailing['subject']=='') {
        $error['subject'] = trans('Message subject is required!');
    }

    if ($mailing['body']=='') {
        $error['body'] = trans('Message body is required!');
    }

    if ($filename = $_FILES['file']['name']) {
        if (is_uploaded_file($_FILES['file']['tmp_name']) && $_FILES['file']['size']) {
            $file = '';
            $fd = fopen($_FILES['file']['tmp_name'], 'r');
            if ($fd) {
                while (!feof($fd)) {
                    $file .= fread($fd, 256);
                }
                fclose($fd);
            }
        } else { // upload errors
            switch ($_FILES['file']['error']) {
                case 1:
                case 2:
                    $error['file'] = trans('File is too large.');
                    break;
                case 3:
                    $error['file'] = trans('File upload has finished prematurely.');
                    break;
                case 4:
                    $error['file'] = trans('Path to file was not specified.');
                    break;
                default:
                    $error['file'] = trans('Problem during file upload.');
                    break;
            }
        }
    }

    if (!$error) {
        $layout['nomenu'] = true;
        $mailing['body'] = textwrap($mailing['body']);
        $mailing['body'] = str_replace("\r", '', $mailing['body']);
        $SMARTY->assign('mailing', $mailing);
        $SMARTY->display($_LMSDIR.'/modules/core/templates/header.html');

        $emails = GetEmails($mailing['group'], $mailing['network'], $mailing['customergroup']);

        $SMARTY->assign('recipcount', count($emails));
        $SMARTY->display($_LMSDIR.'/modules/mailing/templates/mailingsend.html');

        if (count($emails)) {
            $files = null;
            if (isset($file)) {
                $files[0]['content_type'] = $_FILES['file']['type'];
                $files[0]['filename'] = $filename;
                $files[0]['data'] = $file;
            }

            $debug_email = ConfigHelper::getConfig('mail.debug_email');
            if (!empty($debug_email)) {
                echo '<B>'.trans('Warning! Debug mode (using address $a).', $debug_email).'</B><BR>';
            }

            $headers['Date'] = date('D, d F Y H:i:s T');
            $headers['From'] = '"'.$mailing['from'].'" <'.$mailing['sender'].'>';
            $headers['Subject'] = $mailing['subject'];
            $headers['Reply-To'] = $headers['From'];

            foreach ($emails as $key => $row) {
                if (!empty($debug_email)) {
                    $row['email'] = $debug_email;
                }

                $body = $mailing['body'];

                $body = str_replace('%customer', $row['customername'], $body);
                $body = str_replace('%balance', $row['balance'], $body);
                $body = str_replace('%cid', $row['id'], $body);
                $body = str_replace('%pin', $row['pin'], $body);

                if (!(strpos($body, '%last_10_in_a_table') === false)) {
                    $last10 = '';
                    if ($last10_array = $DB->GetAll('SELECT comment, time, value 
						FROM cash WHERE customerid = ?
						ORDER BY time DESC LIMIT 10', array($row['id']))) {
                        foreach ($last10_array as $r) {
                            $last10 .= date("Y/m/d | ", $r['time']);
                            $last10 .= sprintf("%20s | ", sprintf(Localisation::getCurrentMoneyFormat(), $r['value']));
                            $last10 .= $r['comment']."\n";
                        }
                    }
                    $body = str_replace('%last_10_in_a_table', $last10, $body);
                }

                $headers['To'] = '<'.$row['email'].'>';

                echo '<img src="img/mail.gif" border="0" align="absmiddle" alt=""> '.trans('$a of $b ($c): $d &lt;$4&gt;', ($key+1), count($emails), sprintf('%02.1f%%', round((100/count($emails))*($key+1), 1)), $row['customername'], $row['email']);
                echo '<font color=red> '.$LMS->SendMail($row['email'], $headers, $body, $files)."</font><BR>\n";
            }
        }

        $SMARTY->display($_LMSDIR.'/modules/mailing/templates/mailingsend-footer.html');
        $SMARTY->display($_LMSDIR.'/modules/core/templates/footer.html');
        $SESSION->close();
        die;
    }
    $SMARTY->assign('mailing', $mailing);
}

$SMARTY->assign('error', $error);
$SMARTY->assign('networks', $LMS->GetNetworks());
$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
$SMARTY->assign('userinfo', $LMS->GetUserInfo(Auth::GetCurrentUser()));
