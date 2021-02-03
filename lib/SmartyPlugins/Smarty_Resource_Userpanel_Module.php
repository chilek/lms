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

class Smarty_Resource_Userpanel_Module extends Smarty_Resource_Custom
{
    /**
      * Fetch a template and its modification time from database
      *
      * @param string $name template name
      * @param string $source template source
      * @param integer $mtime template modification timestamp (epoch)
      * @return void
      */
    protected function fetch($name, &$source, &$mtime)
    {
        global $module_dir;

        $module = $_GET['m'];
        $style = ConfigHelper::getConfig('userpanel.style', 'default');
        $template_path = $module_dir . $module . DIRECTORY_SEPARATOR . 'style' . DIRECTORY_SEPARATOR
            . $style . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name;
        if (file_exists($template_path)) {
            $mtime = filectime($template_path);
            $source = file_get_contents($template_path);
        } else {
            $template_path = $module_dir . $module . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name;
            if (file_exists($template_path)) {
                $mtime = filectime($template_path);
                $source = file_get_contents($template_path);
            } else {
                $mtime = 0;
                $source = null;
            }
        }
    }

    /**
      * Fetch a template's modification time from database
      *
      * @note implementing this method is optional. Only implement it if modification times can be accessed faster than loading the comple template source.
      * @param string $name template name
      * @return integer timestamp (epoch) the template was modified
      */
    protected function fetchTimestamp($name)
    {
        global $module_dir;

        $module = $_GET['m'];
        $style = ConfigHelper::getConfig('userpanel.style', 'default');
        $template_path = $module_dir . $module . DIRECTORY_SEPARATOR . 'style' . DIRECTORY_SEPARATOR
            .  $style . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name;
        if (file_exists($template_path)) {
            return filectime($template_path);
        } else {
            $template_path = $module_dir . $module . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $name;
            if (file_exists($template_path)) {
                return filectime($template_path);
            } else {
                return 0;
            }
        }
    }
}
