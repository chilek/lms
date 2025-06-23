<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

if (isset($_POST['template'])) {
    if (!empty($_POST['template']['id'])) {
        $result = handle_file_uploads('edit-template-attachments', $error);
    } else {
        $result = handle_file_uploads('add-template-attachments', $error);
    }

    extract($result);
    //$SMARTY->assign('fileupload', $fileupload);
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'add':
        case 'edit':
            $p = $_POST['template'];
            foreach ($p as $idx => $val) {
                if (!is_array($val)) {
                    $p[$idx] = trim($val);
                }
            }

            $attachments = array();
            if (($p['type'] == TMPL_MAIL || $p['type'] == TMPL_USERPANEL || $p['type'] == TMPL_USERPANEL_URGENT)
                && !empty($fileupload[$_GET['action'] . '-template-attachments'])) {
                $attachments = $fileupload[$_GET['action'] . '-template-attachments'];
                foreach ($attachments as &$attachment) {
                    $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
                }
                unset($attachment);
            }

            if (!strlen($p['name'])) {
                $error[$_GET['action'] . '-template-name'] = trans('Empty message template name!');
            }
            if (($p['type'] != TMPL_SMS && $p['type'] != TMPL_WARNING && $p['type'] != TMPL_CNOTE_REASON) && !strlen($p['subject'])) {
                $error[$_GET['action'] . '-template-subject'] = trans('Empty message template subject!');
            }

            if ($p['type'] == TMPL_SMS) {
                $body_type = 'text';
            } else {
                if (empty($p['content-type'])) {
                    $body_type = empty($p['wysiwyg']['html-body']) ? 'text' : 'html';
                } else {
                    $body_type = $p['content-type'];
                }
            }
            if (!strlen($p[$body_type . '-body'])) {
                $error[$_GET['action'] . '-template-' . $body_type . '-body'] = trans('Empty message template body!');
            }

            if ($error) {
                die(json_encode(array('error' => $error)));
            } else {
                $body = $body_type == 'html' ? Utils::removeInsecureHtml($p[$body_type . '-body']) : $p[$body_type . '-body'];
                if ($_GET['action'] == 'add') {
                    $id = $LMS->AddMessageTemplate(
                        $p['type'],
                        $p['name'],
                        $p['subject'],
                        $p['helpdesk-queues'] ?? null,
                        $p['helpdesk-message-types'] ?? null,
                        $body,
                        $body_type,
                        $attachments
                    );
                } else {
                    $attachments_to_delete = array();

                    if (($p['type'] == TMPL_MAIL || $p['type'] == TMPL_USERPANEL || $p['type'] == TMPL_USERPANEL_URGENT)
                        && !empty($p['deleted-existing-attachments'])) {
                        $attachments_to_delete = $p['deleted-existing-attachments'];
                    }

                    $id = $LMS->UpdateMessageTemplate(
                        $p['id'],
                        $p['type'],
                        $p['name'],
                        $p['subject'],
                        $p['helpdesk-queues'] ?? null,
                        $p['helpdesk-message-types'] ?? null,
                        $body,
                        $body_type,
                        $attachments,
                        $attachments_to_delete
                    );
                }

                if (!empty($tmppath)) {
                    rrmdir($tmppath);
                }

                die(json_encode(array('id' => $id)));
            }

            break;

        case 'cancel':
            if (!empty($tmppath)) {
                rrmdir($tmppath);
            }

            die('[]');

            break;

        case 'attachment-view':
            $attachment = $DB->GetRow('SELECT * FROM templateattachments WHERE id = ?', array($_GET['id']));
            $file = STORAGE_DIR . DIRECTORY_SEPARATOR . 'messagetemplates' . DIRECTORY_SEPARATOR . $attachment['templateid'] . DIRECTORY_SEPARATOR . $attachment['filename'];

            header('Content-Type: ' . $attachment['contenttype']);
            header('Cache-Control: private');
            header('Content-Disposition: ' . ($attachment['contenttype'] == 'application/pdf' ? 'inline' : 'attachment') . '; filename=' . $attachment['filename']);
            echo @file_get_contents($file);

            break;
    }
    die;
}

if (isset($_GET['type'])) {
    $type = $_GET['type'];
} else {
    $SESSION->restore('mtlt', $type);
}
$SESSION->save('mtlt', $type);

$layout['pagetitle'] = trans('Message Template List');

$SESSION->add_history_entry();

$SMARTY->assign('type', $type);
$SMARTY->assign('templates', $LMS->GetMessageTemplates($type));
$SMARTY->assign('queues', $LMS->GetQueueList(array('only_accessible' => true, 'stats' => false)));

$SMARTY->display('message/messagetemplatelist.html');
