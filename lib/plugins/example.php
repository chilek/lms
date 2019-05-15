<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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
 * Example LMS plugin class. PHP5 only.
 */
class example_lms_plugin
{
    private $lms;

    /**
     * Class constructor
     *
     * @param object $LMS LMS object
     */
    function __construct($LMS)
    {
        $this->lms = $LMS;
    }

    /**
     * Action after node addition
     *
     * @param array $vars Node data
     *
     * @return array Node data
     */
    function node_after($vars)
    {
        // exec("lmsd -q");

        // always return $vars
        return $vars;
    }
}

// Initialize plugin
$example_plugin = new example_lms_plugin($LMS);

// Register plugin actions:
$LMS->RegisterHook('node_add_after', array($example_plugin, 'node_after'));
$LMS->RegisterHook('node_edit_after', array($example_plugin, 'node_after'));
$LMS->RegisterHook('node_del_after', array($example_plugin, 'node_after'));


/*
 List of supported actions:

 module_load_before - Just before LMS module load

 node_info_init  - Just before displaying nodeinfo.html

 node_add_before - Before node creation (just before executing SQL insert queries)
 node_add_after  - After node creation
 node_add_init   - Just before displaying nodeadd.html

 node_edit_before - Before node edition (just before executing SQL update queries)
 node_edit_after  - After node edition
 node_edit_init   - Just before displaying nodeedit.html

 node_del_before - Before node deletion (just before executing SQL delete queries)
 node_del_after  - After node deletion

 node_set_after  - After changing node status
 node_warn_after - After changing a warning flag

 send_sms_before - Called just before sending SMS, using this you can add your own service handlers

 access_table_init - Called after default access table is built

*/
