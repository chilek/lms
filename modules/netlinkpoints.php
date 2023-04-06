<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2023 LMS Developers
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

header('Content-Type: application/json');

if (!isset($_GET['netlinkid'], $_GET['srcdevid'], $_GET['dstdevid'], $_GET['points'])) {
    die('Missed some request parameters!');
}

$netlinkid = intval($_GET['netlinkid']);
$srcdevid = intval($_GET['srcdevid']);
$dstdevid = intval($_GET['dstdevid']);
$points = json_decode($_GET['points'], true);

if (empty($netlinkid) || empty($points) || !is_array($points)) {
    die('Some request parameters have invalid format!');
}

$DB->BeginTrans();

$DB->Execute('DELETE FROM netlinkpoints WHERE netlinkid = ?', array($netlinkid));

$points = array_slice($points, 1, count($points) - 2);

foreach ($points as $point) {
    $DB->Execute(
        'INSERT INTO netlinkpoints (netlinkid, longitude, latitude) VALUES (?, ?, ?)',
        array(
            $netlinkid,
            str_replace(',', '.', $point['lon']),
            str_replace(',', '.', $point['lat']),
        )
    );
}

$DB->CommitTrans();

die('[]');
