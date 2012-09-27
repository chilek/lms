<?php
/*
 * USTAWIANIE STATUSU CENTRALKI ASTERISK
 * 
 * STATUS:
 * 1 - standard
 * 2 - awaria
 * 3 - wolne
 * 4 - po pracy
*/

$data=getdate();
$d=$data['weekday'];
$h=$data['hours'];
$m=$data['minutes'];


/*
if($d=='Sunday')
    {
        $tmp=4;
        $suggest='WOLNE(3)';
    }
    elseif($d=='Saturday' && $h>=10 && $h<=14)
        {
            $tmp=1;
            $suggest='STANDARD(1)';
        }
        elseif($h>=8 && $h<=16)
            {
                $tmp=1;
	        $suggest='STANDARD(1)';                
            }
            else
                {
                    $tmp=4;
                    $suggest='PO PRACY(4)';
                }

*/

$typ=$LMS->DB->GetOne('select type from pbx limit 1');

/*
if($typ==1 && $tmp==4) {
    echo $DB->Execute('update pbx set type= ? where id= ?', array(4, 1));
    header('Location: http://lms.alfa-system.pl/?m=pbx');
}
*/

$status=$_GET[typ];
if(isset($status))
{
    echo $DB->Execute('update pbx set type= ? where id= ?',array($status,1));
    unset($_GET[typ]);
    header('http://lms.alfa-system.pl/?m=pbx&typ='.$status);
}

switch ($typ) {
    case 0:
        $txt="off";
    break;
    case 1:
        $txt="STANDARD";
    break;
    case 2:
        $txt="AWARIA";
    break;
    case 3:
        $txt="DZIEN WOLNY";
    break;
    case 4:
        $txt="PO PRACY";
    break;   
}

$SMARTY->assign('suggest', $suggest);
$SMARTY->assign('type', $txt);
$SMARTY->display('pbx.html');
?>

