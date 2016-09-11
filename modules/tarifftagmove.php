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

if ($LMS->TarifftagExists($from) && $LMS->TarifftagExists($to) && $_GET['is_sure'] == 1) {
    if ($ids = $DB->GetCol('SELECT id, tariffid FROM tariffassignments WHERE tarifftagid = ' . $from)) {
        foreach ($ids as $id) {
            $DB->Execute('UPDATE tariffassignments SET tarifftagid=?
					WHERE tarifftagid=?', array($to, $from));
            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_TARIFFASSIGN => $id,
                    SYSLOG::RES_TARIFFTAG => $to
                );
                $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);
            }
        }
    }

    $SESSION->redirect('?m=tarifftaginfo&id=' . $to);
} else {
    header("Location: ?" . $SESSION->get('backto'));
}
?>
