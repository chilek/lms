<?php

function GetUsersWithWarning()
{
    global $LMS,$SMARTY,$DB;
    
    $warnings=$DB->GetAll('
    SELECT SUM(cash.value) AS suma,nodes.warning,nodes.ownerid,customers.lastname,customers.name,customers.notes
    FROM 
    (nodes LEFT JOIN customers ON customers.id = nodes.ownerid),
    cash    
    WHERE cash.customerid=nodes.ownerid AND nodes.warning=0
    GROUP BY nodes.ownerid HAVING SUM(cash.value)<(-59.22)
    ORDER BY SUM(cash.value)
    ');
    
//    echo '<pre>';print_r($warnings);echo '</pre>';
    return $warnings;
}                    

$warnings=GetUsersWithWarning();

$ilu=count($warnings);

/*
echo "<ol>";
for($i=0;$i<$ilu;$i++)
{

    echo "<li>".$warnings[$i][lastname]." ".$warnings[$i][name]." - ".$warnings[$i][suma]."</li>";
}
echo "</ol>";
*/


for($i=0;$i<$ilu;$i++)
{

    $suma=$suma+$warnings[$i][suma];
}

$SMARTY->assign('suma',$suma);


$SMARTY->assign('warnings',$warnings);
$SMARTY->display('alfa_no_warnings.html');
?>

