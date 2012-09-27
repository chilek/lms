<?php

$list_new_ip = $LMS->DB->GetAll("SELECT ipaddr_pub_new AS ipnum, INET_NTOA(ipaddr_pub_new) AS ip FROM log_ip_change WHERE ipaddr_pub_new>170000000 ORDER BY ipaddr_pub_new");

$list = $LMS->DB->GetAll("
SELECT CONCAT(customers.name, ' ',customers.lastname) AS kto, FROM_UNIXTIME(log_ip_change.moddate) AS do, INET_NTOA(log_ip_change.ipaddr_pub) AS from_ip,INET_NTOA(log_ip_change.ipaddr_pub_new) AS to_ip 
 FROM log_ip_change JOIN customers ON log_ip_change.ownerid=customers.id ORDER BY log_ip_change.moddate DESC;");    



$ip=$_POST[ip];

if($ip)
{

$list_ip = $LMS->DB->GetAll("
SELECT INET_NTOA(ipaddr_pub) AS from_ip,INET_NTOA(ipaddr_pub_new) AS to_ip,CONCAT(customers.name, ' ',customers.lastname) AS kto, FROM_UNIXTIME(log_ip_change.moddate) AS do
FROM log_ip_change JOIN customers ON log_ip_change.ownerid=customers.id 
WHERE (ipaddr_pub=$ip OR ipaddr_pub_new=$ip)
ORDER BY log_ip_change.moddate DESC;");    
}
//print_r($list_ip);

//$dupa=long2ip($_POST[ip]);


$SMARTY->assign('list_new_ip',$list_new_ip);
$SMARTY->assign('list',$list);
$SMARTY->assign('list_ip',$list_ip);


$SMARTY->display('ipchange.html');

?>

