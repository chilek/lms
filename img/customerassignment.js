// $Id$

function checkAllNodes() {
	$('[name^="assignment[nodes]"]:visible').prop('checked', $('[name="allbox"]').prop('checked'));
	$('[name^="assignment[phones]"]:visible').prop('checked', $('[name="allbox"]').prop('checked'));
}

function updateCheckAllNodes() {
	$('[name="allbox"]').prop('checked',
		$('[name^="assignment[nodes]"]:visible,[name^="assignment[phones]"]:visible').length
		== $('[name^="assignment[nodes]"]:visible:checked,[name^="assignment[phones]"]:visible:checked').length);
}

$('[name^="assignment[nodes]"],[name^="assignment[phones]"]').click(function() {
	updateCheckAllNodes();
});

function tariffSelectionHandler() {
	var tariff_select = $('#tariff-select');
	var selected = tariff_select.find(':selected');
	var tarifftype = selected.attr('data-tarifftype');
	var tariffaccess = selected.attr('data-tariffaccess');
	if (typeof(tariffaccess) == 'undefined') {
		tariffaccess = 0;
	} else {
		tariffaccess = parseInt(tariffaccess);
	}
	var val = tariff_select.val();

	$('#tarifftype').val(tarifftype);

	if (val == -2) {
		$('#a_promotions').show();
	} else {
		$('#a_promotions').hide();
	}

	if (val == '') {
		$('#a_tax,#a_value,#a_productid,#a_name').show();
		$('#a_attribute').hide();
	} else {
		$('#a_tax,#a_value,#a_productid,#a_name').hide();
		if (val == -1) {
			$('#a_attribute').hide();
		} else {
			$('#a_attribute').show();
		}
	}

	if (val == -1) {
		$('#a_numberplan,#a_paytype,#a_address,#a_options,#a_day').hide();
	} else {
		$('#a_numberplan,#a_paytype,#a_address,#a_options,#a_day').show();
	}

	if (tarifftype == assignment_settings.phoneTariffType) {
		$('#a_phones,#a_checkall').show();
		$('#a_nodes,#a_netdevnodes').hide();
	} else {
		$('#a_phones').hide();
		if (val == -1 || val == -2) {
			$('#a_nodes,#a_netdevnodes,#a_checkall').hide();
		} else {
			$('#a_nodes,#a_netdevnodes,#a_checkall').show();
		}
	}

	if (!assignment_settings.hideFinances) {
		if (val <= -1) {
			$('#a_discount').hide();
		} else {
			$('#a_discount').show();
		}
	}

	$('span.global-node-checkbox').each(function(key, value) {
		var authtype = parseInt($(this).attr('data-tariffaccess'));
		if ((authtype && (authtype & tariffaccess)) || !tariffaccess) {
			$(this).show();
		} else {
			$(this).hide();
			$(':checkbox', this).prop('checked', false);
		}
	});

	updateCheckAllNodes();
}

$('#tariff-select').change(tariffSelectionHandler);
