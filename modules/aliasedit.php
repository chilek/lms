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

function AliasExists($login, $domain)
{
    global $DB;
    return ($DB->GetOne('SELECT id FROM aliases WHERE login = ? AND domainid = ?', array($login, $domain)) ? true : false);
}

function AccountExists($login, $domain)
{
    global $DB;
    return ($DB->GetOne('SELECT id FROM passwd WHERE login = ? AND domainid = ?', array($login, $domain)) ? true : false);
}

$aliasold = $DB->GetRow('SELECT a.id, a.login, a.domainid, d.name AS domain
		FROM aliases a JOIN domains d ON (a.domainid = d.id)
		WHERE a.id = ?', array(intval($_GET['id'])));

if (!$aliasold) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$layout['pagetitle'] = trans('Alias Edit: $a', $aliasold['login'] .'@'. $aliasold['domain']);

if (isset($_POST['alias'])) {
    $alias = $_POST['alias'];
    $alias['id'] = $aliasold['id'];
    $alias['login'] = trim($alias['login']);
    $alias['accounts'] = $SESSION->get('aliasaccounts');
    $alias['mailforwards'] = $SESSION->get('aliasmailforwards');

    if (!isset($alias['domainalias'])) {
        if ($alias['login'] == '') {
            $error['login'] = trans('You have to specify alias name!');
        } elseif (!preg_match('/^[a-z0-9._-]+$/', $alias['login'])) {
                $error['login'] = trans('Login contains forbidden characters!');
        }
    } else {
        $alias['login'] == '';
    }
    if (!$alias['domainid']) {
        $error['domainid'] = trans('You have to select domain for alias!');
    } elseif ($alias['login'] != $aliasold['login'] || $alias['domainid'] != $aliasold['domainid']) {
        if (AliasExists($alias['login'], $alias['domainid'])) {
            $error['login'] = trans('Alias with that login name already exists in that domain!');
        } elseif (AccountExists($alias['login'], $alias['domainid'])) {
            $error['login'] = trans('Account with that login name already exists in that domain!');
        }
    }
        
    if (!empty($_GET['delaccount'])) {
        unset($alias['accounts'][intval($_GET['delaccount'])]);
    }

    if ($alias['accountid'] && !isset($alias['accounts'][$alias['accountid']])) {
        if ($account = $DB->GetRow('SELECT p.id, p.login, d.name AS domain
				FROM passwd p, domains d WHERE p.domainid = d.id
					AND p.id = ?', array(intval($alias['accountid'])))) {
            $alias['accounts'][$account['id']] = $account;
        }
    }

    if (!empty($_GET['delmailforward'])) {
        unset($alias['mailforwards'][array_search($_GET['delmailforward'], $alias['mailforwards'])]);
    }

    if ($alias['mailforward'] && (!is_array($alias['mailforwards']) || !in_array($alias['mailforward'], $alias['mailforwards']))) {
        $alias['mailforwards'][] = $alias['mailforward'];
    }

    if (empty($_GET['addaccount']) && empty($_GET['delaccount'])
        && empty($_GET['addaccount']) && empty($_GET['delaccount'])
        && !count($alias['accounts']) && !count($alias['mailforwards'])) {
        $error['accountid'] = trans('You have to select destination account!');
        $error['mailforward'] = trans('You have to specify forward e-mail!');
    }
    
    if (!$error && empty($_GET['addaccount']) && empty($_GET['delaccount'])
        && empty($_GET['addmailforward']) && empty($_GET['delmailforward'])) {
        $DB->BeginTrans();
        
        $DB->Execute(
            'UPDATE aliases SET login = ?, domainid = ?
				WHERE id = ?',
            array($alias['login'],
                    $alias['domainid'],
                    $alias['id'])
        );
        
        $DB->Execute(
            'DELETE FROM aliasassignments WHERE aliasid = ?',
            array($alias['id'])
        );
        
        if (count($alias['accounts'])) {
            foreach ($alias['accounts'] as $account) {
                $DB->Execute('INSERT INTO aliasassignments (aliasid, accountid)
					VALUES(?,?)', array($alias['id'], $account['id']));
            }
        }

        if (count($alias['mailforwards'])) {
            foreach ($alias['mailforwards'] as $mailforward) {
                $DB->Execute('INSERT INTO aliasassignments (aliasid, mail_forward)
					VALUES(?,?)', array($alias['id'], $mailforward));
            }
        }
        
        $DB->CommitTrans();

        $SESSION->remove('aliasaccounts');
        $SESSION->remove('aliasmailforwards');
        $SESSION->redirect('?m=aliaslist');
    }
} else {
    $alias = $aliasold;
    $alias['accounts'] = $DB->GetAllByKey('SELECT p.id, p.login, d.name AS domain
			FROM passwd p JOIN domains d ON (p.domainid = d.id)
			WHERE p.id IN (SELECT accountid FROM aliasassignments
				WHERE aliasid = ? AND mail_forward=\'\')', 'id', array($alias['id']));
    $mailforwards = $DB->GetAllByKey(
        'SELECT mail_forward
			FROM aliasassignments WHERE aliasid = ? AND accountid IS NULL AND mail_forward <> \'\' 
			ORDER BY mail_forward',
        'mail_forward',
        array($alias['id'])
    );
    $alias['mailforwards'] = array();
    if (count($mailforwards)) {
        foreach ($mailforwards as $mailforward => $idx) {
            $alias['mailforwards'][] = $mailforward;
        }
    }
    if ($alias['login'] == '') {
        $alias['domainalias'] = true;
    }
}

if (isset($alias['accounts']) && count($alias['accounts'])) {
    $where = 'AND passwd.id NOT IN ('.implode(',', array_keys($alias['accounts'])).')';
}

$accountlist = $DB->GetAll('SELECT passwd.id, login, domains.name AS domain 
			FROM passwd, domains 
			WHERE domainid = domains.id '
            .(isset($where) ? $where : '')
            .' ORDER BY login, domains.name');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);
$SESSION->save('aliasaccounts', $alias['accounts']);
$SESSION->save('aliasmailforwards', $alias['mailforwards']);

$SMARTY->assign('alias', $alias);
$SMARTY->assign('error', $error);
$SMARTY->assign('accountlist', $accountlist);
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->display('alias/aliasedit.html');
