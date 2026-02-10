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

if (!isset($_GET['id'])) {
    die;
}

$id = intval($_GET['id']);
if (empty($id)) {
    die;
}

$tags = $DB->GetAllByKey(
    'SELECT
        t.id,
        t.name
    FROM ksefinvoices i
    JOIN vdivisions d ON d.id = i.division_id
    JOIN ksefinvoicetagassignments a ON a.ksef_invoice_id = i.id
    JOIN ksefinvoicetags t ON t.id = a.ksef_invoice_tag_id
    WHERE i.id = ?',
    'id',
    [
        $id,
    ]
);
if (empty($tags)) {
    $tags = [];
}

$SMARTY->assign('id', $id);
$SMARTY->assign('invoice_tags', $tags);
$SMARTY->assign('tags', $DB->GetAll('SELECT * FROM ksefinvoicetags ORDER BY UPPER(name)'));

$SMARTY->display('ksef/ksefpurchaseinvoicetags.html');
