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
-c, --schedule-tickets   create events for tickets that have scheduling set
                         (without this option only print events which would be created);
-d, --debug              print all possible output;
EOF;

require_once('script-options.php');

$debug = isset($options['debug']);
$scheduleTickets = isset($options['schedule-tickets']);

$SYSLOG = SYSLOG::getInstance();
$AUTH = null;
$LMS = new LMS($DB, $AUTH, $SYSLOG);
Localisation::setUiLanguage('pl');

$plugin_manager = LMSPluginManager::getInstance();

$LMS->setPluginManager($plugin_manager);

$schedulePlanningForwardEvents = ConfigHelper::getConfig('rt.schedule_planing_forward_events', 3);

function periodicOccurrence(int $anchor, int $periodicity, int $k): ?int
{
    static $days = [DAILY => 1, EVERY_OTHER_DAY => 2, WEEKLY => 7, EVERY_OTHER_WEEK => 14];
    static $months = [MONTHLY => 1, EVERY_OTHER_MONTH => 2, QUARTERLY => 3, HALFYEARLY => 6, YEARLY => 12];

    [$y, $m, $d] = array_map('intval', explode('/', date('Y/n/j', $anchor)));
    if (isset($days[$periodicity])) {
        return mktime(0, 0, 0, $m, $d + $k * $days[$periodicity], $y);
    }
    if (isset($months[$periodicity])) {
        $monthIndex = $m - 1 + $k * $months[$periodicity];
        $y += intdiv($monthIndex, 12);
        $m = $monthIndex % 12 + 1;
        $d = min($d, (int) date('t', mktime(0, 0, 0, $m, 1, $y)));
        return mktime(0, 0, 0, $m, $d, $y);
    }
    return null;
}

function skipHolidays(int $date): int
{
    static $holidays = [];

    while (true) {
        $year = date('Y', $date);
        if (!isset($holidays[$year])) {
            $holidays[$year] = getHolidays($year) ?: [];
        }
        if (!isset($holidays[$year][$date])) {
            return $date;
        }
        $date = strtotime('+1 day', $date);
    }
}

function nextOccurrence(int $anchor, int $periodicity, int $after, int $today): ?int
{
    for ($k = 1; $k <= 10000; $k++) {
        $date = periodicOccurrence($anchor, $periodicity, $k);
        if (!isset($date)) {
            return null;
        }
        if ($date > $after && $date >= $today) {
            return skipHolidays($date);
        }
    }
    return null;
}

$today = strtotime('today');

$ticketsToPlan = $LMS->GetQueueContents(array(
    'periodicity' => -1,
    'state' => -1,
    'count' => false,
    'short' => true,
));
if (empty($ticketsToPlan)) {
    exit(0);
}

foreach ($ticketsToPlan as $t) {
    $ticket = $LMS->GetTicketContents($t['id']);
    if (empty($ticket)) {
        continue;
    }
    $ticketEvents = $LMS->GetEventsByTicketId($ticket['ticketid']) ?: array();
    $firstEvent = null;
    $lastEvent = null;
    foreach ($ticketEvents as $event) {
        if ($firstEvent === null
            || [$event['date'], $event['begintime'], $event['id']]
                < [$firstEvent['date'], $firstEvent['begintime'], $firstEvent['id']]
        ) {
            $firstEvent = $event;
        }
        if ($lastEvent === null || [$event['date'], $event['id']] > [$lastEvent['date'], $lastEvent['id']]) {
            $lastEvent = $event;
        }
    }
    $anchor = $firstEvent ? (int) $firstEvent['date'] : strtotime('today', $ticket['createtime']);
    $lastEvent = $lastEvent ?? array();
    $lastDate = !empty($lastEvent['date']) ? (int) $lastEvent['date'] : null;

    for ($a = ($ticket['openeventcount'] ?? 0); $a < $schedulePlanningForwardEvents; $a++) {
        $date = isset($lastDate) ? nextOccurrence($anchor, (int) $ticket['periodicity'], $lastDate, $today) : max($anchor, $today);
        if (!isset($date)) {
            break;
        }
        $lastDate = $date;

        $eventExists = false;
        foreach ($ticketEvents as $event) {
            if ((int) $event['date'] === (int) $date) {
                $eventExists = true;
                break;
            }
        }
        if ($eventExists) {
            continue;
        }

        $params = [
            'date' => $date,
            'begintime' => $lastEvent['begintime'] ?? 0,
            'endtime' => $lastEvent['endtime'] ?? 0,
            'enddate' => empty($lastEvent['enddate']) ? 0 : $date + ($lastEvent['enddate'] - $lastEvent['date']),
            'title' => $ticket['messages'][0]['subject'] ?? null,
            'description' => $lastEvent['description'] ?? ($ticket['messages'][0]['body'] ?? null),
            'note' => $lastEvent['note'] ?? null,
            'userlist' => isset($ticket['owner']) ? [$ticket['owner']] : null,
            'custid' => $ticket['customerid'] ?? null,
            'address_id' => $ticket['address_id'] ?? null,
            'ticketid' => $ticket['ticketid'],
            'nodeid' => $ticket['nodeid'] ?? null,
            'private' => $lastEvent['private'] ?? 0,
            'type' => $lastEvent['type'] ?? EVENT_OTHER,
            'loggedas' => $ticket['owner'] ?? $ticket['creatorid'],
        ];

        if (!$quiet) {
            printf(
                '%s ticket #%06d: %s %s' . PHP_EOL,
                $scheduleTickets ? 'Creating event for' : '[dry-run] Would create event for',
                $ticket['ticketid'],
                date('Y/m/d', $date),
                $params['title']
            );
        }
        if ($debug) {
            echo print_r($params, true) . PHP_EOL;
        }
        if ($scheduleTickets) {
            $LMS->EventAdd($params);
        }
    }
}
