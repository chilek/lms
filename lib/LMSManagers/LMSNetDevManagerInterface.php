<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2020 LMS Developers
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
 * LMSNetDevManagerInterface
 *
 */
interface LMSNetDevManagerInterface
{
    public function GetNetDevLinkedNodes($id);

    public function NetDevLinkNode($id, $devid, $link = null);

    public function SetNetDevLinkType($dev1, $dev2, $link = null);

    public function IsNetDevLink($dev1, $dev2);

    public function NetDevLink($dev1, $dev2, $link);

    public function NetDevUnLink($dev1, $dev2);

    public function NetDevUpdate($data);

    public function NetDevAdd($data);

    public function DeleteNetDev($id);

    public function NetDevDelLinks($id);

    public function GetNetDev($id);

    public function GetNotConnectedDevices($id);

    public function GetNetDevNames();

    public function GetNetDevName($id);

    public function GetNetDevList($order = 'name,asc');

    public function GetNetDevConnectedNames($id);

    public function GetNetDevLinkType($dev1, $dev2);

    public function CountNetDevLinks($id);

    public function GetNetDevIDByNode($id);

    public function NetDevExists($id);

    public function GetProducers();

    public function GetModels($producerid = null);

    public function GetModelList($pid = null);

    public function GetRadioSectors($netdevid, $technology = 0);

    public function AddRadioSector($netdevid, array $radiosector);

    public function DeleteRadioSector($id);

    public function UpdateRadioSector($id, array $radiosector);

    public function GetManagementUrls($type, $id);

    public function AddManagementUrl($type, $id, array $url);

    public function DeleteManagementUrl($type, $id);

    public function updateManagementUrl($type, $id, array $url);

    public function getNetDevCustomerAssignments($netdevid, $assignments);

    public function getNetDevOwnerByNodeId($nodeid);
}
