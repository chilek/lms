<?php
function GetPPPoEList($id)
{
        global $DB;
        $list = $DB->GetAll('SELECT callingstationid, nasipaddress, acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, framedipaddress, "B" as uploadunit, "B" as downloadunit, acctterminatecause FROM radacct WHERE username=? ORDER BY radacctid DESC', array($id));
        foreach ($list as &$PPPoESession)
        {
            list($PPPoESession['upload'],$PPPoESession['uploadunit']) = setunits($PPPoESession['acctinputoctets']);
            list($PPPoESession['download'],$PPPoESession['downloadunit']) = setunits($PPPoESession['acctoutputoctets']);
            $PPPoESession['acctsessiontimeconv']=date("z \d\\n\i H:i:s",-3600+$PPPoESession['acctsessiontime']);
            if($PPPoESession['acctstoptime'] == 0) $PPPoESession['acctstoptime']='TRWA';
        }
        $list['total'] = sizeof($list);
        return $list;
}
 
    $id=$_GET['id'];
 
 
 
$pppoelist = GetPPPoEList($id);
$listdata['total'] = $pppoelist['total'];
 
unset($pppoelist['total']);
$pagelimit=100;
$page = !isset($_GET['page']) ? ceil($listdata['total']/$pagelimit) : intval($_GET['page']);
$start = ($page - 1) * $pagelimit;
 
$layout['pagetitle'] = trans('Sessions List');
$SESSION->save('backto', $_SERVER['QUERY_STRING']);
 
$SMARTY->assign('customerlist', $LMS->GetCustomerNames());
$SMARTY->assign('pppoelist', $pppoelist);
$SMARTY->assign('pagelimit', $pagelimit);
$SMARTY->assign('page', $page);
$SMARTY->assign('start', $start);
$SMARTY->assign('listdata', $listdata);
 
$SMARTY->display('raddstat.html');
 
?>