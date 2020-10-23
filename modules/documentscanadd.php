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

$scans = array();

if (isset($_POST['documentscans'])) {
    $files = array();

    $result = handle_file_uploads('attachments', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            if (strpos($attachment['type'], 'image') !== 0
                && strpos($attachment['type'], 'pdf') === false) {
                continue;
            }
            $attachment['tmpname'] = $tmppath . DIRECTORY_SEPARATOR . $attachment['name'];
            $attachment['filename'] = $attachment['name'];
            $attachment['md5sum'] = md5_file($attachment['tmpname']);
            $attachment['attachmenttype'] = 0;
            $files[] = $attachment;
        }
    }

    if (empty($files)) {
        $error['files'] = trans('No image files!');
    }

    if (isset($_POST['documents'])) {
        foreach ($files as $file) {
            if (isset($_POST['documents'][$file['filename']])) {
                $docid = $_POST['documents'][$file['filename']];
                if ($LMS->isDocumentAccessible($docid)) {
                    $LMS->AddDocumentFileAttachments($files);
                    $LMS->AddDocumentAttachments($docid, $files);
                }
            }
        }
        $SESSION->redirect('?m=documentscanadd');
    } elseif (!empty($files)) {
        if (!class_exists('Imagick')) {
            die(trans('No PHP Imagick extension installed'));
        }

        $zbarDecoder = new \RobbieP\ZbarQrdecoder\ZbarDecoder();
        $image = new \Imagick();

        foreach ($files as &$file) {
            $image->setResolution(300, 300);
            $image->readImage($file['tmpname'] . '[0]');
            $image->setIteratorIndex(0);
            $image->writeImage($file['tmpname'] . '.png');

            $result = $zbarDecoder->make($file['tmpname'] . '.png');

            @unlink($file['tmpname'] . '.png');

            if ($result->code == 200) {
                $file['fullnumber'] = $result->text;
                $file['documents'] = $LMS->getDocumentsByFullNumber($result->text);
            }
        }
        unset($file);

        $SMARTY->assign('files', $files);
    }
}

$layout['pagetitle'] = trans('Add Scans');

$SMARTY->assign('error', $error);
$SMARTY->assign('scans', $scans);
$SMARTY->display('document/documentscanadd.html');
