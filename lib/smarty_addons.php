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

// Funkcje dodatkowe, do roszerzenia Smarty.

function _smarty_function_sum($args, &$SMARTY)
{
	$array = $args['array'];
	$column = $args['column'];
	$format = (isset($args['string_format']) ? $args['string_format'] : '%d');
	$default = (isset($args['default']) ? $args['default'] : 0);
	if($array)
		foreach($array as $row)
			$result += $row[$column];
	return sprintf($format,(isset($result) ? $result : $default));
}

function _smarty_function_sizeof($args, &$SMARTY)
{
	$array = $args['of'];
	return sizeof($array);
}

function _smarty_function_confirm($args, &$SMARTY)
{
	$text = $args['text'];
        if($SMARTY->_tpl_vars['_LANG'][$text])
	        $text = trim($SMARTY->_tpl_vars['_LANG'][$text]);
	else
		if(!in_array($text, (array) $SMARTY->_tpl_vars['missing_strings']) && $text !='')
			$SMARTY->_tpl_vars['missing_strings'][] = $text;	    
	if(is_array($args))
		foreach($args as $argid => $argval)
			$text = str_replace('$'.$argid, $argval, $text);

	$text = str_replace('\'','\\\'',$text);
	$text = str_replace('"','&quot;',$text);
	$text = str_replace("\r",'',$text);
	$text = str_replace("\n",'\n',$text);

	if($text != '')
		$ret = ' onClick="return confirmLink(this, \''.$text.'\')" ';
	if($args['href'] != '')
		$ret .= ' href="'.$args['href'].'" ';
//		$ret .= ' href="?m=confirm&text='.urlencode($text).'&link='.urlencode($args['href']).'" ';
		
	$ret = eregi_replace('[ ]+', ' ', $ret);
	return $ret;
}

function _smarty_function_tip($args, &$SMARTY)
{
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
		$result = ' onMouseOver="return overlib(\'<B><FONT COLOR=RED>'.$error.'</FONT></B>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onMouseOut="nd();" ';
	elseif($args['text'] != '')
		$result = 'onMouseOver="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onMouseOut="nd();"';
	$result .= ($SMARTY->_tpl_vars['error'][$args['trigger']] ? ($args['bold'] ? ' CLASS="ALERTB" ' : ' CLASS="ALERT" ') : ($args['bold'] ? ' CLASS="BOLD" ' : ''));
	return $result;
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
    $content = trim($content);
    
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

$SMARTY->register_function('sum','_smarty_function_sum');
$SMARTY->register_function('size','_smarty_function_sizeof');
$SMARTY->register_function('tip','_smarty_function_tip');
$SMARTY->register_function('confirm','_smarty_function_confirm');
$SMARTY->register_modifier('to_words','to_words');
$SMARTY->register_modifier('money_format','moneyf');
$SMARTY->register_modifier('striphtml','_smarty_modifier_striphtml');
$SMARTY->register_block('t', '_smarty_block_translate');
$SMARTY->assign('now', time());
$SMARTY->assign('tomorrow', time()+86400);
$SMARTY->assign('yesterday', time()-86400);

?>
