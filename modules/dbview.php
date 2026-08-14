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

function readfile_chunked($filename, $retbytes = true)
{
    $chunksize = 1*(1024*1024); // how many bytes per chunk
    $buffer = '';
    $cnt = 0;
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        echo $buffer;
        flush();
        if ($retbytes) {
            $cnt += strlen($buffer);
        }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
        return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
}

if (!preg_match('/^[0-9]+-[0-9]+$/', $_GET['db'])) {
    die;
}

$filename = ConfigHelper::getConfig('directories.backup_dir') . DIRECTORY_SEPARATOR . 'lms-' . $_GET['db'] . '.sql';
$suffix = '';

header('Content-Type: application/octet-stream');
[$db_dump_date, $db_schema_date] = explode('-', $_GET['db']);
if (extension_loaded('zlib') && strstr($_GET['file'], 'sql.gz')) {
    $suffix = '.gz';
    $filename .= '.gz';
}
header('Content-Disposition: attachment; filename=lms-backup-' . date('Ymd-His', $db_dump_date) . '-' . $db_schema_date . '.sql' . $suffix);
header('Pragma: public');
header('Content-Length: '.filesize($filename));
set_time_limit(0);
readfile_chunked($filename, false);

return true;
