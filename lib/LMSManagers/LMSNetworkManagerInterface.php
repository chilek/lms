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
 * LMSNetworkManagerInterface
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSNetworkManagerInterface
{
    public function NetworkExists($id);

    public function NetworkSet($id, $disabled = -1);

    public function IsIPFree($ip, $netid = 0);

    public function IsIPInNetwork($ip, $netid);

    public function IsIPGateway($ip);

    public function GetPrefixList();

    public function NetworkAdd($netadd);

    public function NetworkDelete($id);

    public function GetNetworkName($id);

    public function getNetworkParams($id);

    public function GetNetworks($with_disabled = true);

    public function GetNetIDByIP($ipaddr);

    public function GetUnlinkedNodes();

    public function GetNetDevIPs($id);

    public function GetNetworkList(array $search);

    public function IsIPValid($ip, $checkbroadcast = false, $ignoreid = 0);

    public function NetworkOverlaps($network, $mask, $hostid, $ignorenet = 0);

    public function NetworkShift($netid, $network = '0.0.0.0', $mask = '0.0.0.0', $shift = 0);

    public function NetworkUpdate($networkdata);

    public function NetworkCompress($id, $shift = 0);

    public function NetworkRemap($src, $dst);

    public function MoveHostsBetweenNetworks($src, $dst);

    public function GetNetworkRecord($id, $page = 0, $plimit = 4294967296, $firstfree = false);

    public function ScanNodes();

    public function GetNetworkPageForIp($netid, $ip);

    public function GetPublicNetworkID($netid);

    public function getFirstFreeAddress($netid);

    public function GetVlanList($params);

    public function GetVlanInfo($id);

    public function AddVlan($args);

    public function DeleteVlan($id);

    public function UpdateVlan($args);
}
