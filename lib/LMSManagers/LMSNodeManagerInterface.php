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
 * LMSNodeManagerInterface
 * 
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSNodeManagerInterface
{
    public function GetNodeOwner($id);

    public function NodeUpdate($nodedata, $deleteassignments = FALSE);

    public function DeleteNode($id);

    public function GetNodeNameByMAC($mac);

    public function GetNodeIDByIP($ipaddr);

    public function GetNodeIDByMAC($mac);

    public function GetNodeIDByName($name);

    public function GetNodeIPByID($id);

    public function GetNodePubIPByID($id);

    public function GetNodeMACByID($id);

    public function GetNodeName($id);

    public function GetNodeNameByIP($ipaddr);

    public function GetNode($id);

    public function GetNodeList(array $params = array());

    public function NodeSet($id, $access = -1);

    public function NodeSetU($id, $access = FALSE);

    public function NodeSetWarn($id, $warning = FALSE);

    public function NodeSwitchWarn($id);

    public function NodeSetWarnU($id, $warning = FALSE);

    public function IPSetU($netdev, $access = FALSE);

    public function NodeAdd($nodedata);

    public function NodeExists($id);

    public function NodeStats();

    public function SetNodeLinkType($node, $link = NULL);

    public function updateNodeField($nodeid, $field, $value);

    public function GetUniqueNodeLocations($customerid);

	public function GetNodeLocations($customerid, $address_id = null);
}
