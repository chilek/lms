<?php

/*
 * LMS version 1.11-cvs
 *
 *  (C) Copyright 2001-2011 LMS Developers
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

function smarty_function_tip($args, &$SMARTY)
{
	if ($popup = $args['dynpopup'])
	{
		if(is_array($args))
			foreach($args as $argid => $argval)
				$popup = str_replace('$'.$argid, $argval, $popup);

		$text = " onmouseover=\"popup('$popup',1,".((int)$args['sticky']).",30,15)\" onmouseout=\"pophide()\"";
		return $text;
	}
	else if ($popup = $args['popup'])
	{
		if(is_array($args))
			foreach($args as $argid => $argval)
				$popup = str_replace('$'.$argid, $argval, $popup);

		$text = " onclick=\"popup('$popup',1,".((int)$args['sticky']).",10,10)\" onmouseout=\"pophide();\"";
		return $text;
	}
	else
	{
	    if($SMARTY->_tpl_vars['error'][$args['trigger']])
	    {
		    $error = str_replace("'",'\\\'',$SMARTY->_tpl_vars['error'][$args['trigger']]);
		    $error = str_replace('"','&quot;',$error);
		    $error = str_replace("\r",'',$error);
		    $error = str_replace("\n",'<BR>',$error);

		    $result = ' onmouseover="popup(\'<b><font color=red>'.$error.'</font></b>\')" onmouseout="pophide()" ';
		    $result .= $args['bold'] ? 'CLASS="alert bold" ' : ' CLASS="alert" ';
	    }
	    elseif($args['text'] != '')
	    {
		    $text = $args['text'];
    		    if($SMARTY->_tpl_vars['_LANG'][$text])
	    		    $text = trim($SMARTY->_tpl_vars['_LANG'][$text]);

		    if(is_array($args))
			    foreach($args as $argid => $argval)
				    $text = str_replace('$'.$argid, $argval, $text);

		    $text = str_replace('\'','\\\'',$text);
		    $text = str_replace('"','&quot;',$text);
		    $text = str_replace("\r",'',$text);
		    $text = str_replace("\n",'<BR>',$text);

		    $result .= 'onmouseover="popup(\''.$text.'\')" onmouseout="pophide()" ';
		    $result .= $args['bold'] ? 'CLASS="bold" ' : '';
	    }

	    return $result;
	}
}

?>
