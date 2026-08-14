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

$promotion = $DB->GetRow(
    'SELECT
        p.id, p.name, p.description, p.disabled, p.datefrom, p.dateto, p.deleted,
        COUNT(a.id) AS assignments
    FROM promotions p
    LEFT JOIN promotionschemas s ON s.promotionid = p.id
    LEFT JOIN assignments a ON a.promotionschemaid = s.id    
    WHERE p.id = ?
    GROUP BY p.id, p.name, p.description, p.disabled, p.datefrom, p.dateto, p.deleted',
    array(intval($_GET['id']))
);

if (!$promotion) {
    $SESSION->redirect('?m=promotionlist');
}

$promotion['attachments'] = $DB->GetAllByKey(
    'SELECT *
    FROM promotionattachments
    WHERE promotionid = ?',
    'id',
    array(
        $_GET['id'],
    )
);

$promotion['schemas'] = $DB->GetAllByKey(
    'SELECT
        s.name, s.disabled, s.description, s.id, s.deleted, s.datefrom, s.dateto,
        COUNT(a.id) AS assignments
    FROM promotionschemas s
    LEFT JOIN assignments a ON a.promotionschemaid = s.id
    WHERE s.promotionid = ?
    GROUP BY s.name, s.disabled, s.description, s.id, s.deleted
    ORDER BY name',
    'id',
    array($promotion['id'])
);

if (!empty($promotion['schemas'])) {
    $schemas = implode(', ', array_keys($promotion['schemas']));
    $promotion['tariffs'] = $DB->GetAll(
        'SELECT
            t.name, t.id, t.type, t.value, t.netvalue, t.flags, t.upceil, t.downceil
        FROM tariffs t
        WHERE t.id IN (SELECT DISTINCT tariffid
            FROM promotionassignments
            WHERE promotionschemaid IN ('.$schemas.')
        )
        ORDER BY t.name, t.value, t.netvalue DESC'
    );
}

$layout['pagetitle'] = trans('Promotion Info: $a', $promotion['name']);

$SESSION->add_history_entry();

$SMARTY->assign('promotion', $promotion);
$SMARTY->display('promotion/promotioninfo.html');
