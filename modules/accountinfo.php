<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2008 LMS Developers
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

$account = $DB->GetRow('SELECT p.id, p.ownerid, p.login, p.realname, 
		p.lastlogin, p.domainid, p.expdate, p.type, p.home, 
		p.quota_sh, p.quota_mail, p.quota_www, p.quota_ftp, p.quota_sql, '
		.$DB->Concat('c.lastname', "' '", 'c.name').' 
		AS customername, d.name AS domain 
		FROM passwd p
		JOIN domains d ON (p.domainid = d.id)
		LEFT JOIN customers c ON (c.id = p.ownerid)
		WHERE p.id = ?', array(intval($_GET['id'])));

if(!$account)
{
	$SESSION->redirect('?'.$SESSION->get('backto'));
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
    
$layout['pagetitle'] = trans('Account Info: $0', $account['login'].'@'.$account['domain']);

$SMARTY->assign('account', $account);
$SMARTY->display('accountinfo.html');

?>
