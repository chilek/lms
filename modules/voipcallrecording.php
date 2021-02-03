<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

define('VOIP_CALL_DIR', ConfigHelper::getConfig(
    'voip.call_recording_directory',
    SYS_DIR . DIRECTORY_SEPARATOR . 'voipcalls'
));

$cdr = $DB->GetRow("SELECT uniqueid, caller, callee, call_start_time, totaltime FROM voip_cdr WHERE id = ?", array($_GET['id']));
$filename = $cdr['uniqueid'];
$filepath = VOIP_CALL_DIR . DIRECTORY_SEPARATOR . $filename;
$out_filename = sprintf(
    '%s_%s_%s_%d',
    date('Y-m-d_H:i:s', $cdr['call_start_time']),
    $cdr['caller'],
    $cdr['callee'],
    $cdr['totaltime']
);

if (empty($filename)) {
    die;
}

if (is_readable($filepath . '.mp3')) {
    $ext = '.mp3';
} elseif (is_readable($filepath . '.ogg')) {
    $ext = '.ogg';
} else {
    $ext = '.wav';
}

$filepath .= $ext;
header('Content-Type: ' . mime_content_type($filepath));

if (isset($_GET['download'])) {
    $out_filename .= $ext;
    header('Content-Disposition: attachment; filename="' . $out_filename . '"');
}

echo file_get_contents($filepath);
die;
