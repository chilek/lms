<?php

/*
 * LMS version 1.7-cvs
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

// Funkcje dodatkowe, do roszerzenia Smarty.

function _smarty_function_sum($args, &$SMARTY)
{
	$array = $args['array'];
	$format = (isset($args['string_format']) ? $args['string_format'] : '%d');
	$default = (isset($args['default']) ? $args['default'] : 0);
	if($array)
		foreach($array as $row)
			if(is_array($row))
				$result += $row[$args['column']];

	$result = isset($result) ? $result : $default;

	if(isset($args['assign']))
		$SMARTY->assign($args['assign'], $result);
	else
		return sprintf($format, $result);
}

function _smarty_function_sizeof($args, &$SMARTY)
{
	$array = $args['of'];
	return sizeof($array);
}

function _smarty_function_tip($args, &$SMARTY)
{
        $dynpopup = $args['dynpopup'];
	if ($dynpopup) 
	{
		if(is_array($args))
			foreach($args as $argid => $argval)
				$dynpopup = str_replace('$'.$argid, $argval, $dynpopup);
		$text = "onmouseover=\"return overlib('<iframe id=&quot;autoiframe&quot; width=150 height=10 frameborder=0 scrolling=no src=&quot;".$dynpopup."&popup=1&quot;></iframe>',HAUTO,VAUTO,OFFSETX,30,OFFSETY,15".($args['sticky'] ? ',STICKY, MOUSEOFF' : '').");\" onmouseout=\"nd();\"";
//		global $SESSION;
//		$text = 'onmouseover="if(getSeconds() < '.$SESSION->timeout.'){ return overlib(\'<iframe id=&quot;autoiframe&quot; frameborder=0 scrolling=no width=220 height=150 src=&quot;'.$dynpopup.'&quot;></iframe>\',HAUTO,VAUTO,OFFSETX,85,OFFSETY,15); }" onmouseout="nd();"';
		return $text;
	} else {
	    $text = $args['text'];
    	    if($SMARTY->_tpl_vars['_LANG'][$text])
	    	    $text = trim($SMARTY->_tpl_vars['_LANG'][$text]);
		else
			if(!in_array($text, (array) $SMARTY->_tpl_vars['missing_strings']) && $text !='')
				$SMARTY->_tpl_vars['missing_strings'][] = $text;	    
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

function _smarty_modifier_striphtml($args)
{
	$search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
			"'<[\/\!]*?[^<>]*?>'si",           // Strip out html tags
			"'([\r\n])[\s]+'",                 // Strip out white space
			"'&(quot|#34);'i",                 // Replace html entities
			"'&(amp|#38);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(nbsp|#160);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i",
			"'&#(\d+);'e");                    // evaluate as php
	
	$replace = array ("",
			"\\1",
			"\"",
			"&",
			"<",
			">",
			" ",
			chr(161),
			chr(162),
			chr(163),
			chr(169),
			"chr(\\1)");

	return preg_replace ($search, $replace, $args);
	
}

function _smarty_block_translate($args, $content, &$SMARTY)
{
    if($SMARTY->_tpl_vars['_LANG'][$content])
	    $content = trim($SMARTY->_tpl_vars['_LANG'][$content]);
    else
	    if(!in_array($content, (array) $SMARTY->_tpl_vars['missing_strings']) && $content !='')
		    $SMARTY->_tpl_vars['missing_strings'][] = $content;	    
    
    if(is_array($args))
	    foreach($args as $argid => $argval)
		    $content = str_replace('$'.$argid, $argval, $content);

    return trim($content);
}

function _smarty_bankaccount($args, &$SMARTY)
{
	return bankaccount($args['id']);
}

$SMARTY->register_function('sum','_smarty_function_sum');
$SMARTY->register_function('size','_smarty_function_sizeof');
$SMARTY->register_function('tip','_smarty_function_tip');
$SMARTY->register_function('bankaccount','_smarty_bankaccount');
$SMARTY->register_modifier('to_words','to_words');
$SMARTY->register_modifier('money_format','moneyf');
$SMARTY->register_modifier('striphtml','_smarty_modifier_striphtml');
$SMARTY->register_block('t', '_smarty_block_translate');
$SMARTY->assign('now', time());
$SMARTY->assign('tomorrow', time()+86400);
$SMARTY->assign('yesterday', time()-86400);

?>
