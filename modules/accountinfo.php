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

// LEFT join with domains for bckward compat.
$account = $DB->GetRow('SELECT p.*, d.name AS domain, '
        .$DB->Concat('c.lastname', "' '", 'c.name').' AS customername 
		FROM passwd p
		LEFT JOIN domains d ON (p.domainid = d.id)
		LEFT JOIN customers c ON (c.id = p.ownerid)
		WHERE p.id = ?', array(intval($_GET['id'])));

if (!$account) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$account['aliases'] = $DB->GetAll('SELECT a.id, a.login, d.name AS domain 
		FROM aliases a JOIN domains d ON (a.domainid = d.id)
		WHERE a.id IN (SELECT aliasid FROM aliasassignments
			WHERE accountid = ?)', array($account['id']));

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
    
$layout['pagetitle'] = trans('Account Info: $a', $account['login'].'@'.$account['domain']);

$SMARTY->assign('account', $account);
$SMARTY->display('account/accountinfo.html');
