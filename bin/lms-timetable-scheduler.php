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

const RT_SCHEDULER_LOCK_ID = 284739102;
$lock = $DB->GetOne('SELECT pg_try_advisory_lock(?)', [RT_SCHEDULER_LOCK_ID]);

if (!$lock) {
    $debug && print "Scheduler already running – exiting.\n";
    exit;
}

$rt_schedule_planing_forward_events = ConfigHelper::getConfig('rt.schedule_planing_forward_events', 3);

function CalculateNextOccurrenceInFuture(
    int $date,
    int $begintime,
    int $endtime,
    int $enddate,
    int $periodicity
): array {
    global $EVENT_PERIODICITY;

    $startTs = $date + $begintime;

    $dt = new DateTime('@' . $startTs);
    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));

    $interval = $EVENT_PERIODICITY[$periodicity]['map'];
    $now = time();

    do {
        $dt->modify($interval)->setTime(
            (int) gmdate('H', $startTs),
            (int) gmdate('i', $startTs),
            (int) gmdate('s', $startTs)
        );

        $holidays = getHolidays($dt->format('Y'));
        while (in_array($dt->format('Y-m-d'), $holidays, true)) {
            $dt->modify('+1 day');
        }
    } while ($dt->getTimestamp() <= $now);

    $newDate = strtotime($dt->format('Y-m-d'));

    return [
        'date'      => $newDate,
        'begintime' => $begintime,
        'endtime'   => $endtime,
        'enddate'   => $enddate ? $newDate + ($enddate - $date) : 0,
    ];
}

function getTicketsToPlan()
{
    global $DB;
    return $DB->GetAll('
        SELECT id
        FROM rttickets
        WHERE
            periodicity IS NOT NULL
            AND periodicity != 0
            AND deleted = 0
            AND state != 2
    ');
}

function getLastTicketEvent($ticketid)
{
    global $DB;
    return $DB->GetRow('
        SELECT e.*
        FROM events e
        WHERE e.ticketid = ?
        ORDER BY e.date DESC, e.id DESC
        LIMIT 1
    ', [$ticketid]) ?: null;
}

try {
    $tickets_to_plan = getTicketsToPlan();
    if (empty($tickets_to_plan)) {
        die();
    }

    foreach ($tickets_to_plan as $idx => $t) {
        $ticket = $LMS->GetTicketContents($t['id']);
        for ($a = ($ticket['openeventcount'] ?? 0); $a < $rt_schedule_planing_forward_events; $a++) {
            $last_opened_ticket_event = getLastTicketEvent($ticket['ticketid']) ?? [];
            $timestamp = ($last_opened_ticket_event['date'] ?? $ticket['createtime']) + ($last_opened_ticket_event['begintime'] ?? 0);

	    // Dane do nowego zdarzenia w zależności od tego czy ticket ma zaplanowane otwarte zdarzenie
	    // (jeśli nie ma planuje na następny dzień)
            if ($last_opened_ticket_event) {
	        $new_event_details = CalculateNextOccurrenceInFuture(
                    $last_opened_ticket_event['date'] ?? $ticket['createtime'],
                    $last_opened_ticket_event['begintime'] ?? 0,
                    $last_opened_ticket_event['endtime'] ?? 0,
                    $last_opened_ticket_event['enddate'] ?? 0,
                    $ticket['periodicity'],
                );
            } else {
                $new_event_details = [
                        'date' => $ticket['createtime'],
                        'begintime' => 0,
                        'endtime' => 0,
                        'enddate' => 0,
                ];
            }

            $params = [
                'date' => $new_event_details['date'],
                'begintime' => $new_event_details['begintime'] ?? 0,
                'endtime' => $new_event_details['endtime'] ?? 0,
                'enddate' => $new_event_details['enddate'] ?? 0,
                'title' => $ticket['messages'][0]['subject'] ?? null,
                'description' => $last_opened_ticket_event['description'] ?? ($ticket['messages'][0]['body'] ?? null),
                'userlist' => isset($t['owner']) ? [$t['owner']] : null,
                'custid' => $ticket['customerid'] ?? null,
                'address_id' => $ticket['address_id'] ?? null,
                'ticketid' => $ticket['ticketid'],
                'nodeid' => $ticket['nodeid'] ?? null,
                'private' => $last_opened_ticket_event['private'] ?? 0,
                'type' => $last_opened_ticket_event['type'] ?? EVENT_OTHER,
                'loggedas' => $ticket['owner'] ?? $ticket['creatorid'],
            ];

            if ($debug) {
                echo print_r($params, true) . PHP_EOL;
	    }
            $LMS->EventAdd($params);
            // aktualizacja "ostatniego eventu"
            $last_opened_ticket_event = array_merge($last_opened_ticket_event, $new_event_details);
        }
    }
} finally {
    $DB->Execute('SELECT pg_advisory_unlock(?)', [RT_SCHEDULER_LOCK_ID]);
}

exit;

$looped_events = '
-- A: enddate < date X KLASYCZNA PĘTLA
SELECT "A" AS reason, id
FROM events
WHERE enddate <> 0
  AND enddate < date

UNION ALL

-- B: endtime < begintime przy tym samym dniu
SELECT "B", id
FROM events
WHERE endtime < begintime
  AND NOT (begintime = 0 AND endtime = 0)

UNION ALL

-- C: całodniowe, tylko jeśli faktycznie cofają się w czasie
SELECT "C", id
FROM events
WHERE begintime = 0
  AND endtime = 86400
  AND enddate <> 0
  AND enddate < date

UNION ALL

-- D: 00:00–00:00, tylko jeśli enddate < date
SELECT "D", id
FROM events
WHERE begintime = 0
  AND endtime = 0
  AND enddate <> 0
  AND enddate < date
ORDER BY reason, id';
