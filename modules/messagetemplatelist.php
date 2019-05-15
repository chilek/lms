<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

            if (!strlen($p['name'])) {
                $error[$_GET['action'] . '-template-name'] = trans('Empty message template name!');
            }
            if (($p['type'] != TMPL_SMS && $p['type'] != TMPL_WARNING) && !strlen($p['subject'])) {
                $error[$_GET['action'] . '-template-subject'] = trans('Empty message template subject!');
            }

            if ($p['type'] == TMPL_SMS || $p['type'] == TMPL_HELPDESK) {
                $body_type = 'text';
            } else {
                $body_type = 'html';
            }
            if (!strlen($p[$body_type . '-body'])) {
                $error[$_GET['action'] . '-template-' . $body_type . '-body'] = trans('Empty message template body!');
            }

            if ($error) {
                die(json_encode(array('error' => $error)));
            } else {
                if ($_GET['action'] == 'add') {
                    $id = $LMS->AddMessageTemplate(
                        $p['type'],
                        $p['name'],
                        $p['subject'],
                        $p['helpdesk-queues'],
                        $p['helpdesk-message-types'],
                        $p[$body_type . '-body']
                    );
                } else {
                    $id = $LMS->UpdateMessageTemplate(
                        $p['id'],
                        $p['type'],
                        $p['name'],
                        $p['subject'],
                        $p['helpdesk-queues'],
                        $p['helpdesk-message-types'],
                        $p[$body_type . '-body']
                    );
                }

                die(json_encode(array('id' => $id)));
            }

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

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('type', $type);
$SMARTY->assign('templates', $LMS->GetMessageTemplates($type));
$SMARTY->assign('queues', $LMS->GetQueueList(array('only_accessible' => true, 'stats' => false)));

$SMARTY->display('message/messagetemplatelist.html');
