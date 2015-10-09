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
 * Smarty extendsall include prefilter plugin
 * Permits {include file="template.tpl"} to load templates with the same name recursively based on the $template_dir queue
 */

class Smarty_Prefilter_Extendsall_Include {
	static public function prefilter_extendsall_include($tpl_source, Smarty_Internal_Template $template) {
		if (is_array($template->smarty->template_dir) === false || preg_match('/^' . DOC_DIR . '/', $tpl_source))
			return $tpl_source;
		// prepend all files in {include} blocks with resource type 'extendsall:'
		return preg_replace('#(\{include\s*file=[\'"])(?:(?![a-z]+:|/))(.+)([\'"][^}]*\})#i', '$1extendsall:$2$3', $tpl_source);
	}
}

?>
