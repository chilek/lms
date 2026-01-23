<?php

/*
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

if ($doc = $DB->GetRow(
    'SELECT
        d.fullnumber,
        d.cdate,
        d.div_ten,
        kbs.environment,
        kbs.ksefnumber AS ksefbatchsessionnumber,
        kbs.lastupdate AS ksefbatchsessionlastupdate,
        kd.status,
        kd.hash,
        kd.ksefnumber
    FROM documents d
    JOIN ksefdocuments kd ON kd.docid = d.id AND kd.status IN ?
    JOIN ksefbatchsessions kbs ON kbs.id = kd.batchsessionid
    WHERE d.id = ?',
    [
        [
            0,
            200,
        ],
        $_GET['id'],
    ]
)) {
        $SMARTY->assign(
            'url',
            KSeF::getQrCodeUrl([
                'environment' => $doc['environment'],
                'ten' => preg_replace('/[^0-9]/', '', $doc['div_ten']),
                'date' => $doc['cdate'],
                'hash' => $doc['hash'],
            ])
        );
        $SMARTY->assign('invoice', $doc);
        $SMARTY->display('invoice/invoiceksefinfo.html');
}
