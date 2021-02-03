<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$domain = $DB->GetRow('SELECT d.id, d.name, d.ownerid, d.description, d.mxbackup, d.type,
		(SELECT COUNT(*) FROM passwd WHERE domainid = d.id) AS accountcnt, 
		(SELECT COUNT(*) FROM records WHERE domain_id = d.id) AS recordscnt,
		(SELECT COUNT(*) FROM aliases WHERE domainid = d.id) AS aliascnt, '
        .$DB->Concat('lastname', "' '", 'c.name').' AS customername
		FROM domains d
		LEFT JOIN customers c ON (d.ownerid = c.id)
		WHERE d.id = ?', array($id));

if (!$domain) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$layout['pagetitle'] = trans('Domain Info: $a', $domain['name']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('domain', $domain);
$SMARTY->display('domain/domaininfo.html');
