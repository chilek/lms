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

interface LMSNetNodeManagerInterface
{
    public function GetNetNode($id);

    public function GetNetNodeName($id);

    public function GetNetNodes();

    public function GetNetNodeList($search = array(), $order = 'name,asc');

    public function NetNodeAdd($netnodedata);

    public function NetNodeExists($id);

    public function NetNodeDelete($id);

    public function NetNodeUpdate($netnodedata);

    public function GetCustomerNetNodes($id);
}
