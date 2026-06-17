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

$filtered_queues = isset($_GET['queueids']) ? Utils::filterIntegers($_GET['queueids']) : [];

$params = [
    'periodicity' => -1, // wszystkie cykliczne
    'count' => false,
];

if (!empty($filtered_queues)) {
	$params['ids'] = $filtered_queues;
};

$rows = $LMS->GetQueueContents($params);

if (is_array($rows)) {
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $p = isset($row['periodicity']) ? (int) $row['periodicity'] : null;

        if (!$p || !isset($events[$p])) {
            continue;
        }

        $ticketid = isset($row['id']) ? (int)$row['id'] : 0;
        if (!$ticketid) {
            continue;
        }

        $ticketEvents = $LMS->GetEventsByTicketId($ticketid) ?: [];
        // Filtrowanie otwartych zdarzeń z datą rozpoczęcia
        $open = array_filter($ticketEvents, fn($e) => isset($e['closed']) && $e['closed'] == 0);

        // Najbliższe zdarzenie
        $next = null;
        if (!empty($open)) {
            usort($open, fn($a, $b) => ($a['begintime'] ?? 0) <=> ($b['begintime'] ?? 0));
            $next = reset($open);
        }

        $next_run = 0;
        if ($next) {
            $next_run = ($next['date'] ?? 0) + ($next['begintime'] ?? 0);
        }

        $events[$p][] = array(
            'ticketid' => isset($row['id']) ? (int) $row['id'] : 0,
            'subject'  => $row['subject'] ?? '',
            'queue_name'  => $row['name'] ?? '',
            'next_run' => $next_run ?? 0,
            'next_run_id' => $next['id'] ?? 0,
        );
    }
}


$EVENT_PERIODICITY = array_filter($EVENT_PERIODICITY, function ($key) use ($events) {
    return !empty($events[$key]);
}, ARRAY_FILTER_USE_KEY);

$SMARTY->assign(array(
    'queuelist'		=> $queuelist,
    'events'            => $events,
    'EVENT_PERIODICITY' => $EVENT_PERIODICITY,
    'filter'         => [ 'queueids' => $filtered_queues ],
));

$SMARTY->display('rt/rtperiodiclist.html');
