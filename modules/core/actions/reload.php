<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
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

$layout['pagetitle'] = trans('Configuration Reload');

$SMARTY->display('header.html');

echo '<H1>'.$layout['pagetitle'].'</H1>';

$_RELOAD_TYPE = $LMS->CONFIG['phpui']['reload_type'];
$_EXECCMD = $LMS->CONFIG['phpui']['reload_execcmd'];

switch($_RELOAD_TYPE)
{
	case 'exec':
		$hosts = $DB->GetAll('SELECT id, name, lastreload, reload, description FROM hosts ORDER BY name');

		if(isset($_GET['setreloads']))
		{
			echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
			$execlist = explode(';',$_EXECCMD);
			foreach($hosts as $host)
				if(in_array($host['id'], (array) $_POST['hosts']))
					foreach($execlist as $execcmd)
					{
						$execcmd = str_replace('%host', $host['name'], $execcmd);
						echo '<P><B>'.$execcmd.'</B>:</P>';
						echo '<PRE>';
						flush();
						echo passthru($execcmd);
						flush();
						echo '</PRE>';
					}
			echo '</TD></TR></TABLE>';
		}
		else
		{
			if(!count($hosts))
			{
				echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
				$execlist = explode(';',$_EXECCMD);
				foreach($execlist as $execcmd)
				{
					$execcmd = str_replace('%host', '', $execcmd);
					echo '<P><B>'.$execcmd.'</B>:</P>';
					echo '<PRE>';
					flush();
					echo passthru($execcmd);
					flush();
					echo '</PRE>';
				}
				echo '</TD></TR></TABLE>';
			}
			else
			{
				$SMARTY->assign('hosts', $hosts);
				$SMARTY->display('reload.html');
			}
		}
	break;

	case 'sql':
		if(isset($LMS->CONFIG['phpui']['reload_sqlquery']))
		{
			$sqlqueries = explode(';',($LMS->CONFIG['phpui']['reload_sqlquery']));
			echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
			foreach($sqlqueries as $query)
			{
				$query = str_replace('%TIME%','?NOW?',$query);
				echo '<P><B>'.trans('Running:').'</B></P>';
				echo '<PRE>'.$query.'</PRE>';
				$DB->Execute($query);
			}
			echo '</TD></TR></TABLE>';
		}
		else
		{
			$hosts = $DB->GetAll('SELECT id, name, lastreload, reload, description FROM hosts ORDER BY name');
			
			if(isset($_GET['setreloads']))
			{
				foreach($hosts as $host)
					if(in_array($host['id'], (array) $_POST['hosts']))
						$DB->Execute('UPDATE hosts SET reload=1 WHERE id=?', array($host['id']));
					else
						$DB->Execute('UPDATE hosts SET reload=0 WHERE id=?', array($host['id']));
			}
			
			$SMARTY->assign('hosts', $hosts);
			$SMARTY->display('reload.html');
		}
	break;

	default:
		echo '<P><B><FONT COLOR="RED">'.trans('Error: Unknown reload type: "$0"!', $_RELOAD_TYPE).'</FONT></B></P>';
	break;
}

$SMARTY->display('footer.html');

?>
