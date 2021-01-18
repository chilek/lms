<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

if ($id) {
    $assignments = $DB->GetOne(
        'SELECT COUNT(a.id) FROM assignment a
        JOIN promotionschemas s ON s.id = a.promotionschemaid
        WHERE s.promotionid = ?',
        array($id)
    );

    if (empty($assignments)) {
        if ($SYSLOG) {
            $args = array(
                SYSLOG::RES_PROMO => $id,
            );
            $SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_DELETE, $args);
        }
    } elseif ($SYSLOG) {
        $args = array(
            SYSLOG::RES_PROMO => $id,
            'deleted' => 1,
        );
        $SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_UPDATE, $args);
    }
    if ($SYSLOG) {
        unset($args['deleted']);
        $schemas = $DB->GetAll(
            'SELECT s.id, COUNT(a.id) AS assignments
            FROM promotionschemas s
            LEFT JOIN assignments a ON a.promotionschemaid = s.id
            WHERE s.promotionid = ?
            GROUP BY s.id',
            array_values($args)
        );
        if (!empty($schemas)) {
            foreach ($schemas as $schema) {
                $args[SYSLOG::RES_PROMOSCHEMA] = $schema['id'];
                if (empty($schema['assignments'])) {
                    $SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_DELETE, $args);
                } else {
                    $SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_UPDATE, $args);
                }
                $assigns = $DB->GetCol(
                    'SELECT id FROM promotionassignments
                    WHERE promotionschemaid = ?',
                    array($schema['id'])
                );
                if (!empty($assigns)) {
                    foreach ($assigns as $assign) {
                        $args[SYSLOG::RES_PROMOASSIGN] = $assign;
                        $SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
                    }
                }
            }
        }
    }
    if (empty($assignments)) {
        $DB->Execute('DELETE FROM promotions WHERE id = ?', array($id));
    } else {
        $DB->Execute('UPDATE promotions SET deleted = 1 WHERE id = ?', array($id));
    }
}

$SESSION->redirect('?m=promotionlist');
