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
 * LMSConfigManagerInterface
 *
 */
interface LMSConfigManagerInterface
{
    public function GetConfigSections();

    public function ConfigOptionExists($params);

    public function GetConfigDefaultType($option);

    public function CheckOption($option, $value, $type);

    public function GetConfigVariable($config_id);

    public function DeleteConfigOption($id);

    public function cloneConfigs($params);

    public function importConfigs($params);

    public function getRelatedDivisions($id);

    public function getRelatedUsers($id, $divisionid = null);

    public function getRelatedOptions($id);

    public function getOptionHierarchy($id);

    public function addConfigOption($option);

    public function editConfigOption($option);

    public function getParentOption($id);

    public function toggleConfigOption($id);
}
