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

$alias = $DB->GetRow('SELECT a.id, a.login, a.domainid, d.name AS domain
		FROM aliases a JOIN domains d ON (a.domainid = d.id)
		WHERE a.id = ?', array(intval($_GET['id'])));

if (!$alias) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

$alias['accounts'] = $DB->GetAllByKey('SELECT p.id, p.login, d.name AS domain
		FROM passwd p JOIN domains d ON (p.domainid = d.id)
		WHERE p.id IN (SELECT accountid FROM aliasassignments
			WHERE aliasid = ?)', 'id', array($alias['id']));
$mailforwards = $DB->GetAllByKey(
    'SELECT mail_forward
		FROM aliasassignments WHERE aliasid = ? AND accountid IS NULL AND mail_forward <> \'\'',
    'mail_forward',
    array($alias['id'])
);
$alias['mailforwards'] = array();
if (count($mailforwards)) {
    foreach ($mailforwards as $mailforward => $idx) {
        $alias['mailforwards'][] = $mailforward;
    }
}

$layout['pagetitle'] = trans('Alias Info: $a', $alias['login'] .'@'. $alias['domain']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('alias', $alias);
$SMARTY->display('alias/aliasinfo.html');
