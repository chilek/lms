<?php
$roczniki=$DB->GetAll('select (100+right(year(curdate()),2)-left(ssn,2)) as wiek, count(id) as ile from customers where ssn>0 group by wiek order by wiek');
$srednia=$DB->GetAll('select sum((100+right(year(curdate()),2)-left(ssn,2))) as suma, count(id) as ile from customers where ssn>0');
//echo '<pre>';print_r($srednia); echo '</pre>';
$SMARTY->assign('roczniki',$roczniki);
$SMARTY->assign('srednia',$srednia);

$SMARTY->display('alfa_age.html');
?>

