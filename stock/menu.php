<?php
if (ConfigHelper::getConfig('phpui.stock')) {
	$menu['stock'] = array(
		'name' => trans('Warehouse'),
		'link' => '?m=stck',
		'css' => 'lms-ui-icon-warehouse',
		'tip' => trans('Stock management'),
		'prio' => 26,
		'submenu' => array(
			array(
				'name' => trans('New receive note'),
				'link' => '?m=stckreceiveadd',
				'prio' => 1
				),
			array(
				'name' => trans('List receive notes'),
				'link' => '?m=stckreceivenotelist',
				'prio' => 5
			),
			array(
				'name' => trans('Manufacturers'),
				'link' => '?m=stckmanufacturerlist',
				'prio' => 10
				),
			array (
				'name' => trans('Add manufacturer'),
				'link' => '?m=stckmanufactureradd',
				'prio' => 11
				),
			array(
				'name' => trans('Stock'),
				'link' => '?m=stckstock',
				'prio' => 20
				),
			array (
				'name' => trans('New product'),
				'link' => '?m=stckproductadd',
				'prio' => 21
				),
			array (
				'name' => trans('Groups'),
				'link' => '?m=stckgrouplist',
				'prio' => 30
				),
			array (
				'name' => trans('New Group'),
				'link' => '?m=stckgroupadd',
				'prio' => 31
				),
			array (
				'name' => trans('Product list'),
				'link' => '?m=stckproductlist',
				'prio' => 40
				),
			array (
				'name' => trans('Warehouses'),
				'link' => '?m=stckwarehouselist',
				'prio' => 80
				),
			array (
				'name' => trans('New warehouse'),
				'link' => '?m=stckwarehouseadd',
				'prio' => 81
				),
			array (
				'name' => trans('Reports'),
				'link' => '?m=printstock',
				'prio' => 99
				),
			),
		);

}
?>
