<?php

/** @noinspection PhpUnused */

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2023 LMS Developers
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
 * LMSMetroportMVNOPlugin
 *
 * @author Rafał Pietraszewicz <r.pietraszewicz@gmail.com>
 */
class LMSMetroportMVNOPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSMetroportMVNOPlugin';
    const PLUGIN_DB_VERSION = '2023050400';
    const PLUGIN_NAME = 'Metroport MVNO synchronization';
    const PLUGIN_DESCRIPTION = 'Metroport MVNO synchronization - prerelease version (unstable)';
    const PLUGIN_DOC_URL = 'https://lms-plus.org/wiki/metroportmvno/';
    const PLUGIN_REPO_URL = 'https://github.com/chilek/lms-plus/tree/metroportmvno';
    const PLUGIN_AUTHOR = 'Rafał Pietraszewicz &lt;r.pietraszewicz@gmail.com&gt;';
    const PLUGIN_SOFTWARE_VERSION = '1.1.3';

    private static $metroportmvno = null;

    public static function getMetroportMVNOInstance()
    {
        if (empty(self::$metroportmvno)) {
            self::$metroportmvno = new MetroportMVNO();
        }
        return self::$metroportmvno;
    }

    public function registerHandlers()
    {
        $this->handlers = array(
            'smarty_initialized' => array(
                'class' => 'MetroportMVNOInitHandler',
                'method' => 'smartyInit',
            ),
            'modules_dir_initialized' => array(
                'class' => 'MetroportMVNOInitHandler',
                'method' => 'modulesDirInit',
            ),
        );
    }
}
