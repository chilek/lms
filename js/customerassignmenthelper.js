// $Id$
const netFlagElem = $("#netflag");
const netPriceElem = $("#netprice");
const grossPriceElem = $("#grossprice");
const invoiceElem = $("#invoice");

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

		if ('tvTariffType' in options) {
			this.tvTariffType = options.tvTariffType;
		} else {
			this.tvTariffType = 0;
		}

		if ('tariffTypes' in options) {
			this.tariffTypes = options.tariffTypes;
		} else {
			this.tariffTypes = tariffTypes;
		}

		if ('variablePrefix' in options) {
			this.variablePrefix = options.variablePrefix;
		} else {
			this.variablePrefix = 'assignment';
		}

		if ('promotionAttachments' in options) {
			this.promotionAttachments = options.promotionAttachments;
		} else {
			this.promotionAttachments = {};
		}

		if ('assignmentPromotionAttachments' in options) {
			this.assignmentPromotionAttachments = options.assignmentPromotionAttachments;
		} else {
			this.assignmentPromotionAttachments = {};
		}

		if ('assignmentPromotionSchemaAttachments' in options) {
			this.assignmentPromotionSchemaAttachments = options.assignmentPromotionSchemaAttachments;
		} else {
			this.assignmentPromotionSchemaAttachments = {};
		}
	} else {
		this.customerid = 0;
		this.selected = {};
		this.internetTariffType = 0;
		this.phoneTariffType = 0;
		this.tvTariffType = 0;
		this.tariffTypes = {};
		this.promotionAttachments = {};
		this.assignmentPromotionAttachments = {};
		this.assignmentPromotionSchemaAttachments = {};
		this.variablePrefix = 'assignment';
    }

    if (!this.customerid) {
		console.log('WARNING! customerid is empty!');
	}

	this.initEventHandlers = function() {
		$('#submit-button,#print-button').click(function () {
			$('#a-day-of-month').prop('required', function() {
				return $(this).is(':visible');
			});

			if ($(this)[0].form.checkValidity()) {
				$('.schema-tariff-checkbox[data-mandatory]:checkbox').prop('disabled', false);
			}
		});

		$('#promotion-select').change(this.promotionSelectionHandler);
		$('#location-select').change(this.locationSelectionHandler);

		$('.schema-tariff-selection').change(this.tariffSelectionHandler);
		$('.schema-tariff-checkbox').change(this.tariffCheckboxHandler);

		$('#a_check_all_terminals').change(this.checkAllTerminalsHandler).trigger('change');

		$('[data-restore-selector]').click(function() {
			$($(this).attr('data-restore-selector')).val($(this).attr('data-restore-value'));
		});
	}

	this.validate = function(e) {
		var schemaid = $('#promotion-select').val();
		var tariffs = {};
		$('[name^="' + helper.variablePrefix + '[sassignmentid][' + schemaid + ']"]').each(function () {
			if ($(this).is('.schema-tariff-selection') || $(this).prop('checked')) {
				if ($(this).val() > 0) {
					tariffs[$(this).attr('data-label')] = $(this).val();
				}
			}
		});
		var location_select = $('#location-select');
		if (!location_select.val().length && $('option:not([value=""])', location_select).length > 1) {
			confirm($t('No location has been selected!'));
			return false;
		}
		if (lmsSettings.missedNodeWarning && $.isEmptyObject(tariffs)) {
			return confirm($t('No nodes has been selected for assignment, by at least one is recommended! Are you sure you want to continue despite of this?'));
		}
		var cancelled = 0;
		$.each(tariffs, function (label, tariffid) {
			if (cancelled) {
				return false;
			}
			var selector  = '[name^="' + helper.variablePrefix + '[snodes][' + schemaid + '][' + label + ']"]';
			if (lmsSettings.missedNodeWarning &&
				helper.tariffTypes[tariffid] == helper.internetTariffType &&
				(($('input' + selector).length && $('div#' + $(selector).closest('div').attr('id').replace('-layer', '') + ':visible').length &&
						!$(selector + ':checked').length) ||
					($('select' + selector).length && !$(selector).val().length)) &&
				!confirm($t('No nodes has been selected for assignment, by at least one is recommended! Are you sure you want to continue despite of this?'))) {
				cancelled = 1;
			}
		});
		if (cancelled) {
			e.stopImmediatePropagation();
			$('.schema-tariff-checkbox[data-mandatory]:checkbox').prop('disabled', true);
			return false;
		}
        return true;
	}

	this.checkAllTerminalsHandler = function() {
		var checkAllElem = $('#check_all_terminals');
		$('.customerdevices .lms-ui-multiselect-container:visible').each(function() {
			$(this).data('multiselect-object').toggleCheckAll(checkAllElem.prop('checked'));
		});
		$('body').on('lms:multiselect:checkall', '.customerdevices select', function(e, data) {
			checkAllElem.prop('checked', data.allChecked);
		});
	}

	this.promotionSelectionHandler = function() {
		var schemaId = parseInt($(this).val());

		$('#a_location,#a_check_all_terminals,#a_options,#a_existingassignments,#a_properties').toggle(schemaId != 0);
		$('#backward-period').toggle(!schemaId);

		$('.promotion-table').hide();

		$("#schema" + schemaId).show();

		var selected_option = $('option:selected', this);
		var schema_title = selected_option.attr('title');
		var promo_title = selected_option.closest('optgroup').attr('title');
		$('#promotion-schema-info').removeAttr('data-tooltip').attr('title', (!promo_title || !promo_title.length) && (!schema_title || !schema_title.length) ? '' :
			(promo_title && promo_title.length ? promo_title : '-') + '<hr>' + (schema_title && schema_title.length ? schema_title : '-'));

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		var allAttachments = 0;
		var allCheckedAttachments = 0;
		var html = '';

		if (helper.promotionAttachments.hasOwnProperty(schemaId) && !$.isEmptyObject(helper.promotionAttachments[schemaId].promotions)) {
			html += '<div class="promotion-attachments">' +
				'<strong>' + $t("from promotion") + '</strong>' +
				'<ul>';

			$.each(helper.promotionAttachments[schemaId].promotions, function (index, attachment) {
				allAttachments++;
				var checked = helper.assignmentPromotionAttachments.hasOwnProperty(attachment.id) &&
					helper.assignmentPromotionAttachments[attachment.id] == attachment.id ||
					!helper.assignmentPromotionAttachments.hasOwnProperty(attachment.id) &&
					attachment.checked;
				if (checked) {
					allCheckedAttachments++;
				}
				html +=
					'<li class="lms-ui-tab-table-row">' +
						'<label class="lms-ui-multi-check-ignore">' +
							'<input type="hidden" name="' + helper.variablePrefix + '[promotion-attachments][' + attachment.id + ']"' +
								' value="0">' +
							'<input type="checkbox" name="' + helper.variablePrefix + '[promotion-attachments][' + attachment.id + ']"' +
								' value="' + attachment.id + '" class="lms-ui-multi-check"' +
								(checked ? ' checked' : '') + '>' +
							'<span>' +
								escapeHtml(attachment.label.length ? attachment.label : attachment.filename) +
							'</span>' +
						'</label>' +
					'</li>';
			});

			html += '</ul></div>';
		}

		if (helper.promotionAttachments.hasOwnProperty(schemaId) && !$.isEmptyObject(helper.promotionAttachments[schemaId].promotionschemas)) {
			html += '<div class="promotion-attachments">' +
				'<strong>' + $t("from promotion schema") + '</strong>' +
				'<ul>';

			$.each(helper.promotionAttachments[schemaId].promotionschemas, function (index, attachment) {
				allAttachments++;
				var checked = helper.assignmentPromotionSchemaAttachments.hasOwnProperty(attachment.id) &&
					helper.assignmentPromotionSchemaAttachments[attachment.id] == attachment.id ||
					!helper.assignmentPromotionSchemaAttachments.hasOwnProperty(attachment.id) &&
					attachment.checked;
				if (checked) {
					allCheckedAttachments++;
				}
				html +=
					'<li class="lms-ui-tab-table-row">' +
						'<label class="lms-ui-multi-check-ignore">' +
							'<input type="hidden" name="' + helper.variablePrefix + '[promotion-schema-attachments][' + attachment.id + ']"' +
								' value="0">' +
							'<input type="checkbox" name="' + helper.variablePrefix + '[promotion-schema-attachments][' + attachment.id + ']"' +
								' value="' + attachment.id + '" class="lms-ui-multi-check"' +
								(checked ? ' checked' : '') + '>' +
							'<span>' +
								escapeHtml(attachment.label.length ? attachment.label : attachment.filename) +
							'</span>' +
						'</label>' +
					'</li>';
			});

			html += '</ul></div>';
		}

		if (html.length) {
			html += '<label class="lms-ui-dotted-line-top multi-check-all-label">' +
				'<input type="checkbox" class="lms-ui-multi-check-all"' +
				(allAttachments == allCheckedAttachments ? ' checked' : '') + '>' +
				$t("Check All") +
				'</label>';
		}

		$('#promotion-attachments').html(html);
		initMultiChecks('#promotion-attachments');
		$('#a_attachments').toggle($('#tariff-select').val() == -2 && typeof(promotionAttachments) != 'undefined' && html.length > 0);

		$('#location-select').trigger('change');
	}

	this.tariffSelectionHandler = function () {
		var selected_tariff = $(this).find(':selected');
		var assignment_id = selected_tariff.attr('data-assignment-id');
		var tariffAccess = parseInt(selected_tariff.attr('data-tariffaccess'));
		var tariffType = parseInt(selected_tariff.attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr.schema-tariff-container');
		var period_tables = tr.find('.single-assignment[data-assignment-id]');

		tr.find('.schema-tariff-count').toggle(selected_tariff.val() != 0);

		tr = tr.next('.customerdevices');

		tr.toggle(tariffAccess != -1);
		period_tables.hide();
		period_tables.filter('[data-assignment-id="' + assignment_id + '"]').show();

		tr.find('.nodes,.netdevnodes').toggle(tariffType == helper.internetTariffType || tariffType == helper.tvTariffType);
		tr.find('.phones').toggle(tariffType == helper.phoneTariffType);

		var selects = tr.find(tariffType == helper.phoneTariffType ? '.phones select' : (tariffType == helper.internetTariffType || tariffType == helper.tvTariffType ? '.nodes select,.netdevnodes select' : ''));
		if (!selects.length) {
			return;
		}

		init_multiselects(selects.filter(function() {
			return $(this).is('.lms-ui-multiselect-deferred:visible') && !$(this).closest('.lms-ui-multiselect-container').length;
		}));

		selects.each(function() {
			$(this).find('option').each(function() {
				var authtype = parseInt($(this).attr('data-tariffaccess'));
				var location = $(this).attr('data-location');
				$(this).toggle(
					((authtype && (authtype & tariffAccess)) || !tariffAccess) &&
					(location == location_select || !location_select.length)
				);
			});
			$(this).trigger('lms:multiselect:updated');
			$(this).trigger('lms:multiselect:toggle_check_all', { checked: $('#check_all_terminals').prop('checked') });
		});
	}

	this.tariffCheckboxHandler = function() {
		var checked = this.checked;
		var assignment_id = $(this).attr('data-assignment-id');
		var tariffAccess = parseInt($(this).attr('data-tariffaccess'));
		var tariffType = parseInt($(this).attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr.schema-tariff-container');
		var period_table = tr.find('.single-assignment[data-assignment-id="' + assignment_id + '"]');

		tr.find('.schema-tariff-count').toggle(checked);

		tr = tr.next('.customerdevices');

		tr.toggle(checked);
		period_table.toggle(checked);

		tr.find('.nodes,.netdevnodes').toggle(tariffType != helper.phoneTariffType);
		tr.find('.phones').toggle(tariffType == helper.phoneTariffType);

		var selects = tr.find(tariffType == helper.phoneTariffType ? '.phones select' : '.nodes select,.netdevnodes select');
		if (!selects.length) {
			return;
		}

		init_multiselects(selects.filter(function() {
			return $(this).is('.lms-ui-multiselect-deferred:visible') && !$(this).closest('.lms-ui-multiselect-container').length;
		}));

		selects.each(function() {
			var select = $(this);
			$(this).find('option').each(function() {
				if (checked) {
					var authtype = parseInt($(this).attr('data-tariffaccess'));
					var location = $(this).attr('data-location');
					$(this).toggle(
						((authtype && (authtype & tariffAccess)) || !tariffAccess || select.attr('name').indexOf('sphones') != -1) &&
						(location == location_select || !location_select.length)
					);
				} else {
					$(this).hide();
				}
			});
			$(this).trigger('lms:multiselect:updated');
			$(this).trigger('lms:multiselect:toggle_check_all', { checked: checked && $('#check_all_terminals').prop('checked') });
		});
	}

	this.locationSelectionHandler = function() {
		$('.schema-tariff-selection').trigger('change');
		$('.schema-tariff-checkbox').trigger('change');

		var location_select = $('#location-select');
		var location_address_id = $('#location-address-id');
		var data_address_id = $(location_select.get(0).options[location_select.get(0).selectedIndex]).attr('data-address-id');
		if (typeof(data_address_id) != 'undefined') {
			location_address_id.val(data_address_id);
		} else {
			location_address_id.val('');
		}
		var validationError = !location_select.val().length && $('option:not([value=""])', location_select).length > 1;
		var errorMessage = location_select.attr('title');
		location_select.toggleClass('lms-ui-error', validationError)
			.next().toggleClass('lms-ui-error', validationError)
			.attr('title', validationError ? errorMessage : null).removeAttr('data-tooltip');

		var schemaId = $('#promotion-select').val();
		var promotionTable = $('#schema' + schemaId);
		var location = location_select.val();
		promotionTable.find('.nodes select,.netdevnodes select').each(function() {
			var schemaTariffElement = $(this).closest('.customerdevices').siblings('.schema-tariff-container').find('.schema-tariff-checkbox,.schema-tariff-selection');
			var tariffAccess = parseInt(
				schemaTariffElement.is('.schema-tariff-checkbox') ?
					schemaTariffElement.attr('data-tariffaccess') :
					schemaTariffElement.find('option:selected').attr('data-tariffaccess')
			);
			$(this).find('option').each(function() {
				var authtype = parseInt($(this).attr('data-tariffaccess'));
				$(this).toggle(
					((authtype && (authtype & tariffAccess)) || !tariffAccess) &&
					(location == '' || location == $(this).attr('data-location'))
				);
			});
			$(this).trigger('lms:multiselect:updated');
		});
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
							location = node.teryt == '1' ? $t('$a (TERYT)', location) : location;
							var nodeid = String(node.id).lpad('0', 4);
							options += '<option value="' + node.id + '"' +
								(("snodes" in selected) && (schemaid in selected.snodes) && (label in selected.snodes[schemaid]) &&
								(selected.snodes[schemaid][label].indexOf(node.id) > -1) ? ' selected' : '') +
								' data-tariffaccess="' + node.authtype + '"' +
								' data-location="' + location + '"' +
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
							location = node.teryt == '1' ? $t('$a (TERYT)', location) : location;
							var nodeid = String(node.id).lpad('0', 4);
							options += '<option value="' + node.id + '"' +
								(("snodes" in selected) && (schemaid in selected.snodes) && (label in selected.snodes[schemaid]) &&
								(selected.snodes[schemaid][label].indexOf(node.id) > -1) ? ' selected' : '') +
								' data-tariffaccess="' + node.authtype + '"' +
								' data-location="' + location + '"' +
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
							'<span class="bold">' + $t('VoIP Accounts:') + '</span><br>';
						html += '<select name="' + helper.variablePrefix + '[sphones][' + schemaid + '][' +
							label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						options = '';
						$.each(data.voipaccounts, function(key, account) {
							var location = String(account.location);
							location = account.teryt == '1' ? $t('$a (TERYT)', location) : location;
							$.each(account.phones, function(key, phone) {
								options += '<option value="' + phone.id + '"' +
									(("sphones" in selected) && (schemaid in selected.sphones) && (label in selected.sphones[schemaid]) &&
									(selected.sphones[schemaid][label].indexOf(phone.id) > -1) ? ' selected' : '') +
									' data-location="' + location + '"' +
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

				var location_type_icons = [
					"lms-ui-icon-mail fa-fw",
					"lms-ui-icon-home fa-fw",
					"lms-ui-icon-customer-location fa-fw",
					"lms-ui-icon-default-customer-location fa-fw"
				];

				var location_count = 0;
				options = '<option value="">' + $t('— all —') + '</option>';
				if (data['with-end-points']) {
					options += '<optgroup label="' + $t("with end-points") + '">';
					$.each(data['with-end-points'], function(key, value) {
						var location = value.teryt == '1' ? $t('$a (TERYT)', value.location) : value.location;
						options += '<option value="' + location + '"' +
							' data-address-id="' + value.id + '"' +
							(("location" in selected) && selected.location == location ? ' selected' : '') +
							' data-icon="' + location_type_icons[value.location_type] + '">' +
							location + '</option>';
						location_count++;
					});
					options += '</optgroup>';
				}
				if (data['without-end-points']) {
					options += '<optgroup label="' + $t("without end-points") + '">';
					$.each(data['without-end-points'], function(key, value) {
						var location = value.teryt == '1' ? $t('$a (TERYT)', value.location) : value.location;
						options += '<option value="' + location + '"' +
							' data-address-id="' + value.id + '"' +
							(("location" in selected) && selected.location == location ? ' selected' : '') +
							' data-icon="' + location_type_icons[value.location_type] + '">' +
							location + '</option>';
						location_count++;
					});
					options += '</optgroup>';
				}

				if (data.hasOwnProperty('document-separation-groups')) {
					var values = [
						{
							value: "",
							text: ""
						}
					];
					$.each(data['document-separation-groups'], function (key, item) {
						values.push({
							value: escapeHtml(item),
							text: escapeHtml(item)
						});
					});
					$('#separatedocument').scombobox('fill', values);
					$('#separatedocument').scombobox('val', '');
				}

				$('#location-select').toggleClass('lms-ui-error', location_count > 1).html(options);
				initAdvancedSelects('#location-select');
				$('#location-select').chosen().change(function() {
					helper.locationSelectionHandler();
				});

				options = '<option value="-1">' + $t('none') + '</option>';
				if (data.addresses) {
					$.each(data.addresses, function(key, value) {
						options += '<option value="' + value.address_id + '"' +
							(("recipient_address_id" in selected) && selected.recipient_address_id == value.address_id ? ' selected' : '') + '>' +
							(value.location_name ? escapeHtml(value.location_name) + ', ' : '') + (value.location ? escapeHtml(value.location) : '') + '</option>';
					});
				}
				$('#recipient-select').html(options);

				$('#a_align_periods').show();

				$('#promotion-select').trigger('change');

				tariffSelectionHandler();

				init_multiselects('select.lms-ui-multiselect-deferred:visible');
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
	$('#tariff-price-variants').html('');

	$('#tarifftype').val(tarifftype);

	if (parseInt(tarifftype) > 0) {
		$('#assignment_type_limit').val(tarifftype);
		$('#a_assignment_type_limit').show();
	} else {
		$('#a_assignment_type_limit').hide();
	}

	$('#a_promotions,#a_align_periods').toggle(val == -2);

	$('#last-settlement').prop('disabled', $('#align-periods').prop('checked') && val == -2)
		.closest('label').toggleClass('lms-ui-disabled', $('#align-periods').prop('checked') && val == -2);

	$('#netflag, #tax, #taxcategory, #splitpayment').prop('disabled', false);
	$('#a_tax, #a_taxcategory, #a_splitpayment').removeClass('lms-ui-disabled');

	if (val == '') {
		$('#a_tax,#a_type,#a_price,#a_currency,#a_splitpayment,#a_taxcategory,#a_productid,#a_name').show();
		$('#a_price, #a_tax, #a_taxcategory, #a_splitpayment').removeClass('lms-ui-disabled');
		if (assignmentNetflag && parseInt(assignmentNetflag) !== 0) {
			$('#grossprice').val(assignmentGrossvalue).prop('disabled', true);
			$('#netprice').val(assignmentNetvalue).prop('disabled', false);
			$('#netflag').prop({checked: true, disabled: false});
			$('#invoice').prop('required', true);
			$('#invoice').find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', true);
		} else {
			$('#grossprice').val(assignmentGrossvalue).prop('disabled', false);
			$('#netprice').val(assignmentNetvalue).prop('disabled', true);
			$('#netflag').prop({checked: false, disabled: false});
			$('#invoice').prop('required', false);
			$('#invoice').find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', false);
		}

		if (assignmentTaxid) {
			$('#tax').val(assignmentTaxid).prop('disabled', false);
		} else {
			$('#tax').val(tariffDefaultTaxId).prop('disabled', false);
		}

		$('#a_attribute').hide();
	} else {
		let tariffGrossPrice = ((assignmentTariffId && assignmentTariffId == val && assignmentGrossvalue) ? assignmentGrossvalue : selected.attr('data-tariffvalue'));
		let tariffNetPrice = ((assignmentTariffId && assignmentTariffId == val && assignmentNetvalue) ? assignmentNetvalue : selected.attr('data-tariffnetvalue'));
		let tariffNetFlag = ((assignmentTariffId && assignmentTariffId == val && assignmentNetflag) ? assignmentNetflag : selected.attr('data-tariffnetflag'));
		let tariffTaxId = ((assignmentTariffId && assignmentTariffId == val && assignmentTaxid ) ? assignmentTaxid : selected.attr('data-tarifftaxid'));
		let tariffBaseNetPrice = selected.attr('data-tariffnetvalue');
		let tariffBaseGrossPrice = selected.attr('data-tariffvalue');
		let tariffPriceVariants = selected.attr('data-tariffpricevariants');

		$('#a_tax,#a_price').show();
		$('#a_type,#a_currency,#a_splitpayment,#a_taxcategory,#a_productid,#a_name').hide();
		if (tariffPriceVariants) {
			let tariffPriceVariantsObj = JSON.parse(tariffPriceVariants);
			if (Object.keys(tariffPriceVariantsObj).length > 0) {
				// draw info with tariff price variants
				drawPriceVariants(tariffPriceVariantsObj, $('#tariff-price-variants'));

				// get tariff price variants according to quantity
				let quantity = $("#quantity").val();
				let priceVariant = getPriceVariant(parseInt(quantity), tariffPriceVariantsObj);
				if (Object.keys(priceVariant).length > 0) {
					grossPriceElem.val(priceVariant.gross_price);
					netPriceElem.val(priceVariant.net_price);
				} else {
					grossPriceElem.val(tariffBaseGrossPrice);
					netPriceElem.val(tariffBaseNetPrice);
				}
			}
		}

		$('#a_price, #a_tax').addClass('lms-ui-disabled');

		if(parseInt(tariffNetFlag) === 1) {
			$('#netflag').prop('checked', true);
			$('#invoice').prop('required', true);
			if ($('#invoice').val() == assignment_settings.DOC_DNOTE) {
				$('#invoice').val('');
			}
			$('#invoice').find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', true);
		} else {
			$('#netflag').prop('checked', false);
			$('#invoice').prop('required', false).removeClass('lms-ui-error');
			$('#invoice').find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', false);
		}
		$('#netflag').prop('disabled', true);
		grossPriceElem.val(tariffGrossPrice).prop('disabled', true);
		netPriceElem.val(tariffNetPrice).prop('disabled', true);

		$('#tax').val(tariffTaxId).prop('disabled', true);

		if (val == -1) {
			$('#tax').val(tariffDefaultTaxId).prop('disabled', false);

			$('#a_tax,#a_type,#a_price,#a_currency,#a_splitpayment,#a_taxcategory,#a_productid,#a_name,#a_attribute').hide();
		} else if (val == -2){
			$('#tax').val(tariffDefaultTaxId).prop('disabled', false);

			$('#a_tax,#a_type,#a_price,#a_currency,#a_splitpayment,#a_taxcategory,#a_productid,#a_name').hide();
			$('#a_attribute').show();
		} else {
			$('#target_price').change();
			$("#discount_value").change();
			$('#a_attribute').show();
		}
	}

	if (val == -1) {
		$('#a_numberplan,#a_paytime,#a_paytype,#a_address,#a_day,#a_options,#a_existingassignments').hide();
		$('#a_properties').show();
	} else {
		$('#a_numberplan,#a_paytime,#a_paytype,#a_address,#a_day').show();
		$('#backward-period').toggle(val != -2 || !promotion_select);
		if ((val == -2 && promotion_select) || (val != -2)) {
			$('#a_options,#a_properties,#a_existingassignments').show();
		} else {
			$('#a_options,#a_properties,#a_existingassignments').hide();
		}
	}

	if (tarifftype == assignment_settings.phoneTariffType) {
		$('#a_phones,#a_nodes,#a_checkall').show();
		$('#a_netdevnodes').hide();
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
			$('.a_discount').hide();
		} else {
			$('.a_discount').show();
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

function claculatePriceFromGross() {
	let grossPriceElemVal = grossPriceElem.val();
	grossPriceElemVal = parseFloat(grossPriceElemVal.replace(/[\,]+/, '.'));

	if (!isNaN(grossPriceElemVal)) {
		let selectedTaxId = $("#tax").find('option:selected').val();
		let tax = $('#tax' + selectedTaxId).val();

		let grossPrice = financeDecimals.round(grossPriceElemVal, 3);
		let netPrice = financeDecimals.round(grossPrice / (tax / 100 + 1), 3);

		netPrice = netPrice.toFixed(3).replace(/[\.]+/, ',');
		netPriceElem.val(netPrice);

		grossPrice = grossPrice.toFixed(3).replace(/[\.]+/, ',');
		grossPriceElem.val(grossPrice);
	} else {
		netPriceElem.val('');
		grossPriceElem.val('');
	}
}

function claculatePriceFromNet() {
	let netPriceElemVal = netPriceElem.val();
	netPriceElemVal = parseFloat(netPriceElemVal.replace(/[\,]+/, '.'))

	if (!isNaN(netPriceElemVal)) {
		let selectedTaxId = $("#tax").find('option:selected').val();
		let tax = $('#tax' + selectedTaxId).val();

		let netPrice = financeDecimals.round(netPriceElemVal, 3);
		let grossPrice = financeDecimals.round(netPrice * (tax / 100 + 1), 3);

		grossPrice = grossPrice.toFixed(3).replace(/[\.]+/, ',');
		grossPriceElem.val(grossPrice);

		netPrice = netPrice.toFixed(3).replace(/[\.]+/, ',');
		netPriceElem.val(netPrice);
	} else {
		grossPriceElem.val('');
		netPriceElem.val('');
	}
}

$('#netflag').on('change', function () {
	if (netFlagElem.is(':checked')) {
		grossPriceElem.prop('disabled', true);
		netPriceElem.prop('disabled', false);
		claculatePriceFromNet();
		invoiceElem.prop('required', true);
		if (invoiceElem.val() === assignment_settings.DOC_DNOTE) {
			invoiceElem.val('');
		}
		invoiceElem.find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', true);
	} else {
		grossPriceElem.prop('disabled', false);
		netPriceElem.prop('disabled', true);
		claculatePriceFromGross();
		invoiceElem.prop('required', false).removeClass('lms-ui-error');
		invoiceElem.find('option[value="' + assignment_settings.DOC_DNOTE + '"]').prop('disabled', false);
	}
});

$("#tax").on('change', function () {
	if (netFlagElem.is(':checked')) {
		claculatePriceFromNet();
	} else {
		claculatePriceFromGross();
	}
});

$("#grossprice").change(function () {
	claculatePriceFromGross();
	$("#target_price").change();
});

$("#netprice").change(function () {
	claculatePriceFromNet();
	$("#target_price").change();
});

$(".format-3f").on('change', function () {
	if ($(this).val()) {
		let roundedValue = financeRound($(this).val().replaceAll(' ', ''), 3);
		$(this).val(roundedValue);
	}
});

$("#discount_value").change(function () {
	let discountType = parseInt($("#discount_type").val());
	let discountValue = parseFloat($(this).val().replaceAll(' ', '').replace(',', '.'));
	let netFlag = $('#netflag').prop('checked');
	let targetPriceElem = $("#target_price");
	let targetPrice;

	if (isNaN(discountValue)) {
		return;
	}

	let price;
	if (netFlag) {
		price = $('#netprice').val();
	} else {
		price = $('#grossprice').val();
	}
	price = parseFloat(price);

	switch (discountType) {
		case lmsSettings.discountPercentage:
			discountValue = parseFloat(financeRound(discountValue.toFixed(3), 3).replace(',', '.'));
			targetPrice = financeRound((price * (100 - discountValue) / 100).toFixed(3), 3);
			break;
		case lmsSettings.discountAmount:
			discountValue = parseFloat(financeRound(discountValue.toFixed(3), 3).replace(',', '.'));
			targetPrice = financeRound((price - discountValue).toFixed(3), 3);
			break;
	}
	$(this).val(discountValue);
	targetPriceElem.val(targetPrice);
});

$("#discount_type").change(function () {
	let discountType = parseInt($(this).val());
	let discountValueElem = $("#discount_value");
	let discountValue = parseFloat(discountValueElem.val().replaceAll(' ', '').replace(',', '.'));
	let netFlag = $('#netflag').prop('checked');
	let targetPriceElem = $("#target_price");
	let targetPrice;

	let price;
	if (netFlag) {
		price = $('#netprice').val();
	} else {
		price = $('#grossprice').val();
	}
	price = parseFloat(price);

	if (!isNaN(discountValue)) {
		switch (discountType) {
			case lmsSettings.discountPercentage:
				discountValue = parseFloat(financeRound(discountValue.toFixed(3), 3).replace(',', '.'));
				targetPrice = financeRound((price * (100 - discountValue) / 100).toFixed(3), 3);
				break;
			case lmsSettings.discountAmount:
				discountValue = parseFloat(financeRound(discountValue.toFixed(3), 3).replace(',', '.'));
				targetPrice = financeRound((price - discountValue).toFixed(3), 3);
				break;
		}
		discountValueElem.val(discountValue);
		targetPriceElem.val(targetPrice);
	}
});

$('#target_price_trigger').change(function() {
	var checked = $(this).prop('checked');
	$('#discount_value,#discount_label').toggle(!checked);
	$('#target_price,#target_price_label').toggle(checked);
});

$('#target_price').change(function() {
	let targetPrice = parseFloat($(this).val().replace(',', '.'));
	if (isNaN(targetPrice)) {
		$(this).val('');
	} else {
		let netFlag = $('#netflag').prop('checked');
		let discountValueElem = $('#discount_value');
		let discountValue = discountValueElem.val();
		let discountType = parseInt($("#discount_type").val());
		let price;
		if (netFlag) {
			price = $('#netprice').val();
		} else {
			price = $('#grossprice').val();
		}
		price = parseFloat(price);
		if (!isNaN(price)) {
			let targetDiscount;
			switch (discountType) {
				case lmsSettings.discountPercentage:
					targetDiscount = financeRound((((price - targetPrice) / price) * 100).toFixed(3), 3);
					break;
				case lmsSettings.discountAmount:
					targetDiscount = financeRound((price - targetPrice).toFixed(3), 3);
					break;
				default:
					targetDiscount = discountValue;
					break;
			}
			discountValueElem.val(targetDiscount);
		}
	}
});

function getPriceVariant(quantity, tariffPriceVariants) {
	let priceVariant = {};
	let upThreshold;
	$.each(tariffPriceVariants, function (idx, price_variant) {
		upThreshold = price_variant.quantity_threshold;
		if (quantity > upThreshold) {
			priceVariant = price_variant;
		} else {
			return false;
		}
	});

	return priceVariant;
}

function drawPriceVariants(tariffPriceVariants, elem) {
	let html = '<fieldset class="price-variants">' +
		'<legend><strong>' + $t('Price variants') + '</strong></legend>' +
			'<div class="lms-ui-box">' +
				'<div class="lms-ui-box-header">' +
					'<div class="lms-ui-box-row">' +
						'<div class="lms-ui-box-field">' +
							'<strong>' + $t('Gross price') + '</strong>' +
						'</div>' +
						'<div class="lms-ui-box-field">' +
							'<strong>' + $t('Net price') + '</strong>' +
						'</div>' +
						'<div class="lms-ui-box-field">' +
							'<strong>' + $t('Quantity threshold') + '</strong>' +
						'</div>' +
					'</div>' +
				'</div>' +
				'<div class="lms-ui-box-body lms-ui-background-cycle">';
	$.each(tariffPriceVariants, function ($idx, price_variant) {
		let gross_price = price_variant.gross_price;
		let net_price = price_variant.net_price;
		let currency = price_variant.currency;
		html += '<div class="lms-ui-box-row highlight">';
		html += '<div class="lms-ui-box-field"><strong>' + gross_price.replace(/[.]+/, ',') + ' ' + currency +'</strong></div>';
		html += '<div class="lms-ui-box-field">' + net_price.replace(/[.]+/, ',') +  ' ' + currency +'</div>';
		html += '<div class="lms-ui-box-field">' + price_variant.quantity_threshold +  ' ' + currency +'</div>';
		html += '</div>';
	});

	html+= '</filedset>';
	let tariffPriceVariantsTemplateElem = $('#tariff_price_variants_template');
	let dataHintElem = tariffPriceVariantsTemplateElem.contents().filter(function () {
		return this.nodeType == 1;
	});
	dataHintElem.attr('data-hint', html);
	elem.append(tariffPriceVariantsTemplateElem.html());
}

$("#quantity").on('change', function () {
	let tariff_select = $('#tariff-select');
	let selected = tariff_select.find(':selected');
	let tariffId = selected.val();
	if (tariffId > 0) {
		let tariffBaseNetPrice = selected.attr('data-tariffnetvalue');
		let tariffBaseGrossPrice = selected.attr('data-tariffvalue');
		let tariffPriceVariants = selected.attr('data-tariffpricevariants');
		let tariffPriceVariantsObj = JSON.parse(tariffPriceVariants);

		if (Object.keys(tariffPriceVariantsObj).length > 0) {
			let priceVariant = getPriceVariant(parseInt($(this).val()), tariffPriceVariantsObj);
			if (Object.keys(priceVariant).length > 0) {
				grossPriceElem.val(priceVariant.gross_price);
				netPriceElem.val(priceVariant.net_price);
			} else {
				grossPriceElem.val(tariffBaseGrossPrice);
				netPriceElem.val(tariffBaseNetPrice);
			}
		}
	}
});

$('#invoice').on('change', function () {
	var tariff_select_val = $('#tariff-select').val();
	if ($(this).val() == assignment_settings.DOC_DNOTE) {
		netFlagElem.prop({checked: false, disabled: true});
		$('#tax, #taxcategory, #splitpayment').prop('disabled', true);
		$('#a_tax, #a_taxcategory, #a_splitpayment').addClass('lms-ui-disabled');
	} else {
		netFlagElem.prop('checked', netFlagElem.is(':checked'));
		netFlagElem.prop('disabled', netFlagElem.is(':disabled'));
		$('#taxcategory, #splitpayment').prop('disabled', false);
		$('#a_taxcategory, #a_splitpayment').removeClass('lms-ui-disabled');
		if (tariff_select_val == '') {
			if (invoiceElem.val() == '') {
				netFlagElem.prop({checked: false, disabled: false});
			}
			$('#tax').prop('disabled', false);
			$('#a_tax').removeClass('lms-ui-disabled');
		} else {
			$('#tax').prop('disabled', $('#tax').is(':disabled'));
			$('#a_tax').toggleClass('lms-ui-disabled', $('#tax').is(':disabled'));
		}
	}
});

$('#align-periods').change(function() {
	$('#last-settlement').prop({
		"disabled": $(this).prop('checked'),
		"checked": $(this).prop('checked') ? false : $('#last-settlement').prop('checked')
	}).closest('label').toggleClass('lms-ui-disabled', $(this).prop('checked'));
}).change();
