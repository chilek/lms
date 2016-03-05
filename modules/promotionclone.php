<?php
/**
 * @author Maciej_Wawryk
 */
$id = ($_GET['id']);
$DB->Execute('INSERT INTO promotions (name, description, disabled)
	SELECT ' . $DB->Concat('name',"' (kopia)'"). ', description, disabled
	FROM promotions WHERE id = ?;', array($id));
$newid = $DB->GetLastInsertID('promotions');
$schemas = $DB->GetAll('SELECT * FROM promotionschemas WHERE promotionid = ?', array($id));
if($schemas) foreach ($schemas as $schema) {
    $DB->Execute('INSERT INTO promotionschemas (name, description, data, promotionid, disabled, continuation, ctariffid) VALUES (?, ?, ?, ?, ?, ?, ?)
    ', array(
        $schema['name'], $schema['description'],
        $schema['data'], $newid, $schema['disabled'],
        $schema['continuation'], $schema['ctariffid']
    ));
    $schemaid = $DB->GetLastInsertID('promotionschemas');
    $DB->Execute('INSERT INTO promotionassignments (promotionschemaid, tariffid, data)
        SELECT ?, tariffid, data
        FROM promotionassignments WHERE promotionschemaid = ?;', array($schemaid, $schema['id']));
}

$SESSION->redirect('?m=promotionlist');

?>