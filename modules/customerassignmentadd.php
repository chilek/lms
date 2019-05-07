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

	$result = $LMS->ValidateAssignment($a);
	extract($result);

	if (!$LMS->CheckSchemaModifiedValues($a))
		$error['promotion-select'] = trans('Illegal promotion schema period value modification!');

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

		$LMS->UpdateExistingAssignments($a);

		if (is_array($a['stariffid'][$schemaid])) {
			$modifiedvalues = $a['values'][$schemaid];
			$copy_a = $a;
			$snodes = $a['snodes'][$schemaid];
			$sphones = $a['sphones'][$schemaid];

			foreach ($a['stariffid'][$schemaid] as $label => $v) {
				if (!$v)
					continue;

			    $copy_a['promotiontariffid'] = $v;
			    $copy_a['modifiedvalues'] = isset($modifiedvalues[$label][$v]) ? $modifiedvalues[$label][$v] : array();
			    $copy_a['nodes'] = $snodes[$label];
				$copy_a['phones'] = $sphones[$label];
				$tariffid = $LMS->AddAssignment($copy_a);
			}
		} else {
			$LMS->UpdateExistingAssignments($a);
			$tariffid = $LMS->AddAssignment($a);
		}

        if ($a['tarifftype'] == SERVICE_PHONE && !empty($a['phones']))
            $tariffid = $tariffid[0];

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
		$a['invoice'] = $default_assignment_invoice;
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

$layout['pagetitle'] = trans('New Liability: $a', '<A href="?m=customerinfo&id='.$customer['id'].'">'.$customer['name'].'</A>');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$LMS->executeHook(
    'customerassignmentadd_before_display',
    array(
        'a' => $a,
        'smarty' => $SMARTY,
    )
);

$SMARTY->assign('promotions', $LMS->GetPromotions());
$SMARTY->assign('customernodes', $LMS->GetCustomerNodes($customer['id']));
$SMARTY->assign('customernetdevnodes', $LMS->getCustomerNetDevNodes($customer['id']));
$SMARTY->assign('voipaccounts', $LMS->GetCustomerVoipAccounts($customer['id']));
$SMARTY->assign('customeraddresses', $LMS->getCustomerAddresses($customer['id']));
$SMARTY->assign('numberplanlist', $LMS->GetNumberPlans(array(
	'doctype' => DOC_INVOICE,
	'cdate' => null,
	'division' => $customer['divisionid'],
	'next' => false,
)));

$SMARTY->assign('tags', $LMS->TarifftagGetAll());

$SMARTY->assign('assignment'          , $a);

$SMARTY->assign('tariffs'             , $LMS->GetTariffs());
$SMARTY->assign('taxeslist'           , $LMS->GetTaxes());
$SMARTY->assign('assignments'         , $LMS->GetCustomerAssignments($customer['id'], true, false));
$SMARTY->assign('customerinfo'        , $customer);

$SMARTY->display('customer/customerassignmentsedit.html');

?>
