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

// get customer name and check privileges using customerview
$customer = $DB->GetRow('SELECT id, divisionid, '
    .$DB->Concat('lastname',"' '",'name').' AS name
    FROM customerview WHERE id = ?', array($_GET['id']));

if (!$customer) {
    $SESSION->redirect('?'.$SESSION->get('backto'));
}

if (isset($_POST['assignment'])) {
	$a = $_POST['assignment'];

	foreach ($a as $key => $val) {
	    if (!is_array($val))
		    $a[$key] = trim($val);
	}

	$period = sprintf('%d',$a['period']);

	switch($period) {
		case DAILY:
			$at = 0;
		break;

		case WEEKLY:
			$at = sprintf('%d',$a['at']);

			if (ConfigHelper::checkConfig('phpui.use_current_payday') && $at == 0)
				$at = strftime('%u', time());

			if ($at < 1 || $at > 7)
				$error['at'] = trans('Incorrect day of week (1-7)!');
		break;

		case MONTHLY:
			$at = sprintf('%d',$a['at']);

			if (ConfigHelper::checkConfig('phpui.use_current_payday') && $at == 0)
				$at = date('j', time());

			if (!ConfigHelper::checkConfig('phpui.use_current_payday')
				&& ConfigHelper::getConfig('phpui.default_monthly_payday') > 0 && $at == 0)
				$at = ConfigHelper::getConfig('phpui.default_monthly_payday');

			$a['at'] = $at;

			if($at > 28 || $at < 1)
				$error['at'] = trans('Incorrect day of month (1-28)!');
		break;

		case QUARTERLY:
			if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			} elseif(!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			} else {
				list($d,$m) = explode('/',$a['at']);
			}

			if (!$error) {
				if ($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');

				if ($m>3 || $m<1)
					$error['at'] = trans('Incorrect month number (max.3)!');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case HALFYEARLY:
			if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at']) && $a['at'])
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			elseif (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			} else {
				list($d,$m) = explode('/',$a['at']);
			}

			if (!$error) {
				if ($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');

				if ($m>6 || $m<1)
					$error['at'] = trans('Incorrect month number (max.6)!');

				$at = ($m-1) * 100 + $d;
			}
		break;

		case YEARLY:
			if (ConfigHelper::checkConfig('phpui.use_current_payday') && !$a['at']) {
				$d = date('j', time());
				$m = date('n', time());
				$a['at'] = $d.'/'.$m;
			} elseif(!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
				$error['at'] = trans('Incorrect date format! Enter date in DD/MM format!');
			} else {
				list($d,$m) = explode('/',$a['at']);
			}

			if (!$error) {
				if ($d>30 || $d<1 || ($d>28 && $m==2))
					$error['at'] = trans('This month doesn\'t contain specified number of days');

				if ($m>12 || $m<1)
					$error['at'] = trans('Incorrect month number');

				$ttime = mktime(12, 0, 0, $m, $d, 1990);
				$at = date('z',$ttime) + 1;
			}
		break;

		default: // DISPOSABLE
			$period = DISPOSABLE;

			if(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['at'])) {
				list($y, $m, $d) = explode('/', $a['at']);
				if (checkdate($m, $d, $y)) {
					$at = mktime(0, 0, 0, $m, $d, $y);

					if ($at < mktime(0, 0, 0) && !$a['atwarning']) {
						$a['atwarning'] = TRUE;
						$error['at'] = trans('Incorrect date!');
					}
				} else
					$error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
			} else
				$error['at'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		break;
	}

	if ($a['datefrom'] == '') {
		$from = 0;
	} elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/',$a['datefrom'])) {
		list($y, $m, $d) = explode('/', $a['datefrom']);

		if (checkdate($m, $d, $y))
			$from = mktime(0, 0, 0, $m, $d, $y);
		else
			$error['datefrom'] = trans('Incorrect charging time!');
	} else
		$error['datefrom'] = trans('Incorrect charging time!');

	if ($a['dateto'] == '') {
		$to = 0;
	} elseif(preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $a['dateto'])) {
		list($y, $m, $d) = explode('/', $a['dateto']);

		if (checkdate($m, $d, $y))
			$to = mktime(23, 59, 59, $m, $d, $y);
		else
			$error['dateto'] = trans('Incorrect charging time!');
	} else
		$error['dateto'] = trans('Incorrect charging time!');

	if ($to < $from && $to != 0 && $from != 0)
		$error['dateto'] = trans('Incorrect date range!');

	$a['discount'] = str_replace(',', '.', $a['discount']);
	$a['pdiscount'] = 0;
	$a['vdiscount'] = 0;
	if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $a['discount'])) {
		$a['pdiscount'] = ($a['discount_type'] == DISCOUNT_PERCENTAGE ? floatval($a['discount']) : 0);
		$a['vdiscount'] = ($a['discount_type'] == DISCOUNT_AMOUNT ? floatval($a['discount']) : 0);
	}
	if ($a['pdiscount'] < 0 || $a['pdiscount'] > 99.99)
		$error['discount'] = trans('Wrong discount value!');

	if (intval($a['tariffid']) <= 0)
		switch ($a['tariffid']) {
			// suspending
			case -1:
				$a['tariffid']  = null;
				$a['discount']  = 0;
				$a['pdiscount'] = 0;
				$a['vdiscount'] = 0;
				$a['value']     = 0;

				unset($a['schemaid'], $a['stariffid'], $a['invoice'], $a['settlement'], $error['at']);
				$at = 0;
			break;

			// promotion schema
			case -2:
				if (!$from) {
					$error['datefrom'] = trans('Promotion start date is required!');
				} else {
					$schemaid = isset($a['schemaid']) ? intval($a['schemaid']) : 0;
					if (count($a['stariffid'][$schemaid]) == 1) {
						$a['promotiontariffid'] = $a['stariffid'][$schemaid][0];
					} else {
						$a['promotiontariffid'] = $a['stariffid'][$schemaid];
					}

					$a['value']     = 0;
					$a['discount']  = 0;
					$a['pdiscount'] = 0;
					$a['vdiscount'] = 0;
					// @TODO: handle other period/at values
					$a['period'] = MONTHLY; // dont know why, remove if you are sure
					$a['at'] = 1;
				}
			break;

			// tariffless
			default:
				if (!$a['name'])
					$error['name'] = trans('Liability name is required!');

				if (!$a['value'])
					$error['value'] = trans('Liability value is required!');
				elseif (!preg_match('/^[-]?[0-9.,]+$/', $a['value']))
					$error['value'] = trans('Incorrect value!');
				elseif ($a['discount_type'] == 2 && $a['discount'] && $a['value'] - $a['discount'] < 0) {
					$error['value'] = trans('Value less than discount are not allowed!');
					$error['discount'] = trans('Value less than discount are not allowed!');
				}

				unset($a['schemaid'], $a['stariffid']);
		}
	else {
		if ($a['discount_type'] == 2 && $a['discount']
			&& $DB->GetOne('SELECT value FROM tariffs WHERE id = ?', array($a['tariffid'])) - $a['discount'] < 0) {
			$error['value'] = trans('Value less than discount are not allowed!');
			$error['discount'] = trans('Value less than discount are not allowed!');
		}

		unset($a['schemaid'], $a['stariffid']);
	}

        $hook_data = $LMS->executeHook(
            'customerassignmentadd_validation_before_submit', 
            array(
                'a' => $a,
                'error' => $error
            )
        );
        $a = $hook_data['a'];
        $error = $hook_data['error'];

	if (!$error) {
		$a['customerid'] = $customer['id'];
		$a['period']     = $period;
		$a['at']         = $at;
		$a['datefrom']   = $from;
		$a['dateto']     = $to;

		$DB->BeginTrans();

		if (is_array($a['stariffid'][$schemaid])) {
			$copy_a = $a;
			$snodes = $a['snodes'][$schemaid];

			foreach ($a['stariffid'][$schemaid] as $label => $v) {
				if (!$v)
					continue;

			    $copy_a['promotiontariffid'] = $v;
			    $copy_a['nodes'] = $snodes[$label];
				$tariffid = $LMS->AddAssignment($copy_a);
			}
		} else {
			$tariffid =$LMS->AddAssignment($a);
		}

        if ($a['tarifftype'] == TARIFF_PHONE && !empty($a['phones'])) {
            $tariffid = $tariffid[0];

            foreach($a['phones'] as $p)
                $DB->Execute('INSERT INTO voip_number_assignments (number_id, assignment_id) VALUES (?,?)', array($p, $tariffid));
        }

		$DB->CommitTrans();

		$LMS->executeHook(
			'customerassignmentadd_after_submit',
			array(
				'assignment' => $a,
			)
		);

		$SESSION->redirect('?'.$SESSION->get('backto'));
	}

	$a['alltariffs'] = isset($a['alltariffs']);

	$SMARTY->assign('error', $error);
}
else
{
	$default_assignment_invoice = ConfigHelper::getConfig('phpui.default_assignment_invoice');
	if (!empty($default_assignment_invoice))
		$a['invoice'] = true;
	$default_assignment_settlement = ConfigHelper::getConfig('phpui.default_assignment_settlement');
	if (!empty($default_assignment_settlement))
		$a['settlement'] = true;
	$default_assignment_period = ConfigHelper::getConfig('phpui.default_assignment_period');
	if (!empty($default_assignment_period))
		$a['period'] = $default_assignment_period;
	$default_assignment_at = ConfigHelper::getConfig('phpui.default_assignment_at');
	if (!empty($default_assignment_at))
		$a['at'] = $default_assignment_at;
}

