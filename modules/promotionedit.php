<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2013 LMS Developers
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

$promotion = isset($_POST['promotion']) ? $_POST['promotion'] : NULL;
$action = isset($_GET['action']) ? $_GET['action'] : null;

if ($action == 'tariffdel' && ($tariffid = intval($_GET['tid'])))
{
    $promotionid = intval($_GET['id']);

    $DB->Execute('DELETE FROM promotionassignments WHERE tariffid = ?
        AND promotionschemaid IN (SELECT id FROM promotionschemas
            WHERE promotionid = ?)', array($tariffid, $promotionid));

    $SESSION->redirect('?m=promotioninfo&id='.$promotionid);
}

if ($promotion)
{
	foreach ($promotion as $key => $value)
		$promotion[$key] = trim($value);

	if ($promotion['name']=='' && $promotion['description']=='')
	{
		$SESSION->redirect('?m=promotionlist');
	}

    $promotion['id'] = intval($_GET['id']);

	if ($promotion['name'] == '')
		$error['name'] = trans('Promotion name is required!');
	else if ($DB->GetOne('SELECT id FROM promotions WHERE name = ? AND id <> ?',
	    array($promotion['name'], $promotion['id']))
	) {
		$error['name'] = trans('Specified name is in use!');
    }

	if (!$error)
	{
        $DB->Execute('UPDATE promotions SET name = ?, description = ?
            WHERE id = ?',
            array($promotion['name'], $promotion['description'],
                $promotion['id']));

		$SESSION->redirect('?m=promotioninfo&id='.$promotion['id']);
	}
}
else
{
    $promotion = $DB->GetRow('SELECT * FROM promotions WHERE id = ?',
        array(intval($_GET['id'])));
}

$layout['pagetitle'] = trans('Promotion Edit: $a', $promotion['name']);

$SMARTY->assign('error', $error);
$SMARTY->assign('promotion', $promotion);
$SMARTY->display('promotionedit.html');

?>
