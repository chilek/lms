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
 * LMSNetDevManagerInterface
 * 
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSNetDevManagerInterface
{
    public function GetNetDevLinkedNodes($id);

    public function NetDevLinkNode($id, $devid, $type = 0, $technology = 0, $speed = 100000, $port = 0);

    public function SetNetDevLinkType($dev1, $dev2, $type = 0, $technology = 0, $speed = 100000);
    
    public function IsNetDevLink($dev1, $dev2);
    
    public function NetDevLink($dev1, $dev2, $type = 0, $technology = 0, $speed = 100000, $sport = 0, $dport = 0);
    
    public function NetDevUnLink($dev1, $dev2);
    
    public function NetDevUpdate($data);
    
    public function NetDevAdd($data);
    
    public function DeleteNetDev($id);
    
    public function NetDevDelLinks($id);
    
    public function GetNetDev($id);
    
    public function GetNotConnectedDevices($id);
    
    public function GetNetDevNames();
    
    public function GetNetDevList($order = 'name,asc');
    
    public function GetNetDevConnectedNames($id);
    
    public function GetNetDevLinkType($dev1, $dev2);
    
    public function CountNetDevLinks($id);
    
    public function GetNetDevIDByNode($id);
    
    public function NetDevExists($id);
}
