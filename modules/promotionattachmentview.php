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

$attachmentid = isset($_GET['attachmentid']) && is_numeric($_GET['attachmentid']) ? intval($_GET['attachmentid']) : null;
if (empty($attachmentid)) {
    die;
}

$attachment = $DB->GetRow(
    'SELECT *
    FROM promotionattachments
    WHERE id = ?',
    array($attachmentid)
);
if (empty($attachment)) {
    die;
}

if (isset($_GET['promotionid'])) {
    $file = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotions' . DIRECTORY_SEPARATOR
        . $attachment['promotionid'] . DIRECTORY_SEPARATOR . $attachment['filename'];
} else {
    $file = STORAGE_DIR . DIRECTORY_SEPARATOR . 'promotionschemas' . DIRECTORY_SEPARATOR
        . $attachment['promotionschemaid'] . DIRECTORY_SEPARATOR . $attachment['filename'];
}
if (file_exists($file)) {
    if (isset($_GET['thumbnail']) && ($width = intval($_GET['thumbnail'])) > 0
        && class_exists('Imagick') && strpos($attachment['contenttype'], 'image/') === 0) {
        $imagick = new \Imagick($file);
        $imagick->scaleImage($width, 0);
        header('Content-Type: ' . $attachment['contenttype']);
        header('Cache-Control: private');
        header('Content-Disposition: ' . ($attachment['contenttype'] == 'application/pdf' ? 'inline' : 'attachment') . '; filename="' . $attachment['filename'] . '"');
        echo $imagick->getImageBlob();
    } else {
        $office2pdf_command = ConfigHelper::getConfig('documents.office2pdf_command', '', true);

        if (!empty($office2pdf_command) && !empty($_GET['preview-type']) && $_GET['preview-type'] == 'office') {
            $filename = $attachment['filename'];
            $i = strpos($filename, '.');
            if ($i !== false) {
                $extension = mb_substr($filename, $i + 1);
                if (preg_match('/^(odt|ods|doc|docx|xls|xlsx|rtf)$/i', $extension)) {
                    $extension = 'pdf';
                }
                $filename = mb_substr($filename, 0, $i) . '.' . $extension;
            }

            header('Content-Type: application/pdf');
            header('Cache-Control: private');
            header('Content-Disposition: inline; filename="' . $filename . '"');

            echo Utils::office2pdf(array(
                'content' => file_get_contents($file),
                'subject' => trans('Document'),
                'doctype' => Utils::docTypeByMimeType($attachment['contenttype']),
                'dest' => 'S',
            ));
        } else {
            header('Content-Type: ' . $attachment['contenttype']);
            header('Cache-Control: private');
            header('Content-Disposition: ' . ($attachment['contenttype'] == 'application/pdf' ? 'inline' : 'attachment') . '; filename="' . $attachment['filename'] . '"');
            echo @file_get_contents($file);
        }
    }
}
