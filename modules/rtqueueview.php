<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2019 LMS Developers
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

$LMS->CleanupTicketLastView();

// queue id's
if (isset($_GET['id'])) {
    if ($_GET['id'] == 'all') {
        $filter['ids'] = null;
    } else {
        if (is_array($_GET['id'])) {
            $filter['ids'] = Utils::filterIntegers($_GET['id']);
        } elseif (intval($_GET['id'])) {
            $filter['ids'] = Utils::filterIntegers(array($_GET['id']));
        }
        if (!isset($filter['ids']) || empty($filter['ids'])) {
            $SESSION->redirect('?m=rtqueuelist');
        }
        if (isset($filter['ids'])) {
            $filter['ids'] = array_filter($filter['ids'], array($LMS, 'QueueExists'));
        }
    }
} else {
    if (!empty($filter['ids'])) {
        foreach ($filter['ids'] as $queueidx => $queueid) {
            if (!$LMS->GetUserRightsRT(Auth::GetCurrentUser(), $queueid)) {
                unset($filter['ids'][$queueidx]);
            }
        }
        if (empty($filter['ids'])) {
            access_denied();
        }
    }
}

if (empty($filter['ids'])) {
    $queues = $DB->GetCol('SELECT queueid FROM rtrights WHERE userid=?', array(Auth::GetCurrentUser()));

    if (!$queues) {
        access_denied();
    }

    if (count($queues) != $DB->GetOne('SELECT COUNT(*) FROM rtqueues')) {
        $filter['ids'] = $queues;
    } else {
        $filter['ids'] = null;
    }
}

// category id's
if (!empty($_GET['catid'])) {
    if (!is_array($_GET['catid'])) {
        $_GET['catid'] = array($_GET['catid']);
    }

    if (!in_array('all', $_GET['catid'])) {
        $filter['catids'] = Utils::filterIntegers($_GET['catid']);
    }
}

if (!empty($filter['catids'])) {
    foreach ($filter['catids'] as $catidx => $catid) {
        if ($catid != -1) {
            if (!$LMS->GetUserRightsToCategory(Auth::GetCurrentUser(), $catid)) {
                unset($filter['catids'][$catidx]);
            }
        }
    }
    if (empty($filter['catids'])) {
        access_denied();
    }
}
/*else {
    $categories = $DB->GetCol('SELECT categoryid FROM rtcategoryusers WHERE userid=?', array(Auth::GetCurrentUser()));
    $all_cat = $DB->GetOne('SELECT COUNT(*) FROM rtcategories');

    if (!$categories && $all_cat)
        access_denied();

    if (count($categories) != $all_cat)
        $filter['catids'] = $categories;
}*/

// sort order
if (isset($_GET['o'])) {
    $filter['order'] = $_GET['o'];
}

// service id's
if (isset($_GET['ts'])) {
    if (is_array($_GET['ts'])) {
        $filter['serviceids'] = Utils::filterIntegers($_GET['ts']);
    } elseif (intval($_GET['ts'])) {
        $filter['serviceids'] = Utils::filterIntegers(array($_GET['ts']));
    } elseif ($_GET['ts'] == 'all') {
        $filter['serviceids'] = null;
    }
}

// verifier id's
if (isset($_GET['vids'])) {
    if (is_array($_GET['vids'])) {
        $filter['verifierids'] = Utils::filterIntegers($_GET['vids']);
        if (count($filter['verifierids']) == 1 && reset($filter['verifierids']) <= 0) {
            $filter['verifierids'] = intval(reset($filter['verifierids']));
        }
    } elseif (intval($_GET['vids']) > 0) {
        $filter['verifierids'] = Utils::filterIntegers(array($_GET['vids']));
    } else {
        $filter['verifierids'] = intval($_GET['vids']);
    }
} elseif (!isset($filter['verifierids'])) {
    $filter['verifierids'] = 'all';
}

// project id's
if (isset($_GET['pids'])) {
    if (is_array($_GET['pids'])) {
        $filter['projectids'] = Utils::filterIntegers($_GET['pids']);
    } elseif (intval($_GET['pids'])) {
        $filter['projectids'] = Utils::filterIntegers(array($_GET['pids']));
    } elseif ($_GET['pids'] == 'all') {
        $filter['projectids'] = null;
    }
}

// types
if (isset($_GET['tt'])) {
    if (is_array($_GET['tt'])) {
        $filter['typeids'] = Utils::filterIntegers($_GET['tt']);
    } elseif (intval($_GET['tt'])) {
        $filter['typeids'] = Utils::filterIntegers(array($_GET['tt']));
    } elseif ($_GET['tt'] == 'all') {
        $filter['typeids'] = null;
    }
}

// owner
if (!empty($_GET['owner']) && (!in_array('all', $_GET['owner']))) {
    if (!is_array($_GET['owner'])) {
        $_GET['owner'] = array($_GET['owner']);
    }
    $filter['owner'] = Utils::filterIntegers($_GET['owner']);
}

// removed or not?
if (isset($_GET['r'])) {
    $filter['removed'] = $_GET['r'];
}

// deadline
if (isset($_GET['d'])) {
    $filter['deadline'] = $_GET['d'];
}