$expired = isset($_GET['expired']) ? $_GET['expired'] : false;

$layout['pagetitle'] = trans('New Liability: $a', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$customernodes = $LMS->GetCustomerNodes($customer['id']);
unset($customernodes['total']);

$LMS->executeHook(
    'customerassignmentadd_before_display',
    array(
        'a' => $a,
        'smarty' => $SMARTY,
    )
);

$promotions = $DB->GetAll('SELECT id, name,
		(CASE WHEN datefrom < ?NOW? AND (dateto = 0 OR dateto > ?NOW?) THEN 1 ELSE 0 END) AS valid
	FROM promotions WHERE disabled <> 1');

$promotion_schemas = $DB->GetAll('SELECT p.id AS promotionid, p.name AS promotion, s.name, s.id,
	(SELECT ' . $DB->GroupConcat('tariffid', ',') . '
		FROM promotionassignments WHERE promotionschemaid = s.id
	) AS tariffs
	FROM promotions p
	JOIN promotionschemas s ON (p.id = s.promotionid)
	WHERE p.disabled <> 1 AND s.disabled <> 1
		AND EXISTS (SELECT 1 FROM promotionassignments
		WHERE promotionschemaid = s.id LIMIT 1)
	ORDER BY p.name, s.name');

$promotion_schema_assignments = $DB->GetAll('SELECT
		p.id AS promotion_id, p.name as promotion_name,
		ps.id AS schema_id, ps.name as schema_name,
		t.name as tariff_name, pa.optional,
		label, t.id as tariffid, t.authtype
	FROM promotions p
	  LEFT JOIN promotionschemas ps ON p.id = ps.promotionid
	  LEFT JOIN promotionassignments pa ON ps.id = pa.promotionschemaid
	  LEFT JOIN tariffs t ON pa.tariffid = t.id
	ORDER BY
	  p.name, ps.name, pa.orderid ASC');

$promotion_schema_items = array();

if (!empty($promotion_schema_assignments)) {
	$promotion_schema_assignment_labels = $DB->GetAll('SELECT promotionschemaid AS schemaid,
			label, COUNT(*) AS cnt FROM promotionassignments
		WHERE label IS NOT NULL
		GROUP BY promotionschemaid, label
		HAVING COUNT(*) > 1');
	$promotion_schema_selections = array();
	if (!empty($promotion_schema_assignment_labels))
		foreach ($promotion_schema_assignment_labels as $label)
			$promotion_schema_selections[$label['schemaid']][$label['label']] = $label['cnt'];

	$sid = 0;
    foreach ($promotion_schema_assignments as $assign) {
        $pid = $assign['promotion_id'];
    	$pn   = $assign['promotion_name'];
    	if ($assign['schema_id'] != $sid) {
			$sid = $assign['schema_id'];
			$index = 0;
			$selection_indexes = array();
    	}
        $sn   = $assign['schema_name'];
        $selid = empty($assign['label']) || !isset($promotion_schema_selections[$sid][$assign['label']])
			? null : $assign['label'];

        if (!isset($schemas[$pid][$sid]))
			$schemas[$pid][$sid] = array();

        $promotion_schema_item = array(
			'tariffid' => $assign['tariffid'],
			'tariff'   => $assign['tariff_name'],
			'value'    => $assign['value'],
			'optional' => $assign['optional'],
			'label'    => $assign['label'],
			'authtype' => $assign['authtype'],
		);

		if (!empty($selid)) {
			if (!isset($selection_indexes[$selid]))
				$selection_indexes[$selid] = $index++;

			if (!isset($promotion_schema_items[$pid][$sid][$selection_indexes[$selid]]['selection']))
				$promotion_schema_items[$pid][$sid][$selection_indexes[$selid]]['selection'] = array(
				 	'items' => array(),
					'label' => $selid,
				);
			$promotion_schema_items[$pid][$sid][$selection_indexes[$selid]]['selection']['required'] =
				empty($assign['optional']);

			$promotion_schema_items[$pid][$sid][$selection_indexes[$selid]]['selection']['items'][] =
				$promotion_schema_item;
		} else
			$promotion_schema_items[$pid][$sid][$index++]['single'] = $promotion_schema_item;
	}
}

// -----
// remove duplicated customer nodes
// -----

$netdevnodes = $LMS->getCustomerNetDevNodes($customer['id']);

if ($customernodes) {
	foreach ($customernodes as $v) {
		if (isset($netdevnodes[$v['id']]))
			unset($netdevnodes[$v['id']]);
    }
}

$SMARTY->assign('customernetdevnodes' , $netdevnodes);

// -----

$SMARTY->assign('tags', $LMS->TarifftagGetAll());

$SMARTY->assign('assignment'          , $a);
$SMARTY->assign('customernodes'       , $customernodes);
$SMARTY->assign('locations'           , $LMS->GetUniqueNodeLocations($customer['id']));
$SMARTY->assign('customervoipaccs'    , $LMS->getCustomerVoipAccounts($customer['id']));
$SMARTY->assign('customeraddresses'   , $LMS->getCustomerAddresses($customer['id']));

$SMARTY->assign('promotions'          , $promotions);
$SMARTY->assign('promotion_schemas'   , $promotion_schemas);
$SMARTY->assign('promotion_schema_items' , $promotion_schema_items);

$SMARTY->assign('tariffs'             , $LMS->GetTariffs());
$SMARTY->assign('taxeslist'           , $LMS->GetTaxes());
$SMARTY->assign('expired'             , $expired);
$SMARTY->assign('assignments'         , $LMS->GetCustomerAssignments($customer['id'], $expired));
$SMARTY->assign('numberplanlist'      , $LMS->GetNumberPlans(array(
	'doctype' => DOC_INVOICE,
	'cdate' => null,
	'division' => $customer['divisionid'],
	'next' => false,
)));
$SMARTY->assign('customerinfo'        , $customer);

$SMARTY->display('customer/customerassignmentsedit.html');

?>
