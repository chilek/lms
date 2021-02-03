<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2009 Webvisor Sp. z o.o.
 *
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
 */

include(LIB_DIR.'/dns.php');

$id = $_GET['id']*1;

$record = $DB->GetRow('SELECT d.name AS domainname, r.*
	FROM domains d, records r
	WHERE r.domain_id = d.id AND r.id = ?', array($id));

if (isset($_POST['record'])) {
    $rec = $_POST['record'];
    
    foreach ($rec as $idx => $val) {
        $rec[$idx] = trim(strip_tags($val));
    }
    
    $record = array_merge($record, $rec);

    if ($record['ttl']*1 <= 0 || !is_numeric($record['ttl'])) {
        $error['ttl'] = trans('Wrong TTL');
    }

    // call validate... after all checks
    if (!$error) {
        validate_dns_record($record, $error);
    }

    if (!$error) {
        if (strlen($record['name'])) {
            $record['name'] = trim($record['name'], '.').'.';
        }
        $record['name'] .= $record['domainname'];

        $DB->Execute(
            'UPDATE records SET name = ?, type = ?, content = ?, ttl = ?, prio = ?
			WHERE id = ?',
            array($record['name'],
                $record['type'],
                $record['content'],
                $record['ttl'],
                $record['prio'],
                $record['id']
            )
        );

        update_soa_serial($record['domain_id']);

        $SESSION->redirect('?m=recordlist&d='.$record['domain_id']);
    }
    
    $SMARTY->assign('error', $error);
} else {
    parse_dns_record($record);
}

$layout['pagetitle'] = trans('DNS Record Edit');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('record', $record);
$SMARTY->display('record/recordedit.html');