// status/state
if (isset($_GET['s'])) {
    $filter['state'] = $_GET['s'];
} elseif (!isset($filter['state'])) {
    $filter['state'] = ConfigHelper::getConfig('phpui.ticketlist_status');
    if (strlen($filter['state'])) {
        $filter['state'] = explode(',', $filter['state']);
    }
}
if (is_array($filter['state'])) {
    if (in_array(-1, $filter['state'])) {
        $filter['state'] = -1;
    } else {
        $filter['state'] = Utils::filterIntegers($filter['state']);
    }
} elseif ($filter['state'] < 0) {
    $filter['state'] = intval($filter['state']);
} elseif (isset($filter['state'])) {
    $filter['state'] = array(intval($filter['state']));
}

// priority
if (isset($_GET['priority'])) {
    if (is_array($_GET['priority'])) {
        $filter['priority'] = Utils::filterIntegers($_GET['priority']);
    } elseif ($_GET['priority'] == 'all') {
        $filter['priority'] = null;
    } else {
        $filter['priority'] = Utils::filterIntegers(array($_GET['priority']));
    }
} elseif (!isset($filter['priority'])) {
    $filter['priority'] = ConfigHelper::getConfig('phpui.ticketlist_priority');
    if (strlen($filter['priority'])) {
        $filter['priority'] = explode(',', $filter['priority']);
    }
}

// netnodeid's
if (isset($_GET['nnids'])) {
    if (is_array($_GET['nnids'])) {
        $filter['netnodeids'] = Utils::filterIntegers($_GET['nnids']);
    } elseif (intval($_GET['nnids'])) {
        $filter['netnodeids'] = Utils::filterIntegers(array($_GET['nnids']));
    } elseif ($_GET['nnids'] == 'all') {
        $filter['netnodeids'] = null;
    }
}

if (isset($_GET['unread'])) {
    $filter['unread'] = $_GET['unread'];
} elseif (!isset($filter['unread'])) {
    $filter['unread'] = -1;
}

if (!is_array($_GET['parent']) && !empty($_GET['parent'])) {
    $filter['parent'] = intval($_GET['parent']);
}

if (isset($_GET['rights'])) {
    $filter['rights'] = $_GET['rights'];
} else {
    $filter['rights'] = RT_RIGHT_READ;
}

if (isset($_GET['page'])) {
    $filter['page'] = intval($_GET['page']);
} elseif (!isset($filter['page']) || empty($filter['page'])) {
    $filter['page'] = 1;
}

$SESSION->saveFilter($filter);

$layout['pagetitle'] = trans('Tickets List');

$filter['netdevids'] = null;
$filter['count'] = true;

$filter['total'] = intval($LMS->GetQueueContents($filter));

$filter['limit'] = intval(ConfigHelper::getConfig('phpui.ticketlist_pagelimit', $filter['total']));
$filter['offset'] = ($filter['page'] - 1) * $filter['limit'];
if ($filter['offset'] > $filter['total']) {
    $filter['page'] = 1;
    $filter['offset'] = 0;
}
$filter['count'] = false;

$queue = $LMS->GetQueueContents($filter);

$pagination = LMSPaginationFactory::getPagination(
    $filter['page'],
    $filter['total'],
    $filter['limit'],
    ConfigHelper::checkConfig('phpui.short_pagescroller')
);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$filter['direction'] = $queue['direction'];
$filter['order'] = $queue['order'];

unset($queue['total']);
unset($queue['state']);
unset($queue['priority']);
unset($queue['order']);
unset($queue['direction']);
unset($queue['owner']);
unset($queue['removed']);
unset($queue['deadline']);
unset($queue['service']);
unset($queue['type']);
unset($queue['unread']);
unset($queue['parent']);
unset($queue['rights']);
unset($queue['verifier']);
unset($queue['netnode']);

$queues = $LMS->GetQueueList(array('stats' => false));
$categories = $LMS->GetUserCategories(Auth::GetCurrentUser());

$projects = $LMS->GetProjects('name', array());
unset($projects['total']);
unset($projects['order']);
unset($projects['direction']);

$netnodelist = $LMS->GetNetNodeList(array(), 'name');
unset($netnodelist['total']);
unset($netnodelist['order']);
unset($netnodelist['direction']);

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'assign':
            if (!empty($_GET['ticketid'])) {
                if (isset($_GET['check-conflict'])) {
                    header('Content-Type: application/json');
                    die(json_encode($LMS->TicketIsAssigned($_GET['ticketid'])));
                }
                $LMS->TicketChange($_GET['ticketid'], array('owner' => Auth::GetCurrentUser()));
                $SESSION->redirect(str_replace('&action=assign', '', $_SERVER['REQUEST_URI'])
                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
            }
            break;
        case 'assign2':
                $LMS->TicketChange($_GET['ticketid'], array('verifierid' => Auth::GetCurrentUser()));
                $SESSION->redirect(str_replace('&action=assign2', '', $_SERVER['REQUEST_URI'])
                . ($SESSION->is_set('backid') ? '#' . $SESSION->get('backid') : ''));
            break;
        case 'unlink':
            $LMS->TicketChange($_GET['ticketid'], array('parentid' => null));
            $backto = $SESSION->get('backto');
            if (empty($backto)) {
                $SESSION->redirect('?m=rtqueuelist');
            } else {
                $SESSION->redirect('?' . $backto);
            }
            break;
    }
}

$SESSION->remove('backid');

$SMARTY->assign('pagination', $pagination);
$SMARTY->assign('queues', $queues);
$SMARTY->assign('projects', $projects);
$SMARTY->assign('categories', $categories);
$SMARTY->assign('queue', $queue);
$SMARTY->assign('netnodelist', $netnodelist);
$SMARTY->assign('users', $LMS->GetUserNames());

$SMARTY->display('rt/rtqueueview.html');
