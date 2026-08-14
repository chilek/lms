<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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
    global $DB,$LMS,$SESSION,$SMARTY;

    if (isset($_GET['confirm_old'])) {
         $DB->Execute('UPDATE nodes SET warning=0 WHERE ownerid = ?', array($SESSION->id));
    } elseif ($DB->GetOne('SELECT MAX(warning) FROM vnodes WHERE ownerid = ?', array($SESSION->id))) {
        $warning = $LMS->GetCustomerMessage($SESSION->id);
        $SMARTY->assign('warning', $warning);
    }


    if (isset($_GET['confirm'])) {
        $confirm = $_GET['confirm'];
        $DB->Execute(
            'UPDATE messageitems SET status = ?, lastdate = ?NOW? WHERE id = ?',
            array(MSG_DELIVERED, $confirm)
        );
        header('Location: ?m=notices');
    } else {
        $notices = $DB->GetAllByKey(
            'SELECT
                m.subject,
                m.cdate,
                (CASE WHEN mi.body IS NULL THEN m.body ELSE mi.body END) AS body,
                m.type,
                m.contenttype,
                mi.id,
                mi.messageid,
                mi.destination,
                mi.status,
                mi.lastdate,
                mi.lastreaddate,
                mi.body as mibody
            FROM customers c
            JOIN messageitems mi ON mi.customerid = c.id
            JOIN messages m ON m.id = mi.messageid
            WHERE m.type IN ?
                AND c.id = ?
            ORDER BY mi.status asc, m.cdate desc',
            'messageid',
            array(
                array(MSG_USERPANEL, MSG_USERPANEL_URGENT,),
                $SESSION->id,
            )
        );
        if (!empty($notices)) {
            $attachments = $DB->GetAll(
                'SELECT
                    c.messageid,
                    f.*
                FROM filecontainers c
                JOIN files f ON f.containerid = c.id
                WHERE c.messageid IN ?',
                array(
                    array_keys($notices),
                )
            );
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (!isset($notices[$attachment['messageid']]['attachments'])) {
                        $notices[$attachment['messageid']]['attachments'] = array();
                    }
                    $notices[$attachment['messageid']]['attachments'][$attachment['id']] = $attachment;
                }
            }
        }

        $SMARTY->assign('notices', $notices);
    }

    if (isset($_GET['confirm_urgent'])) {
        $notice_handler = UserpanelNoticeHandler::getInstance();
        $notice_handler->markNoticeAsDelivered($_GET['confirm_urgent']);
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            print json_encode(array(
                'urgent_notice' => $notice_handler->getUrgentNotice(),
                'unread_notices' => $notice_handler->getUnreadNotices(),
            ));
            return;
        }
        header('Location: ?m=notices');
    }
    $SMARTY->display('module:notices.html');
}

function module_attachmentview()
{
    global $DB, $SESSION;

    if (!isset($_GET['id'])) {
        die;
    }

    $attachmentid = intval($_GET['id']);

    $attachment = $DB->GetRow(
        'SELECT
            f.*
        FROM files f
        JOIN filecontainers c ON c.id = f.containerid
        JOIN messages m ON m.id = c.messageid
        JOIN messageitems mi ON mi.messageid = m.id
        WHERE mi.customerid = ?
            AND f.id = ?',
        array(
            $SESSION->id,
            $attachmentid,
        )
    );
    if (empty($attachment)) {
        die;
    }

    $filename = DOC_DIR . DIRECTORY_SEPARATOR . substr($attachment['md5sum'], 0, 2) . DIRECTORY_SEPARATOR . $attachment['md5sum'];
    if (!file_exists($filename)) {
        die;
    }

    header('Content-Type: ' . $attachment['contenttype']);

    if (!preg_match('/(^text|pdf|image)/i', $attachment['contenttype'])) {
        header('Content-Disposition: attachment; filename=' . $attachment['filename']);
        header('Pragma: public');
    } else {
        header('Content-Disposition: inline; filename="' . $attachment['filename'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($filename));
    }

    readfile($filename);

    die;
}

function setNoticeRead($noticeid)
{
    $db = LMSDB::getInstance();
    $result = new xajaxResponse();

    $notice_handler = UserpanelNoticeHandler::getInstance();
    $notice_handler->markNoticeAsRead($noticeid);
    $unread_notices = $notice_handler->getUnreadNotices();
    if (empty($unread_notices)) {
        $result->script("$('.lms-userpanel-notices').removeClass('lms-userpanel-icon-warning');");
    }

    return $result;
}

$LMS->InitXajax();
$LMS->RegisterXajaxFunction('setNoticeRead');
$SMARTY->assign('xajax', $LMS->RunXajax());
