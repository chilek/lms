<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

function smarty_function_tip($params, $template)
{
	$result = '';

	if (array_key_exists('dynpopup', $params) && $popup = $params['dynpopup']) {
		if(is_array($params))
			foreach($params as $paramid => $paramval)
				$popup = str_replace('$'.$paramid, $paramval, $popup);

		$text = " onmouseover=\"popup('$popup',1," . (array_key_exists('sticky', $params) && $params['sticky'] ? 1 : 0) . ",30,15)\" onmouseout=\"pophide()\"";
		return $text;
	} else if (array_key_exists('popup', $params) && $popup = $params['popup']) {
		if(is_array($params))
			foreach($params as $paramid => $paramval)
				$popup = str_replace('$'.$paramid, $paramval, $popup);

		$text = " onclick=\"popup('$popup',1," . (array_key_exists('sticky', $params) && $params['sticky'] ? 1 : 0) . ",10,10)\" onmouseout=\"pophide();\"";
		return $text;
	} else {
		$class = array_key_exists('class', $params) ? $params['class'] : '';
		$tmpl = $template->getTemplateVars('error');
		if (array_key_exists('trigger', $params) && $tmpl[$params['trigger']]) {
			$error = str_replace("'", '\\\'', $tmpl[$params['trigger']]);
			$error = str_replace('"', '&quot;', $error);
			$error = str_replace("\r", '', $error);
			$error = str_replace("\n", '<BR>', $error);

			$result = ' onmouseover="popup(\'<b><font color=red>'.$error.'</font></b>\')" onmouseout="pophide()" ';
			$result .= ' class="' . (empty($class) ? '' : $class) . ($params['bold'] ? ' alert bold" ' : ' alert" ');
		} elseif {
			if ($params['text'] != '') {
				$text = $params['text'];
				unset($params['text']);
				$text = trans(array_merge((array)$text, $params));

				$text = str_replace('\'', '\\\'', $text);
				$text = str_replace('"', '&quot;', $text);
				$text = str_replace("\r", '', $text);
				$text = str_replace("\n", '<BR>', $text);

				$result .= 'onmouseover="popup(\''.$text.'\')" onmouseout="pophide()" ';
			}
			$result .= ' class="' . (empty($class) ? '' : $class) . (array_key_exists('bold', $params) && $params['bold'] ? ' bold' : '') . '" ';
		}

		return $result;
	}
}

?>
