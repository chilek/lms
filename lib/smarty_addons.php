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

function _smarty_function_tip($args, &$SMARTY)
{
	$error = eregi_replace('(\'|")','\\\1',$SMARTY->_tpl_vars['error'][$args['trigger']]);
	$text = eregi_replace('(\'|")','\\\1',$args['text']);
	
	if($SMARTY->_tpl_vars['error'][$args['trigger']])
		return ' onMouseOver="return overlib(\'<B><FONT COLOR=RED>'.$error.'</FONT></B>\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onMouseOut="nd();"'.($args['bold'] ? ' CLASS="ALERTB" ' : ' CLASS="ALERT" ');
	elseif($args['text'] != "")
		return 'onMouseOver="return overlib(\''.$text.'\',HAUTO,VAUTO,OFFSETX,15,OFFSETY,15);" onMouseOut="nd();"'.($args['bold'] ? ' CLASS="BOLD" ' : '');	
}

$SMARTY->register_function('sum','_smarty_function_sum');
$SMARTY->register_function('size','_smarty_function_sizeof');
$SMARTY->register_function('tip','_smarty_function_tip');
$SMARTY->register_modifier('to_words','to_words');
/*
 * $Log$
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
