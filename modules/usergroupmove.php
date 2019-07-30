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

$from = intval($_GET['from']);
$to = intval($_GET['to']);

if ($LMS->UsergroupExists($from) && $LMS->UsergroupExists($to)) {
    if ($ids = $DB->GetCol('SELECT id, usergroupid FROM userassignments WHERE usergroupid = ' . $from)) {
        foreach ($ids as $id) {
            $DB->Execute('UPDATE userassignments SET usergroupid=?
					WHERE usergroupid=?', array($to, $from));
            if ($SYSLOG) {
                $args = array(
                    SYSLOG::RES_USERASSIGN => $id,
                    SYSLOG::RES_USERGROUP => $to
                );
                $SYSLOG->AddMessage(SYSLOG::RES_ASSIGN, SYSLOG::OPER_UPDATE, $args);
            }
        }
    }

    $SESSION->redirect('?m=usergroupinfo&id='.$to);
} else {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}
