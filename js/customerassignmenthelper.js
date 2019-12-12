// $Id$

function CustomerAssignmentHelper(options) {
	var helper = this;

	if (typeof options === 'object') {
		if ('customerid' in options) {
			this.customerid = options.customerid;
	    } else {
			this.customerid = 0;
		}

		if ('selected' in options) {
			this.selected = options.selected;
		} else {
			this.selected = {};
		}

		if ('internetTariffType' in options) {
			this.internetTariffType = options.internetTariffType;
		} else {
			this.internetTariffType = 0;
		}

		if ('phoneTariffType' in options) {
			this.phoneTariffType = options.phoneTariffType;
		} else {
			this.phoneTariffType = 0;
		}

		if ('tariffTypes' in options) {
			this.tariffTypes = options.tariffTypes;
		} else {
			this.tariffTypes = tariffTypes;
		}

		if ('variablePrefix' in options) {
			this.variablePrefix = options.variablePrefix;
		} else {
			this.variablePrefix = tariffTypes;
		}
    } else {
		this.customerid = 0;
		this.selected = {};
		this.internetTariffType = 0;
		this.phoneTariffType = 0;
		this.tariffTypes = {};
		this.variablePrefix = 'assignment';
    }

    if (!this.customerid) {
		console.log('WARNING! customerid is empty!');
	}

	this.initEventHandlers = function() {
		$('#submit-button').click(function () {
			$('.schema-tariff-checkbox[data-mandatory]:checkbox').removeAttr('disabled');
		});

		$('#promotion-select').change(this.promotionSelectionHandler);
		$('#location-select').change(this.locationSelectionHandler);

		$('.schema-tariff-selection').change(this.tariffSelectionHandler);
		$('.schema-tariff-checkbox').change(this.tariffCheckboxHandler);

		$('[data-restore-selector]').click(function() {
			$($(this).attr('data-restore-selector')).val($(this).attr('data-restore-value'));
		});
	}

	this.validate = function(e) {
		var schemaid = $('#promotion-select').val();
		var tariffs = {};
		$('[name^="' + helper.variablePrefix + '[stariffid][' + schemaid + ']"]').each(function () {
			if ($(this).is('.schema-tariff-selection') || $(this).prop('checked')) {
				if ($(this).val() > 0) {
					tariffs[$(this).attr('data-label')] = $(this).val();
				}
			}
		});
		if ($.isEmptyObject(tariffs)) {
			return confirm($t('No nodes has been selected for assignment, by at least one is recommended! Are you sure you want to continue despite of this?'));
		}
		var cancelled = 0;
		$.each(tariffs, function (label, tariffid) {
			if (cancelled) {
				return false;
			}
			var selector  = '[name^="' + helper.variablePrefix + '[snodes][' + schemaid + '][' + label + ']"]';
			if (helper.tariffTypes[tariffid] == helper.internetTariffType &&
				(($('input' + selector).length && $('div#' + $(selector).closest('div').attr('id').replace('-layer', '') + ':visible').length &&
						!$(selector + ':checked').length) ||
					($('select' + selector).length && !$(selector).val().length)) &&
				!confirm($t('No nodes has been selected for assignment, by at least one is recommended! Are you sure you want to continue despite of this?'))) {
				cancelled = 1;
			}
		});
		if (cancelled) {
			e.stopImmediatePropagation();
			$('.schema-tariff-checkbox[data-mandatory]:checkbox').attr('disabled', true);
			return false;
		}
        return true;
	}

	this.promotionSelectionHandler = function() {
		if (parseInt($(this).val())) {
			$('#a_location,#a_options,#a_existingassignments,#a_properties').show();
		} else {
			$('#a_location,#a_options,#a_existingassignments,#a_properties').hide();
		}

		$('.promotion-table').hide();

		$("#schema" + $(this).val()).show();

		var selected_option = $('option:selected', this);
		var schema_title = selected_option.attr('title');
		var promo_title = selected_option.closest('optgroup').attr('title');
		$('#promotion-schema-info').removeAttr('data-tooltip').attr('title', (!promo_title || !promo_title.length) && (!schema_title || !schema_title.length) ? '' :
			(promo_title && promo_title.length ? promo_title : '-') + '<hr>' + (schema_title && schema_title.length ? schema_title : '-'));

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		$('#location-select').trigger('change');
	}

	this.tariffSelectionHandler = function () {
		var selected_tariff = $(this).find(':selected');
		var assignment_id = selected_tariff.attr('data-assignment-id');
		var tariffaccess = parseInt(selected_tariff.attr('data-tariffaccess'));
		var tarifftype = parseInt(selected_tariff.attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr.schema-tariff-container');
		var period_tables = tr.find('.single-assignment[data-assignment-id]');
		tr = tr.next('.customerdevices');

		tr.toggle(tariffaccess != -1);
		period_tables.hide();
		period_tables.filter('[data-assignment-id="' + assignment_id + '"]').show();

		switch (tarifftype) {
			case helper.internetTariffType:
				tr.find('div.nodes,div.netdevnodes').show();
				tr.find('div.phones').hide();
				break;
			case helper.phoneTariffType:
				tr.find('div.nodes,div.netdevnodes').hide();
				tr.find('div.phones').show();
				break;
			default:
				tr.find('div.nodes,div.netdevnodes,div.phones').hide();
		}

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		var ms = [];
		if (tarifftype == helper.phoneTariffType) {
			ms.push(tr.find('div.phones .lms-ui-multiselect'));
		} else {
			ms.push(tr.find('div.nodes .lms-ui-multiselect'));
			ms.push(tr.find('div.netdevnodes .lms-ui-multiselect'));
		}
        if (!ms.length) {
			return;
		}
        $.each(ms, function(index, select) {
			if (!select) {
				return;
			}
        	var ms = select.data('multiselect-object');
        	if (!ms) {
        		return;
        	}
			ms.getOptions().each(function (key) {
				var authtype = parseInt($(this).attr('data-tariffaccess'));
				var location = $(this).attr('data-location');
				if (((authtype && (authtype & tariffaccess)) || !tariffaccess) &&
					(location == location_select || !location_select.length)) {
					ms.showOption(key);
				} else {
					ms.hideOption(key);
				}
			});
			ms.refreshSelection();
		});
	}

	this.tariffCheckboxHandler = function() {
		var checked = this.checked;
		var assignment_id = $(this).attr('data-assignment-id');
		var tariffaccess = parseInt($(this).attr('data-tariffaccess'));
		var tarifftype = parseInt($(this).attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr.schema-tariff-container');
		var period_table = tr.find('.single-assignment[data-assignment-id="' + assignment_id + '"]');
		tr = tr.next('.customerdevices');

		tr.toggle(checked);
		period_table.toggle(checked);

		switch (tarifftype) {
			case helper.phoneTariffType:
				tr.find('div.nodes,div.netdevnodes').hide();
				tr.find('div.phones').show();
				break;
			default:
				tr.find('div.nodes,div.netdevnodes').show();
				tr.find('div.phones').hide();
		}

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		var ms = [];
		if (tarifftype == helper.phoneTariffType) {
			ms.push(tr.find('div.phones .lms-ui-multiselect'));
		} else {
			ms.push(tr.find('div.nodes .lms-ui-multiselect'));
			ms.push(tr.find('div.netdevnodes .lms-ui-multiselect'));
		}
        if (!ms.length) {
			return;
		}
        $.each(ms, function(index, select) {
			if (!select) {
				return;
			}
        	var ms = select.data('multiselect-object');
        	if (!ms) {
        		return;
        	}
			ms.getOptions().each(function (key) {
				if (checked) {
					var authtype = parseInt($(this).attr('data-tariffaccess'));
					var location = $(this).attr('data-location');
					if (((authtype && (authtype & tariffaccess)) || !tariffaccess) &&
						(location == location_select || !location_select.length)) {
						ms.showOption(key);
					} else {
						ms.hideOption(key);
					}
				} else {
					ms.hideOption(key);
				}
			});
		});
	}

    this.locationSelectionHandler = function() {
		$('.schema-tariff-selection').trigger('change');
		$('.schema-tariff-checkbox').trigger('change');
	}

	this.updateDevices = function() {
		if (typeof this.customerid === 'undefined') {
			return;
		}
		var customerid = parseInt(this.customerid);
		if (isNaN(customerid)) {
			return;
		}

		if (typeof this.selected === 'undefined') {
			selected = {};
		} else {
			selected = this.selected;
		}

		$.ajax('?m=customerassignmenthelper&api=1&customerid=' + customerid, {
			async: true,
			method: 'POST',
			dataType: 'json',
			success: function(data) {
				String.prototype.lpad = function(padString, length) {
					var str = this;
					while (str.length < length)
						str = padString + str;
					return str;
				}

				var customerdevices = $('.customerdevices');
				$('td', customerdevices).remove();

				customerdevices.each(function() {
					var schemaid = $(this).attr('data-schemaid');
					var label = $(this).attr('data-label');
					var td = $('<td/>');
					var html = '';
					var options;

					if (data.nodes) {
						html += '<div class="nodes"><img src="img/node.gif"> ' +
							'<span class="bold">' + $t('Nodes:') + '</span><br>';
						html += '<select name="' + helper.variablePrefix + '[snodes][' + schemaid + '][' +
							label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						options = '';
						$.each(data.nodes, function(key, node) {
							var location = String(node.location);
							if (location.length > 50) {
								location = location.substr(0, 50) + '...';
							}
							var nodeid = String(node.id).lpad('0', 4);
							options += '<option value="' + node.id + '"' +
								(("snodes" in selected) && (schemaid in selected.snodes) && (label in selected.snodes[schemaid]) &&
								(selected.snodes[schemaid][label].indexOf(node.id) > -1) ? ' selected' : '') +
								' data-tariffaccess="' + node.authtype + '"' +
								' data-location="' + node.location + '"' +
								' data-html-content="<strong>' + node.name + '</strong>' +
								' (' + nodeid + ')' + (location.length ? ' / ' + location : '') + '"';
							options += '>';
							options += node.name + ' (' + nodeid + ')' +
								(location.length ? ' / ' + location : '');
							options += '</option>';
						});

						html += options;
						html += '</select></div>';
					}

					if (data.netdevnodes) {
						html += '<div class="netdevnodes"><img src="img/netdev.gif"> ' +
							'<span class="bold">' + $t('Network Devices:') + '</span><br>';
						html += '<select name="' + helper.variablePrefix + '[snodes][' + schemaid + '][' +
							label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						options = '';
						$.each(data.netdevnodes, function(key, node) {
							var location = String(node.location);
							if (location.length > 50) {
								location = location.substr(0, 50) + '...';
							}
							var nodeid = String(node.id).lpad('0', 4);
							options += '<option value="' + node.id + '"' +
								(("snodes" in selected) && (schemaid in selected.snodes) && (label in selected.snodes[schemaid]) &&
								(selected.snodes[schemaid][label].indexOf(node.id) > -1) ? ' selected' : '') +
								' data-tariffaccess="' + node.authtype + '"' +
								' data-location="' + node.location + '"' +
								' data-html-content="<strong>' + node.name + '</strong>' +
								' (' + nodeid + ')' + ' / ' + node.netdev_name + (location.length ? ' / ' + location : '') + '"';
							options += '>';
							options += node.name + ' (' + nodeid + ')' +
								(location.length ? ' / ' + location : '');
							options += '</option>';
						});

						html += options;
						html += '</select></div>';
					}

					if (data.voipaccounts) {
						html += '<div class="phones"><img src="img/voip.gif"> ' +
							'<span class="bold">' + $t('Voip Accounts:') + '</span><br>';
						html += '<select name="' + helper.variablePrefix + '[sphones][' + schemaid + '][' +
							label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						options = '';
						$.each(data.voipaccounts, function(key, account) {
							var location = String(account.location);
							if (location.length > 50) {
								location = location.substr(0, 50) + '...';
							}
							$.each(account.phones, function(key, phone) {
								options += '<option value="' + phone.id + '"' +
									(("sphones" in selected) && (schemaid in selected.sphones) && (label in selected.sphones[schemaid]) &&
									(selected.sphones[schemaid][label].indexOf(phone.id) > -1) ? ' selected' : '') +
									' data-location="' + account.location + '"' +
									' data-html-content="<strong>' + phone.phone + '</strong>' +
									' / ' + account.login + (location.length ? ' / ' + location : '') + '"';
								options += '>';
								options += phone.phone + ' / ' + account.login + (location.length ? ' / ' + location : '');
								options += '</option>';
							});
						});

						html += options;
						html += '</select></div>';
					}

					td.html(html).appendTo(this);
				});

				options = '<option value="">' + $t('- all -') + '</option>';
				if (data.locations) {
					options += '<optgroup label="' + $t("with end-points") + '">';
					$.each(data.locations, function(key, value) {
						options += '<option value="' + value + '"' +
							(("location" in selected) && selected.location == value ? ' selected' : '') + '>' +
							value + '</option>';
					});
					options += '</optgroup>';
				}
				if (data['without-end-points']) {
					options += '<optgroup label="' + $t("without end-points") + '">';
					$.each(data['without-end-points'], function(key, value) {
						options += '<option value="' + value.location + '"' +
							(("location" in selected) && selected.location == value.location ? ' selected' : '') + '>' +
							value.location + '</option>';
					});
					options += '</optgroup>';
				}

				$('#location-select').html(options);

				options = '<option value="-1">' + $t('none') + '</option>';
				if (data.addresses) {
					$.each(data.addresses, function(key, value) {
						options += '<option value="' + value.address_id + '"' +
							(("recipient_address_id" in selected) && selected.recipient_address_id == value.address_id ? ' selected' : '') + '>' +
							(value.location_name ? value.location_name + ', ' : '') + value.location + '</option>';
					});
				}
				$('#recipient-select').html(options);

				$('#a_promotions').show();

                init_multiselects('select.lms-ui-multiselect-deferred:visible');

				$('#promotion-select').trigger('change');
				tariffSelectionHandler();
            }
		});
    }

	this.setCustomer = function(customerid) {
		this.customerid = customerid;
		this.selected = {};
		this.updateDevices();
	}

	this.updateDevices();
	this.initEventHandlers();
}


function checkAllNodes() {
	$('[name^="assignment[nodes]"]:visible').prop('checked', $('[name="allbox"]').prop('checked'));
	$('[name^="assignment[phones]"]:visible').prop('checked', $('[name="allbox"]').prop('checked'));
}

function updateCheckAllNodes() {
	$('[name="allbox"]').prop('checked',
		$('[name^="assignment[nodes]"]:visible,[name^="assignment[phones]"]:visible').length ==
			$('[name^="assignment[nodes]"]:visible:checked,[name^="assignment[phones]"]:visible:checked').length);
}

$('[name^="assignment[nodes]"],[name^="assignment[phones]"]').click(function() {
	updateCheckAllNodes();
});

$('#last-day-of-month').click(function() {
	var checked = $(this).prop('checked');
	$('#a-day-of-month').toggle(!checked).prop('disabled', checked);
});

$('#assignment-period').change(function() {
	$('#last-day-of-month').closest('label').toggle($(this).val() == lmsSettings.monthlyPeriod);
});

function tariffSelectionHandler() {
	var promotion_select = parseInt($('#promotion-select').val());
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

	if (parseInt(tarifftype) > 0) {
		$('#assignment_type_limit').val(tarifftype);
		$('#a_assignment_type_limit').show();
	} else {
		$('#a_assignment_type_limit').hide();
	}

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
		$('#a_numberplan,#a_paytype,#a_address,#a_day,#a_options,#a_existingassignments').hide();
		$('#a_properties').show();
	} else {
		$('#a_numberplan,#a_paytype,#a_address,#a_day').show();
		if ((val == -2 && promotion_select) || (val != -2)) {
			$('#a_options,#a_properties,#a_existingassignments').show();
		} else {
			$('#a_options,#a_properties,#a_existingassignments').hide();
		}
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

	$('#a_count').toggle(val >= 0);

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
