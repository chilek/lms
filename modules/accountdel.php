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

$id = $_GET['id'];

if ($id) {
    $DB->BeginTrans();

    if ($DB->Execute('DELETE FROM passwd WHERE id = ?', array($id))) {
        // alias-account assignments...
        $DB->Execute('DELETE FROM aliasassignments WHERE accountid = ?', array($id));
        // ...and orphaned aliases
        $DB->Execute('DELETE FROM aliases 
			WHERE NOT EXISTS (SELECT 1 FROM aliasassignments 
				WHERE aliasid = aliases.id)');
    }

    $DB->CommitTrans();
}

header('Location: ?m=accountlist');
