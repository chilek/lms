<?

$p = $_GET[p];
$netid = $_POST[netid];

if(!isset($p))
	$js = "var targetfield = window.opener.targetfield;";
if($p == "main")
	$js = "var targetfield = parent.targetfield;";

$layout[pagetitle] = $lang[pagetitle_choosemac];

$SMARTY->assign("layout",$layout);
$SMARTY->assign("part",$p);
$SMARTY->assign("js",$js);
if($p == "main")
	$SMARTY->assign("maclist",$LMS->GetMACs());
$SMARTY->display("choosemac.html");

?>
