<?php

/*
 * LMS version 1.11-git
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

$id = intval($_GET['id']);

if ($id) {
    if ($LMS->getDocumentType($id) >= 0 || !$LMS->isDocumentAccessible($id)) {
        access_denied();
    }

    if (isset($_GET['recover'])) {
        if (!$DB->Execute(
            'UPDATE documents SET cancelled = 0 WHERE id = ? AND cancelled = 1 AND closed = ?',
            array($id, DOC_OPEN)
        )) {
            return;
        }
        $document = $DB->GetRow('SELECT * FROM documents WHERE id = ?', array($id));
    } else {
        if ($LMS->isDocumentPublished($id) && !ConfigHelper::checkPrivilege('published_document_modification')) {
            return;
        }

        if ($LMS->isDocumentReferenced($id)) {
            return;
        }

        if ($LMS->isArchiveDocument($id)) {
            return;
        }

        $hook_data = $LMS->executeHook('documentcancel_before_cancel', array(
            'id' => $id,
        ));
        if (isset($hook_data['continue']) && empty($hook_data['continue'])) {
            return;
        }

        if (!$DB->Execute(
            'UPDATE documents SET cancelled = 1 WHERE id = ? AND cancelled = 0 AND closed = ?',
            array($id, DOC_OPEN)
        )) {
            return;
        }
        $document = $DB->GetRow('SELECT * FROM documents WHERE id = ?', array($id));
    }

    if ($SYSLOG) {
        $args = array(
            SYSLOG::RES_DOC => $document['id'],
            SYSLOG::RES_CUST => $document['customerid'],
            SYSLOG::RES_USER => Auth::GetCurrentUser()
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DOC, SYSLOG::OPER_UPDATE, $args);
    }

    $SESSION->redirect_to_history_entry();
}

$SESSION->redirect('?m=documentlist');
