<?php

/**
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2021 LMS Developers
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

function smarty_function_js(array $params, Smarty_Internal_Template $template)
{
    if (isset($params['plugin']) && isset($params['filename'])) {
        $filename = PLUGIN_DIR . DIRECTORY_SEPARATOR . $params['plugin'] . DIRECTORY_SEPARATOR
            . 'js' . DIRECTORY_SEPARATOR . $params['filename'];
        if (file_exists($filename)) {
            return file_get_contents($filename);
        }
    } else {
        $js_file = preg_replace(
            array('/^[a-z]+:(\[[0-9]+\])?/i', '/\.[^\.]+$/'),
            array('', ''),
            $template->template_resource
        ) . '.js';
        return '<script src="js/templates/' . $js_file . '"></script>';
    }
}
