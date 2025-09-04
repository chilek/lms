#!/usr/bin/env php
<?php

/*
 * LMS version 28.x
 *
 *  (C) Copyright 2001-2025 LMS Developers
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

$script_parameters = array(
    'schedule-tickets' => 'c',
    'debug' => 'd',
);

$script_help = <<<EOF
-c, --schedule-tickets   create events for tickets with periodicity set;
-d, --debug              print all possible output;
EOF;

require_once('script-options.php');

$debug = isset($options['debug']);

// Initialize Session, Auth and LMS classes
$SYSLOG = null;
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
Localisation::setUiLanguage('pl');

$plugin_manager = LMSPluginManager::getInstance();
$LMS->setPluginManager($plugin_manager);

$rt_schedule_planing_forward_events = ConfigHelper::getConfig('rt.schedule_planing_forward_events', 3);
$tickets_to_plan = $LMS->GetQueueContents(
    [
            'periodicity' => -1,
            'deleted' => 0,
            'closed' => 0,
            'short' => true,
        ]
);

function CalculateNextOccurrenceInFuture($start, $periodicity)
{
    global $EVENT_PERIODICITY;
    $timestamp = (new DateTime())->setTimestamp($start);
    $interval = $EVENT_PERIODICITY[$periodicity]['map'];
    $now = time();

    do {
        $timestamp->modify($interval);
    } while ($timestamp->getTimestamp() <= $now);

    return $timestamp->getTimestamp();
}

if (empty($tickets_to_plan)) {
       die();
}

foreach ($tickets_to_plan as $idx => $t) {
    $t = $LMS->GetTicketContents($t['id']);

    for ($a = $t['openeventcount'] ?? 0; $a < $rt_schedule_planing_forward_events; $a++) {
        $ticket_events = $LMS->GetEventsByTicketId($t['ticketid']);
        $last_ticket_event = (is_array($ticket_events) && !empty($ticket_events)) ? end($ticket_events) : null;
        $timestamp = $last_ticket_event['date'] ?? $t['createtime'];
        $next_occurrence = CalculateNextOccurrenceInFuture($timestamp, $t['periodicity']);
        $params = [
            'date' => $next_occurrence,
            'begintime' => $last_ticket_event['begintime'] ?? 0,
            'endtime' => $last_ticket_event['endtime'] ?? 0,
            'enddate' => $last_ticket_event['enddate'] ?? 0,
            'title' => $t['messages'][0]['subject'] ?? null,
            'description' => $t['messages'][0]['body'] ?? null,
            'userlist' => array($t['owner']) ?? null,
            'custid' => $t['customerid'] ?? null,
            'address_id' => $t['address_id'] ?? null,
            'ticketid' => $t['ticketid'],
            'nodeid' => $t['nodeid'] ?? null,
            'private' => 0,
            'type' => EVENT_OTHER,
        ];
        if ($debug) {
            print_r($params) . PHP_EOL;
        }
        $LMS->EventAdd($params);
    }
}
