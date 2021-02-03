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
 * LMSVoipAccountManagerInterface
 *
 * @author Maciej Lew <maciej.lew.1987@gmail.com>
 */
interface LMSVoipAccountManagerInterface
{
    public function getVoipAccountList($order = 'login,asc', $search = null, $sqlskey = 'AND');

    public function voipAccountSet($id, $access = -1);

    public function voipAccountSetU($id, $access = false);

    public function voipAccountAdd($voipaccountdata);

    public function voipAccountExists($id);

    public function getVoipAccountOwner($id);

    public function getVoipAccount($id);

    public function getVoipAccountIDByLogin($login);

    public function getVoipAccountIDByPhone($phone);

    public function getVoipAccountLogin($id);

    public function deleteVoipAccount($id);

    public function voipAccountUpdate($voipaccountdata);

    public function getCustomerVoipAccounts($id);

    public function getVoipBillings(array $a);

    public function getVoipTariffs();

    public function getVoipTariffRuleGroups();
}
