<?php

$p = $_GET[p];
$netid = $_POST[netid];

if(!isset($p))
	$js = "var targetfield = window.opener.targetfield;";
if($p == "main")
	$js = "var targetfield = parent.targetfield;";

$layout[pagetitle] = "Wybierz adres MAC";

$SMARTY->assign("layout",$layout);
$SMARTY->assign("part",$p);
$SMARTY->assign("js",$js);
if($p == "main")
	$SMARTY->assign("maclist",$LMS->GetMACs());
$SMARTY->display("choosemac.html");

/*
 * $Log$
 * Revision 1.8  2003/08/24 13:12:54  lukasz
 * - massive attack: s/<?/<?php/g - that was causing problems on some fucked
 *   redhat's :>
 *
 * Revision 1.7  2003/08/18 16:52:19  lukasz
 * - added CVS Log tags
 *
 */
?>