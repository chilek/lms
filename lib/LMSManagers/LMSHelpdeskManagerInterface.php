<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2013 LMS Developers
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
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSHelpdeskManagerInterface
{

    public function GetQueue($id);

    public function GetQueueContents($ids, $order = 'createtime,desc', $state = NULL, $owner = 0, $catids = NULL);

    public function GetUserRightsRT($user, $queue, $ticket = NULL);

    public function GetQueueList($stats = true);

    public function GetQueueNames();

    public function QueueExists($id);

    public function GetQueueIdByName($queue);

    public function GetQueueName($id);

    public function GetQueueEmail($id);

    public function GetQueueStats($id);

    public function GetCategory($id);

    public function GetUserRightsToCategory($user, $category, $ticket = NULL);

    public function GetCategoryList($stats = true);

    public function GetCategoryStats($id);

    public function CategoryExists($id);

    public function GetCategoryIdByName($category);

    public function GetCategoryListByUser($userid = NULL);

    public function RTStats();

    public function GetQueueByTicketId($id);

    public function TicketExists($id);

    public function TicketAdd($ticket, $files = NULL);

    public function GetTicketContents($id);

    public function GetMessage($id);
    
    public function TicketChange($ticketid, array $props); 
}
