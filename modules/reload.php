<?php

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

// Agggrrr. Nie zwracajcie uwagi na styl pisania *TEGO* kawa³ka kodu :)
// Jest 7:35 a ja ca³± noc nie spa³em :)

$layout[pagetitle] = $lang[pagetitle_reload];

$SMARTY->display("header.html");

?><H1><?=$layout[pagetitle]?></H1><?

$_RELOAD_TYPE = (! $_CONFIG[phpui]['reload_type'] ? "sql" : $_CONFIG[phpui]['reload_type']);
$_EXECCMD = (! $_CONFIG[phpui]['reload_execcmd'] ? "/bin/true" : $_CONFIG[phpui]['reload_execcmd']);

switch($_RELOAD_TYPE)
{
	case "exec":
		$execlist = explode(";",$_EXECCMD);
		echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
		foreach($execlist as $execcmd)
		{
			echo "<P><B>".$execcmd."</B>:</P>";
			echo "<PRE>";
			passthru($execcmd);
			echo "</PRE>";
		}
		echo '</TD></TR></TABLE>';
	break;

	case "sql":
		if(isset($_CONFIG[phpui]['reload_sqlquery']))
		{
			$sqlqueries = explode(";",($_CONFIG[phpui]['reload_sqlquery']));
			echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
			foreach($sqlqueries as $query)
			{
				$query = str_replace("%TIME%",$LMS->sqlTSfmt(),$query);
				echo "<P><B>Wykonuje:</B></P>";
				echo "<PRE>".$query."</PRE>";
				$ADB->Execute($query);
			}
			echo '</TD></TR></TABLE>';
		}else{
			if(isset($_GET[cancel]))
			{
				echo 'Usuniêto zlecenie prze³adowania konfiguracji.';
				$LMS->DeleteTS("_force");
			}else
				if($reloadtimestamp = $LMS->GetTS("_force"))
				{
					echo 'Wykryto zlecenie prze³adowania konfiguracji z '.date("Y/m/d H:i",time()).'.<BR>';
					echo 'Kliknij <A HREF="?m=reload&cancel">tutaj</a> aby anulowaæ to zlecenie.';
				}else{
					echo 'Zapisano zlecenie prze³adowania konfiguracji w bazie danych.';
					$LMS->SetTS("_force");
				}
		}
	break;

	default:

		echo "<P><B><FONT COLOR=\"RED\">B³±d! Niepoprawny typ reloadu: '".$_RELOAD_TYPE."' !</FONT></B></P>";
	break;

}

$SMARTY->display("footer.html");

?>
