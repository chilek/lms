<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2022 LMS Developers
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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id) {
        $LMS->DeleteFileContainers('messageid', $id);
        $DB->BeginTrans();
        if (isset($_GET['customerid'])) {
            $DB->Execute(
                'DELETE FROM messageitems
                WHERE messageid = ?
                    AND customerid = ?',
                array(
                    $id,
                    $_GET['customerid'],
                )
            );
        } else {
            $DB->Execute('DELETE FROM messageitems WHERE messageid = ?', array($id));
        }
        $DB->Execute(
            'DELETE FROM messages
            WHERE id = ?
                AND NOT EXISTS (SELECT 1 FROM messageitems i WHERE i.messageid = messages.id)',
            array($id)
        );
        $DB->CommitTrans();
    }
} elseif (isset($_POST['marks'])) {
    $ids = Utils::filterIntegers($_POST['marks']);
    if (!empty($ids)) {
        foreach ($_POST['marks'] as $mkey => $mark) {
            $LMS->DeleteFileContainers('messageid', $mkey);
        }
        $DB->BeginTrans();
        if (isset($_GET['customerid'])) {
            $DB->Execute(
                'DELETE FROM messageitems
                WHERE messageid IN ?
                    AND customerid = ?',
                array(
                    $ids,
                    $_GET['customerid'],
                )
            );
        } else {
            $DB->Execute(
                'DELETE FROM messageitems WHERE messageid IN ?',
                array(
                    $ids,
                )
            );
        }
        $DB->Execute(
            'DELETE FROM messages
            WHERE id IN ?
                AND NOT EXISTS (SELECT 1 FROM messageitems i WHERE i.messageid = messages.id)',
            array(
                $ids,
            )
        );
        $DB->CommitTrans();
    }
}

$SESSION->redirect_to_history_entry();
