<? // $Id$

/*
 * LMS version 1.0
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
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 *  $Id$
 */

$layout[pagetitle]="Lista u¿ytkowników";

$order=$_GET['o'];

$SMARTY->assign("order",$order);

$sql="SELECT id, lastname, name, status, email, phone1, address, info FROM users ";

if(isset($order))
	switch ($order){

		case "name":
			$sql .= " ORDER BY lastname ASC";
			$layout[sortmsg] = "Sortowanie wed³ug nazwiska.";
			break;
		case "addr":
			$sql .= " ORDER BY address ASC";
			$layout[sortmsg] = "Sortowanie wed³ug adresu.";
			break;
		case "id":
			$sql .= " ORDER BY id ASC";
			$layout[sortmsg] = "Sortowanie wed³ug ID.";
			break;
	
	}
else
{
	$sql .= " ORDER BY lastname ASC";
	$layout[sortmsg] = "Sortowanie wed³ug nazwiska.";
}

$DB->ExecSQL($sql);

while($DB->FetchRow()){
	list($userlist[id][],$userlist[lastname][],$userlist[name][],$userlist[status][],$userlist[email][],$userlist[phone1][],$userlist[address][],$userlist[info][]) = $DB->row;
}

for($i=0;$i<sizeof($userlist[id]);$i++)
	$userlist[balance][$i] = $LMS->GetUserBalance($userlist[id][$i]);

$_SESSION[from] = $QUERY_STRING;

$SMARTY->assign("layout",$layout);
$SMARTY->assign("userlist",$userlist);

$SMARTY->display("header.html");
$SMARTY->display("userlist.html");
$SMARTY->display("footer.html");
?>
