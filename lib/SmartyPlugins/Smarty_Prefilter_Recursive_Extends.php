<?php

/*
 *  LMS version 1.11-git
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

/**
 * Smarty recursiveextends prefilter plugin
 * Permits {extends file="template.tpl"} to load templates with the same name recursively based on the $template_dir queue
 */

class Smarty_Prefilter_Recursive_Extends {
	public function prefilter_recursive_extends($tpl_source, Smarty_Internal_Template $template) {
		if (is_array($template->smarty->template_dir) === false)
			return $tpl_source;
		$currentPath = rtrim(dirname($template->smarty->_current_file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		//$currentFile = basename($template->smarty->_current_file);
		$currentFile = $template->template_resource;
		// can we find an {extend} block for the current template?
		$res = preg_match('#(?P<before>\{extends\s*file=[\'"])' . preg_quote($currentFile) . '(?P<after>[\'"][^}]*\})#i', $tpl_source, $regexResult);
		if (empty($res))
			return $tpl_source;
		$newTemplateDir = array_slice($template->smarty->template_dir, array_search($currentPath, $template->smarty->template_dir) + 1);
		if (empty($newTemplateDir) === false)
			foreach ($newTemplateDir as $key => $value)
				if (file_exists(rtrim($value, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $currentFile)) {
					$newExtendPath = rtrim($value, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $currentFile;
					break;
				}
		if (isset($newExtendPath) === true)
			$tpl_source = str_replace( $regexResult[0], $regexResult['before'] . $newExtendPath . $regexResult['after'], $tpl_source );
		return $tpl_source;
	}
}

?>
