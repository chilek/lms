<?

/*
 * LMS version 1.1-cvs
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

$_SESSION[backto] = $_SERVER[QUERY_STRING];

$layout[pagetitle]="Lista u�ytkownik�w zad�u�onych";
$layout[ultitle]=" zad�u�onych";

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

if (isset($_SESSION[ulp]) && !isset($_GET[page]))
	$_GET[page] = $_SESSION[ulp];
	    

$userlist=$LMS->GetUserList($o,$s);
$listdata[state] = $userlist[state];
$listdata[order] = $userlist[order];
$listdata[direction] = $userlist[direction];

$_SESSION[ulp] = $page;

unset($userlist[total]);
unset($userlist[state]);
unset($userlist[order]);
unset($userlist[below]);
unset($userlist[over]);
unset($userlist[direction]);
// $listdata total

foreach($userlist as $idx => $row)
{
	if($row[balance] < 0)
	{
		$nuserlist[] = $userlist[$idx];
		if($row[balance] < 0)
			$listdata[below] = $listdata[below] + $row[balance];
		elseif($row[balance] > 0)
			$listdata[over] = $listdata[over] + $row[balance];
	}
}

$userlist = $nuserlist;
$listdata[total] = sizeof($userlist);

$page = (! $_GET[page] ? 1 : $_GET[page]);
$pagelimit = (! $_CONFIG[phpui][userlist_pagelimit] ? $listdata[total] : $_CONFIG[phpui][userlist_pagelimit]);
$start = ($page - 1) * $pagelimit;

$SMARTY->assign("layout",$layout);
$SMARTY->assign("userlist",$userlist);
$SMARTY->assign("listdata",$listdata);
$SMARTY->assign("tariffs",$LMS->GetTariffs());
$SMARTY->assign("pagelimit",$pagelimit);
$SMARTY->assign("page",$page);
$SMARTY->assign("start",$start);

$SMARTY->display("userlist.html");

/*
 * $Log$
 * Revision 1.10  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>