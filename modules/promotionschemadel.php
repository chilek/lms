<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

$id = intval($_GET['id']);
$promotionid = $DB->GetOne(
    'SELECT promotionid FROM promotionschemas
    WHERE id = ?',
    array($id)
);

if ($SYSLOG) {
    $args = array(
        SYSLOG::RES_PROMOSCHEMA => $id,
        SYSLOG::RES_PROMO => $promotionid,
        'deleted' => 1,
    );
    $SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_UPDATE, $args);
    $assigns = $DB->GetAll(
        'SELECT id, tariffid FROM promotionassignments WHERE promotionschemaid = ?',
        array($id)
    );
    if (!empty($assigns)) {
        foreach ($assigns as $assign) {
            $args[SYSLOG::RES_PROMOASSIGN] = $assign['id'];
            $args[SYSLOG::RES_TARIFF] = $assign['tariffid'];
            $SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
        }
    }
}
$DB->Execute('UPDATE promotionschemas SET deleted = 1 WHERE id = ?', array($id));

$SESSION->redirect('?m=promotioninfo&id=' . $promotionid);
