<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2016 LMS Developers
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
 * LMSCustomerManagerInterface
 * 
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
interface LMSCustomerManagerInterface
{
    public function getCustomerName($id);

    public function getCustomerEmail($id);

    public function customerExists($id);

    public function getCustomerNodesNo($id);

    public function getCustomerIDByIP($ipaddr);

    public function getCustomerStatus($id);

    public function getCustomerNames();

    public function getAllCustomerNames();

    public function getCustomerNodesAC($id);

    public function getCustomerBalance($id, $totime = null);

    public function getCustomerBalanceList($id, $totime = null, $direction = 'ASC');

    public function customerStats();

    public function customerAdd($customeradd);

    public function getCustomerList($params);

    public function getCustomerNodes($id, $count = null);

    public function GetCustomerNetworks($id, $count = null);

    public function GetCustomer($id, $short = false);

    public function customerUpdate($customerdata);

    public function deleteCustomer($id);
    
    public function deleteCustomerPermanent($id);
}
