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

if (isset($_GET['file']) || isset($_GET['cid'])) {
    if (!($LMS->CheckTicketAccess($_GET['tid']) & RT_RIGHT_READ)) {
        access_denied();
    }

    if (isset($_GET['file'])) {
        $filename = urldecode($_GET['file']);
        $attach = $DB->GetRow('SELECT * FROM rtattachments WHERE messageid = ? AND filename = ?', array(intval($_GET['mid']), $filename));
    } else {
        $cid = urldecode($_GET['cid']);
        $attach = $DB->GetRow('SELECT * FROM rtattachments WHERE messageid = ? AND cid = ?', array(intval($_GET['mid']), $cid));
    }

    if ($attach) {
        $file = ConfigHelper::getConfig('rt.mail_dir') . DIRECTORY_SEPARATOR . sprintf(
            '%06d' . DIRECTORY_SEPARATOR . '%06d' . DIRECTORY_SEPARATOR . '%s',
            $_GET['tid'],
            $_GET['mid'],
            $attach['filename']
        );
        if (file_exists($file)) {
            if (isset($_GET['thumbnail']) && ($width = intval($_GET['thumbnail'])) > 0
                && class_exists('Imagick') && strpos($attach['contenttype'], 'image/') === 0) {
                $imagick = new \Imagick($file);
                $imagick->scaleImage($width, 0);
                header('Content-Type: ' . $attach['contenttype']);
                header('Cache-Control: private');
                header('Content-Disposition: ' . ($attach['contenttype'] == 'application/pdf' ? 'inline' : 'attachment') . '; filename=' . $filename);
                echo $imagick->getImageBlob();
            } else {
                header('Content-Type: ' . $attach['contenttype']);
                header('Cache-Control: private');
                header('Content-Disposition: ' . ($attach['contenttype'] == 'application/pdf' ? 'inline' : 'attachment') . '; filename=' . $filename);
                echo @file_get_contents($file);
            }
        }
        $SESSION->close();
        die;
    }
}

if (!isset($_GET['id'])) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$message = $LMS->GetMessage($_GET['id']);

if (!($LMS->CheckTicketAccess($message['ticketid']) & RT_RIGHT_READ)) {
    access_denied();
}

if ($message['userid']) {
    $message['username'] = $LMS->GetUserName($message['userid']);
}

if ($message['deluserid']) {
    $message['delusername'] = $LMS->GetUserName($message['deluserid']);
}

if ($message['customerid']) {
    $message['customername'] = $LMS->GetCustomerName($message['customerid']);
}

if (!empty($message['attachments']) && count($message['attachments'])) {
    foreach ($message['attachments'] as $key => $val) {
        list($size, $unit) = setunits(@filesize(ConfigHelper::getConfig('rt.mail_dir') . DIRECTORY_SEPARATOR
        . sprintf('%06d' . DIRECTORY_SEPARATOR . '%06d' . DIRECTORY_SEPARATOR . '%s', $message['ticketid'], $message['id'], $val['filename'])));
        $message['attachments'][$key]['size'] = $size;
        $message['attachments'][$key]['unit'] = $unit;
    }
}
if ($message['inreplyto']) {
    $reply = $LMS->GetMessage($message['inreplyto']);
    $message['inreplytoid'] = $reply['subject'];
}

if (!$message['customerid'] && !$message['userid'] && !$message['mailfrom'] && !$message['phonefrom']) {
    $message['requestor'] = $DB->GetOne('SELECT requestor FROM rttickets WHERE id=?', array($message['ticketid']));
}

$layout['pagetitle'] = trans('Ticket Review');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('message', $message);
$SMARTY->display('rt/rtmessageview.html');
