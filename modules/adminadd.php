<?

/*
 * LMS version 1.0-cvs
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

$adminadd = $_POST[adminadd];

if(isset($adminadd))
{
	foreach($adminadd as $key => $value)
		$adminadd[$key] = trim($value);
	
	if($adminadd[login]==""&&$adminadd[name]==""&&$adminadd[password]==""&&$adminadd[confirm]=="")
	{
		header("Location: ?m=adminadd");
		exit(0);
	}
	
	if($LMS->GetAdminIDByLogin($adminadd[login]))
		$error[login] = "Podany login istnieje!";
	elseif(!eregi("^[a-z0-9.-_]+$",$adminadd[login]))
		$error[login] = "Login zawiera niepoprawne znaki!";

	if($adminadd[email]!="" && !eregi("^[0-9a-z\-_\.]+@[0-9a-z\-_\.]+\.[a-z]{2,}$",$adminadd[email]))
		$error[email] = "Podany email nie wydaje siê byæ poprawny!";

	if($adminadd[password]=="")
		$error[password] = "Has³o nie mo¿e byæ puste!";
	elseif($adminadd[password]!=$adminadd[confirm])
		$error[password] = "Has³a nie s± takie same!";
	
	if(!$error)
	{
		header("Location: ?m=adminedit&id=".$LMS->AdminAdd($adminadd));
		exit(0);
	}
}

$layout[pagetitle]="Dodaj nowego administratora";
$SMARTY->assign("layout",$layout);
$SMARTY->assign("adminadd",$adminadd);
$SMARTY->assign("error",$error);
$SMARTY->display("adminadd.html");

?>
