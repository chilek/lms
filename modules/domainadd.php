<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

include(LIB_DIR.'/dns.php');

function GetDomainIdByName($name)
{
    global $DB;
    return $DB->GetOne('SELECT id FROM domains WHERE name = ?', array($name));
}

$domainadd = array();

if (isset($_POST['domainadd'])) {
    $domainadd = $_POST['domainadd'];

    $domainadd['name'] = trim($domainadd['name']);
    $domainadd['description'] = trim($domainadd['description']);
    $domainadd['master'] = trim($domainadd['master']);

    if ($domainadd['type'] == 'SLAVE') {
        if (!check_ip($domainadd['master'])) {
            $error['master'] = trans('IP address of master NS is required!');
        }
    } else {
            $domainadd['master'] = '';
        if (!check_ip($domainadd['ipwebserver'])) {
            $error['ipwebserwer'] = trans('IP address of webserver is required!');
        }
        if (!check_ip($domainadd['ipmailserver'])) {
            $error['ipmailserwer'] = trans('IP address of mailserver is required!');
        }
    }

    if ($domainadd['name'] == '') {
                $error['name'] = trans('Domain name is required!');
    } else if ($errorname = check_hostname_fqdn($domainadd['name'], false, true)) {
            $error['name'] = $errorname;
    } else if (GetDomainIdByName($domainadd['name'])) {
        $error['name'] = trans('Domain with specified name exists!');
    }
    
    if ($domainadd['ownerid']) {
        $limits = $LMS->GetHostingLimits($domainadd['ownerid']);
        
        if ($limits['domain_limit'] !== null) {
            if ($limits['domain_limit'] > 0) {
                $cnt = $DB->GetOne(
                    'SELECT COUNT(*) FROM domains WHERE ownerid = ?',
                    array($domainadd['ownerid'])
                );
            }

            if ($limits['domain_limit'] == 0 || $limits['domain_limit'] <= $cnt) {
                $error['ownerid'] = trans('Exceeded domains limit of selected customer ($a)!', $limits['domain_limit']);
            }
        }
    }
    
    if (!$error) {
        $DB->BeginTrans();
    
        $DB->Execute(
            'INSERT INTO domains (name, ownerid, type, master, description, mxbackup) VALUES (?,?,?,?,?,?)',
            array($domainadd['name'],
                    empty($domainadd['ownerid']) ? null : $domainadd['ownerid'],
                    $domainadd['type'],
                    $domainadd['master'],
                    $domainadd['description'],
                    empty($domainadd['mxbackup']) ? 0 : 1)
        );

        $lid = $DB->GetLastInsertID('domains');

        if ($domainadd['type'] != 'SLAVE') {
            $tlds = explode('.', $domainadd['name']);

            $DB->Execute(
                'INSERT INTO records(domain_id,name,ttl,type,prio,content)
				VALUES (?, ?, ?, \'SOA\', 0, ?)',
                array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                    ConfigHelper::getConfig('zones.master_dns').' '.ConfigHelper::getConfig('zones.hostmaster_mail').' '
                    .date('Ymd').'00 '.ConfigHelper::getConfig('zones.ttl_refresh').' '
                    .ConfigHelper::getConfig('zones.ttl_retry').' '.ConfigHelper::getConfig('zones.ttl_expire').' '
                    .ConfigHelper::getConfig('zones.ttl_minimum'))
            );

            $DB->Execute(
                'INSERT INTO records(domain_id,name,ttl,type,prio,content)
				VALUES (?, ?, ?, \'NS\', 0, ?)',
                array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                    ConfigHelper::getConfig('zones.master_dns'))
            );

            $DB->Execute(
                'INSERT INTO records(domain_id,name,ttl,type,prio,content)
				VALUES (?, ?, ?, \'NS\', 0, ?)',
                array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                    ConfigHelper::getConfig('zones.slave_dns'))
            );
        
            if ($tlds[count($tlds)-2].$tlds[count($tlds)-1] != 'in-addrarpa') {
                $DB->Execute(
                    'INSERT INTO records(domain_id,name,ttl,type,prio,content)
					VALUES (?, ?, ?, \'A\', 0, ?)',
                    array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                        $domainadd['ipwebserver'])
                );
                $DB->Execute(
                    'INSERT INTO records(domain_id,name,ttl,type,prio,content)
					VALUES (?, ?, ?, \'A\', 0, ?)',
                    array($lid,'www.'.$domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                        $domainadd['ipwebserver'])
                );
                $DB->Execute(
                    'INSERT INTO records(domain_id,name,ttl,type,prio,content)
					VALUES (?, ?, ?, \'A\', 0, ?)',
                    array($lid, 'mail.'.$domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                        $domainadd['ipmailserver'])
                );
                $DB->Execute(
                    'INSERT INTO records(domain_id,name,ttl,type,prio,content)
					VALUES (?, ?, ?, \'MX\', 10, ?)',
                    array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                        ConfigHelper::getConfig('zones.default_mx'))
                );
                if (ConfigHelper::getConfig('zones.default_spf')) {
                    $DB->Execute(
                        'INSERT INTO records(domain_id,name,ttl,type,prio,content)
						VALUES (?, ?, ?, \'TXT\', 0, ?)',
                        array($lid, $domainadd['name'], ConfigHelper::getConfig('zones.default_ttl'),
                            ConfigHelper::getConfig('zones.default_spf'))
                    );
                }
            }
        }
        
        $DB->CommitTrans();
        
        if (!isset($domainadd['reuse'])) {
            $SESSION->redirect('?m=domainlist');
        }
        
        unset($domainadd['name']);
        unset($domainadd['description']);
    }
} elseif (isset($_GET['cid'])) {
        $domainadd['ownerid'] = intval($_GET['cid']);
}

if (empty($domainadd['ipwebserver'])) {
    $domainadd['ipwebserver'] = ConfigHelper::getConfig('zones.default_webserver_ip');
}
if (empty($domainadd['ipmailserver'])) {
    $domainadd['ipmailserver'] = ConfigHelper::getConfig('zones.default_mailserver_ip');
}

$layout['pagetitle'] = trans('New Domain');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('domainadd', $domainadd);
$SMARTY->assign('error', $error);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->display('domain/domainadd.html');
