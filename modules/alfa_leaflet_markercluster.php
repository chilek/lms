<?php

$node_cords=$DB->GetAll('SELECT  `name` ,  `longitude` ,  `latitude` 
            FROM  `nodes` 
            WHERE longitude IS NOT NULL 
                    AND latitude IS NOT NULL');

$str='';
foreach($node_cords as $node)
{
    $str.='['.$node['latitude'].', '.$node['longitude'].', "'.$node['name'].'"],';
}
$node_cords=substr($str, 0, -1);


//print_r($nodeCords);

$SMARTY->assign('node_cords',$node_cords);

$SMARTY->display('alfa_leaflet_markercluster.html');
?>

