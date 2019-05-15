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
$layout['nomenu'] =  true;

echo '<BR><BLOCKQUOTE><H1>'.trans('Compacting Database').'</H1><PRE>';
echo trans('$a records before compacting.<BR>', $DB->GetOne('SELECT COUNT(*) FROM stats'));

if (isset($_POST['delete'])) {
    $yeardeleted = $DB->Execute('DELETE FROM stats where dt < ?NOW? - 365*24*60*60');
    echo trans('$a at least one year old records have been removed.<BR>', $yeardeleted);
}

if (isset($_POST['removedeleted'])) {
    if ($nodes_from_stats = $DB->GetCol('SELECT DISTINCT nodeid FROM stats')) {
        $nodes = $DB->GetCol('SELECT id FROM vnodes');
        foreach ($nodes_from_stats as $node) {
            if (!in_array($node, $nodes)) {
                if ($DB->Execute('DELETE FROM stats WHERE nodeid = '.$node)) {
                    echo trans('Statistics for computer $a has been removed<BR>', $node);
                }
            }
        }
    }
}

if (isset($_POST['level'])) {
    $time = time();
    switch ($_POST['level']) {
        case 1:
            $period = $time-24*60*60;
            $step = 24*60*60;
            break; //1 day, day
        case 2:
            $period = $time-30*24*60*60;
            $step = 24*60*60;
            break;//month, day
        case 3:
            $period = $time-365*24*60*60;
            $step = 60*60;
            break; //month, hour
    }
    if ($mintime = $DB->GetOne('SELECT MIN(dt) FROM stats')) {
        $nodes = $DB->GetAll('SELECT id, name FROM vnodes ORDER BY name');
        foreach ($nodes as $node) {
            echo "'".$node['name']."'\t: ";
            $deleted = 0;
            $inserted = 0;
            $DB->BeginTrans();
            $maxtime = $period;
            while ($maxtime > $mintime) {
                $data = $DB->GetRow('SELECT sum(upload) as upload, sum(download) as download FROM stats WHERE dt >= ? AND dt < ? AND nodeid=? GROUP BY nodeid', array($maxtime-$step,$maxtime,$node['id']));
                $deleted += $DB->Execute('DELETE FROM stats WHERE nodeid=? AND dt >= ? AND dt < ?', array($node['id'],$maxtime-$step,$maxtime));
                $download = (isset($data['download']) ? $data['download'] : 0);
                $upload = (isset($data['upload']) ? $data['upload'] : 0);
                if ($download || $upload) {
                    $inserted += $DB->Execute('INSERT INTO stats (nodeid, dt, upload, download) VALUES (?, ?, ?, ?)', array($node['id'], $maxtime, $upload, $download ));
                }
                $maxtime -= $step;
            }
            $DB->CommitTrans();
            echo trans('$a - removed, $b - inserted<BR>', $deleted, $inserted);
        }
    }
}

echo trans('$a records after compacting.<BR>', $DB->GetOne("SELECT COUNT(*) FROM stats"));
echo '<P><BR><B><A HREF="javascript:window.close();">'.trans('You can close this window now.').'</A></B></BLOCKQUOTE>';
