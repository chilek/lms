<?

/*
 * LMS version 1.3-cvs
 *
 *  (C) Copyright 2001-2003 LMS Developers
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

include_once('class.php');

$loginform = $_POST[loginform];
$login = $loginform[login];
$passwd = $loginform[pwd];

$id = $LMS->DB->GetOne('SELECT id FROM users WHERE phone1 = ? AND pin = ?', array($login, $passwd));

$SMARTY->assign('user',$LMS->GetUser($id));
$SMARTY->assign('userinfo',$LMS->GetUser($id));
$SMARTY->assign('balancelist',$LMS->GetUserBalanceList($id));
$SMARTY->assign('layout',$layout);
$SMARTY->assign('limit',15);

$SMARTY->display('balanceview.html');

?>
