<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

$db = LMSDB::getInstance();
$lms = LMS::getInstance();
$layout['pagetitle'] = trans('Import configuration');
$error = null;
$config = array();
if (isset($_POST['config'])) {
    $config = $_POST['config'];
}

check_file_uploads();

if (isset($_POST['fileupload'])) {
    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (empty($files)) {
        $error['files'] = trans('No files selected!');
    } else {
        foreach ($files as $file) {
            if (strpos(mime_content_type($tmppath . DIRECTORY_SEPARATOR . $file['name']), 'text') !== 0) {
                $error['files'] = trans('Non plain text file detected!');
            }

            $parseResult = @parse_ini_file($tmppath . DIRECTORY_SEPARATOR . $file['name'], true);
            if (!is_array($parseResult)) {
                $error['files'] = trans('Bad file structure!');
            }
        }
    }

    if (!$error && isset($_POST['config'])) {
        $db->BeginTrans();
        foreach ($files as $file) {
            $filename = $file['name'];
            $filecontent = file_get_contents($tmppath . DIRECTORY_SEPARATOR . $filename);

            $filePath = $tmppath . DIRECTORY_SEPARATOR . $filename;
            $lms->importConfigs(
                array(
                    'file' => $filePath,
                    'targetType' => $_POST['config']['target-type'],
                    'withparentbindings' => (isset($_POST['config']['withparentbindings']) ? intval($_POST['config']['withparentbindings']) : null),
                    'targetUser' => (isset($_POST['config']['target-user']) ? intval($_POST['config']['target-user']) : null),
                    'targetDivision' => (isset($_POST['config']['target-division']) ? intval($_POST['config']['target-division']) : null),
                    'override' => (isset($_POST['config']['override']) ? intval($_POST['config']['override']) : null)
                )
            );
        }
        $db->CommitTrans();

        // deletes uploaded files
        if (!empty($tmppath)) {
            rrmdir($tmppath);
        }

        $SMARTY->clearAssign('fileupload');
        $SESSION->redirect('?m=configlist');
    }
} elseif (isset($_FILES['file'])) { // upload errors
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

$SMARTY->assign('users', $lms->getUsers(array('superuser' => 1)));
$SMARTY->assign('sections', $lms->GetConfigSections());
$SMARTY->assign('divisions', $lms->GetDivisions());
$SMARTY->assign('error', $error);
$SMARTY->assign('config', $config);
$SMARTY->display('config/configimport.html');
