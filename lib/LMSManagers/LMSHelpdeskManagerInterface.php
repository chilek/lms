<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2019 LMS Developers
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

/**
 * LMSHelpdeskManagerInterface
 *
 */
interface LMSHelpdeskManagerInterface
{

    public function GetQueue($id);

    public function GetQueueContents(array $params);

    public function GetUserRightsRT($user, $queue, $ticket = null);

    public function GetQueueList(array $params);

    public function GetQueueNames();

    public function GetMyQueues();

    public function QueueExists($id);

    public function GetQueueIdByName($queue);

    public function GetQueueVerifier($id);

    public function GetQueueName($id);

    public function GetQueueEmail($id);

    public function GetQueueStats($id);

    public function GetCategory($id);

    public function GetUserRightsToCategory($user, $category, $ticket = null);

    public function GetCategoryList($stats = true);

    public function GetCategoryStats($id);

    public function CategoryExists($id);

    public function GetCategoryIdByName($category);

    public function GetCategoryName($id);

    public function GetUserCategories($userid = null);

    public function RTStats();

    public function GetQueueByTicketId($id);

    public function GetEventsByTicketId($id);

    public function GetQueueNameByTicketId($id);

    public function TicketExists($id);

//  public function SaveTicketMessageAttachments($ticketid, $messageid, $files, $cleanup = false);

    public function TicketMessageAdd($message, $files = null);

    public function TicketAdd($ticket, $files = null);

    public function GetLastMessageID();

    public function LimitQueuesToUserpanelEnabled($queuelist, $queueid);

    public function GetTicketContents($id, $short = false);

    public function GetMessage($id);

    public function GetFirstMessage($ticketid);

    public function GetLastMessage($ticketid);

    public function TicketChange($ticketid, array $props);

    public function GetQueueCategories($queueid);

    public function ReplaceNotificationSymbols($text, array $params);

    public function ReplaceNotificationCustomerSymbols($text, array $params);

    public function NotifyUsers(array $params);

    public function CleanupTicketLastView();

    public function MarkQueueAsRead($queueid);

    public function MarkTicketAsRead($ticketid);

    public function MarkTicketAsUnread($ticketid);

    public function GetIndicatorStats();

    public function DetermineSenderEmail($queue_email, $ticket_email, $user_email, $forced_order = null);

    public function GetTicketRequestorPhone($ticketid);

    public function CheckTicketAccess($ticketid);

    public function GetRelatedTickets($ticketid);

    public function getSelectedRelatedTickets(array $ticketids);

    public function GetTicketParentID($ticketid);

    public function IsTicketLoop($ticketid, $parentid);

    public function GetRTSmtpOptions();

    public function CopyQueuePermissions($src_userid, $dst_userid);

    public function CopyCategoryPermissions($src_userid, $dst_userid);

    public function TicketIsAssigned($ticketid);
}
