<?php
/**
 * @author Maciej_Wawryk
 */

$layout['pagetitle'] = trans('User Groups List');

$usergrouplist = $LMS->UsergroupGetList();

$listdata['total'] = $usergrouplist['total'];
$listdata['totalcount'] = $usergrouplist['totalcount'];

unset($usergrouplist['total']);
unset($usergrouplist['totalcount']);

$SMARTY->assign('usergrouplist', $usergrouplist);
$SMARTY->assign('listdata', $listdata);
$SMARTY->display('user/usergrouplist.html');
