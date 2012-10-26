<?php

function newCustomers($year)
{
    global $LMS,$SMARTY,$DB,$SESSION;
    $income=$DB->GetAll('
    SELECT COUNT(id) AS suma, MONTH(FROM_UNIXTIME(creationdate)) as month
    FROM customers
    WHERE deleted=0 AND YEAR(FROM_UNIXTIME(creationdate))='.$year.' 
    GROUP BY MONTH(FROM_UNIXTIME(creationdate))
    ');
//    echo '<pre>';print_r($income);echo '</pre>';
    return $income;
}                    

function deletedCustomers($year)
{
    global $LMS,$SMARTY,$DB,$SESSION;
    $income=$DB->GetAll('
    SELECT COUNT(id) AS suma, MONTH(FROM_UNIXTIME(moddate)) as month
    FROM customers
    WHERE deleted=1 AND YEAR(FROM_UNIXTIME(moddate))='.$year.' 
    GROUP BY MONTH(FROM_UNIXTIME(moddate))
    ');
//    echo '<pre>';print_r($income);echo '</pre>';
    return $income;
}                    


function GetUserID()
{
    global $SESSION;
    return $SESSION->_content[session_id];
}

$SMARTY->assign('user',GetUserID());

if($_GET['year']>0)
{
    $SMARTY->assign('income',newCustomers($_GET['year']));
    $SMARTY->assign('deleted',deletedCustomers($_GET['year']));
}else{
    $SMARTY->assign('income',0);
}

$SMARTY->display('alfa_newclients.html');
?>

