<?php

/*
 * LMS version 1.5-cvs
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
$SMARTY->display('reloadheader.html');

echo '<H1>'.$layout['pagetitle'].'</H1>';

$_RELOAD_TYPE = $LMS->CONFIG['phpui']['reload_type'];
$_EXECCMD = $LMS->CONFIG['phpui']['reload_execcmd'];

switch($_RELOAD_TYPE)
{
	case 'exec':
		$execlist = explode(';',$_EXECCMD);
		echo '<TABLE WIDTH="100%" BGCOLOR="#F4F0EC" CELLPADDING="5"><TR><TD CLASS="FALL">';
		foreach($execlist as $execcmd)
		{
			echo '<P><B>'.$execcmd.'</B>:</P>';
			echo '<PRE>'.passthru($execcmd).'</PRE>';
		}
		echo '</TD></TR></TABLE>';
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
				$LMS->DB->Execute($query);
			}
			echo '</TD></TR></TABLE>';
		}else{
			if(isset($_GET['cancel']))
			{
				echo trans('Reload order deleted.');
				$LMS->DeleteTS('_force');
			}else
				if($reloadtimestamp = $LMS->GetTS('_force'))
				{
					if(!isset($_GET['refresh'])) 
					{					
						echo trans('Discovered reload order from date $0.', date('Y/m/d H:i',$reloadtimestamp)).'<BR>';
						echo trans('You can $0cancel$1 or $2refresh$3 them.','<A HREF="?m=reload&cancel">','</A>','<A HREF="?m=reload&refresh">','</A>');
					} else {
						echo trans('Reload order saved.');
						$LMS->SetTS('_force');
					}
				} else {
					echo trans('Reload order saved.');
					$LMS->SetTS('_force');
				}
		}
	break;

	default:
		echo '<P><B><FONT COLOR="RED">'.trans('Error: Unknown reload type: "$0"!', $_RELOAD_TYPE).'</FONT></B></P>';
	break;
}

$SMARTY->display('footer.html');

?>
