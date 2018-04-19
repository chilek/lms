<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2015 LMS Developers
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

use \plugins\LMSNovitusHDPlugin\lib\LMSHelper;


$layout['pagetitle'] = trans('Novitus HD');

$type = htmlspecialchars($_GET['type']);

global $LMS;
$LMS->InitXajax();
$LMS->RegisterXajaxFunction(array('getInvoices', 'printInvoice','printDailyReport','printPeriodReport','setTime','getConfig','setConfig','getLastTransaction','getCurrentTransaction','closeTransaction','setErrorHandler','getLastError','getHeader'));


switch ($type) {
	case 'info':
		$printer = new NovitusHD();

		$version = $printer->getPrinterVersion();
		$fiscalMemory = $printer->getFiscalMemory();
		$taxes = $printer->getTaxRates();
		$consttaxes = NovitusHD::TAXRATES;
		$cashInfo = $printer->getCashInformation();
		$config = $printer->configure(array_keys(NovitusHD::CONFIGOPTIONS), 'get');

		unset($printer);
		$SMARTY->assign('ver', $version);
		$SMARTY->assign('fiscal', $fiscalMemory);
		$SMARTY->assign('taxes', $taxes);
		$SMARTY->assign('consttaxes', $consttaxes);
		$SMARTY->assign('cashinfo', $cashInfo);
		$SMARTY->assign('config', $config['children'][0]['children']);
		$SMARTY->display('info.tpl');

		break;

	case 'invoice':

		$SMARTY->assign('customergroups', $LMS->CustomergroupGetAll());
		$SMARTY->assign('numberplans', $LMS->GetNumberPlans(array(
			'doctype' => array(DOC_INVOICE, DOC_CNOTE),
		)));
		$SMARTY->assign('divisions', $LMS->GetDivisions());
		$SMARTY->assign('xajax', $LMS->RunXajax());
		$SMARTY->display('invoice.tpl');
		break;

	case 'actions':

		$SMARTY->assign('xajax', $LMS->RunXajax());
		$SMARTY->display('actions.tpl');

		break;
	case 'config';

		$SMARTY->assign('xajax', $LMS->RunXajax());
		$SMARTY->assign('configoptions', NovitusHD::CONFIGOPTIONS);
		$SMARTY->display('config.tpl');
		break;
	default:
		$SMARTY->display('info.tpl');
		break;

}

/**
 * @param $form
 * @param bool $fullData
 * @return xajaxResponse
 */
function getInvoices($form, $fullData = false){
	$obj = new xajaxResponse();

	$formArray = json_decode($form, true);
	$data = array();
	foreach ($formArray as $item){ // item['name] $item['value']
		$data[$item['name']] = $item['value'];
	}

		$from = $data['invoicefrom'];
		$to = $data['invoiceto'];

		// date format 'yyyy/mm/dd'
		if ($to) {
			list($year, $month, $day) = explode('/', $to);
			$date['to'] = mktime(23, 59, 59, $month, $day, $year);
		} else {
			$to = date('Y/m/d', time());
			$date['to'] = mktime(23, 59, 59); //koniec dnia dzisiejszego
		}

		if ($from) {
			list($year, $month, $day) = explode('/', $from);
			$date['from'] = mktime(0, 0, 0, $month, $day, $year);
		} else {
			$from = date('Y/m/d', time());
			$date['from'] = mktime(0, 0, 0); //początek dnia dzisiejszego
		}

		$invoices = LMSHelper::getInvoices($date['from'], $date['to'],
			htmlspecialchars($data['customer_type']),
			(int)$data['customer'],
			(int)$data['division'],
			(int)$data['group'],
			(isset($data['groupexclude']) ? true : false),
			(isset($data['showonlynotfiscalized']) ? true : false)
		);

	if ($invoices){

		if ($fullData){
			global $LMS;

			$invoicesTable = '';
			$index = 1;
			foreach ($invoices as $id){
				$tmp = $LMS->GetInvoiceContent($id);
				$invNo = $tmp['template'];
				$invNo = str_replace('%N', $tmp['number'], $invNo);
				$invNo = str_replace('%m', $tmp['month'], $invNo);
				$invNo = str_replace('%Y', $tmp['year'], $invNo);
				$tmp['fiscalized'] = LMSHelper::isInvoiceFiscalized($tmp['id']) ? '1' : '0';

				$invoicesTable .= '<tr class="highlight '.(($index % 2 === 0) ? 'lucid' : 'light') .' '.($tmp['fiscalized'] ? 'blend' : '').'"><td>'.$invNo.'</td><td><strong>&lang;'.$tmp['customerid'].'&rang;</strong> '.$tmp['name'].'</td><td>'.$tmp['total'].'</td><td'.($tmp['fiscalized'] ? ' class="green">TAK' : ' class="red">NIE').'</td></tr>';
				unset($tmp);
				$index++;
			}
			$invoicesTable = '<table class="lmsbox"><thead><tr><th>'.trans('Number').'</th><th>'.trans('Customer').'</th><th>'.trans('Value').'</th><th>'.trans('Fiscalized').'</th></tr></thead>'.$invoicesTable.'</table>';



			$obj->assign('novitusLog', 'innerHTML', $invoicesTable);
		} else {
			$obj->call('startPrinting', $invoices);
		}

	} else {
		$obj->assign('novitusLog', 'innerHTML', '<h4>'.trans('No not fiscalized invoices found').'</h4>');
	}

	return $obj;
}

