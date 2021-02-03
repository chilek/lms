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

$SESSION->restore('conls', $section);

function parse_cfg_val($value)
{
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    } else {
        return (string) $value;
    }
}

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'config.php');

$DB->BeginTrans();

foreach (array('phpui', 'invoices', 'notes', 'receipts', 'finances', 'sms', 'mail', 'zones') as $sec) {
    if (!empty($CONFIG[$sec]) && (!$section || $section == $sec)) {
        foreach ($CONFIG[$sec] as $key => $val) {
            $args = array(
            'section' => $sec,
            'var' => $key,
            'value' => parse_cfg_val($val)
            );
            $DB->Execute(
                'INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
                array_values($args)
            );

            if ($SYSLOG) {
                $args[SYSLOG::RES_UICONFIG] = $DB->GetLastInsertID('uiconfig');
                $SYSLOG->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_ADD, $args);
            }
        }
    }
}

if (isset($CONFIG['userpanel'])) {
    if ($SYSLOG) {
        $configs = $DB->GetCol('SELECT id FROM uiconfig WHERE section = ?', array('userpanel'));
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $args = array(SYSLOG::RES_UICONF => $config);
                $SYSLOG->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_DELETE, $args);
            }
        }
    }
    // it's possible that userpanel config is in database yet
    $DB->Execute('DELETE FROM uiconfig WHERE section = \'userpanel\'');

    foreach ($CONFIG['userpanel'] as $key => $val) {
        $args = array(
            'section' => 'userpanel',
            'var' => $key,
            'value' => parse_cfg_val($val)
        );
        $DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)', array_values($args));

        if ($SYSLOG) {
            $args[SYSLOG::RES_UICONF] = $DB->GetLastInsertID('uiconfig');
            $SYSLOG->AddMessage(SYSLOG::RES_UICONF, SYSLOG::OPER_ADD, $args);
        }
    }
}

/*
foreach($CONFIG['directories'] as $key => $val)
{
    $DB->Execute('INSERT INTO uiconfig(section, var, value) VALUES(?,?,?)',
            array('directories', $key, $val)
            );
}
*/

$DB->CommitTrans();

header('Location: ?m=configlist');
