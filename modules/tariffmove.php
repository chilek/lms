<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

$from = intval($_GET['from']);
$to = intval($_GET['to']);

if ($LMS->TariffExists($from) && $LMS->TariffExists($to) && $_GET['is_sure'] == 1) {
    $network = ((isset($_GET['netid']) && $LMS->NetworkExists($_GET['netid'])) ? $_GET['netid'] : null);

    if ($network) {
            $net = $LMS->GetNetworkParams($network);
    }
    
    if ($ids = $DB->GetCol('SELECT assignments.id AS id FROM assignments, customerview c '
            .($network ? 'LEFT JOIN nodes ON c.id = nodes.ownerid ' : '')
            .'WHERE customerid = c.id AND deleted = 0 AND tariffid = '.$from
            .($network ? ' AND ((ipaddr > '.$net['address'].' AND ipaddr < '.$net['broadcast'].') OR (ipaddr_pub > '
            .$net['address'].' AND ipaddr_pub < '.$net['broadcast'].')) ' : ''))) {
        foreach ($ids as $id) {
            $DB->Execute('UPDATE assignments SET tariffid=?
					WHERE id=? AND tariffid=?', array($to, $id, $from));
            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_ASSIGN => $id,
                    SYSLOG::RES_TARIFF => $to
                );
                $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);
            }
        }
    }

    $SESSION->redirect('?m=tariffinfo&id='.$to.($network ? '&netid='.$network : ''));
} else {
    header("Location: ?".$SESSION->get('backto'));
}
