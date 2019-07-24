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
    if (!$DB->GetOne('SELECT COUNT(*) FROM documents WHERE numberplanid=?', array($id))) {
        if ($SYSLOG) {
            $args = array(SYSLOG::RES_NUMPLAN => $id);
            $SYSLOG->AddMessage(SYSLOG::RES_NUMPLAN, SYSLOG::OPER_DELETE, $args);
            $assigns = $DB->GetAll('SELECT * FROM numberplanassignments WHERE planid = ?', array($id));
            if (!empty($assigns)) {
                foreach ($assigns as $assign) {
                    $args = array(
                    SYSLOG::RES_NUMPLANASSIGN => $assign['id'],
                    SYSLOG::RES_NUMPLAN => $assign['planid'],
                    SYSLOG::RES_DIV => $assign['divisionid']
                    );
                    $SYSLOG->AddMessage(SYSLOG::RES_NUMPLANASSIGN, SYSLOG::OPER_DELETE, $args);
                }
            }
        }
        $DB->Execute('DELETE FROM numberplanassignments WHERE planid=?', array($id));
        $DB->Execute('DELETE FROM numberplans WHERE id=?', array($id));
    }
}

$SESSION->redirect('?'.$SESSION->get('backto'));
