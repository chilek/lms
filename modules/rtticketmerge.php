<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2026 LMS Developers
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

function ticketMergeFail($sourceTicketId, $message)
{
    global $SESSION;

    $SESSION->save('rt_ticket_merge_error', $message);
    $SESSION->redirect('?m=rtticketview&id=' . intval($sourceTicketId));
}

function ticketMergeQueueRights($ticketId)
{
    global $LMS;

    if (ConfigHelper::checkPrivilege('full_access')) {
        return RT_RIGHT_READ | RT_RIGHT_WRITE;
    }
    if (intval($ticketId) <= 0) {
        return 0;
    }
    return intval($LMS->CheckTicketAccess($ticketId));
}

if (!empty($_GET['preview'])) {
    $leftTicketId = intval($_GET['leftid'] ?? 0);
    $rightTicketId = intval($_GET['rightid'] ?? 0);
    $mergeIntoLeft = (($_GET['direction'] ?? 'left') !== 'right');
    $sourceTicketId = $mergeIntoLeft ? $rightTicketId : $leftTicketId;
    $targetTicketId = $mergeIntoLeft ? $leftTicketId : $rightTicketId;
    header('Content-Type: text/html; charset=UTF-8');

    $mergeError = null;
    $mergeReady = false;
    $leftTicket = null;
    $rightTicket = null;
    $leftRights = ticketMergeQueueRights($leftTicketId);
    $rightRights = ticketMergeQueueRights($rightTicketId);
    if ($leftRights & (RT_RIGHT_READ | RT_RIGHT_WRITE)) {
        $leftTicket = $LMS->GetTicketContents($leftTicketId);
    }
    if ($rightRights & (RT_RIGHT_READ | RT_RIGHT_WRITE)) {
        $rightTicket = $LMS->GetTicketContents($rightTicketId);
    }

    if ($leftTicketId <= 0 || empty($leftTicket)) {
        $mergeError = trans('Ticket does not exist');
    } elseif ($rightTicketId > 0 && (!($leftRights & RT_RIGHT_WRITE) || !($rightRights & RT_RIGHT_WRITE))) {
        $mergeError = trans('You have no write rights to both ticket queues!');
    } elseif ($rightTicketId > 0) {
        if (empty($rightTicket)) {
            $mergeError = trans('Ticket does not exist');
        } else {
            $comparison = $LMS->getTicketMergeComparison($sourceTicketId, $targetTicketId);
            if ($comparison === null) {
                $mergeReady = true;
            } else {
                $mergeError = is_string($comparison) ? $comparison : trans('Ticket does not exist');
            }
        }
    }

    $mergeReopenTarget = false;
    $mergeRelationParent = null;
    $mergeRelationChildren = null;
    $mergeCustomerWarning = null;
    if (!empty($leftTicket) && !empty($rightTicket)) {
        $sourceTicket = $mergeIntoLeft ? $rightTicket : $leftTicket;
        $targetTicket = $mergeIntoLeft ? $leftTicket : $rightTicket;
        if ($mergeReady && intval($sourceTicket['state']) != RT_RESOLVED && intval($targetTicket['state']) == RT_RESOLVED) {
            $mergeReopenTarget = true;
        }
        if (intval($sourceTicket['customerid']) !== intval($targetTicket['customerid'])) {
            $mergeCustomerWarning = trans('Source and target tickets belong to different customers.');
        }
        if ($mergeReady && $sourceTicketId != $targetTicketId) {
            if (!empty($sourceTicket['parent']['id'])) {
                $parentLabel = sprintf('%06d', intval($sourceTicket['parent']['id']));
                if (!empty($sourceTicket['parent']['name'])) {
                    $parentLabel .= ' ' . $sourceTicket['parent']['name'];
                }
                $mergeRelationParent = trans('Source ticket parent: $a.', $parentLabel);
            } elseif (!empty($sourceTicket['parentid'])) {
                $mergeRelationParent = trans('Source ticket parent: $a.', sprintf('%06d', intval($sourceTicket['parentid'])));
            }
            $childCount = 0;
            $childTickets = $LMS->GetChildTickets($sourceTicketId);
            if (!empty($childTickets)) {
                foreach ($childTickets as $childTicket) {
                    if (intval($childTicket['id']) != $targetTicketId) {
                        $childCount++;
                    }
                }
            }
            if ($childCount > 0) {
                $mergeRelationChildren = trans('Child tickets to be moved to the target: $a.', $childCount);
            }
        }
    }
    if ($mergeCustomerWarning && $mergeError === $mergeCustomerWarning) {
        $mergeError = null;
    }

    $SMARTY->assign([
        'mergeError' => $mergeError,
        'mergeReady' => $mergeReady,
        'mergeReopenTarget' => $mergeReopenTarget,
        'mergeRelationParent' => $mergeRelationParent,
        'mergeRelationChildren' => $mergeRelationChildren,
        'mergeCustomerWarning' => $mergeCustomerWarning,
        'leftTicket' => $leftTicket,
        'rightTicket' => $rightTicket,
        'mergeKeepSide' => $mergeIntoLeft ? 'left' : 'right',
    ]);
    $SMARTY->display('rt/rtticketmergepreview.html');
    $SESSION->close();
    die;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['merge']) || !is_array($_POST['merge'])) {
    $SESSION->redirect('?m=rtqueuelist');
}

$merge = $_POST['merge'];
$openedTicketId = intval($merge['openedid'] ?? 0);
$sourceTicketId = intval($merge['sourceid'] ?? 0);
$targetTicketId = intval($merge['targetid'] ?? 0);
$leftTicketId = intval($merge['leftid'] ?? 0);
$rightTicketId = intval($merge['rightid'] ?? 0);
$keep = [];
if (isset($_POST['keep']) && is_array($_POST['keep'])) {
    $keep = $_POST['keep'];
} elseif (isset($merge['keep']) && is_array($merge['keep'])) {
    $keep = $merge['keep'];
}

if ($openedTicketId <= 0
    || ($openedTicketId !== $sourceTicketId && $openedTicketId !== $targetTicketId)
) {
    ticketMergeFail($openedTicketId, trans('Ticket merge failed!'));
}
if (!((ticketMergeQueueRights($sourceTicketId) & RT_RIGHT_WRITE) && (ticketMergeQueueRights($targetTicketId) & RT_RIGHT_WRITE))) {
    ticketMergeFail($openedTicketId, trans('You have no write rights to both ticket queues!'));
}

$reopenTarget = !empty($merge['reopentarget']);
$result = $LMS->mergeTickets(
    $sourceTicketId,
    $targetTicketId,
    $keep,
    $leftTicketId,
    $rightTicketId,
    $reopenTarget
);
if ($result === true || (is_array($result) && !empty($result['warning']))) {
    if (is_array($result) && !empty($result['warning'])) {
        $SESSION->save('rt_ticket_merge_error', $result['warning']);
    }
    $SESSION->redirect('?m=rtticketview&id=' . $targetTicketId);
}

ticketMergeFail(
    $openedTicketId,
    is_string($result) ? $result : trans('Ticket merge failed!')
);
