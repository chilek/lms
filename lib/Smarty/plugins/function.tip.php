<?php

/*
 * LMS version 1.10-cvs
 *
 *  (C) Copyright 2001-2007 LMS Developers
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
        $dynpopup = $args['dynpopup'];
	if ($dynpopup) 
	{
		if(is_array($args))
			foreach($args as $argid => $argval)
				$dynpopup = str_replace('$'.$argid, $argval, $dynpopup);
		$text = "onmouseover=\"return overlib('<iframe id=&quot;autoiframe&quot; width=100 height=10 frameborder=0 scrolling=no src=&quot;".$dynpopup."&popup=1&quot;></iframe>',HAUTO,VAUTO,OFFSETX,30,OFFSETY,15".($args['sticky'] ? ',STICKY, MOUSEOFF' : '').");\" onmouseout=\"nd();\"";
//		global $SESSION;
//		$text = 'onmouseover="if(getSeconds() < '.$SESSION->timeout.'){ return overlib(\'<iframe id=&quot;autoiframe&quot; frameborder=0 scrolling=no width=220 height=150 src=&quot;'.$dynpopup.'&quot;></iframe>\',HAUTO,VAUTO,OFFSETX,85,OFFSETY,15); }" onmouseout="nd();"';
		return $text;
	} 
	else 
	{
	    $text = $args['text'];
    	    if($SMARTY->_tpl_vars['_LANG'][$text])
	    	    $text = trim($SMARTY->_tpl_vars['_LANG'][$text]);

	    if(is_array($args))
		    foreach($args as $argid => $argval)
			    $text = str_replace('$'.$argid, $argval, $text);

	    $error = str_replace("'",'\\\'',$SMARTY->_tpl_vars['error'][$args['trigger']]);
	    $error = str_replace('"','&quot;',$error);
	    $error = str_replace("\r",'',$error);
	    $error = str_replace("\n",'<BR>',$error);
	
	    $text = str_replace('\'','\\\'',$text);
	    $text = str_replace('"','&quot;',$text);
	    $text = str_replace("\r",'',$text);
	    $text = str_replace("\n",'<BR>',$text);
	
	    if($SMARTY->_tpl_vars['error'][$args['trigger']])
		    $result = ' onmouseover="return overlib(\'<b><font color=red>'.$error.'</font></b>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();" ';
	    elseif($args['text'] != '')
		    $result = 'onmouseover="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onmouseout="nd();"';

	    $result .= ($SMARTY->_tpl_vars['error'][$args['trigger']] ? ($args['bold'] ? ' CLASS="alert bold" ' : ' CLASS="alert" ') : ($args['bold'] ? ' CLASS="bold" ' : ''));

	    return $result;
	}
}

?>
