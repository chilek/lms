<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

if ($doc = $DB->GetRow('SELECT number, cdate, type, customerid, numberplans.template, extnumber
			FROM documents
			LEFT JOIN numberplans ON (numberplanid = numberplans.id)
			WHERE documents.id = ?', array($_GET['id']))) {
    $ntempl = docnumber(array(
        'number' => $doc['number'],
        'template' => $doc['template'],
        'customerid' => $doc['customerid'],
        'cdate' => $doc['cdate'],
        'ext_num' => $doc['extnumber'],
    ));

    switch ($doc['type']) {
        case DOC_INVOICE:
            $ntempl = trans('Invoice No. $a', $ntempl);
            break;
        case DOC_RECEIPT:
            $ntempl = trans('Cash Receipt No. $a', $ntempl);
            break;
        case DOC_CNOTE:
            $ntempl = trans('Credit Note No. $a', $ntempl);
            break;
        case DOC_DNOTE:
            $ntempl = trans('Debit Note No. $a', $ntempl);
            break;
    }

    $SMARTY->assign('content', $ntempl);
    $SMARTY->display('dynpopup.html');
}
