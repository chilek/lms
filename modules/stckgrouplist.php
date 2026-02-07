<?php
function check($str){
        $c=strlen($str)==1 ? ord($str) : 0;    // get the ascii code if it is a single character
	return ($c>=ord('A') && $c<=ord('Z'));  // it is a single character between a and z
}

if (!check($_GET['s']))
	$listdata['state'] = NULL;
else
	$listdata['state'] = $_GET['s'];
	

$layout['pagetitle'] = trans('Product groups');

if(!isset($_GET['o']))
	$SESSION->restore('sglo', $o);
else
	$o = $_GET['o'];

$SESSION->save('sglo', $o);

$grouplist = $LMSST->GroupGetList($o, $listdata['state']);
$listdata['total'] = $grouplist['total'];
$listdata['direction'] = $grouplist['direction'];
$listdata['order'] = $grouplist['order'];
unset($grouplist['total']);
unset($grouplist['direction']);
unset($grouplist['order']);

if(!isset($_GET['page']))
        $SESSION->restore('sglp', $_GET['page']);

$page = (! $_GET['page'] ? 1 : $_GET['page']);
$pagelimit = (!ConfigHelper::getConfig('phpui.grouplist_pagelimit')? 100 : ConfigHelper::getConfig('phpui.grouplist_pagelimit'));
$start = ($page - 1) * $pagelimit;

$SESSION->save('sglp', $page);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('page',$page);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('grouplist', $grouplist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('stck/stckgrouplist.html');
?>
