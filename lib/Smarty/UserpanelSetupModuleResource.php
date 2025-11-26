<?php

/*
 *  LMS version 1.11-git
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

namespace Lms\Smarty;

class UserpanelSetupModuleResource extends \Smarty\Resource\CustomPlugin
{
    /**
     * Fetch a template and its modification time
     *
     * @param string  $name   template name (module:template.tpl)
     * @param string  $source template source
     * @param integer $mtime  template modification timestamp (epoch)
     * @return void
     */
    protected function fetch($name, &$source, &$mtime)
    {
        global $module_dir;

        $template = explode(':', $name, 2);

        if (count($template) < 2) {
            $source = null;
            $mtime  = 0;
            return;
        }

        list($module, $filename) = $template;

        $template_path = $module_dir
            . $module . DIRECTORY_SEPARATOR
            . 'templates' . DIRECTORY_SEPARATOR
            . $filename;

        if (file_exists($template_path)) {
            $mtime  = filectime($template_path);
            $source = file_get_contents($template_path);
        } else {
            $mtime  = 0;
            $source = null;
        }
    }

    /**
     * Fetch a template's modification time
     *
     * @param string $name template name
     * @return integer timestamp (epoch)
     */
    protected function fetchTimestamp($name)
    {
        global $module_dir;

        $template = explode(':', $name, 2);

        if (count($template) < 2) {
            return 0;
        }

        list($module, $filename) = $template;

        $template_path = $module_dir
            . $module . DIRECTORY_SEPARATOR
            . 'templates' . DIRECTORY_SEPARATOR
            . $filename;

        if (file_exists($template_path)) {
            return filectime($template_path);
        }

        return 0;
    }
}
