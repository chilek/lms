<?php
$access_table_stck['lms-stck-full'] = array(
	'label' => trans('full access to lms-stck module'),
	'allow_regexp' => '^(stck)[a-z]+$'
	);

$access_table_stck['lms-stck-reports'] = array(
	'label' => trans('lms-stck reports'),
	'allow_regexp' => '^printstock$'
	);

$access_table_stck['lms-stck-xml'] = array(
	'label' => trans('lms-stck XML stock wholesale reports'),
	'allow_regexp' => '^stckxml$'
	);

$access = AccessRights::getInstance();
foreach ($access_table_stck as $name => $permission) {
    $access->appendPermission(new Permission(
        $name,
        $permission['label'],
        $permission['allow_regexp'] ?? null,
        $permission['deny_regexp'] ?? null,
        $permission['allow_menu_items'] ?? null,
        $permission['deny_menu_items'] ?? null
    ));
}


/*$access['table'][100]['name']      = 'magazyn';
$access['table'][100]['allow_reg'] = '^(stck)[a-z]+$';

$access['table'][101]['name']           = 'raporty magazynowe';
$access['table'][101]['allow_reg']      = '^printstock$';
*/?>
