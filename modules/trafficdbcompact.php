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

$layout['pagetitle'] = trans('Network Statistics Compacting');

if (!isset($_GET['level']) && !isset($_GET['removeold']) && !isset($_GET['removedeleted'])) {
    $SMARTY->display('traffic/trafficdbcompact.html');
    $SESSION->close();
    die;
}

set_time_limit(0);

$SMARTY->display('header.html');

echo '<H1>'.trans('Compacting Database').'</H1><PRE>';
echo trans('$a records before compacting.<BR>', $DB->GetOne('SELECT COUNT(*) FROM stats'));
flush();

if (isset($_GET['removeold'])) {
    if ($deleted = $DB->Execute('DELETE FROM stats where dt < ?NOW? - 365*24*60*60')) {
        echo trans('$a at least one year old records have been removed.<BR>', $deleted);
        flush();
    }
}

if (isset($_GET['removedeleted'])) {
    if ($deleted = $DB->Execute('DELETE FROM stats WHERE nodeid NOT IN (SELECT id FROM vnodes)')) {
        echo trans('$a records for deleted nodes have been removed.<BR>', $deleted);
        flush();
    }
}

if (isset($_GET['level'])) {
    $time = time();
    switch ($_GET['level']) {
        case 'medium':
            $period = $time-30*24*60*60;
            $step = 24*60*60;
            break;//month, day
        case 'high':
            $period = $time-30*24*60*60;
            $step = 60*60;
            break; //month, hour
        default:
            $period = $time-24*60*60;
            $step = 24*60*60;
            break; //1 day, day
    }

    if ($mintime = $DB->GetOne('SELECT MIN(dt) FROM stats')) {
        $nodes = $DB->GetAll('SELECT id, name FROM vnodes ORDER BY name');

        foreach ($nodes as $node) {
            $deleted = 0;
            $inserted = 0;
            $maxtime = $period;
            $timeoffset = date('Z');
            $dtdivider = 'FLOOR((dt+'.$timeoffset.')/'.$step.')';

            $data = $DB->GetAll('SELECT SUM(download) AS download, SUM(upload) AS upload,
                    COUNT(dt) AS count, MIN(dt) AS mintime, MAX(dt) AS maxtime, nodesessionid
                FROM stats WHERE nodeid = ? AND dt >= ? AND dt < ? 
                GROUP BY nodeid, nodesessionid, '.$dtdivider.'
                ORDER BY mintime', array($node['id'], $mintime, $maxtime));

            if ($data) {
                // If divider-record contains only one record we can skip it
                // This way we'll minimize delete-insert operations count
                // e.g. in situation when some records has been already compacted
                foreach ($data as $rid => $record) {
                    if ($record['count'] == 1) {
                        unset($data[$rid]);
                    } else {
                        break;
                    }
                }

                // all records for this node has been already compacted
                if (empty($data)) {
                    echo $node['name'].': '.trans('$a - removed, $b - inserted<BR>', 0, 0);
                    flush();
                    continue;
                }

                $values = array();
                // set start datetime of the period
                $data = array_values($data);
                $nodemintime = $data[0]['mintime'];

                $DB->BeginTrans();

                // delete old records
                $DB->Execute(
                    'DELETE FROM stats WHERE nodeid = ? AND dt >= ? AND dt <= ?',
                    array($node['id'], $nodemintime, $maxtime)
                );

        // insert new (summary) records
                foreach ($data as $record) {
                    $deleted += $record['count'];

                    if (!$record['download'] && !$record['upload']) {
                        continue;
                    }

                    $values[] = sprintf(
                        '(%d, %d, %d, %d, %s)',
                        $node['id'],
                        $record['maxtime'],
                        $record['upload'],
                        $record['download'],
                        $DB->Escape(empty($record['nodesessionid']) ? null : $record['nodesessionid'])
                    );
                }

                if (!empty($values)) {
                    $inserted = $DB->Execute('INSERT INTO stats
				(nodeid, dt, upload, download, nodesessionid) VALUES ' . implode(', ', $values));
                }

                $DB->CommitTrans();

                echo $node['name'].': '.trans('$a - removed, $b - inserted<BR>', $deleted, $inserted);
                flush();
            }
        }
    }
}

echo trans('$a records after compacting.', $DB->GetOne('SELECT COUNT(*) FROM stats'));
echo '</PRE>';
flush();

$SMARTY->display('footer.html');
