<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C); 2001-2019 LMS Developers
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
 * LMSUserManagerInterface
 *
 */
interface LMSUserManagerInterface
{
    public function setUserPassword($id, $passwd);

    public function SetUserAuthentication($id, $twofactorauth, $twofactorauthsecretkey);

    public function getUserName($id = null);

    public function getUserNames();

    public function getUserNamesIndexedById();

    public function getUserList();

    public function getUserIDByLogin($login);

    public function userAdd($user);

    public function userDelete($id);

    public function userExists($id);

    public function userAccess($id, $access);

    public function getUserInfo($id);

    public function userUpdate($user);

    public function getUserRights($id);

    public function PasswdExistsInHistory($id, $passwd);
}
