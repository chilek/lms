<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

header('Content-Type: text/plain');

if (isset($_GET['countryid']) && !empty($_GET['countryid']) && $LMS->GetCountryName($_GET['countryid']) != 'Poland') {
    die;
}

if (!isset($_GET['house']) || (!isset($_GET['city']) && !isset($_GET['cityid']))) {
    die;
}

if (isset($_GET['cityid']) && !empty($_GET['cityid'])) {
    $params['cityid'] = $_GET['cityid'];
    if (isset($_GET['streetid']) && !empty($_GET['streetid'])) {
        $params['streetid'] = $_GET['streetid'];
    } else {
        die;
    }
} elseif (isset($_GET['city']) && !empty($_GET['city'])) {
    $params['city'] = $_GET['city'];
    if (isset($_GET['street']) && !empty($_GET['street'])) {
        $params['street'] = $_GET['street'];
    } else {
        die;
    }
}
if (empty($_GET['house'])) {
    die;
}
$params['house'] = $_GET['house'];

echo $LMS->GetZipCode($params);
