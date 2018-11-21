<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$_RELOAD_TYPE = ConfigHelper::getConfig('phpui.reload_type');
$_EXECCMD = ConfigHelper::getConfig('phpui.reload_execcmd');

$serverTime = date("r");

if (ConfigHelper::checkConfig('phpui.reload_timer'))
	$SMARTY->assign('serverTime', $serverTime);

switch ($_RELOAD_TYPE) {
	case 'exec':
		$hosts = $DB->GetAll('SELECT id, name, lastreload, reload, description FROM hosts ORDER BY name');

		if ((isset($_GET['setreloads']) && isset($_POST['hosts'])) || count($hosts) == 1) {
			$SMARTY->display('header.html');

			echo '<H1>'.$layout['pagetitle'].'</H1>';

			$execlist = explode(';',$_EXECCMD);

			foreach ($hosts as $host)
				if (count($hosts) == 1 || in_array($host['id'], $_POST['hosts'])) {
					echo '<H3>'.trans('Host:').' '.$host['name'].'</H3>';
					echo '<TABLE WIDTH="100%" class="superlight" CELLPADDING="5"><TR><TD class="fall">';
					foreach($execlist as $execcmd)
					{
						$execcmd = str_replace('%host', $host['name'], $execcmd);
						$execcmd_buffer = popen ("$execcmd", "r");
						echo '<P><B>'.$execcmd.'</B>:</P>';
						flush();

						while(!feof($execcmd_buffer)) 
						{
							$output = fread($execcmd_buffer, 1);
							echo nl2br($output);
							flush();
							ob_flush();
						}
					    pclose($execcmd_buffer);
					}
					echo '</TD></TR></TABLE>';
					
					$DB->Execute('UPDATE hosts SET lastreload = ?NOW?, reload = 0 WHERE id = ?', array($host['id']));
				}
			$SMARTY->display('footer.html');
		} else {
			$SMARTY->assign('hosts', $hosts);
			$SMARTY->display('reload.html');
		}
	break;

	case 'sql':
		$hosts = $DB->GetAll('SELECT id, name, lastreload, reload, description FROM hosts ORDER BY name');

		$reload_sqlquery = ConfigHelper::getConfig('phpui.reload_sqlquery');
		if (!empty($reload_sqlquery) && $hosts) {
			if ((isset($_GET['setreloads']) && isset($_POST['hosts'])) || count($hosts) == 1) {
				$SMARTY->display('header.html');

				$sqlqueries = explode(';', $reload_sqlquery);

				echo '<H1>'.$layout['pagetitle'].'</H1>';

				foreach ($hosts as $host)
					if (count($hosts) == 1 || in_array($host['id'], $_POST['hosts'])) {
						echo '<H3>'.trans('Host:').' '.$host['name'].'</H3>';
						echo '<TABLE WIDTH="100%" class="superlight" CELLPADDING="5"><TR><TD class="fall">';
						foreach($sqlqueries as $query)
						{
							$query = str_replace('%TIME%', '?NOW?', $query);
							$query = str_replace('%host', $host['name'], $query);
							echo '<B>'.trans('Query:').'</B>';
							echo '<PRE>'.$query.'</PRE>';
							$DB->Execute($query);
						}
						echo '</TD></TR></TABLE>';
					}
				$SMARTY->display('footer.html');
			} else {
				$SMARTY->assign('hosts', $hosts);
				$SMARTY->display('reload.html');
			}
		} else {
			if (isset($_GET['setreloads']) && isset($_POST['hosts']) && $hosts) {
				foreach($hosts as $host)
					if(in_array($host['id'], $_POST['hosts']))
						$DB->Execute('UPDATE hosts SET reload=1 WHERE id=?', array($host['id']));
					else
						$DB->Execute('UPDATE hosts SET reload=0 WHERE id=?', array($host['id']));

				$SESSION->redirect('?m=reload');
			} else {
				$SMARTY->assign('hosts', $hosts);
				$SMARTY->display('reload.html');
			}
		}
	break;

	default:
		echo '<P><B><FONT COLOR="RED">'.trans('Error: Unknown reload type: "$a"!', $_RELOAD_TYPE).'</FONT></B></P>';
	break;
}

?>
