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

$passwd = $_POST[passwd];

$id = (isset($_GET[id])) ? $_GET[id] : $SESSION->id;

if($LMS->AdminExists($id))
{
	if(isset($passwd))
	{
		if($passwd[passwd] == "" || $passwd[confirm] == "")
			$error[password] .= "Has�o nie mo�e by� puste!<BR>";
		
		if($passwd[passwd] != $passwd[confirm])
			$error[password] .= "Podane has�a si� r�ni�!";
		
		if(!$error)
		{
			$LMS->SetAdminPassword($id,$passwd[passwd]);
			header("Location: ?m=welcome");
		}
	}

	$passwd[realname] = $LMS->GetAdminName($id);
	$passwd[id] = $id;
	$layout[pagetitle]="Zmiana has�a";
	$SMARTY->assign("layout",$layout);
	$SMARTY->assign("error",$error);
	$SMARTY->assign("passwd",$passwd);
	$SMARTY->display("adminpasswd.html");

}
else
{
	header("Location: ?m=".$_SESSION[lastmodule]);
	exit(0);
}

?>
