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

function userpanel_style_change() {
	$files = getdir(USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates_c', '^.*\.html\.php$');
	if (!empty($files))
		foreach ($files as $file)
			unlink(USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates_c' . DIRECTORY_SEPARATOR . $file);
}

function module_setup()
{
    global $SMARTY, $DB, $USERPANEL, $layout, $LMS;
    $layout['pagetitle'] = trans('Userpanel Configuration');
    $SMARTY->assign('page_header', ConfigHelper::getConfig('userpanel.page_header', ''));
    $SMARTY->assign('company_logo', ConfigHelper::getConfig('userpanel.company_logo', ''));
    $SMARTY->assign('stylelist', getdir(USERPANEL_DIR . DIRECTORY_SEPARATOR . 'style', '^[a-z0-9]*$'));
    $SMARTY->assign('style', ConfigHelper::getConfig('userpanel.style', 'default'));
    $SMARTY->assign('hint', ConfigHelper::getConfig('userpanel.hint', 'modern'));
    $SMARTY->assign('hide_nodes_modules', ConfigHelper::getConfig('userpanel.hide_nodes_modules', 0));
    $SMARTY->assign('reminder_mail_sender', ConfigHelper::getConfig('userpanel.reminder_mail_sender', ''));
    $SMARTY->assign('reminder_mail_subject', ConfigHelper::getConfig('userpanel.reminder_mail_subject', trans('credential reminder')));
    $SMARTY->assign('reminder_mail_body', ConfigHelper::getConfig('userpanel.reminder_mail_body', "ID: %id\nPIN: %pin"));
    $SMARTY->assign('reminder_sms_body', ConfigHelper::getConfig('userpanel.reminder_sms_body', "ID: %id, PIN: %pin"));
    $SMARTY->assign('auth_type', ConfigHelper::getConfig('userpanel.auth_type', 1));
    $SMARTY->assign('force_ssl', ConfigHelper::getConfig('userpanel.force_ssl', ConfigHelper::getConfig('phpui.force_ssl', 1)));
    $SMARTY->assign('google_recaptcha_sitekey', ConfigHelper::getConfig('userpanel.google_recaptcha_sitekey', ''));
	$SMARTY->assign('google_recaptcha_secret', ConfigHelper::getConfig('userpanel.google_recaptcha_secret', ''));
	$enabled_modules = ConfigHelper::getConfig('userpanel.enabled_modules', null, true);
	if (is_null($enabled_modules)) {
		$enabled_modules = array();
		if (!empty($USERPANEL->MODULES))
			foreach ($USERPANEL->MODULES as $module)
				$enabled_modules[] = $module['module'];
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES (?, ?, ?)",
			array('userpanel', 'enabled_modules', implode(',', $enabled_modules)));
	} else
		$enabled_modules = explode(',', $enabled_modules);
    $SMARTY->assign('enabled_modules', $enabled_modules);
    $SMARTY->assign('total', count($USERPANEL->MODULES));
    $SMARTY->display('file:' . USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'setup.html');
}

function module_submit_setup()
{
    global $DB, $LMS;
	if (!isset($_POST['hint'])) {
		module_setup();
		return;
	}
    // write main configuration
    if($test = $DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'hint'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'hint'", array($_POST['hint']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'hint', ?)", array($_POST['hint']));

    if ($oldstyle = $DB->GetOne("SELECT value FROM uiconfig WHERE section = 'userpanel' AND var = 'style'")) {
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'style'", array($_POST['style']));
		if ($oldstyle != $_POST['style'])
			userpanel_style_change();
    } else
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

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'page_header'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'page_header'", array($_POST['page_header']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'page_header', ?)", array($_POST['page_header']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'company_logo'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'company_logo'", array($_POST['company_logo']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'company_logo', ?)", array($_POST['company_logo']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'reminder_sms_body'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'reminder_sms_body'", array($_POST['reminder_sms_body']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'reminder_sms_body', ?)", array($_POST['reminder_sms_body']));

    if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'auth_type'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'auth_type'", array($_POST['auth_type']));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'auth_type', ?)", array($_POST['auth_type']));

    if($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'force_ssl'"))
        $DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'force_ssl'", array(isset($_POST['force_ssl']) ? 1 : 0));
    else
        $DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'force_ssl', ?)", array(isset($_POST['force_ssl']) ? 1 : 0));

	if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'google_recaptcha_sitekey'"))
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'google_recaptcha_sitekey'", array($_POST['google_recaptcha_sitekey']));
	else
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'google_recaptcha_sitekey', ?)", array($_POST['google_recaptcha_sitekey']));

	if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'google_recaptcha_secret'"))
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'google_recaptcha_secret'", array($_POST['google_recaptcha_secret']));
	else
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'google_recaptcha_secret', ?)", array($_POST['google_recaptcha_secret']));

	if (isset($_POST['enabled_modules']))
		$enabled_modules = implode(',', array_keys($_POST['enabled_modules']));
	else
		$enabled_modules = '';
	if ($DB->GetOne("SELECT 1 FROM uiconfig WHERE section = 'userpanel' AND var = 'enabled_modules'"))
		$DB->Execute("UPDATE uiconfig SET value = ? WHERE section = 'userpanel' AND var = 'enabled_modules'", array($enabled_modules));
	else
		$DB->Execute("INSERT INTO uiconfig (section, var, value) VALUES('userpanel', 'enabled_modules', ?)", array($enabled_modules));

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
    $SMARTY->display('file:' . USERPANEL_DIR . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'setup_rights.html');
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

function module_save_module_order() {
	$DB = LMSDB::getInstance();
	$DB->Execute('UPDATE uiconfig SET value = ? WHERE section = ? AND var = ?',
		array(implode(',', $_POST['modules']), 'userpanel', 'module_order'));
	header('Content-Type: application/json');
	echo json_encode(array('result' => 'OK'));
	die;
}

?>
