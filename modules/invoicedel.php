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

if (isset($_POST['marks'])) {
    $ids = $_POST['marks'];
} else {
    $ids = array($_GET['id']);
}
$ids = Utils::filterIntegers($ids);
if (empty($ids)) {
    return;
}

foreach ($ids as $id) {
    if ($LMS->isDocumentPublished($id) && !ConfigHelper::checkPrivilege('published_document_modification')) {
        continue;
    }

    if ($LMS->isDocumentReferenced($id)) {
        continue;
    }

    if ($LMS->isArchiveDocument($id)) {
        continue;
    }

    $hook_data = $LMS->executeHook('invoicedel_before_delete', array(
        'id' => $id,
    ));
    if (isset($hook_data['continue']) && empty($hook_data['continue'])) {
        continue;
    }
    $DB->BeginTrans();
    $LMS->InvoiceDelete($id);
    $DB->CommitTrans();
}

$SESSION->redirect($_SERVER['HTTP_REFERER']);
