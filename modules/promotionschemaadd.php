<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2012 LMS Developers
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

$schema = isset($_POST['schema']) ? $_POST['schema'] : NULL;

if ($schema)
{
	foreach ($schema as $key => $value)
	    if (!is_array($value))
    		$schema[$key] = trim($value);

    $schema['promotionid'] = intval($_GET['id']);

	if ($schema['name']=='' && $schema['description']=='')
	{
		$SESSION->redirect('?m=promotioninfo&id='.$schema['promotionid']);
	}

	if ($schema['name'] == '')
		$error['name'] = trans('Schema name is required!');
	else if ($DB->GetOne('SELECT id FROM promotionschemas
	    WHERE name = ? AND promotionid = ?', array($schema['name'], $schema['promotionid']))
	) {
		$error['name'] = trans('Specified name is in use!');
    }

    if (empty($schema['continuation']) && !empty($schema['ctariffid']))
        $error['ctariffid'] = trans('Additional subscription is useless when contract prolongation is not set!');

	if (!$error)
	{
        $data = array();
        foreach ($schema['periods'] as $period) {
            if ($period = intval($period))
                $data[] = $period;
            else
                break;
        }
        $data = implode(';', $data);

        $DB->Execute('INSERT INTO promotionschemas (promotionid, name,
                description, data, continuation, ctariffid)
            VALUES (?, ?, ?, ?, ?, ?)',
            array($schema['promotionid'],
                $schema['name'],
                $schema['description'],
                $data,
                !empty($schema['continuation']) ? 1 : 0,
                !empty($schema['ctariffid']) ? $schema['ctariffid'] : null,
            ));

        $sid = $DB->GetLastInsertId('promotionschemas');

        // pre-fill promotionassignments with all tariffs in specified promotion
        $DB->Execute('INSERT INTO promotionassignments (promotionschemaid, tariffid)
            SELECT ?, tariffid
            FROM promotionassignments
            WHERE promotionschemaid IN (SELECT id FROM promotionschemas WHERE promotionid = ?)
            GROUP BY tariffid', array($sid, $schema['promotionid']));

		if (empty($schema['reuse']))
		{
			$SESSION->redirect('?m=promotionschemainfo&id='.$sid);
		}

		unset($schema['name']);
		unset($schema['description']);
		$schema['reuse'] = '1';
	}
}
else
{
    $schema['promotionid']  = $_GET['id'];
    $schema['continuation'] = 1;
    $schema['periods']      = array(0);
}

$schema['selection'] = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,30,36,42,48,60);

$layout['pagetitle'] = trans('New Schema');

$SMARTY->assign('error', $error);
$SMARTY->assign('schema', $schema);
$SMARTY->assign('tariffs', $LMS->GetTariffs());
$SMARTY->display('promotionschemaadd.html');

?>
