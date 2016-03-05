<?php
/**
 * @author Maciej_Wawryk
 */
$id = intval($_GET['id']);
$schema = $DB->GetRow('SELECT * FROM promotionschemas WHERE id = ?', array($id));
if($schema) {
    $DB->Execute('INSERT INTO promotionschemas (name, description, data, promotionid, disabled, continuation, ctariffid)
        VALUES (?, ?, ?, ?, ?, ?, ?)', array(
            $schema['name'] . ' (kopia)', $schema['description'],
            $schema['data'], $schema['promotionid'], $schema['disabled'],
            $schema['continuation'], $schema['ctariffid']
    ));
    $schemaid = $DB->GetLastInsertID('promotionschemas');
    $DB->Execute('
        INSERT INTO promotionassignments (promotionschemaid, tariffid, data)
        SELECT ?, tariffid, data
        FROM promotionassignments WHERE promotionschemaid = ?;', array($schemaid, $schema['id']));
	$SESSION->redirect('?m=promotioninfo&id=' . $schema['promotionid']);
}

$SESSION->redirect('?m=promotionlist');

?>