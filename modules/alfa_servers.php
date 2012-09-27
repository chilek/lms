<?php
//example data
/*
$hosts[0][ip]="80.51.89.1";
$hosts[0][ports]="22";

$hosts[1][ip]="80.51.89.2";
$hosts[1][ports]="21,53,80";

$hosts[2][ip]="80.51.89.3";
$hosts[2][ports]="80";

$hosts[3][ip]="80.51.89.4";
$hosts[3][ports]="21,22,80,3306";

$hosts[4][ip]="80.51.89.5";
$hosts[4][ports]="21,22,53";

$hosts[5][ip]="80.51.89.6";
$hosts[5][ports]="21,22,80,3306";

$hosts[6][ip]="80.51.89.7";
$hosts[6][ports]="21,22,80,110,587,3306";

$hosts[7][ip]="80.51.89.8";
$hosts[7][ports]="21,80";

$hosts[8][ip]="80.51.89.9";
$hosts[8][ports]="21,22,80,3306";

$hosts[9][ip]="80.51.89.10";
$hosts[9][ports]="21,80";

$hosts[10][ip]="80.51.89.11";
$hosts[10][ports]="22";

$hosts[11][ip]="80.51.89.12";
$hosts[11][ports]="22";

$hosts[12][ip]="80.51.89.13";
$hosts[12][ports]="443";

$hosts[13][ip]="80.51.89.14";
$hosts[13][ports]="22,80";
*/

function CheckHost($host,$port)
{
    $fp = fsockopen($host, $port,$errno,$errstr, 4);
    if (!$fp){
	$status=0;
    } else {
	$status=1;
    }                                	

    fclose($fp);
    return $status;
}

//foreach ($hosts as $host)
$sum_hosts=count($hosts);
for($a=0;$a<$sum_hosts;$a++)
{
    $ports = explode(",", $hosts[$a][ports]);
    $ile=count($ports);
    $stats='';
    for($b=0;$b<$ile;$b++)
    {
    $stat=CheckHost($hosts[$a][ip],$ports[$b]);
    $stats.=",".$stat;
    //echo $hosts[$a][ip].":".$ports[$b]." = ".$stat."<br>";
    }

    $stats=substr($stats,1)."<br>";
    
    $hosts[$a][stats]=$stats;
    
}
    
//echo '<pre>';print_r($hosts);echo '</pre>';
$roczniki=$DB->GetAll('select (100+right(year(curdate()),2)-left(ssn,2)) as wiek, count(id) as ile from customers where ssn>0 group by wiek order by wiek');
$srednia=$DB->GetAll('select sum((100+right(year(curdate()),2)-left(ssn,2))) as suma, count(id) as ile from customers where ssn>0');
//echo '<pre>';print_r($srednia); echo '</pre>';
$SMARTY->assign('roczniki',$roczniki);
$SMARTY->assign('srednia',$srednia);
$SMARTY->assign('hosts',$hosts);
$SMARTY->display('alfa_servers.html');
?>

