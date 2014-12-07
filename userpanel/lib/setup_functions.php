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
    $SMARTY->assign('style', ConfigHelper::getConfig('userpanel.style', 'default'));
    $SMARTY->assign('hint', ConfigHelper::getConfig('userpanel.hint', 'modern'));
    $SMARTY->assign('hide_nodes_modules', ConfigHelper::getConfig('userpanel.hide_nodes_modules', 0));
    $SMARTY->assign('reminder_mail_sender', ConfigHelper::getConfig('userpanel.reminder_mail_sender', ''));
    $SMARTY->assign('reminder_mail_subject', ConfigHelper::getConfig('userpanel.reminder_mail_subject', trans('credential reminder')));
    $SMARTY->assign('reminder_mail_body', ConfigHelper::getConfig('userpanel.reminder_mail_body', "ID: %id\nPIN: %pin"));
    $SMARTY->assign('reminder_sms_body', ConfigHelper::getConfig('userpanel.reminder_sms_body', "ID: %id, PIN: %pin"));
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

    if($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'style'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'style'", array($_POST['style']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'style', ?)", array($_POST['style']));
    
    if($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'hide_nodes_modules'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'hide_nodes_modules'", array(isset($_POST['hide_nodes_modules']) ? 1 : 0));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'hide_nodes_modules', ?)", array(isset($_POST['hide_nodes_modules']) ? 1 : 0));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'reminder_mail_sender'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'reminder_mail_sender'", array($_POST['reminder_mail_sender']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'reminder_mail_sender', ?)", array($_POST['reminder_mail_sender']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'reminder_mail_subject'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'reminder_mail_subject'", array($_POST['reminder_mail_subject']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'reminder_mail_subject', ?)", array($_POST['reminder_mail_subject']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'reminder_mail_body'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'reminder_mail_body'", array($_POST['reminder_mail_body']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'reminder_mail_body', ?)", array($_POST['reminder_mail_body']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'reminder_sms_body'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'reminder_sms_body'", array($_POST['reminder_sms_body']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'reminder_sms_body', ?)", array($_POST['reminder_sms_body']));

    LMSConfig::getConfig(array(
        'force' => true,
        'force_ui_only' => true,
    ));
    
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
