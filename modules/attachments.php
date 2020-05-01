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

if (isset($_GET['type'])) {
    $attachmenttype = $_GET['type'];
}
if (!preg_match('/^[a-z0-9_]+$/', $attachmenttype)) {
    die;
}

switch ($attachmenttype) {
    case 'netdevid':
    case 'netdevmodelid':
    case 'netnodeid':
        if (!ConfigHelper::checkPrivilege('network_management')) {
            if (isset($_GET['type'])) {
                access_denied();
            } else {
                return;
            }
        }
        break;
    case 'messageid':
        if (!ConfigHelper::checkPrivilege('messaging')) {
            if (isset($_GET['type'])) {
                access_denied();
            } else {
                return;
            }
        }
        break;
}

if (isset($_GET['attachmentaction'])) {
    switch ($_GET['attachmentaction']) {
        case 'updatecontainer':
            header('Content-Type: application/json');
            if ($LMS->UpdateFileContainer(array(
                    'id' => $_GET['id'],
                    'description' => $_POST['description'],
                ))) {
                die('[]');
            } else {
                die(json_encode(array(
                    'error' => trans('Cannot update file container description!'),
                )));
            }
            break;
        case 'deletecontainer':
            $LMS->DeleteFileContainer($_GET['id']);
            break;
        case 'viewfile':
            $file = $LMS->GetFile($_GET['fileid']);
            if (empty($file)) {
                die;
            }

            header('Content-Type: ' . $file['contenttype']);
            if (!preg_match('/^text/i', $file['contenttype'])) {
                $pdf = preg_match('/pdf/i', $file['contenttype']);
                if (!isset($_GET['save'])) {
                    if ($pdf) {
                        header('Content-Disposition: inline; filename="'.$file['filename'] . '"');
                        header('Content-Transfer-Encoding: binary');
                        header('Content-Length: ' . filesize($file['filepath']) . ' bytes');
                    } else {
                        if (isset($_GET['thumbnail']) && ($width = intval($_GET['thumbnail'])) > 0
                            && class_exists('Imagick') && strpos($file['contenttype'], 'image/') === 0) {
                            $imagick = new \Imagick($file['filepath']);
                            $imagick->scaleImage($width, 0);
                            echo $imagick->getImageBlob();
                            die;
                        } else {
                            header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
                        }
                    }
                } else {
                    header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
                }
                header('Pragma: public');
            }
            echo @file_get_contents($file['filepath']);
            die;
            break;

        case 'downloadzippedcontainer':
            $LMS->GetZippedFileContainer($_GET['id']);
            die;
            break;
    }
    if (isset($_GET['restore']) && !empty($_GET['restore'])) {
        $SESSION->redirect('?' . $SESSION->get('backto').'&restore=1&resourceid=' . $_GET['resourceid']);
    } else {
        $SESSION->redirect('?' . $SESSION->get('backto'));
    }
}

if (isset($_GET['resourceid'])) {
    $attachmentresourceid = $_GET['resourceid'];
}
if (!preg_match('/^[0-9]+$/', $attachmentresourceid)) {
    die;
}

if (isset($_POST['upload'])) {
    $uploaded_attachmenttype = $_POST['upload']['attachmenttype'];
    $files = 'files-' . $uploaded_attachmenttype;
    $result = handle_file_uploads($files, $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    $upload = $_POST['upload'];

    header('Content-Type: application/json');

    if (!$error) {
        $files = $result[$files];
        if (!empty($files)) {
            foreach ($files as &$file) {
                $file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
            }
            unset($file);
            $LMS->AddFileContainer(array(
                'description' => $upload['description'],
                'files' => $files,
                'type' => $uploaded_attachmenttype,
                'resourceid' => $attachmentresourceid,
            ));
        }

        // deletes uploaded files
        if (!empty($files)) {
            rrmdir($tmppath);
        }

        if (isset($upload['restore']) && !empty($upload['restore'])) {
            die(json_encode(array('url' => '?' . $SESSION->get('backto').'&restore=1&resourceid=' . $attachmentresourceid)));
        } else {
            die(json_encode(array('url' => '?' . $SESSION->get('backto'))));
        }
    }

    die('{}');
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
