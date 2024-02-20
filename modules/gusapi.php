<?php

/**
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

if (!isset($_GET['searchtype']) || empty($_GET['searchdata'])) {
    return;
}

if (!in_array(
    $_GET['searchtype'],
    array(
        Utils::GUS_REGON_API_SEARCH_TYPE_TEN,
        Utils::GUS_REGON_API_SEARCH_TYPE_REGON,
        Utils::GUS_REGON_API_SEARCH_TYPE_RBE,
    )
)) {
    return;
}

$result = Utils::getGusRegonData($_GET['searchtype'], $_GET['searchdata']);

header('Content-Type: application/json');

if (is_int($result)) {
    switch ($result) {
        case Utils::GUS_REGON_API_RESULT_BAD_KEY:
            die(json_encode(array('error' => trans('Bad REGON API user key'))));
        case Utils::GUS_REGON_API_RESULT_NO_DATA:
            die(json_encode(array('warning' => trans("No data found in REGON database"))));
    }
} elseif (is_string($result)) {
    die(json_encode(array('error' => $result)));
} else {
    die(json_encode($result));
}
