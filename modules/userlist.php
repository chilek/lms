<? // $Id$

/*
 * LMS version 1.0-cvs
 *
 *  (C) Copyright 2002 Rulez.PL Development Team
 *  (C) Copyright 2001-2002 NetX ACN
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

$_SESSION[userdelfrom] = $QUERY_STRING;

$layout[pagetitle]="Lista u¿ytkowników";

if(!isset($_GET[o]))
	$o = $_SESSION[ulo];
else
	$o = $_GET[o];
$_SESSION[ulo] = $o;

if(!isset($_GET[s]))
	$s = $_SESSION[uls];
else
	$s = $_GET[s];
$_SESSION[uls] = $s;

if(isset($_GET[search]))
{
	$SMARTY->assign("search",TRUE);
	$search[username] = $_GET[username];
	$search[address] = $_GET[address];
	$search[phone1] = $_GET[phone];
	$search[phone2] = $_GET[phone];
	$search[phone3] = $_GET[phone];
	$search[email] = $_GET[email];
	$userlist=$LMS->GetUserList($o,$s,$search);
}else
	$userlist=$LMS->GetUserList($o,$s);

$SMARTY->assign("layout",$layout);
$SMARTY->assign("userlist",$userlist);
$SMARTY->assign("tariffs",$LMS->GetTariffs());


$SMARTY->display("header.html");
$SMARTY->display("userheader.html");
$SMARTY->display("userlist.html");
$SMARTY->display("footer.html");

?>
