<?php

if(isset($_POST['tarifftagadd'])){
    $tarifftagadd = $_POST['tarifftagadd'];

    foreach($tarifftagadd as $key => $value)
	    $tarifftagadd[$key] = trim($value);

    if($tarifftagadd['name']=='' && $tarifftagadd['description']==''){
	    $SESSION->redirect('?m=tarifftaglist');
    }

    if($tarifftagadd['name'] == ''){
	$error['name'] = trans('Tag name required!');
    }
    elseif(strlen($tarifftagadd['tag']) > 255){
	$error['name'] = trans('Tag name is too long!');
    }
    elseif(!preg_match('/^[._a-z0-9-]+$/i', $tarifftagadd['name'])){
	$error['name'] = trans('Invalid chars in tag name!');
    }
    elseif($LMS->TarifftagGetId($tarifftagadd['tag'])){
	$error['name'] = trans('Tag with name $a already exists!',$tarifftagadd['name']);
    }

    if(!$error){
	$SESSION->redirect('?m=tarifftaglist&id='.$LMS->TarifftagAdd($tarifftagadd));
    }

    $SMARTY->assign('error',$error);
    $SMARTY->assign('tarifftagadd',$tarifftagadd);
}

$layout['pagetitle'] = trans('New Tag');
$SMARTY->display('tariff/tarifftagadd.html');

