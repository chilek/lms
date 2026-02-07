<?php

if (!isset($_GET['id']) || !$_GET['is_sure'] || !ctype_digit($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');
elseif (! $LMSST->ProductExists($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Edit product');
$error = NULL;

if ($LMSST->ProductDel($_GET['id']))
	$SESSION->redirect('?m=stckproductlist');
else
	$SESSION->redirect('?m=stckproductinfo&id='.$_GET['id']);
?>
