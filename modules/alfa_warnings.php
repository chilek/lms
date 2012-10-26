<?php

function GetUsersWithWarning()
{
    global $LMS,$SMARTY,$DB;
    
    $warnings=$DB->GetAll('
    SELECT SUM(cash.value) AS suma,nodes.warning,nodes.ownerid,customers.lastname,customers.name,customers.notes
    FROM 
    (nodes LEFT JOIN customers ON customers.id = nodes.ownerid),
    cash    
    WHERE nodes.warning=1 AND cash.customerid=nodes.ownerid 
    GROUP BY nodes.ownerid
    ORDER BY SUM(cash.value)
    ');
//    echo '<pre>';print_r($warnings);echo '</pre>';
    return $warnings;
}                    

$warnings=GetUsersWithWarning();

$ilu=count($warnings);


for($i=0;$i<$ilu;$i++)
{

    $suma=$suma+$warnings[$i][suma];
}

$SMARTY->assign('suma',$suma);
$SMARTY->assign('warnings',$warnings);
$SMARTY->display('alfa_warnings.html');
?>

