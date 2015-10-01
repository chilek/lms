<?php

$layout['pagetitle']='Historia sygnałów: '.$LMS->GetNodeName($_GET['id']);

if ($signallist = $DB->GetAll('SELECT * FROM signals WHERE nodeid='.$_GET['id'].' ORDER BY date DESC')) {
	foreach ($signallist as $idx => $row) {
		$netdev=$LMS->GetNetDev($row['netdev']);
		$signallist[$idx]['ap']=$netdev['name'];
		list($data,$units)=setunits($row['rxbytes']);
		$signallist[$idx]['rxbytes']=number_format($data,2,',',' ').' '.$units;
		list($data,$units)=setunits($row['txbytes']);
		$signallist[$idx]['txbytes']=number_format($data,2,',',' ').' '.$units;
		$signallist[$idx]['date']=substr($row['date'],0,16);
	}

}

$SMARTY->assign('signallist',$signallist);

$SMARTY->display('node/nodesignals.html');

?>
