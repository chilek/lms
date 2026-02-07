<?php
$stock = NULL;
if (isset($_POST['stock'])) {
	$stock = $_POST['stock'];
	foreach($stock as $k => $v) {
		if (!ctype_digit($v) && $v != "") {
			unset($stock);
			break;
		}
		if ($v == "") {
			$stock[$k] = NULL;
		}
	}
} 
$stock['manufacturer'] = '331';
if (!$stock['warehouse'])
	$stock['warehouse'] = $LMSST->WarehouseGetDefaultId();

$productlist = $LMSST->StockList($o, $stock['manufacturer'], $stock['group'], $stock['warehouse'], NULL, 1);
unset($productlist['total']);
unset($productlist['direction']);
unset($productlist['order']);
unset($productlist['totalpcs']);
unset($productlist['totalvg']);
unset($productlist['totalvn']);

$xml_stck = new DomDocument('1.0', 'utf-8');
$xml_stck->preserveWhiteSpace = false;
$xml_stck->formatOutput = true;

$xslt = $xml_stck->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="/stock/stock.xsl"');
$xml_stck->appendChild($xslt);

$xml_root = $xml_stck->createElement('stockInfo');

$xml_stck->appendChild($xml_root);

foreach ($productlist as $k => $v) {
	unset($v['valuenet'], $v['valuegross'], $v['type']);
	$xml_prod = $xml_stck->createElement('product');
	$xml_prod_id = $xml_stck->createAttribute('id');
	$xml_prod_id->value = $v['id'];
	$xml_prod->appendChild($xml_prod_id);
	foreach ($v as $k2 => $v2) {
		$xml_prod_p = $xml_stck->createElement($k2, $v2);
		$xml_prod->appendChild($xml_prod_p);
	}
	$xml_root->appendChild($xml_prod);
}

header("Content-type: text/xml");
echo $xml_stck->saveXML();

/*$warehouselist = $LMSST->WarehouseGetList($o);
unset($warehouselist['total']);
unset($warehouselist['direction']);
unset($warehouselist['order']);

$manufacturerlist = $LMSST->ManufacturerGetList($o);
unset($manufacturerlist['total']);
unset($manufacturerlist['direction']);
unset($manufacturerlist['order']);

$grouplist = $LMSST->GroupGetList($o);
$params['sql4'] = sprintf('%.4f', microtime(true) - START_TIME);
unset($grouplist['total']);
unset($grouplist['direction']);
unset($grouplist['order']);
*/

//print_r($productlist);
?>
