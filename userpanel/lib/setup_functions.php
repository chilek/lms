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

function module_setup()
{
    global $SMARTY, $DB, $USERPANEL, $layout, $LMS;
    $layout['pagetitle'] = trans('Userpanel Configuration');
    $SMARTY->assign('stylelist', getdir(USERPANEL_DIR.'/style', '^[a-z0-9]*$'));
    $SMARTY->assign('style', isset($LMS->CONFIG['userpanel']['style']) ? $LMS->CONFIG['userpanel']['style'] : 'default');
    $SMARTY->assign('hint', isset($LMS->CONFIG['userpanel']['hint']) ? $LMS->CONFIG['userpanel']['hint'] : 'modern');
    $SMARTY->assign('hide_nodes_modules', isset($LMS->CONFIG['userpanel']['hide_nodes_modules']) ? $LMS->CONFIG['userpanel']['hide_nodes_modules'] : 0);
    $SMARTY->assign('total', sizeof($USERPANEL->MODULES));
    $SMARTY->display(USERPANEL_DIR.'/templates/setup.html');
}

function module_submit_setup()
{
    global $DB, $LMS;
    // write main configuration
    if($test = $DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'hint'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'hint'", array($_POST['hint']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'hint', ?)", array($_POST['hint']));
    $LMS->CONFIG['userpanel']['hint'] = $_POST['hint'];


    if($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'style'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'style'", array($_POST['style']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'style', ?)", array($_POST['style']));
    $LMS->CONFIG['userpanel']['style'] = $_POST['style'];
    
    if($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'hide_nodes_modules'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'hide_nodes_modules'", array(isset($_POST['hide_nodes_modules']) ? 1 : 0));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'hide_nodes_modules', ?)", array(isset($_POST['hide_nodes_modules']) ? 1 : 0));
    $LMS->CONFIG['userpanel']['hide_nodes_modules'] = isset($_POST['hide_nodes_modules']) ? 1 : 0;

    module_setup();
}

function module_rights()
{
    global $SMARTY, $DB, $LMS, $layout;
    
    $layout['pagetitle'] = trans('Customers\' rights');
    
    $customerlist = $LMS->GetCustomerNames();
    $userpanelrights = $DB->GetAll('SELECT id, module, name, description, setdefault FROM up_rights');

    $SMARTY->assign('customerlist',$customerlist);
    $SMARTY->assign('userpanelrights', $userpanelrights);
    $SMARTY->display(USERPANEL_DIR.'/templates/setup_rights.html');
}

function module_submit_rights()
{
    global $DB;
    $setrights=$_POST['setrights'];
    if(isset($setrights) && isset($setrights['mcustomerid'])) {
        $newrights=$setrights['rights'];
        foreach($setrights['mcustomerid'] as $customer) {
            $oldrights=$DB->GetAll('SELECT id, rightid FROM up_rights_assignments WHERE customerid=?',
                array($customer));
            if($oldrights != null)
                foreach($oldrights as $right)
                    if(isset($newrights[$right['rightid']]))
                        unset($newrights[$right['rightid']]);
                    else
                        $DB->Execute('DELETE FROM up_rights_assignments WHERE id=?',
                            array($right['id']));
            if($newrights != null)
                foreach($newrights as $right)
                    $DB->Execute('INSERT INTO up_rights_assignments(customerid, rightid) VALUES(?, ?)',
                        array($customer, $right));
        }
    }
    module_rights();
}

function module_submit_rights_default()
{
    global $DB;
    $rights = isset($_POST['setdefaultrights']) ? $_POST['setdefaultrights'] : array();
    foreach($DB->GetCol('SELECT id FROM up_rights') as $right)
        $DB->Execute('UPDATE up_rights SET setdefault = ? WHERE id = ?',
	        array(isset($rights[$right]) ? 1 : 0, $right));
    module_rights();
}

?>
