<?php

/**
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

$layout['pagetitle'] = trans('Document send');

$SMARTY->display('header.html');

if (!isset($_GET['sent'], $_SERVER['HTTP_REFERER']) && !preg_match('/m=documentsend/', $_SERVER['HTTP_REFERER'])) {
    set_time_limit(0);

    echo '<H1>' . $layout['pagetitle'] . '</H1>';

    if (isset($_POST['marks'])) {
        $docids = $DB->GetCol(
            "SELECT id
            FROM documents
            WHERE id IN (" . implode(',', Utils::filterIntegers(array_values($_POST['marks']))) . ")"
        );
    } elseif (isset($_GET['id']) && intval($_GET['id'])) {
        $docids = array(intval($_GET['id']));
    }

    if (empty($docids)) {
        echo '<span class="red">' . trans("Fatal error: No documents were selected!") . '</span><br>';
    } else {
        $docs = $DB->GetAll(
            "SELECT
                d.id,
                d.type,
                d.customerid,
                d.name,
                m.email,
                p.phone
            FROM documents d
            JOIN (
                SELECT
                    customerid, "
                    . $DB->GroupConcat('contact') . " AS email
                FROM customercontacts
                WHERE (type & ?) = ?
                GROUP BY customerid
            ) m ON m.customerid = d.customerid
            LEFT JOIN (
                SELECT
                    customerid, "
                     . $DB->GroupConcat('contact') . " AS phone
                FROM customercontacts
                WHERE (type & ?) = ?
                GROUP BY customerid
            ) p ON p.customerid = d.customerid
            WHERE d.id IN ?
            ORDER BY d.id",
            array(
                CONTACT_EMAIL | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                CONTACT_EMAIL | CONTACT_DOCUMENTS,
                CONTACT_MOBILE | CONTACT_DOCUMENTS | CONTACT_DISABLED,
                CONTACT_MOBILE | CONTACT_DOCUMENTS,
                $docids,
            )
        );

        if (!empty($docs)) {
            $currtime = time();
            if (!isset($quiet)) {
                $quiet = false;
            }
            if (!isset($test)) {
                $test = false;
            }
            $LMS->SendDocuments(
                $docs,
                'frontend',
                compact(
                    'currtime',
                    'quiet',
                    'test'
                )
            );
        }
    }

    echo '<script type="text/javascript">';
    echo "history.replaceState({}, '', location.href.replace(/&(is_sure|sent)=1/gi, '') + '&sent=1');";
    echo '</script>';
}

$SMARTY->display('footer.html');
