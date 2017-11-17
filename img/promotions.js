// $Id$

function Promotions(options) {
	var promotion = this;

	if (typeof options === 'object') {
		if ('customerid' in options) {
			this.customerid = options["customerid"];
	    } else {
			this.customerid = 0;
		}

		if ('selected' in options) {
			this.selected = options["selected"];
		} else {
			this.selected = {};
		}

		if ('internetTariffType' in options) {
			this.internetTariffType = options["internetTariffType"];
		} else {
			this.internetTariffType = 0;
		}

		if ('phoneTariffType' in options) {
			this.phoneTariffType = options["phoneTariffType"];
		} else {
			this.phoneTariffType = 0;
		}

		if ('tariffTypes' in options) {
			this.tariffTypes = options["tariffTypes"];
		} else {
			this.tariffTypes = tariffTypes;
		}

		if ('variablePrefix' in options) {
			this.variablePrefix = options["variablePrefix"];
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
		$('#assignment-submit').click(function () {
			$('.schema-tariff-checkbox[data-mandatory]:checkbox').removeAttr('disabled');
		});

		$('#promotion-select').change(this.promotionSelectionHandler);
		$('#location-select').change(this.locationSelectionHandler);

		$('.schema-tariff-selection').change(this.tariffSelectionHandler);
		$('.schema-tariff-checkbox').change(this.tariffCheckboxHandler);
	}

	this.validate = function(e) {
		var schemaid = $('#promotion-select').val();
		var tariffs = {};
		$('[name^="' + promotion.variablePrefix + '[stariffid][' + schemaid + ']"]').each(function () {
			if ($(this).is('.schema-tariff-selection') || $(this).prop('checked')) {
				if ($(this).val() > 0) {
					tariffs[$(this).attr('data-label')] = $(this).val();
				}
			}
		});
		if ($.isEmptyObject(tariffs)) {
			return false;
		}
		var cancelled = 0;
		$.each(tariffs, function (label, tariffid) {
			if (cancelled) {
				return false;
			}
			if (promotion.tariffTypes[tariffid] == promotion.internetTariffType
		        && !$('[name^="' + promotion.variablePrefix + '[snodes][' + schemaid + '][' + label + ']"]:checked').length
			    && !confirm(lmsMessages.nodeAssignmentWarning)) {
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
		$('.promotion-table').hide();

		var t = $(this).find('option:selected').val();

		$("#schema" + t).show();

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		$('#location-select').trigger('change');
	}

	this.tariffSelectionHandler = function () {
		var selected_tariff = $(this).find(':selected');
		var tariffaccess = parseInt(selected_tariff.attr('data-tariffaccess'));
		var tarifftype = parseInt(selected_tariff.attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr').next('.customerdevices');

		if (tariffaccess == -1) {
			tr.hide();
		} else {
			tr.show();
		}

		switch (tarifftype) {
			case promotion.internetTariffType:
				tr.find('div.nodes,div.netdevnodes').show();
				tr.find('div.phones').hide();
				break;
			case promotion.phoneTariffType:
				tr.find('div.nodes,div.netdevnodes').hide();
				tr.find('div.phones').show();
				break;
			default:
				tr.find('div.nodes,div.netdevnodes,div.phones').hide();
		}

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		var ms;
		if (tarifftype == promotion.phoneTariffType) {
			ms = tr.find('div.phones .lms-ui-multiselect').data('multiselect-object');
		} else {
			ms = tr.find('div.nodes .lms-ui-multiselect,div.netdevnodes .lms-ui-multiselect').data('multiselect-object');
		}
        if (!ms) {
			return;
		}
		ms.getOptions().each(function(key) {
			var authtype = parseInt($(this).attr('data-tariffaccess'));
			var location = $(this).attr('data-location');
			if (((authtype && (authtype & tariffaccess)) || !tariffaccess)
				&& (location == location_select || !location_select.length)) {
				ms.showOption(key);
			} else {
				ms.hideOption(key);
			}
		});
		ms.refreshSelection();
	}

	this.tariffCheckboxHandler = function() {
		var checked = this.checked;
		var tariffaccess = parseInt($(this).attr('data-tariffaccess'));
		var tarifftype = parseInt($(this).attr('data-tarifftype'));
		var location_select = $('#location-select').val();
		var tr = $(this).closest('tr').next('.customerdevices');

		if (checked) {
			tr.show();
		} else {
			tr.hide();
		}

		switch (tarifftype) {
			case promotion.phoneTariffType:
				tr.find('div.nodes,div.netdevnodes').hide();
				tr.find('div.phones').show();
				break;
			default:
				tr.find('div.nodes,div.netdevnodes').show();
				tr.find('div.phones').hide();
		}

		init_multiselects('select.lms-ui-multiselect-deferred:visible');

		var ms;
		if (tarifftype == promotion.phoneTariffType) {
			ms = tr.find('div.phones .lms-ui-multiselect').data('multiselect-object');
		} else {
			ms = tr.find('div.nodes .lms-ui-multiselect,div.netdevnodes .lms-ui-multiselect').data('multiselect-object');
		}
		if (!ms) {
			return;
		}
		ms.getOptions().each(function (key) {
			if (checked) {
				var authtype = parseInt($(this).attr('data-tariffaccess'));
				var location = $(this).attr('data-location');
				if (((authtype && (authtype & tariffaccess)) || !tariffaccess)
					&& (location == location_select || !location_select.length)) {
					ms.showOption(key);
				} else {
					ms.hideOption(key);
				}
			} else {
				ms.hideOption(key);
			}
		});
		ms.refreshSelection();
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

		$.ajax('?m=customerdevices&api=1&customerid=' + customerid, {
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

					if (data["nodes"]) {
						html += '<div class="nodes"><img src="img/node.gif"> '
							+ '<span class="bold">' + lmsMessages.nodes + '</span><br>';
						html += '<select name="' + promotion.variablePrefix + '[snodes][' + schemaid + ']['
                            + label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						var options = '';
						$.each(data["nodes"], function(key, node) {
							var location = String(node["location"]);
							if (location.length > 50) {
								location.substr(0, 50) + '...';
							}
							var nodeid = String(node["id"]).lpad('0', 4);
							options += '<option value="' + node["id"] + '"'
								+ (("snodes" in selected) && (schemaid in selected["snodes"]) && (label in selected["snodes"][schemaid])
								&& (selected["snodes"][schemaid][label].indexOf(node["id"]) > -1) ? ' selected' : '')
								+ ' data-tariffaccess="' + node["authtype"] + '"'
								+ ' data-location="' + node["location"] + '"'
								+ ' data-html-content="<strong>' + node["name"] + '</strong>'
								+ ' (' + nodeid + ')' + (location.length ? ' / ' + location : '') + '"';
							options += '>';
							options += node["name"] + ' (' + nodeid + ')'
								+ (location.length ? ' / ' + location : '');
							options += '</option>';
						});

						html += options;
						html += '</select></div>';
					}

					if (data["netdevnodes"]) {
						html += '<div class="netdevnodes"><ing src="img/netdev.gif"> '
							+ '<span class="bold">' + lmsMessages.netdevices + '</span><br>';
						html += '<select name="' + promotion.variablePrefix + '[snodes][' + schemaid + ']['
                            + label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						var options = '';
						$.each(data["netdevnodes"], function(key, node) {
							var location = String(node["location"]);
							if (location.length > 50) {
								location.substr(0, 50) + '...';
							}
							var nodeid = String(node["id"]).lpad('0', 4);
							options += '<option value="' + node["id"] + '"'
								+ (("snodes" in selected) && (schemaid in selected["snodes"]) && (label in selected["snodes"][schemaid])
								&& (selected["snodes"][schemaid][label].indexOf(node["id"]) > -1) ? ' selected' : '')
								+ ' data-tariffaccess="' + node["authtype"] + '"'
								+ ' data-location="' + node["location"] + '"'
								+ ' data-html-content="<strong>' + node["name"] + '</strong>'
								+ ' (' + nodeid + ')' + ' / ' + node["netdev_name"] + (location.length ? ' / ' + location : '') + '"';
							options += '>';
							options += node["name"] + ' (' + nodeid + ')'
								+ (location.length ? ' / ' + location : '');
							options += '</option>';
						});

						html += options;
						html += '</select></div>';
					}

					if (data["voipaccounts"]) {
						html += '<div class="phones"><img src="img/voip.gif"> '
							+ '<span class="bold">' + lmsMessages.voipAccounts + '</span><br>';
						html += '<select name="' + promotion.variablePrefix + '[sphones][' + schemaid + ']['
							+ label + '][]" multiple class="lms-ui-multiselect-deferred" data-separator="<hr>">';

						var options = '';
						$.each(data["voipaccounts"], function(key, account) {
							var location = String(account["location"]);
							if (location.length > 50) {
								location.substr(0, 50) + '...';
							}
							$.each(account["phones"], function(key, phone) {
								options += '<option value="' + phone["id"] + '"'
									+ (("sphones" in selected) && (schemaid in selected["sphones"]) && (label in selected["sphones"][schemaid])
									&& (selected["sphones"][schemaid][label].indexOf(phone["id"]) > -1) ? ' selected' : '')
									+ ' data-location="' + account["location"] + '"'
									+ ' data-html-content="<strong>' + phone["phone"] + '</strong>'
									+ ' / ' + account["login"] + (location.length ? ' / ' + location : '') + '"';
								options += '>';
								options += phone["phone"] + ' / ' + account["login"] + (location.length ? ' / ' + location : '');
								options += '</option>';
							});
						});

						html += options;
						html += '</select></div>';
					}

					td.html(html).appendTo(this);
				});

				$('#a_promotions').show();
                init_multiselects('select.lms-ui-multiselect-deferred:visible');

				$('#promotion-select').trigger('change');
				$('#tariff-select').trigger('change');
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
