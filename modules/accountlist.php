<?php

/*
 * LMS version 1.5-cvs
 *
 *  (C) Copyright 2001-2004 LMS Developers
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

$layout['pagetitle'] = 'Zarz±dzanie kontami';

$accountlist = $LMS->DB->GetAll('SELECT passwd.id, passwd.ownerid, login, passwd.lastlogin, users.name, users.lastname FROM passwd, users WHERE users.id = passwd.ownerid ORDER BY name, lastname, login');
$listdata['total'] = sizeof($accountlist);

$_SESSION['backto'] = $_SERVER['QUERY_STRING'];

$SMARTY->assign('accountlist',$accountlist);
$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('layout',$layout);
$SMARTY->display('accountlist.html');

?>
