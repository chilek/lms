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
    if (!$DB->GetOne('SELECT 1 FROM nodegroupassignments WHERE nodegroupid = ? 
		LIMIT 1', array($id))) {
        $DB->BeginTrans();
        $DB->Execute('DELETE FROM nodegroups WHERE id = ?', array($id));
//      $DB->Execute('DELETE FROM nodegroupassignments WHERE nodegroupid = ?', array($id));
        if ($SYSLOG) {
            $SYSLOG->AddMessage(
                SYSLOG::RES_NODEGROUP,
                SYSLOG::OPER_DELETE,
                array(SYSLOG::RES_NODEGROUP => $id)
            );
        }
        $DB->CommitTrans();
        $LMS->CompactNodeGroups();
    }
}

$SESSION->redirect('?m=nodegrouplist');
