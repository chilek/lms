#!/usr/bin/env php
<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2024 LMS Developers
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

$script_parameters = array(
    'from:' => 'f:',
    'to:' => 't:',
    'customerid:' => null,
);

$script_help = <<<EOF
-f, --from=<YYYY/MM/DD>         time period start;
-t, --to=<YYYY/MM/DD>           time period end;
    --customerid=<id>           narrow assigned to specifed customer
EOF;

require_once('script-options.php');

$SYSLOG = SYSLOG::getInstance();

// Initialize Session, Auth and LMS classes

$SYSLOG = null;
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);

$customerid = isset($options['customerid']) && intval($options['customerid']) ? $options['customerid'] : null;

if (isset($options['from'])) {
    [$year, $month, $day] = explode('/', $options['from']);
    $from = mktime(0, 0, 0, $month, $day, $year);
} else {
    $from = mktime(0, 0, 0);
}

if (isset($options['to'])) {
    [$year, $month, $day] = explode('/', $options['to']);
    $to = mktime(23, 59, 59, $month, $day, $year) + 1;
} else {
    $to = mktime(23, 59, 59) + 1;
}

$docids = $DB->GetCol(
    "SELECT id
    FROM documents
    WHERE " . ($customerid ? 'customerid = ' . $customerid . ' AND ' : '') . "
    type < 0 AND cdate >= ? AND cdate < ?",
    array($from, $to)
);

if (empty($docids)) {
    if ($quiet) {
        die;
    } else {
        die('No doucments to archive!' . PHP_EOL);
    }
}

if (!$quiet) {
    echo 'Archiving ' . count($docids) . ' documents ...' . PHP_EOL;
}

$LMS->ArchiveDocuments($docids);
