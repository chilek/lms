<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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

if ($action == 'tariffdel' && ($tariffid = intval($_GET['tid']))) {
	$promotionid = intval($_GET['id']);

	$args = array(
		SYSLOG::RES_TARIFF => $tariffid,
		SYSLOG::RES_PROMO => $promotionid
	);
	if ($SYSLOG) {
		$assigns = $DB->GetAll('SELECT id, tariffid, promotionschemaid
			FROM promotionassignments WHERE tariffid = ?
			AND promotionschemaid IN (SELECT id FROM promotionschemas
				WHERE promotionid = ?)', array_values($args));
		if (!empty($assigns)) {
			foreach ($assigns as $assign) {
				$args[SYSLOG::RES_PROMOASSIGN] = $assign['id'];
				$args[SYSLOG::RES_PROMOSCHEMA] = $assign['promotionschemaid'];
				$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
			}
			unset($args[SYSLOG::RES_PROMOASSIGN]);
		}
	}

	$DB->Execute('DELETE FROM promotionassignments WHERE tariffid = ?
		AND promotionschemaid IN (SELECT id FROM promotionschemas
		WHERE promotionid = ?)', array_values($args));

	$SESSION->redirect('?m=promotioninfo&id=' . $promotionid);
}

if ($promotion) {
	foreach ($promotion as $key => $value)
		$promotion[$key] = trim($value);

	if ($promotion['name']=='' && $promotion['description']=='')
		$SESSION->redirect('?m=promotionlist');

	$promotion['id'] = intval($_GET['id']);

	if ($promotion['name'] == '')
		$error['name'] = trans('Promotion name is required!');
	else if ($DB->GetOne('SELECT id FROM promotions WHERE name = ? AND id <> ?',
			array($promotion['name'], $promotion['id']))) {
		$error['name'] = trans('Specified name is in use!');
	}

	if (empty($promotion['datefrom']))
		$promotion['from'] = 0;
	else
	{
		$from = date_to_timestamp($promotion['datefrom']);
		if(empty($from))
			$error['datefrom'] = trans('Incorrect effective start time!');
	}

        if (empty($promotion['dateto']))
                $promotion['to'] = 0;
        else
        {
		$to = date_to_timestamp($promotion['dateto']);
		if(empty($to))
			$error['dateto'] = trans('Incorrect effective start time!');
        }

	if ($promotion['to'] != 0 && $promotion['from'] != 0 && $to < $from)
		$error['dateto'] = trans('Incorrect date range!');

	if (!$error) {
		$args = array(
			'name' => $promotion['name'],
			'description' => $promotion['description'],
			'datefrom' => $promotion['from'],
			'dateto' => $promotion['to'],
			SYSLOG::RES_PROMO => $promotion['id']
		);
		$DB->Execute('UPDATE promotions SET name = ?, description = ?, datefrom = ?, dateto = ?
			WHERE id = ?', array_values($args));

		if ($SYSLOG)
			$SYSLOG->AddMessage(SYSLOG::RES_PROMO, SYSLOG::OPER_UPDATE, $args);

		$SESSION->redirect('?m=promotioninfo&id=' . $promotion['id']);
	}
} else {
	$promotion = $DB->GetRow('SELECT * FROM promotions WHERE id = ?',
		array(intval($_GET['id'])));

	if ($promotion['datefrom'])
		$promotion['datefrom'] = date('Y/m/d', $promotion['datefrom']);

	if ($promotion['dateto'])
		$promotion['dateto'] = date('Y/m/d', $promotion['dateto']);
}

$layout['pagetitle'] = trans('Promotion Edit: $a', $promotion['name']);

$SMARTY->assign('error', $error);
$SMARTY->assign('promotion', $promotion);
$SMARTY->display('promotion/promotionedit.html');

?>
