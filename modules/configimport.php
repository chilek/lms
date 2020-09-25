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

check_file_uploads();

if (isset($_POST['fileupload'])) {
    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (!isset($_GET['target-type']) || empty($_GET['target-type'])) {
        $error['type'] = trans('Target variables type has not been selected!');
        include(MODULES_DIR . DIRECTORY_SEPARATOR . 'configlist.php');
        die;
    }

    if (empty($files)) {
        $error['files'] = trans('No files selected!');
    } elseif (count($files) > 1) {
        $error['files'] = trans('Too many files selected!');
    } else {
        $file = $files[0];
        if (strpos(mime_content_type($tmppath . DIRECTORY_SEPARATOR . $file['name']), 'text') !== 0) {
            $error['files'] = trans('Non plain text file detected!');
        }
        if (isset($error['files'])) {
            include(MODULES_DIR . DIRECTORY_SEPARATOR . 'configlist.php');
            die;
        }
    }

    $filename = $file['name'];
    $filecontent = file_get_contents($tmppath . DIRECTORY_SEPARATOR . $filename);

    $filePath = $tmppath . DIRECTORY_SEPARATOR . $filename;
    $DB->BeginTrans();
    $LMS->importConfigs(
        array(
            'file' => $filePath,
            'targetType' => $_GET['target-type'],
            'withparentbindings' => (isset($_GET['withparentbindings']) ? intval($_GET['withparentbindings']) : null),
            'targetUser' => (isset($_GET['target-user']) ? intval($_GET['target-user']) : null),
            'targetDivision' => (isset($_GET['target-division']) ? intval($_GET['target-division']) : null),
            'override' => (isset($_GET['override']) ? intval($_GET['override']) : null)
        )
    );
    $DB->CommitTrans();

    // deletes uploaded files
    if (!empty($tmppath)) {
        rrmdir($tmppath);
    }

    $SMARTY->clearAssign('fileupload');

    include(MODULES_DIR . DIRECTORY_SEPARATOR . 'configlist.php');
    die;
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
