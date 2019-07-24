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
    if ($SYSLOG) {
        $config = $DB->GetRow('SELECT instanceid, hostid FROM daemonconfig c
			JOIN daemoninstances i ON i.id = c.instanceid WHERE c.id = ?', array($id));
        $args = array(
            SYSLOG::RES_DAEMONINST => $config['instanceid'],
            SYSLOG::RES_HOST => $config['hostid'],
            SYSLOG::RES_DAEMONCONF => $id
        );
        $SYSLOG->AddMessage(SYSLOG::RES_DAEMONCONF, SYSLOG::OPER_DELETE, $args);
    }
    $DB->Execute('DELETE FROM daemonconfig WHERE id = ?', array($id));
}

header('Location: ?'.$SESSION->get('backto'));
