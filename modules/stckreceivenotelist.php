<?php
$layout['pagetitle'] = trans('Receive notes list');

if(!isset($_GET['o']))
	$SESSION->restore('srnlo', $o);
else
	$o = $_GET['o'];

$SESSION->save('srnlo', $o);

if(!isset($_GET['sprn']))
	$SESSION->restore('srnlsp', $sprn);
else
	$sprn = $_GET['sprn'];

$SESSION->save('srnlsp', $sprn);

switch ($_GET['action']) {
	case 'srna':
		foreach ($_POST['marks'] as $k => $v) {
			$LMSST->ReceiveNoteAccount($k);
			//print_r($rn);
		}
		break;
	default:
		break;
}

$filter['days'] = 90;//default numer of showed days

if ($_GET['action'] == 'filter') {
	if (!empty($_POST['customerid']) && $LMS->CustomerExists($_POST['customerid']))
		$filter['cid'] = $_POST['customerid'];
	else
		$filter['cid'] = NULL;
	
	if (!empty($_POST['stckrnn']) && $LMSST->ReceiveNoteExists($_POST['stckrnn']))
		$filter['stckrnn'] = $_POST['stckrnn'];
	else
		$filter['stckrnn'] = NULL;
	if (!empty($_POST['days'])) {
		if (ctype_digit($_POST['days']))
			$filter['days'] = $_POST['days'];
		elseif ($_POST['days'] == '-')
			$filter['days'] = false;
	}
	
	$SESSION->save('stckrnl', $filter);
} else {
	$SESSION->restore('stckrnl', $filter);
}

$receivenotelist = $LMSST->ReceiveNoteList($o, $pagelimit, $page, $sprn, $filter['cid'], $filter['stckrnn'], $filter['days']);
$listdata['total'] = $receivenotelist['total'];
$listdata['direction'] = $receivenotelist['direction'];
$listdata['order'] = $receivenotelist['order'];
$listdata['totalvu'] = $receivenotelist['totalvu'];

if(!isset($_GET['page']))
	$SESSION->restore('srnlp', $_GET['page']);

$pagelimit = (!ConfigHelper::getConfig('phpui.receivenotelist_pagelimit') ? 100 : ConfigHelper::getConfig('phpui.receivenotelist_pagelimit'));
$page = (! $_GET['page'] ? (floor($listdata['total']/$pagelimit)) : $_GET['page']);

unset($receivenotelist['totalvu']);
unset($receivenotelist['total']);
unset($receivenotelist['direction']);
unset($receivenotelist['order']);

foreach ($receivenotelist as $k => $v)
	$receivenotelist[$k]['sbalance'] = $LMS->GetCustomerBalance($v['sid']);

$start = ($page - 1) * $pagelimit;

if ($start > $listdata['total']) {
        $page = 1;
        $start = 0;
}

$SESSION->save('srnlp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('filter', $filter);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('listdata', $listdata);
$SMARTY->assign('sprn', $sprn);
$SMARTY->assign('receivenotelist', $receivenotelist);
$SMARTY->display('stck/stckreceivenotelist.html');
?>
