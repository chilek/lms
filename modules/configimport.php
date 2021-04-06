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
$layout['pagetitle'] = trans('Import settings');
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

            $filePath = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
            $fileInfo = pathinfo($filePath);
            $fileExtension = $fileInfo['extension'];

            if (!$fileExtension) {
                $error['files'] = trans('No file extension!');
            } else {
                if ($fileExtension == 'ini') {
                    $parseResult = @parse_ini_file($tmppath . DIRECTORY_SEPARATOR . $file['name'], true);
                    if (!is_array($parseResult)) {
                        $error['files'] = trans('Bad file structure!');
                    }
                }
            }
        }
    }

    if (!$error && $config) {
        $db->BeginTrans();
        foreach ($files as $file) {
            $filePath = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
            $fileInfo = pathinfo($filePath);
            $fileExtension = $fileInfo['extension'];

            $lms->importConfigs(
                array(
                    'file' => $filePath,
                    'fileExtension' => $fileExtension,
                    'targetType' => $config['target-type'],
                    'withparentbindings' => (isset($config['withparentbindings']) ? intval($config['withparentbindings']) : null),
                    'targetUser' => (isset($config['target-user']) ? intval($config['target-user']) : null),
                    'targetDivision' => (isset($config['target-division']) ? intval($config['target-division']) : null),
                    'override' => (isset($config['override']) ? intval($config['override']) : null)
                )
            );
        }
        $db->CommitTrans();

        // deletes uploaded files
        if (!empty($tmppath)) {
            rrmdir($tmppath);
        }

        $SMARTY->clearAssign('fileupload');
        $SESSION->redirect('?m=configimport');
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
