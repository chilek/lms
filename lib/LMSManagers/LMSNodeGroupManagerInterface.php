<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2013 LMS Developers
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
 * LMSNodeGroupManagerInterface
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSNodeGroupManagerInterface
{
    public function GetNodeGroupNames();

    public function GetNodeGroupNamesByNode($nodeid);

    public function GetNodeGroupNamesWithoutNode($nodeid);

    public function GetNodesWithoutGroup($groupid, $network = null);

    public function GetNodesWithGroup($groupid, $network = null);

    public function GetNodeGroup($id, $network = null);

    public function CompactNodeGroups();

    public function getNodeGroupIdByName($group_name);

    public function addNodeGroupAssignment(array $params);

    public function deleteNodeGroupAssignment(array $params);
}
