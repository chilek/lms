<?php

/*
 *  LMS version 1.11-git
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

$USERPANEL->AddModule(
    trans('Documents'),    // Display name
    'documents',         // Module name - must be the same as directory name
    trans('Document management'), // Tip
    15,         // Priority
    trans('This module allows to manage documents'),   // Description
    array(      // Array of submenus in LMS
        array(
            'name' => trans('Documents to review'),
            'link' => '?m=documentlist&s=2',
            'tip' => trans('Documents which require your review'),
            'prio' => 40,
        ),
    ),
    'lms-userpanel-documents'
);

require_once('UserpanelDocumentHandler.php');
$document_handler = new UserpanelDocumentHandler($DB, $SMARTY, $SESSION->id);

$USERPANEL->registerCallback('documents', function ($db, $smarty, $mod_dir) use ($document_handler) {
    $document_warnings = $document_handler->getDocumentWarnings();
    if (empty($document_warnings)) {
        return '';
    }
    $smarty->assign('document_warnings', $document_warnings);

    global $module_dir;
    $old_module_dir = $module_dir;
    $module_dir = $mod_dir;

    $html = $smarty->fetch('module:documents-callback-handler.html');

    $module_dir = $old_module_dir;

    return $html;
});
