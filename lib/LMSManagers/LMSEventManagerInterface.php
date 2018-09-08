<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2018 LMS Developers
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
 * LMSEventManagerInterface
 * 
 */
interface LMSEventManagerInterface
{
	public function EventAdd($event);

	public function EventUpdate($event);

	public function EventDelete($id);

	public function GetEvent($id);

    public function GetEventList(array $params);

    public function EventSearch($search, $order = 'date,asc', $simple = false);

    public function GetCustomerIdByTicketId($id);

	public function EventOverlaps(array $params);

    public function AssignUserToEvent($id, $userid);

    public function UnassignUserFromEvent($id, $userid);

	public function MoveEvent($id, $delta);
}
