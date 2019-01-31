<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2017 LMS Developers
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

$action = !empty($_GET['action']) ? $_GET['action'] : null;

if ($action == 'tariff' && !empty($_POST['form'])) {
	$form = $_POST['form'];
	$assignmentid = intval($_GET['aid']);
	$schemaid = intval($_GET['id']);

	$data = array();
	$regexp = '/^(' . ($assignmentid ? 'tariffval|tariffperiod' : 'value|period') .')([0-9]+)$/';
	$regexp2 = '/^(' . ($assignmentid ? 'tariffopt|tariffsel' : 'opt|sel') .')$/';
	$mons = array(YEARLY => 12, HALFYEARLY => 6, QUARTERLY => 3, MONTHLY => 1);
	$schema = $DB->GetOne('SELECT data FROM promotionschemas WHERE id = ?', array($schemaid));
	$schema = explode(';', $schema);

	$optional = $label = 0;
	foreach ($form as $key => $value) {
		$form[$key] = trim($value);

		if (preg_match($regexp, $key, $m)) {
			// periods
			if (strpos($m[1], 'period') !== false) {
				$val = intval($form[$key]);
				$skey = $m[2]-1;

				if (empty($val))
					$data[$m[2]]['period'] = $val;
				else if (!array_key_exists($val, $mons))
					$error[$key] = trans('Incorrect value!');
				else if ($schema[$skey] && ($schema[$skey] % $mons[$val]))
					$error[$key] = trans('Not possible to use this period here!');
				else
					$data[$m[2]]['period'] = $val;
			}
			// values
			else {
				if (!strlen($form[$key]))
					$data[$m[2]]['value'] = 'NULL';
				else if (!preg_match('/^[-]?[0-9.,]+$/', $form[$key]))
					$error[$key] = trans('Incorrect value!');
				else
					$data[$m[2]]['value'] = str_replace(',', '.', $form[$key]);
			}
		} elseif (preg_match($regexp2, $key)) {
			if (preg_match('/opt$/', $key) && intval($value))
				$optional = 1;
			elseif (preg_match('/sel$/', $key)) {
				if ($value == '-1') {
					if ($key == 'tariffsel')
						if (!strlen($form['tariffnewsel']))
							$error['tariffnewsel'] = trans('Incorrect value!');
						else
							$label = $form['tariffnewsel'];
					elseif ($key == 'sel')
						if (!strlen($form['newsel']))
							$error['newsel'] = trans('Incorrect value!');
						else
							$label = $form['newsel'];
				} elseif ($value == '0')
					$label = null;
				else
					$label = $value;
			}
		}
	}

	if (!$error) {
		foreach ($data as $idx => $d)
			$data[$idx] = $d['value'].(!empty($d['period']) ? ':'.intval($d['period']) : '');
		$datastr = implode(';', $data);

		$promotionid = $DB->GetOne('SELECT promotionid FROM promotionschemas WHERE id = ?',
			array($schemaid));
		if (!empty($assignmentid)) {
			$DB->Execute('UPDATE promotionassignments
				SET optional = ?, label = ?, data = ? WHERE id = ?',
				array($optional, $label, $datastr, $assignmentid));
			if ($SYSLOG) {
				$args = array(
					SYSLOG::RES_PROMOASSIGN => $assignmentid,
					SYSLOG::RES_PROMOSCHEMA => $schemaid,
					SYSLOG::RES_TARIFF => $form['tariffid'],
					SYSLOG::RES_PROMO => $promotionid,
					'optional' => $optional,
					'label' => empty($label) ? null : $label,
					'data' => $datastr
				);
				$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_UPDATE, $args);
			}
		} else {
			$orderid = $DB->GetOne('SELECT MAX(orderid) FROM promotionassignments
				WHERE promotionschemaid = ?', array($schemaid));
			$args =	array(
				SYSLOG::RES_PROMOSCHEMA => $schemaid,
				SYSLOG::RES_TARIFF => intval($form['tariffid']),
				'optional' => $optional,
				'label' => empty($label) ? null : $label,
				'data' => $datastr,
				'orderid' => empty($orderid) ? 1 : $orderid,
			);
			$DB->Execute('INSERT INTO promotionassignments
				(promotionschemaid, tariffid, optional, label, data, orderid)
				VALUES (?, ?, ?, ?, ?, ?)', array_values($args));
			if ($SYSLOG) {
				$args[SYSLOG::RES_PROMO] = $promotionid;
				$args[SYSLOG::RES_PROMOASSIGN] =
					$DB->GetLastInsertID('promotionassignments');
				$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_ADD, $args);
			}
		}

		$SESSION->redirect('?m=promotionschemainfo&id=' . $schemaid);
	}

	$data = $_POST['form'];
	$data['aid'] = $assignmentid ? $assignmentid : null;

	$SMARTY->assign('formdata', $data);
	$SMARTY->assign('error', $error);
	include MODULES_DIR . '/promotionschemainfo.php';
	die;
} else if ($action == 'tariffdel') {
	$aid = intval($_GET['aid']);
	if ($SYSLOG) {
		$assign = $DB->GetRow('SELECT promotionschemaid, tariffid, promotionid
				FROM promotionassignments a
				JOIN promotionschemas s ON s.id = a.promotionschemaid
				WHERE a.id = ?', array($aid));
		$args = array(
			SYSLOG::RES_PROMOASSIGN => $aid,
			SYSLOG::RES_PROMOSCHEMA => $assign['promotionschemaid'],
			SYSLOG::RES_TARIFF => $assign['tariffid'],
			SYSLOG::RES_PROMO => $assign['promotionid']
		);
		$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_DELETE, $args);
	}

	$DB->Execute('DELETE FROM promotionassignments WHERE id = ?', array($aid));

	$SESSION->redirect('?m=promotionschemainfo&id='.intval($_GET['id']));
} else if ($action == 'tariff-reorder') {
	header('Content-Type: application/json');

	if (!isset($_GET['id']) || !isset($_POST['assignments']))
		$result = 'ERROR';
	else {
		$assignments = array_flip($DB->GetCol('SELECT id FROM promotionassignments
			WHERE promotionschemaid = ?', array($_GET['id'])));
		$orderid = 1;
		foreach ($_POST['assignments'] as $a)
			if (isset($assignments[$a]))
				$DB->Execute('UPDATE promotionassignments SET orderid = ?
					WHERE id = ?', array($orderid++, $a));
		$result = 'OK';
	}

	echo json_encode(array('result' => $result));
	die;
}

$oldschema = $DB->GetRow('SELECT * FROM promotionschemas WHERE id = ?',
	array(intval($_GET['id'])));

if (isset($_POST['schema'])) {
	$schema =  $_POST['schema'];

	foreach ($schema as $key => $value)
		if (!is_array($value))
			$schema[$key] = trim($value);

	if ($schema['name']=='' && $schema['description']=='')
		$SESSION->redirect('?m=promotioninfo&id='.$oldschema['promotionid']);

	$schema['id'] = $oldschema['id'];

	if ($schema['name'] == '')
		$error['name'] = trans('Schema name is required!');
	else if ($DB->GetOne('SELECT id FROM promotionschemas
		WHERE name = ? AND promotionid = ? AND id <> ?',
		array($schema['name'], $oldschema['promotionid'], $schema['id'])))
		$error['name'] = trans('Specified name is in use!');

	if (!$error) {
		$data = array();
		foreach ($schema['periods'] as $period)
			if ($period = intval($period))
				$data[] = $period;
			else
				break;

		$DB->BeginTrans();

		$args = array(
			'name' => $schema['name'],
			'description' => $schema['description'],
			'data' => implode(';', $data),
			SYSLOG::RES_PROMOSCHEMA => $schema['id']
		);
		$DB->Execute('UPDATE promotionschemas SET name = ?, description = ?, data = ?
			WHERE id = ?', array_values($args));

		if ($SYSLOG) {
			$args[SYSLOG::RES_PROMO] = $oldschema['promotionid'];
			$SYSLOG->AddMessage(SYSLOG::RES_PROMOSCHEMA, SYSLOG::OPER_UPDATE, $args);
		}

		// re-check promotionassignments data, check the number of periods
		// and remove excessive data or add data for additional periods
		$tariffs = $DB->GetAll('SELECT a.id, a.data, a.tariffid, t.value
			FROM promotionassignments a
			JOIN tariffs t ON (t.id = a.tariffid)
			WHERE a.promotionschemaid = ?', array($schema['id']));

		if (!empty($tariffs)) {
			$data_cnt = count($data) + 2; // +1 for activation item, +1 for continuation item
			foreach ($tariffs as $tariff) {
				$tdata = explode(';', $tariff['data']);
				$tdata_cnt = count($tdata);
				$last_data = array_pop($tdata);
				// nothing's changed
				if ($tdata_cnt == $data_cnt)
					continue;
				// added periods
				if ($data_cnt > $tdata_cnt)
					for ($i=0; $i<$data_cnt-$tdata_cnt; $i++)
						$tdata[] = str_replace(',', '.', $tariff['value']);
				// removed periods
				else
					$tdata = array_slice($tdata, 0, $data_cnt);

				$tdata[] = $last_data;

				$args = array(
					'data' => implode(';', $tdata),
					SYSLOG::RES_PROMOASSIGN => $tariff['id']
				);
				$DB->Execute('UPDATE promotionassignments SET data = ?
					WHERE id = ?', array_values($args));

				if ($SYSLOG) {
					$args[SYSLOG::RES_TARIFF] = $tariff['tariffid'];
					$args[SYSLOG::RES_PROMO] = $oldschema['promotionid'];
					$args[SYSLOG::RES_PROMOSCHEMA] = $schema['id'];
					$SYSLOG->AddMessage(SYSLOG::RES_PROMOASSIGN, SYSLOG::OPER_UPDATE, $args);
				}
			}
		}

		$DB->CommitTrans();

		$SESSION->redirect('?m=promotionschemainfo&id='.$schema['id']);
	}
} else {
	$schema = $oldschema;
	$schema['periods'] = explode(';', $schema['data']);
	$schema['promotionname'] = $LMS->GetPromotionNameBySchemaID($schema['id']);
}

$schema['selection'] = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,30,36,42,48,60);

$layout['pagetitle'] = trans('Schema Edit: $a', $oldschema['name']);

$SMARTY->assign('error', $error);
$SMARTY->assign('schema', $schema);
$SMARTY->display('promotion/promotionschemaedit.html');

?>
