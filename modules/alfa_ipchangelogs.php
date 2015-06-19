<?php
 
$list_new_ip = $LMS->DB->GetAll("SELECT ipaddr_new AS ipnum, INET_NTOA(ipaddr_new) AS ip FROM log_ip_change WHERE ipaddr_new>170000000 ORDER BY ipaddr_new");
 
$list = $LMS->DB->GetAll("
SELECT CONCAT(customers.name, ' ',customers.lastname) AS kto, FROM_UNIXTIME(log_ip_change.moddate) AS do, INET_NTOA(log_ip_change.ipaddr) AS from_ip,INET_NTOA(log_ip_change.ipaddr_new) AS to_ip 
 FROM log_ip_change JOIN customers ON log_ip_change.ownerid=customers.id ORDER BY log_ip_change.moddate DESC;");    
 
 
 
$ip=$_POST[ip];
 
if($ip)
{
 
$list_ip = $LMS->DB->GetAll("
SELECT INET_NTOA(ipaddr) AS from_ip,INET_NTOA(ipaddr_new) AS to_ip,CONCAT(customers.name, ' ',customers.lastname) AS kto, FROM_UNIXTIME(log_ip_change.moddate) AS do
FROM log_ip_change JOIN customers ON log_ip_change.ownerid=customers.id 
WHERE (ipaddr=$ip OR ipaddr_new=$ip)
ORDER BY log_ip_change.moddate DESC;");    
}
//print_r($list_ip);
 
//$dupa=long2ip($_POST[ip]);
 
 
$SMARTY->assign('list_new_ip',$list_new_ip);
$SMARTY->assign('list',$list);
$SMARTY->assign('list_ip',$list_ip);
$SMARTY->display('alfa_ipchangelogs.html');
?>