function printInvoice($id){
	$obj = new xajaxResponse();
	$p = new NovitusHD();

	$res = $p->printInvoice($id);

	if ($res['status'] === 'NOK'){

		if ($res['reason'] === 2){
			$obj->append('novitusLog', 'innerHTML', $res['error'].'<br>');
			$obj->call('printInvoice');

		} else {
			$obj->append('novitusLog', 'innerHTML', '<h3 class="errorList">'.$res['error'].'</h3>');
			$obj->call('stopPrinting');
		}

	} else {
		$obj->append('novitusLog', 'innerHTML', 'Faktura: <strong>'.$res['data']['humanNo'].' - '.$res['data']['name']. '</strong> wydrukowana<br>');
		$obj->call('printInvoice');
	}

	unset($res);
	unset($p);
	return $obj;
}

function printDailyReport($date = null){
	$obj = new xajaxResponse();
	$p = new NovitusHD();

	$res = $p->printDailyReport($date);

	if ($res['status'] === 'OK') {

		$obj->assign('novitusActionsDailyReport', 'innerHTML', trans('Daily report task has been sent to printer'));
	} else {
		$lastError = $p->getLastError();
		$obj->assign('novitusActionsDailyReport', 'innerHTML', trans('Error sending data'));
		$obj->assign('novitusActionsDailyReport', 'innerHTML', '<br>'.trans($lastError['attr']['desc']));
	}

	unset($p);
	return $obj;
}

function printPeriodReport($datefrom, $dateto, $kind){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->printPeriodReport($datefrom, $dateto, $kind);

	if ($res['status'] === 'OK') {

		$obj->assign('novitusActionsMonthlyReport', 'innerHTML', trans('Report task has been sent to printer'));
	} else {
		$obj->assign('novitusActionsMonthlyReport', 'innerHTML', trans('Error sending data').': '.trans($res['error']));
	}

	unset($p);
	return $obj;
}

function setTime($date){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->setDateTime($date);

	if ($res['status'] === 'OK') {

		$obj->assign('novitusConfigSetTime', 'innerHTML', trans('Set time has been sent to printer'));
	} else {
		$obj->assign('novitusConfigSetTime', 'innerHTML', trans('Error sending data').': '.trans($res['error']));
	}

	unset($p);
	return $obj;
}

function getLastTransaction(){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->getLastTransactionState();

	if ($res) {
		$dataTable = '';

		$dataTable .= '<div><p>'.trans('Type').': <strong>'.$res['attr']['type'].'</strong>, '.trans('Transaction state').': <strong>'.$res['attr']['state'].'</strong>, Data: <strong>'.$res['attr']['date'].'</strong>, ' .trans('Print number').': <strong>'.$res['attr']['printoutno'].'</strong></p></div>';
		$dataTable .= '';

		$obj->assign('novitusActionsGetLastTransaction', 'innerHTML', $dataTable);
	} else {
		$obj->assign('novitusActionsGetLastTransaction', 'innerHTML', trans('Error sending data').': '.trans($res['error']));
	}

	unset($p);
	return $obj;
}

function getCurrentTransaction(){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->getCurrentTransactionState();

	if ($res) {

		$dataTable = '';
		if ($res['attr']['type'] === 'none'){
			$dataTable .= '<p>'.trans('Printer has no open transaction').'</p>';
		} else {
			$dataTable .= '<p>'.trans('Type').': <strong>'.$res['attr']['type'].'</strong>, '.trans('Gross total').': <strong>'.$res['attr']['grosstotal'].'</strong></p><input type="button" value="'.trans('Clear transaction').'" onclick="xajax_closeTransaction()">';
		}

		$obj->assign('novitusActionsCurrentTransaction', 'innerHTML', $dataTable);
	} else {
		$obj->assign('novitusActionsCurrentTransaction', 'innerHTML', trans('Error sending data').': '.trans($res['error']));
	}

	unset($p);
	return $obj;
}

function getLastError(){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->getLastError();

	if ($res['status'] === 'OK') {


		$obj->assign('novitusActionsLastError', 'innerHTML', trans($res['data']['attr']['desc']));
	} else {
		$obj->assign('novitusActionsLastError', 'innerHTML', trans($res['error']));
	}

	unset($p);
	return $obj;
}

function closeTransaction(){
	$obj = new xajaxResponse();
	$p = new NovitusHD();
	$res = $p->cancelInvoice();

	if ($res) {

		$obj->assign('novitusActionsCurrentTransaction', 'innerHTML', trans('Transaction cleared'));
	} else {
		$obj->assign('novitusActionsCurrentTransaction', 'innerHTML', trans('Error sending data').': '.trans($res['error']));
	}

	unset($p);
	return $obj;
}

function setConfig($config)
{

	$obj = new xajaxResponse();
	$p = new NovitusHD();

	$configData = array(
		$config[0]['value'] => $config[1]['value']
	);

	$res = $p->configure($configData, 'set');

	if ($res) {

		$obj->assign('novitusConfigSetConfig', 'innerHTML', trans('Option set'));
	} else {
		$obj->assign('novitusConfigSetConfig', 'innerHTML', trans('Error sending data') . ': ' . trans($res['error']));
	}

	unset($p);
	return $obj;
}

function getConfig($opt){
	$obj = new xajaxResponse();
	$p = new NovitusHD();

	$res = $p->configure([$opt]);

	if ($res) {

		$obj->call('setOption', $res['children'][0]['children'][0]['value']);
	} else {
		$obj->assign('novitusConfigSetConfig', 'innerHTML', trans($res['error']));
	}

	unset($p);
	return $obj;
}

function setErrorHandler($type){
	$obj = new xajaxResponse();
	$p = new NovitusHD();

	$res = $p->setErrorHandler($type);

	if ($res['status'] === 'OK') {

		$obj->assign('novitusConfigErrorHandler', 'innerHTML', trans('Option set'));
	} else {
		$obj->assign('novitusConfigErrorHandler', 'innerHTML', trans('Error sending data') . ': ' . trans($res['error']));
	}

	unset($p);
	return $obj;
}


