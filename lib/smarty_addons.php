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
	// onClick="return confirmLink(this, 'Jeste¶ pewien ¿e chcesz rozliczyæ WSZYSTKIE nale¿no¶ci u¿ytkownika: {$userlist[userlist].username|upper|escape} z bazy danych?\n\n')"

	$text = str_replace('\'','\\\'',$args['text']);
	$text = str_replace('"','&quot;',$text);
	$text = str_replace("\r",'',$text);
	$text = str_replace("\n",'\n',$text);

	if($text != "")
		return ' onClick="return confirmLink(this, \''.$text.'\')" ';
}

function _smarty_function_tip($args, &$SMARTY)
{
	$error = str_replace("'",'\\\'',$SMARTY->_tpl_vars['error'][$args['trigger']]);
	$error = str_replace('"','&quot;',$error);
	$error = str_replace("\r",'',$error);
	$error = str_replace("\n",'<BR>',$error);
	
	$text = str_replace('\'','\\\'',$args['text']);
	$text = str_replace('"','&quot;',$text);
	$text = str_replace("\r",'',$text);
	$text = str_replace("\n",'<BR>',$text);
	
	if($SMARTY->_tpl_vars['error'][$args['trigger']])
		$result = ' onMouseOver="return overlib(\'<B><FONT COLOR=RED>'.$error.'</FONT></B>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onMouseOut="nd();" ';
	elseif($args['text'] != "")
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

$SMARTY->register_function('sum','_smarty_function_sum');
$SMARTY->register_function('size','_smarty_function_sizeof');
$SMARTY->register_function('tip','_smarty_function_tip');
$SMARTY->register_function('confirm','_smarty_function_confirm');
$SMARTY->register_modifier('to_words','to_words');
$SMARTY->register_modifier('striphtml','_smarty_modifier_striphtml');

/*
 * $Log$
 * Revision 1.11  2003/10/08 04:01:29  lukasz
 * - html fixes in netdevices
 * - added new smarty function called {confirm text="confirm message"}
 * - little bugfix with netdev field in nodes (alec, pse, add this to
 *   changelog, also consider making 'UPGRADING' chapter in doc if it not
 *   exists yet)
 * - lot of small changes, mainly cosmetic
 *
 * Revision 1.10  2003/10/06 22:02:10  lukasz
 * - ju¿ nie psuje
 *
 * Revision 1.9  2003/10/02 13:15:15  lukasz
 * - eskejepowanie
 *
 * Revision 1.8  2003/09/24 22:33:15  lukasz
 * - more error tips
 *
 * Revision 1.7  2003/09/22 14:32:23  lukasz
 * - test
 *
 * Revision 1.6  2003/09/22 01:14:17  lukasz
 * - new popups
 *
 * Revision 1.5  2003/09/13 12:49:49  lukasz
 * - tsave
 *
 * Revision 1.4  2003/09/09 23:40:03  lukasz
 * - added to_words
 *
 * Revision 1.3  2003/09/09 01:22:28  lukasz
 * - nowe finanse
 * - kosmetyka
 * - bugfixy
 * - i inne rzeczy o których aktualnie nie pamiêtam
 *
 * Revision 1.2  2003/09/08 03:15:14  lukasz
 * - added string_format="%d"
 *
 * Revision 1.1  2003/09/08 03:12:22  lukasz
 * - dodane {sum array=$array column="columnname" default="default value"}
 *
 */

?>
