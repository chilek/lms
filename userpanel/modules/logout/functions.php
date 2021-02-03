<?php

/*
 *  LMS version 1.11-git
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

function module_main()
{
    global $SESSION, $LMS;
    
    $SESSION->LogOut();

    if (ConfigHelper::getConfig('userpanel.logout_url')!='') {
        header('Location: '.ConfigHelper::getConfig('userpanel.logout_url'));
    } else {
        header('Location: ?m=');
    }
}

if (defined('USERPANEL_SETUPMODE')) {
    function module_setup()
    {
        global $SMARTY,$LMS;
        $SMARTY->assign('logouturl', ConfigHelper::getConfig('userpanel.logout_url'));
        $SMARTY->display('module:logout:setup.html');
    }

    function module_submit_setup()
    {
        global $SMARTY,$DB;
        $DB->Execute('UPDATE uiconfig SET value = ? WHERE section = \'userpanel\' AND var = \'logout_url\'', array($_POST['logouturl']));
        header('Location: ?m=userpanel&module=logout');
    }
}
