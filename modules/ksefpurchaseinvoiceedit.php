<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

use \Lms\KSeF\KSeF;

header('Content-type: application/json');

if (!isset($_POST['id'], $_POST['action'])) {
    die('[]');
}

$id = intval($_POST['id']);
if (empty($id)) {
    die(json_encode([
        'error' => 'id parameter validation error',
    ]));
}

switch ($_POST['action']) {
    case 'restore':
    case 'ignore':
        $res = $DB->Execute(
            'UPDATE ksefinvoices
            SET posting = ?
            WHERE id = ?',
            [
                (int)($_POST['action'] == 'restore'),
                $id,
            ]
        );
        if (empty($res)) {
            die(json_encode([
                'error' => 'SQL error',
            ]));
        }
        break;
    case 'settle':
    case 'unsettle':
        die('[]');
        break;
    default:
        die(json_encode([
            'error' => 'unsupported action',
        ]));
}

die('[]');
