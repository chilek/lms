<?

/*
 * LMS version 1.4-cvs
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

$layout['pagetitle'] = 'Panel u¿ytkownika';

include_once('class.php');
include_once('authentication.inc');

$loginform = $_POST['loginform'];
$login = ($loginform['login'] ? $loginform['login'] : 0);
$pin = ($loginform['pwd'] ? $loginform['pwd'] : 0);

//sposoby autoryzacji u¿ytkownika
$id = GetUserIDByPhone1AndPIN($login, $pin);
//$id = GetUserIDByContractAndPIN($login, $pin);
//$id = GetUserIDByIDAndPIN($login, $pin);

if($id)
{
	$SMARTY->assign('user',$LMS->GetUser($id));
	$SMARTY->assign('userinfo',$LMS->GetUser($id));
	$SMARTY->assign('balancelist',$LMS->GetUserBalanceList($id));
	$SMARTY->assign('limit',15);
	$SMARTY->display('balanceview.html');
}
else
	header('Location: index.php?error=1');

?>
