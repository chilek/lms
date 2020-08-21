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
 * LMSCustomerGroupManagerInterface
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSCustomerGroupManagerInterface
{
    public function CustomergroupWithCustomerGet($id);

    public function CustomergroupAdd($customergroupdata);

    public function CustomergroupUpdate($customergroupdata);

    public function CustomergroupDelete($id);

    public function CustomergroupExists($id);

    public function CustomergroupGetId($name);

    public function CustomergroupGetName($id);

    public function CustomergroupGetAll();

    public function CustomergroupGet($id, $network = null);

    public function CustomergroupGetList();

    public function CustomergroupGetForCustomer($id);

    public function GetGroupNamesWithoutCustomer($customerid);

    public function CustomerassignmentGetForCustomer($id);

    public function CustomerassignmentDelete($customerassignmentdata);

    public function CustomerassignmentAdd($customerassignmentdata);

    public function CustomerassignmentExist($groupid, $customerid);

    public function GetCustomerWithoutGroupNames($groupid, $network = null);

    public function getAllCustomerGroups();
}
