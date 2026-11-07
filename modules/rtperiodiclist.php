<?php

$SESSION->add_history_entry();

$events = [];

foreach ($EVENT_PERIODICITY as $key => $data) {
    if ($key == DISPOSABLE) {
        continue;
    }
    $events[$key] = [];
}

$queuelist = $LMS->GetQueueList(
    [
      'stats' => false,
      'deleted' => false,
    ]
);

$filteredQueues = isset($_GET['queueids']) ? Utils::filterIntegers($_GET['queueids']) : [];

$params = [
    'periodicity' => -1,
    'count' => false,
    'short' => true,
];

if (!empty($filteredQueues)) {
    $params['ids'] = $filteredQueues;
}

$rows = $LMS->GetQueueContents($params);

if (is_array($rows)) {
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $periodicity = isset($row['periodicity']) ? (int) $row['periodicity'] : null;

        if (!$periodicity || !isset($events[$periodicity])) {
            continue;
        }

        $ticketId = isset($row['id']) ? (int) $row['id'] : 0;
        if (!$ticketId) {
            continue;
        }

        $ticketEvents = $LMS->GetEventsByTicketId($ticketId) ?: [];
        $openEvents = array_filter($ticketEvents, fn($event) => isset($event['closed']) && $event['closed'] == 0);

        $nextEvent = null;
        if (!empty($openEvents)) {
            usort($openEvents, fn($a, $b) => ($a['begintime'] ?? 0) <=> ($b['begintime'] ?? 0));
            $nextEvent = reset($openEvents);
        }

        $nextRun = 0;
        if ($nextEvent) {
            $nextRun = ($nextEvent['date'] ?? 0) + ($nextEvent['begintime'] ?? 0);
        }

        $events[$periodicity][] = array(
            'ticketid' => $ticketId,
            'subject' => $row['subject'] ?? '',
            'queueName' => $row['name'] ?? '',
            'nextRun' => $nextRun,
            'nextRunId' => $nextEvent['id'] ?? 0,
        );
    }
}


$EVENT_PERIODICITY = array_filter($EVENT_PERIODICITY, function ($key) use ($events) {
    return !empty($events[$key]);
}, ARRAY_FILTER_USE_KEY);

$SMARTY->assign(array(
    'queuelist'     => $queuelist,
    'events'            => $events,
    'EVENT_PERIODICITY' => $EVENT_PERIODICITY,
    'filter' => [ 'queueids' => $filteredQueues ],
));

$SMARTY->display('rt/rtperiodiclist.html');
