<?php
function MonthlyIncome($year,$month)
{
    global $LMS,$SMARTY,$DB,$SESSION;
    $income=$DB->GetAll('
    SELECT SUM(value) AS suma, DAY(FROM_UNIXTIME(time)) as day
    FROM cash
    WHERE value>0 AND YEAR(FROM_UNIXTIME(time))='.$year.' AND MONTH(FROM_UNIXTIME(time))='.$month.'
    GROUP BY DAY(FROM_UNIXTIME(time))
    ');
    //echo '<pre>';print_r($income);echo '</pre>';
    return $income;
    
// per month    
//SELECT SUM(value) AS suma, MONTH(FROM_UNIXTIME(time)) as month
//FROM cash
//WHERE value>0 AND YEAR(FROM_UNIXTIME(time))=2011 
//GROUP BY month(FROM_UNIXTIME(time))    
}                    

function IncomePerMonth($only_year)
{
    global $LMS,$SMARTY,$DB,$SESSION;
    $income=$DB->GetAll('SELECT MONTH(FROM_UNIXTIME(time)) as month, SUM(value) AS suma FROM cash WHERE value>0 AND YEAR(FROM_UNIXTIME(time))='.$only_year.' GROUP BY month(FROM_UNIXTIME(time))');
#    echo '<pre>';print_r($income);echo '</pre>';
    return $income;
}

function GetUserID()
{
    global $SESSION;
    return $SESSION->_content[session_id];
}

$SMARTY->assign('user',GetUserID());

if($_GET['year']>0 AND $_GET['month']>0)
{
    $SMARTY->assign('income',MonthlyIncome($_GET['year'],$_GET['month']));
}
elseif($_GET['only_year']>2000)
{
    $SMARTY->assign('IncomePerMonth',IncomePerMonth($_GET['only_year']));
}else{
    $SMARTY->assign('income',0);
}

$SMARTY->display('alfa_income.html');
?>

