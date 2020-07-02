/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

function savePersistentSettings(data) {
	return $.ajax('?m=persistentsetting', {
		async: true,
		method: 'POST',
		data: data,
		dataType: 'json',
		error: function(jqXHR, textStatus, errorThrown) {
			if (errorThrown != 'abort') {
				alert($t('AJAX: Error during persistent setting save on server:') + ' ' + errorThrown);
			}
		}
	});
}

var dataTablesLanguage = {};
$.ajax("js/jquery-datatables-i18n/" + lmsSettings.language + ".json", {
	method: "GET",
	success: function(data, textStatus, jqXHR) {
		dataTablesLanguage = data;
	}
});

jQuery.cachedScript = function(url, options) {
	options = $.extend( options || {}, {
		dataType: "script",
		cache: true,
		url: url
	});
	return jQuery.ajax(options);
}

function show_pagecontent() {
	$('#lms-ui-spinner').hide();
	$('#lms-ui-contents').show();
	if (location.hash.length && $(location.hash).length) {
		$(location.hash)[0].scrollIntoView();

		// workaround floating top position of menu panel and contents area
		// when url contains internal link
/*		var viewportTop = $(window).scrollTop();
		window.setTimeout(function(viewportTop) {

				$('#lms-ui-menu-panel-toggle').css('margin-top', viewportTop);
				$('#lms-ui-menu-panel').css('margin-top', viewportTop);
				$('#lms-ui-contents').css('margin-top', viewportTop);
			}, 50, viewportTop);
*/
		var contents = $('#lms-ui-contents');
		if (contents.length) {
			window.setTimeout(function() {
					contents[0].scrollIntoView();
				}, 50);
		}
	} else if (history.state) {
		window.setTimeout(function(scrollTop) {
				$('#lms-ui-module-view').scrollTop(scrollTop);
			}, 0, history.state.scrollTop);
	}
}

$.datepicker._gotoToday = function(id) {
	var target = $(id);
	var inst = this._getInst(target[0]);
	if (this._get(inst, 'gotoCurrent') && inst.currentDay) {
		inst.selectedDay = inst.currentDay;
		inst.drawMonth = inst.selectedMonth = inst.currentMonth;
		inst.drawYear = inst.selectedYear = inst.currentYear;
	} else {
		var date = new Date();
		inst.selectedDay = date.getDate();
		inst.drawMonth = inst.selectedMonth = date.getMonth();
		inst.drawYear = inst.selectedYear = date.getFullYear();
		// the below two lines are new
		this._setDateDatepicker(target, date);
		this._selectDate(id, this._getDateDatepicker(target));
	}
	this._notifyChange(inst);
	this._adjustDate(target);
}

function init_autolinker(selector) {
	$(selector).each(function() {
		$(this).html(Autolinker.link($(this).html(), { stripPrefix: false }));
	});
}

function init_multiselects(selector) {
	var multiselects = $(selector);
	if (multiselects.length) {
		multiselects.each(function() {
			new multiselect({
				id: $(this).uniqueId().attr('id'),
				defaultValue: $(this).attr('data-default-value'),
				shortenToDefaultValue: $(this).attr('data-shorten-to-default-value'),
				popupTitle: $(this).attr('data-popup-title'),
				type: $(this).attr('data-type'),
				icon: $(this).attr('data-icon'),
				button: $(this).attr('data-button'),
				bottom: lmsSettings.multiSelectPopupOnBottom,
				separator: $(this).attr('data-separator'),
				maxVisible: lmsSettings.multiSelectMaxVisible,
				substMessage: '- $a options selected -',
				tooltipMessage: $(this).attr('data-tooltip-message')
			});
		});
	}
}

function init_datepickers(selector) {
	var options = {
		showButtonPanel: true,
		dateFormat: "yy/mm/dd",
		changeYear: true,
		beforeShow: function (input, inst) {
			if ($(input).is('[data-tooltip]')) {
				$(input).tooltip('disable');
				$(this).data('input-tooltip', input);
			}
			var icon = $(input).next().find('[data-tooltip]');
			if (icon.length) {
				icon.tooltip('disable');
				$(this).data('icon-tooltip', icon);
			}
			setTimeout(function () {
				var btnHtml = '<button type="button" class="ui-datepicker-current ui-state-default ui-priority-secondary ' +
					'ui-corner-all lms-ui-datepicker-clear">' + $t('<!datepicker>Clear') + '</button>';
				var target = $(input);
				var widget = target.datepicker("widget");
				var buttonPane = widget.find(".ui-datepicker-buttonpane");
				if (buttonPane.find('.lms-ui-datepicker-clear').length) {
					return;
				}
				var btn = $(btnHtml);
				btn.appendTo(buttonPane);

				function click() {
					target.datepicker("setDate", '');
					setTimeout(function () {
						var buttonPane = widget.find(".ui-datepicker-buttonpane");
						if (buttonPane.find('.lms-ui-datepicker-clear').length) {
							return;
						}
						var btn = $(btnHtml);
						btn.appendTo(buttonPane);
						btn.click(click);
					}, 1);
				}

				btn.click(click);
			}, 1);
		},
		onChangeMonthYear: function (year, month, instance) {
			var input = this;
			setTimeout(function () {
				var target = $(input);
				var widget = target.datepicker("widget");
				var buttonPane = widget.find(".ui-datepicker-buttonpane");
				if (buttonPane.find('.lms-ui-datepicker-clear').length) {
					return;
				}
				var btnHtml = '<button type="button" class="ui-datepicker-current ui-state-default ui-priority-secondary ' +
					'ui-corner-all lms-ui-datepicker-clear">' + $t('<!datepicker>Clear') + '</button>';
				var btn = $(btnHtml);
				btn.appendTo(buttonPane);

				function click() {
					target.datepicker("setDate", '');
					setTimeout(function () {
						var buttonPane = widget.find(".ui-datepicker-buttonpane");
						if (buttonPane.find('.lms-ui-datepicker-clear').length) {
							return;
						}
						var btn = $(btnHtml);
						btn.appendTo(buttonPane);
						btn.click(click);
					}, 1);
				}

				btn.click(click);
			}, 1);
		},
		onClose: function (dateText, inst) {
			if ($(this).data('input-tooltip') !== undefined) {
				$(this).tooltip('enable');
			}
			if ($(this).data('icon-tooltip') !== undefined) {
				$(this).data('icon-tooltip').tooltip('enable');
			}
		}
	}

	if (!lmsSettings.openCalendarOnInputClick) {
		options.showOn = 'button';
		options.buttonText = '<i class="fas fa-calendar-alt" title="' + $t('Click here to open calendar') + '"></i>';
	}

	$(selector).each(function() {
		var unix = $(this).hasClass('unix') || $(this).hasClass('lms-ui-date-unix');
		var value = $(this).val();
		var dt = null;
		if (unix) {
			if (parseInt(value)) {
				dt = new Date();
				dt.setTime($(this).val() * 1000);
			}
			var tselem = $(this).clone(true).removeData().attr('type', 'hidden');
			tselem.insertBefore($(this).removeAttr('name'));
			if ($(this).val() == '0') {
				$(this).val('');
			}
			$.extend(options, { altField: tselem, altFormat: $.datepicker.TIMESTAMP });
			$(this).change(function() {
				if ($(this).val() == '') {
					tselem.val('0');
				}
			});
		}
		$(this).wrap('<div class="lms-ui-date-container"/>');
		$(this).datepicker(options).attr("autocomplete", 'off')
		if (unix) {
			//$(this).off('change').removeAttr('onchange');
			if (dt) {
				$(this).datepicker('setDate', dt);
			}
		}
		options.altField = '';
		options.altFormat = '';
	});
}

function initAdvancedSelects(selector) {
	$(selector).each(function () {
		if ($(this).next('.chosen-container').length) {
			$(this).trigger('chosen:updated');
			return;
		}
		$(this).on('chosen:ready', function () {
			if (typeof ($(this).attr('required')) !== 'undefined') {
				$(this).next().toggleClass('lms-ui-error', RegExp("^0?$").test($(this).val()));
			}
		});
		$(this).chosen($.extend({
			no_results_text: $t('No results match'),
			placeholder_text_single: $t('Select an Option'),
			placeholder_text_multiple: $t('Select Some Options'),
			display_selected_options: false,
			search_contains: true,
			disable_search_threshold: 5,
			inherit_select_classes: true
		}, $(this).attr('data-options') ? JSON.parse($(this).attr('data-options')) : {}));
		$(this).chosen().change(function (e, data) {
			if (typeof ($(this).attr('required')) !== 'undefined') {
				$(this).next().toggleClass('lms-ui-error', typeof (data) === 'undefined' || RegExp("^0?$").test(data.selected));
			}
		});
	});
}

function updateAdvancedSelects(selector) {
	$(selector).each(function() {
		$(this).trigger('chosen:updated');
	});
}

function init_comboboxes(selector) {
	$(selector).each(function() {
		$(this).scombobox($.extend({ wrap: false },
			$(this).attr('data-options') ? JSON.parse($(this).attr('data-options')) : {},
			$(this).attr('data-alt-field') ? { altField: $(this).attr('data-alt-field') } : {},
			$(this).attr('data-alt-invalid-field') ? { altInvalidField: $(this).attr('data-alt-invalid-field') } : {}
		));
		var scombobox = $(this).parent('.scombobox');
		$('.scombobox-display', scombobox).addClass(
			$.grep($('select', scombobox).attr('class').split(' '), function(value) {
				return value != 'lms-ui-combobox';
			}));
		if ($(this).attr('data-value')) {
			scombobox.scombobox('val', $(this).attr('data-value'));
			$(this).removeAttr('data-value');
		} else if ($(this).attr('data-id-value')) {
			scombobox.scombobox('val', $(this).attr('data-id-value'));
		}
	});
	if ($('.scombobox').length) {
		// dynamicaly insert hidden input element with name as original select element
		// the purpose is simple: we want to submit custom value to server
		$('.scombobox').scombobox('change', function (e) {
			var scomboboxelem = $(this).closest('.scombobox');
			var name = scomboboxelem.find('select').attr('name');
			$(this).attr('name', name);
		}, 'lms-ui');
		// hide tooltip after combo box activation because it can interfere with dropdown list
		$('.scombobox').scombobox('click', function(e) {
			if ($(this).is('[data-tooltip]')) {
				$(this).removeAttr('data-tooltip').tooltip('disable');
			}
		}, 'lms-ui');
		$('.scombobox').scombobox('keypress', function(e) {
			if ($(this).is('[data-tooltip]')) {
				$(this).removeAttr('data-tooltip').tooltip('disable');
			}
		}, 'lms-ui');
	}
}

function init_titlebars(selector) {
	$(selector).each(function() {
		$(this).prop('onclick', null);
		$(this).click(function () {
			var elemid = $(this).attr('data-lmsbox-content');
			showOrHide(elemid);
			$('#' + elemid).find('.lms-ui-datatable').each(function () {
				if (!$.fn.dataTable.isDataTable(this)) {
					initDataTable(this);
				}
			});
		});
		$(this).find('td a,td :input, div a,div :input').click(function (e) {
			e.stopPropagation();
		});
	});
}

function init_attachment_lists(selector) {
	if (!selector) {
		selector = 'body';
	}

	$(selector + ' .toggle-file-list').click(function() {
		var containerid = parseInt($(this).attr('data-container-id'));
		var elem = $('#files-' + containerid);
		elem.toggle();
		$(this).html('<img src="img/' + (elem.is(':visible')  ? 'desc' : 'asc') + '_order.gif">');
	});

	$(selector + ' .container-edit').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		row.find('.container-view,.container-edit').hide();
		row.find('.container-modify,.container-save,.container-cancel').show();
		var description = row.find('.container-modify > input');
		description.attr('data-old-value', description.val()).removeClass('alert').focus();
	});

	$(selector + ' .container-modify').keydown(function(e) {
		switch (e.key) {
			case 'Enter':
				$(this).closest('.lms-ui-tab-table-row').find('.container-save').click();
				break;
			case 'Escape':
				$(this).closest('.lms-ui-tab-table-row').find('.container-cancel').click();
				break;
		}
	});

	$(selector + ' .container-cancel').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		row.find('.container-view,.container-edit').show();
		row.find('.container-modify,.container-save,.container-cancel').hide();
		var description = row.find('.container-modify > input');
		description.val(description.attr('data-old-value'));
	});

	$(selector + ' .container-save').click(function() {
		var row = $(this).closest('.lms-ui-tab-table-row');
		var description = row.find('.container-modify > input');
		if (description.attr('data-old-value') != description.val()) {
			var form = $('#filecontainer-form-' + row.attr('data-attachmenttype'));
			$.ajax({
				url: form.attr('action') + '&id=' + row.attr('data-id'),
				type: form.attr('method'),
				data: {
					description: description.val()
				},
				success: function (data) {
					if (data.hasOwnProperty('error')) {
						description.addClass('alert').attr('title', data.error);
					} else {
						row.find('.container-view,.container-edit').show();
						row.find('.container-modify,.container-save,.container-cancel').hide();
						row.find('.container-view').html(
							description.val().length ? Autolinker.link(description.val(), { stripPrefix: false }) : '---'
						);
					}
				}
			});
		}
	});

	$(selector + ' .container-del').click(function() {
		confirmDialog($t("Are you sure you want to delete this file container?"), this).done(function() {
			location.href = $(this).attr('href');
		});
		return false;
	});

	$(selector + ' .container-add-button').click(function() {
		var addbutton = $(this);
		addbutton.closest('.lms-ui-tab-buttons').prop('disabled', true);
		var formdata = new FormData(this.form);
		formdata.delete(addbutton.parent().find('[type="file"]').attr('name'));
		$.ajax($(this.form).attr('action'), {
			type: "POST",
			contentType: false,
			dataType: "json",
			data: formdata,
			processData: false,
			success: function(data) {
				if (data.hasOwnProperty("url")) {
					location.href = data.url;
				}
				addbutton.closest('.lms-ui-tab-buttons').prop('disabled', false);
			},
			error: function() {
				addbutton.closest('.lms-ui-tab-buttons').prop('disabled', false);
			}
		});
		return false;
	});
}

function initAutoGrow(selector) {
	$(selector + ':not(.lms-ui-autogrow-initiated)').inputAutogrow({
		minWidth: 200,
		maxWidth: 500
	}).addClass('lms-ui-autogrow-initiated');
}

function initListQuickSearch(options) {
	$.extend({
		single: false,
		field_name_pattern: 'list',
		item_content: function(item) {
			if (item.hasOwnProperty('name')) {
				return sprintf('#%06d', item.id) + ' <a href="?m=list&id=' + item.id + '">' + item.name + '</a>';
			} else {
				return '<a href="?m=list&id=' + item.id + '">' + sprintf('#%06d', item.id) + '</a>';
			}
		},
		excluded_elements: [],
		conflict_lists: []
	}, options);
	if (!options.hasOwnProperty('selector') || !options.hasOwnProperty('ajax')) {
		return;
	}
	var list_container = $(options.selector);
	if (!list_container.length) {
		return;
	}
	var list = list_container.find('.lms-ui-list');
	var list_suggestion = list_container.find('.lms-ui-list-suggestion');
	new AutoSuggest(
		list_container.closest('form')[0], list_suggestion[0], options.ajax, 0,
		function (data) {
			list_suggestion.val('');
			var html = '<li data-item-id="' + data.id + '">' +
				'<input type="hidden" name="' + options.field_name_pattern.replace('%id%', data.id) + '" value="' + data.id + '">' +
				'<i class="lms-ui-icon-delete lms-ui-list-unlink"></i>' + (options.item_content)(data) + '</li>';
			if (options.single) {
				list.html(html).show();
			} else {
				list.append(html).show();
			}
			list_container.trigger('lms:list_updated', { list: list.find('li') });
		},
		function (suggestions) {
			var result = [];
			var itemids = [];

			list.find('li[data-item-id]').each(function () {
				itemids.push($(this).attr('data-item-id'));
			});
			$(options.conflict_lists).each(function(key, list) {
				$(list).find('li[data-item-id]').each(function () {
					itemids.push($(this).attr('data-item-id'));
				});
			});

			itemids = itemids.concat(options.excluded_elements);

			$.each(suggestions, function (key, suggestion) {
				if (itemids.indexOf(suggestion.id) == -1) {
					result.push(suggestion);
				}
			});

			return result;
		}
	);
}

function showGallery(data) {
	$('.lms-ui-gallery-container').show();
	Galleria.ready(function() {
		var gallery = this;
		this.addElement('buttons').appendChild('container', 'buttons');
		this.$('buttons').html('<i class="fullscreen-button lms-ui-icon-fullscreen-on"></i><i class="close-button lms-ui-icon-hide"></i>')
			.on('click', 'i', function() {
				if ($(this).is('.close-button')) {
					gallery.destroy();
					$('.lms-ui-gallery-container').hide();
				} else {
					gallery.toggleFullscreen();
					$(this).toggleClass(['lms-ui-icon-fullscreen-on', 'lms-ui-icon-fullscreen-off']);
				}
			});
		this.attachKeyboard({
			left: this.prev, // applies the native prev() function
			right: this.next,
			escape: function() {
				gallery.destroy();
				$('.lms-ui-gallery-container').hide();
			}
		});
		this.lazyLoadChunks(10);
	}).run(".lms-ui-gallery", {
		dataSource: data,
		keepSource: true,
		thumbnails: "lazy",
		preload: 0,
		_toggleInfo: false
	});
}

function hideMainScrollbars() {
	$('body,#lms-ui-contents,#lms-ui-module-view').addClass('fullscreen-popup');
	$('#lms-ui-back-to-top').addClass('fullscreen-popup');
}

function showMainScrollbars() {
	$('body,#lms-ui-contents,#lms-ui-module-view').removeClass('fullscreen-popup');
	$('#lms-ui-back-to-top').removeClass('fullscreen-popup');
}

$(function() {
	var autocomplete = "off";
	var elementsToInitiate = 0;
	var scrollTimeout = null;

	$('#lms-ui-module-view').scroll(function(e) {
		if (scrollTimeout) {
			clearTimeout(scrollTimeout);
		}
		scrollTimeout = setTimeout(function() {
				history.replaceState({ scrollTop: $('#lms-ui-module-view').scrollTop()}, window.title );
			}, 200);
	});

	$('.lms-ui-button-submit').one('click', function(e) {
		$(this).unbind('click');
	});

	init_datepickers('div.calendar input,div.lms-ui-date,input.calendar,input.lms-ui-date');

	$('.lms-ui-button-date-period').click(function() {
		var from = $(this).attr('data-from');
		var to = $(this).attr('data-to');
		var period = $(this).attr('data-period');
		var fromdate, todate;
		var fromvalue = $(from).val();
		var tovalue = $(from).val();

		if (fromvalue.match(/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/)) {
			fromdate = new Date(fromvalue.replace(/\//g, '-'));
		} else {
			fromdate = new Date();
		}
		if (tovalue.match(/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/)) {
			todate = new Date(tovalue.replace(/\//g, '-'));
		} else {
			todate = new Date();
		}

		if (period == 'previous-month' || typeof(period) === 'undefined') {
			fromdate.setDate(0);
			fromdate.setDate(1);
			todate.setDate(0);
		} else if (period == 'current-month') {
			fromdate = new Date();
			todate = new Date();
			fromdate.setDate(1);
			todate.setMonth(todate.getMonth() + 1);
			todate.setDate(0);
		} else if (period == 'next-month') {
			fromdate.setMonth(fromdate.getMonth() + 1);
			fromdate.setDate(1);
			todate.setMonth(fromdate.getMonth() + 1);
			todate.setDate(0);
		} else if (period == 'current-year') {
			fromdate = new Date();
			todate = new Date();
			fromdate.setMonth(0);
			fromdate.setDate(1);
			todate.setMonth(11);
			todate.setDate(31);
		} else if (period == 'previous-year' || period == 'next-year') {
			fromdate.setMonth(0);
			fromdate.setDate(1);
			if (period == 'previous-year') {
				fromdate.setFullYear(fromdate.getFullYear() - 1);
			} else {
				fromdate.setFullYear(fromdate.getFullYear() + 1);
			}
			todate.setMonth(11);
			todate.setDate(31);
			todate.setFullYear(fromdate.getFullYear());
		}

		$(from).val(sprintf("%04d/%02d/%02d", fromdate.getFullYear(), fromdate.getMonth() + 1, fromdate.getDate()));
		$(to).val(sprintf("%04d/%02d/%02d", todate.getFullYear(), todate.getMonth() + 1, todate.getDate()));
	});

	$.datetimepicker.setLocale(lmsSettings.language);
	var datetimepickeroptions = {
		step: 30,
		closeOnDateSelect: true,
		dayOfWeekStart: 1,
		onShow: function (current_time, input) {
			if ($(input).is('[data-tooltip]')) {
				$(input).tooltip('disable');
			}
			var icon = $(input).next();
			if (icon.is('[data-tooltip]')) {
				icon.tooltip('disable');
			}
		},
		onClose: function (current_time, input) {
			if ($(input).is('[data-tooltip]')) {
				$(input).tooltip('enable');
			}
			var icon = $(input).next();
			if (icon.is('[data-tooltip]')) {
				icon.tooltip('enable');
			}
		},
		openOnFocus: false
	};
	$('div.calendar-time input,div.lms-ui-datetime input,input.calendar-time,input.lms-ui-datetime').each(function() {
		var options = datetimepickeroptions;
		if ($(this).hasClass('calendar-time-seconds') || $(this).hasClass('lms-ui-datetime-seconds')) {
			options.format = "Y/m/d H:i:s";
		}
		if ($(this).attr('data-format')) {
			options.format = $(this).attr('data-format');
		}
		if ($(this).attr('data-time-step')) {
			options.step = parseInt($(this).attr('data-time-step'));
		} else {
			options.step = lmsSettings.eventTimeStep
		}
		$(this).datetimepicker(options).attr("autocomplete", autocomplete);

		// avoid datetimepicker ui control close after second click on trigger button
		var xdsoft_datetimepicker = $(this).data('xdsoft_datetimepicker');
		xdsoft_datetimepicker.on('open.xdsoft', function() {
			var body = $('body')[0];
			$([ body, window ]).off('mousedown.xdsoft');
			$([ body, window ]).on('mousedown.xdsoft', function arguments_callee6(e) {
				if ($(e.target).is('.ui-datepicker-trigger')) {
					return;
				}
				xdsoft_datetimepicker.trigger('close.xdsoft');
				$([body, window]).off('mousedown.xdsoft', arguments_callee6);
			})
			var current_time = xdsoft_datetimepicker.find('.xdsoft_time.xdsoft_current');
			if (current_time.length && current_time.prev().length) {
				current_time.prev()[0].scrollIntoView();
			}
		});

		if (lmsSettings.openCalendarOnInputClick) {
			$(this).click(function () {
				$(this).datetimepicker('toggle');
			});
		} else {
			$('<i class="fas fa-calendar-alt ui-datepicker-trigger" title="' + $t('Click here to open calendar') + '"></i>')
				.insertAfter(this).click(function () {
				$(this).prev().datetimepicker('toggle');
			});
		}
	});

	init_multiselects('select.lms-ui-multiselect');

	$(document).on('mouseup', '[data-target-url]',
		function(e) {
			var elem = $(this);
			var target = $(e.target);
			var url = $(this).attr('data-target-url');
			var link = target.closest('a');
			var ifLink = (link.length && elem.find(link).length > 0);
			var ifButton = elem.find(target.closest('button')).length > 0;
			var ifNewWindow = (e.which == 2 || e.ctrlKey);
			var column = target.closest('td,.lms-ui-tab-table-column');

			if (ifButton || (ifLink && link.attr('href')) ||
				(column.length && column.is('.lms-ui-buttons,.buttons'))) {
				return;
			}

			if (ifNewWindow) {
				window.open(url);
			} else {
				location.href = url;
			}
		}
	);

	$('.lms-ui-button-clipboard').attr('title', $t('Click copies to clipboard'));
	new ClipboardJS('.lms-ui-button-clipboard');

	$(document).on('mouseenter', '[title]', function() {
		if ($(this).is('[data-tooltip]')) {
			return;
		}
		tooltipClass = '';
		if ($(this).hasClass('lms-ui-error') || $(this).hasClass('alert')) {
			tooltipClass += ' lms-ui-error';
			if ($(this).hasClass('bold')) {
				tooltipClass += ' bold';
			}
		} else if ($(this).hasClass('lms-ui-warning')) {
			tooltipClass += 'lms-ui-warning';
		} else if ($(this).hasClass('bold')) {
			tooltipClass += 'bold';
		}

		var title = $(this).attr('title');
		$(this).attr('data-tooltip', title).removeAttr('title');
		$(this).tooltip({
			items: '[data-tooltip]',
			content: title,
			show: false,
			hide: false,
			track: true,
			classes: {
				'ui-tooltip': tooltipClass
			},
			create: function() {
				$(this).tooltip('open');
			}
		});
	});

	[
		{ class: 'lms-ui-tooltip-voipaccountinfo', url: '?m=voipaccountinfoshort&id='},
		{ class: 'lms-ui-tooltip-invoiceinfo', url: '?m=invoiceinfo&id='},
		{ class: 'lms-ui-tooltip-docnumber', url: '?m=number&id='},
		{ class: 'lms-ui-tooltip-customerinfo', url: '?m=customerinfoshort&id='},
		{ class: 'lms-ui-tooltip-nodelist', url: '?m=nodelistshort&id='},
		{ class: 'lms-ui-tooltip-ewxnodelist', url: '?m=ewxnodelist&id='},
		{ class: 'lms-ui-tooltip-rtticketinfo', url: '?m=rtticketinfo&id='},
		{ class: 'lms-ui-tooltip-customerassignmentinfo', url: '?m=customerassignmentinfo&id='},
		{ class: 'lms-ui-tooltip-nodegroupinfo', url: '?m=nodeinfo&nodegroups=1&id='},
		{ class: 'lms-ui-tooltip-netdevlist', url: '?m=ewxdevlist&id='},
		{ class: 'lms-ui-tooltip-eventinfoshort', url: '?m=eventinfoshort&id='}
	].forEach(function(popup) {
		$('.' + popup.class).tooltip({
			items: '.' + popup.class,
			show: false,
			//hide: false,
			track: false,
			position: { my: "left top", at: "left bottom", collision: "flipfit" },
			open: function(e, ui) {
				if (typeof(e.originalEvent) === 'undefined') {
					return false;
				}
				var id = $(ui.tooltip).attr('id');
				$('div.ui-tooltip').not('#' + id).remove();
			},
			close: function(e, ui) {
				$(ui.tooltip).mouseenter(function() {
					$(this).stop(true);
				}).mouseleave(function() {
					$(this).remove();
				});
			},
			tooltipClass: popup.class,
			content: function(callback) {
				var elem = $(this);
				var resourceid = elem.attr('data-resourceid');
				$.ajax(popup.url + resourceid, {
					async: true,
					success: function(data) {
						callback(data);
						// elem.tooltip('disable');
						// elem.tooltip('enable');
					}
				});
			}
		});
	});

	initAdvancedSelects('select.lms-ui-advanced-select');

	init_comboboxes('.lms-ui-combobox');

	var documentviews = $('.documentview');

	documentviews.tooltip({
		track: true,
		items: '.documentview-image',
		show: false,
		//hide: false,
		tooltipClass: 'documentview',
		content: function() {
			var href = $(this).attr('href') + '&api=1&thumbnail=300';
			return '<img src="' + href + '" style="max-width: 300px;">';
			//return '';
		}
	});

	documentviews.on("click", function() {
		var dialog = $('#' + $(this).attr('data-dialog-id'));
		var url = dialog.attr('data-url');
		if ($(this).hasClass('documentview-image')) {
			$(this).tooltip('disable');
			dialog.html('<img src="' + url + '" style="width: 100%;">');
		} else if ($(this).hasClass('documentview-audio')) {
			dialog.html('<audio src="' + url + '" style="width: 100%;" controls preload="none">' +
				$t('Your browser does not support the audio element.') + '</audio>');
			var audioelem = dialog.find('audio').get(0);
			audioelem.currentTime = 0;
			audioelem.play();
		} else if ($(this).hasClass('documentview-video')) {
			dialog.html('<video src="' + url + '" style="width: 100%;" controls preload="none">' +
				$t('Your browser does not support the video element.') + '</video>');
			var videoelem = dialog.find('video').get(0);
			videoelem.currentTime = 0;
			videoelem.play();
		} else if ($(this).hasClass('documentview-pdf')) {
			window.open(url, '_blank', 'left=' + (window.screen.availWidth * 0.1) +
				',top=' + (window.screen.availHeight * 0.1) +
				',width=' + (window.screen.availWidth * 0.8) +
				',height=' + (window.screen.availHeight * 0.8));
			return false;
		}
		dialog.dialog('open');
		return false;
	});

	$('.documentviewdialog').dialog({
		modal: true,
		autoOpen: false,
		resizable: false,
		draggable: false,
		minWidth: 0,
		minHeight: 0,
		dialogClass: 'documentviewdialog',
		open: function(event, ui) {
			var elem = $('#' + $(this).attr('id').replace(/dialog/, ''));
			$(this).dialog('option', 'position', elem.hasClass('documentview-audio') ?
				{ my: 'center', at: 'center', of: window } : { my: 'top', at: 'top', of: window })
				.dialog('option', { width: 'auto' });
			$('.ui-widget-overlay').bind('click', function() {
				$(this).siblings('.ui-dialog').find('.ui-dialog-content')
					.dialog('close');
			});
		},
		close: function(event, ui) {
			var elem = $('#' + $(this).attr('id').replace(/dialog/, ''));
			if (elem.hasClass('documentview-image')) {
				elem.tooltip('enable');
			} else if (elem.hasClass('documentview-audio')) {
				$(this).find('audio').get(0).pause();
			} else if (elem.hasClass('documentview-video')) {
				$(this).find('video').get(0).pause();
			}
		}
	})
//		.on('click', function() {
//			$(this).dialog('close');
//		})
	.parent().resizable({
		aspectRatio: true
	})
	.draggable();

	$(document).on('keyup keydown', function(e) {
		$('body').css('user-select', e.shiftKey ? 'none' : 'auto');
	});

	var tbodies = $('table.lms-ui-multi-check,tbody.lms-ui-multi-check,div.lms-ui-multi-check');
	$.each(tbodies, function(index, elem) {
		var tbody = $(elem);
		if (tbody.is('table')) {
			tbody = tbody.find('tbody');
		}
		var checkboxes = tbody.parent().find(':checkbox');
		var allcheckboxes = checkboxes.filter('.lms-ui-multi-check');

		var checkall = checkboxes.filter('.lms-ui-multi-check-all');
		if (!checkall.length) {
			checkall = tbody.siblings('thead,tfoot').filter('.lms-ui-multi-check-all');
		}
		if (checkall.length) {
			checkall.parent().addClass('lms-ui-multi-check-all');
			$(checkall).click(function() {
				var checked = $(this).prop('checked');
				checkall.not(this).each(function() {
					$(this).prop('checked', checked);
				});
				allcheckboxes.filter(':visible').each(function() {
					this.checked = checked;
				});
			});
		} else {
			checkall = null;
		}

		elem.updateCheckAll = function() {
			allcheckboxes.filter(':not(:visible)').prop('checked', false);
			updateCheckAll();
		}

		function checkElements(checkbox) {
			// reorder all checkboxes list when it is contained in lms-ui-datatable
			if ($(checkbox).closest('.lms-ui-datatable').length) {
				checkboxes = tbody.parent().find(':checkbox');
				allcheckboxes = checkboxes.filter('.lms-ui-multi-check');
			}

			var i = allcheckboxes.index(allcheckboxes.filter('[data-prev-checked]:visible')),
				j = allcheckboxes.index(checkbox);
			if (i > -1) {
				var checked = $(allcheckboxes[i]).attr('data-prev-checked') == 'true' ? true : false;
				var start = Math.min(i, j);
				var stop = Math.max(i, j);
				for (i = start; i <= stop; i++) {
					allcheckboxes[i].checked = checked;
				}
				updateCheckAll();
			}
		}

		function updateCheckAll() {
			if (checkall) {
				checkall.prop('checked', allcheckboxes.filter(':visible:checked').length == allcheckboxes.filter(':visible').length);
			}
		}

		$.each(allcheckboxes, function(index, elem) {
			var checkbox = $(elem)[0];
			var row = $(checkbox).closest('tr,.lms-ui-tab-table-row');
			row.click(function(e) {
				if ($(e.target).closest('.lms-ui-button-clipboard').length) {
					return;
				}
				if (e.shiftKey) {
					checkElements(checkbox);
				} else {
					checkbox.checked = !checkbox.checked;
					allcheckboxes.filter('[data-prev-checked]').removeAttr('data-prev-checked');
					$(checkbox).attr('data-prev-checked', checkbox.checked);
					updateCheckAll();
				}
			});
			row.find(':checkbox').click(function(e) {
				if (e.shiftKey) {
					checkElements(this);
				} else {
					allcheckboxes.filter('[data-prev-checked]').removeAttr('data-prev-checked');
					$(this).attr('data-prev-checked', this.checked);
					updateCheckAll();
				}
				e.stopPropagation();
			});
			row.find('a:not(.lms-ui-button-clipboard)').click(function(e) {
				e.stopPropagation();
			});
		});
	});

	var dataTables = $('.lms-ui-datatable');
	dataTables.each(function() {
		var trStyle = $(this).closest('tr').attr('style');
		if (trStyle === undefined || !trStyle.match(/display:\s*none/)) {
			elementsToInitiate++;
		}
	});

	function initDataTable(elem) {
		var init = $(elem).data('init');

		var columnSearch = $(elem).hasClass('lms-ui-datatable-column-search');
		var columnToggle = $(elem).hasClass('lms-ui-datatable-column-toggle');

		if (columnSearch) {
			var tr = $('thead tr', elem).clone().addClass('search-row');
			tr.appendTo($('thead', elem)).find('th').each(function(key, th) {
				var content = '';
				if ((searchable = $(th).attr('data-searchable')) === undefined ||
					searchable == 'true') {
					if ((selectValue = $(th).attr('data-select-value')) !== undefined &&
						selectValue == 'true') {
						var selectValues = [];
						tr.parent().siblings('tbody').children('tr').each(function(index, row) {
							value = $($('td', row)[key]).html().trim();
							if (!value.length || selectValues.indexOf(value) > -1)
								return;
							selectValues.push(value);
						});
						if (selectValues.length > 1) {
							content = '<select' + ($(th).attr('data-filter-id') ? ' id="' + $(th).attr('data-filter-id') + '"' : '') +
								'><option value="">'  + $t('- any -') + '</option>';
							selectValues.sort().forEach(function(value, index) {
								content += '<option value="' + value + '">' + value + '</option>';
							});
							content += '</select>';
						}
					} else {
						content = '<input type="search" placeholder="' + $t('Search') + '"' +
							($(th).attr('data-filter-id') ? ' id="' + $(th).attr('data-filter-id') + '"' : '') + '>';
					}
				} else {
					content = '';
				}
				$(th).html(content);
			});
			$('thead .search-row input', elem).on('keyup change search', function() {
				$(elem).DataTable().column($(this).parent().index() + ':visible')
					.search(this.value.length ? this.value : '', true).draw();
			});
			$('thead .search-row select', elem).on('change', function() {
				var value = this.value;
				$(elem).DataTable().column($(this).parent().index() + ':visible')
					.search(value.length ? '^' + value + '$' : '', true).draw();
			});
		}

		$(elem).on('init.dt', function(e, settings) {
			var searchColumns = $(this).data('init').searchColumns;
			var api = new $.fn.dataTable.Api(settings);
			$(this).data('api', api);
			var state = api.state.loaded();

			if (state && columnSearch) {
				var i = 0;
				var searchFields = $('thead input[type="search"],thead select', elem);
				api.columns().every(function(index) {
					var columnState = state.columns[index];
					var searchValue = '';
					if (!columnState.visible) {
						return;
					}
					if (typeof searchColumns[index].search === 'undefined') {
						$(searchFields[i]).val(state.columns[index].search.search.replace(/[\^\$]/g, ''));
					} else {
						$(searchFields[i]).val(searchColumns[index].search.replace(/[\^\$]/g, ''));
						if (searchColumns[index].search.length) {
							if ($(searchFields[i]).is('thead select')) {
								searchValue = '^' + searchColumns[index].search + '$';
							} else {
								searchValue = searchColumns[index].search;
							}
						}
						//console.log(searchValue);
						api.column(index).search(searchValue, true).draw();
					}
					i++;
				});
			}

			if (columnToggle) {
				var toggle = $(elem).siblings('div.top').find('div.lms-ui-datatable-column-toggle');
				var content = '<form name="' + $(elem).attr('id') + '" class="column-toggle">' +
					'<select class="column-toggle" class="lms-ui-multiselect" name="' +
					$(elem).attr('id') + '-column-toggle[]" multiple' +
					' title="' + $t('Column visibility') + '">';
				api.columns().every(function(index) {
					var text = $(this.header()).text().trim();
					if (text.indexOf(':') > 0) {
						content += '<option value="' + index + '"' +
							(!state || state.columns[index].visible ? ' selected' : '') + '>' + text.replace(':', '') + '</option>';
					}
				});
				content += '</select></form>';
				toggle.html(content);
				var multiselectId = toggle.find('select.column-toggle').uniqueId().attr('id');
				new multiselect({
					id: multiselectId,
					defaultValue: null,
					icon: 'lms-ui-icon-configuration',
					type: 'tiny'
				});
				toggle.find('#' + multiselectId).on('lms:multiselect:itemclick', function(e, data) {
					api.column(data.index).visible(data.checked);
				});
			}

			var clearSettings = $(elem).siblings('div.top').find('div.lms-ui-datatable-clear-settings');
			clearSettings.html('<i class="lms-ui-icon-clear" title="' + $t('Clear settings') + '"/>');
			clearSettings.click(function() {
				if (state) {
					api.state.clear();
				}
				api.columns().every(function() {
					this.visible(true);
				});
				$('thead tr:last-child th', elem).each(function(index, th) {
					if ($('input[type="search"]', th).length) {
						$('input[type="search"]', th).val('');
						api.column(index).search('');
					} else if ($('select', th).length) {
						$('select', th).prop('selectedIndex', 0);
						api.column(index).search('');
					}
				});
				$(elem).parent().find('.column-toggle').each(function() {
					$(this).find('option:not(:disabled)').attr('selected', 'selected');
				}).trigger('lms:multiselect:updated');

				var pageLen = $(elem).attr('data-page-length');
				if (pageLen !== undefined) {
					api.page.len(pageLen);
				} else {
					api.page.len(10);
				}

				var order = $(elem).attr('data-order');
				if (order !== undefined) {
					api.order(JSON.parse(order));
				} else {
					api.order([[0, 'asc']]);
				}
				api.search('');

				api.draw('full-hold');

				var displayStart = $(elem).attr('data-display-start');
				api.page(displayStart !== undefined ?
					Math.ceil(displayStart / pageLen) : 0).draw('page');
			});

			if (elementsToInitiate > 0) {
				elementsToInitiate--;
				if (!elementsToInitiate) {
					show_pagecontent();
				}
			}
		}).on('column-visibility.dt', function(e, settings, column, visible) {
			if (!visible)
				return;
			var api = $(this).data('api');
			var searchValue = api.columns(column).search()[0];
			var state = api.state();
			var columnStates = state ? state.columns : null;
			var i = 0;
			api.columns().every(function(index) {
				if (index == column) {
					$('thead tr:last-child th:nth-child(' + (i + 1) + ') :input', elem).val(searchValue.replace(/[\^\$]/g, ''));
				}
				if (columnStates && !columnStates[index].visible) {
					return;
				}
				i++;
			});
		}).on('responsive-resize.dt', function (e, datatable, columns) {
			// first fired events contains "-" characters instead of column visibility states
			// so we should omit this event
			if (typeof(columns[columns.length - 1]) == 'boolean') {
				var search_fields = $(this).closest('table').find('.search-row th');
				$.each(columns, function (index, visible) {
					datatable.column(index).visible(true);
					if (visible) {
						search_fields.eq(index).show();
					} else {
						search_fields.eq(index).hide();
					}
				});
			}
		}).on('mouseenter', '.child', function() {
			$(this).prev().addClass('highlight');
		}).on('mouseleave', '.child', function() {
			$(this).prev().removeClass('highlight');
		});

		$(elem).DataTable({
//			language: {
//				url: "js/jquery-datatables-i18n/" + lmsSettings.language + ".json"
//			},
			responsive: {
				details: {
					display: $.fn.dataTable.Responsive.display.childRowImmediate,
					type: ''
				}
			},
			language: dataTablesLanguage,
			initComplete: function(settings, json) {
				$(elem).show();
			},
			dom: init.dom,
			stripeClasses: [],
			//deferRender: true,
			processing: true,
			stateDuration: lmsSettings.settingsTimeout,
			lengthMenu: [[ 10, 25, 50, 100, -1 ], [ 10, 25, 50, 100, $t('all') ]],
			displayStart: init.displayStart,
			searchCols: init.searchColumns,
			stateSave: init.stateSave,
			stateSaveProps: init.stateSaveProps,
			ordering: init.ordering,
			orderCellsTop: init.orderCellsTop,
			stateSaveParams: function(settings, data) {
				var api = new $.fn.dataTable.Api(settings);
				var stateSaveProps = api.init().stateSaveProps;
				if (!Array.isArray(stateSaveProps)) {
					return;
				}
				for (var property in data) {
					if (data.hasOwnProperty(property)) {
						if (property == "time" || stateSaveProps.indexOf(property) >= 0) {
							continue;
						}
						delete data[property];
					}
				}
			}
		});
	}

	dataTables.each(function() {
		var init = {};
		init.displayStart = $(this).attr('data-display-start');
		if (init.displayStart === undefined) {
			init.displayStart = 0;
		}
		init.searchColumns = $(this).attr('data-search-columns');
		if (init.searchColumns === undefined) {
			init.searchColumns = [];
		} else {
			init.searchColumns = JSON.parse(init.searchColumns);
		}
		init.stateSave = $(this).attr('data-state-save');
		init.stateSaveProps = true;
		if (init.stateSave === undefined) {
			init.stateSave = false;
		} else if (init.stateSave.match(/^\[(.+)\]$/)) {
			init.stateSaveProps = JSON.parse(RegExp.$1);
		} else {
			init.stateSave = true;
		}
		init.ordering = $(this).attr('data-ordering');
		if (init.ordering === undefined) {
			init.ordering = true;
		}
		init.orderCellsTop = $(this).attr('data-searching');
		if (init.orderCellsTop === undefined) {
			init.orderCellsTop = true;
		}
		init.dom = '<"top"<"lms-ui-datatable-toolbar"<"lms-ui-datatable-clear-settings"><"lms-ui-datatable-column-toggle">l>pf>rt<"bottom"i><"clear">';
		$(this).data('init', init);

		var trStyle = $(this).closest('tr').attr('style');
		if (trStyle !== undefined && trStyle.match(/display:\s*none/)) {
			return;
		}

		initDataTable(this);
	});

	init_titlebars('.lmsbox-titlebar');

	init_autolinker('.lms-ui-autolinker');

	$('.lms-ui-row-all-check').each(function() {
		$(this).click(function() {
			var checked = true;
			$.each($(this).find(':checkbox'), function(key, value) {
				var elem = $(value)[0];
				if (!key) {
					checked = !elem.checked;
				}
				elem.checked = checked;
			});
		});
		$(this).find('a').click(function(e) {
			e.stopPropagation();
		});
		$(this).find(':checkbox').click(function(e) {
			e.stopPropagation();
		});
	});

	$('.lms-ui-sortable-persistent').sortable({
		items: "> .lms-ui-sortable",
		//handle: ".lmsbox-titlebar",
		handle: ".lms-ui-sortable-handle",
		axis: "y",
		opacity: 0.9,
		update: function(event, ui) {
			data = {};
			data[$(this).attr('id') + '-order'] = $(this).sortable("toArray").join(';');
			savePersistentSettings(data);
		}
	});

	function selectableClickHandler(e) {
		if (!e.ctrlKey) {
			if (e.shiftKey) {
				var rows = $(this).parent().children('tr');
				var i = rows.index($(this).siblings('tr[data-prev-selected]')),
					j = rows.index(this);
				if (i > -1) {
					var selected = $(rows[i]).attr('data-prev-selected') == 'true' ? true : false;
					var start = Math.min(i, j);
					var stop = Math.max(i, j);
					for (i = start; i <= stop; i++) {
						if (selected) {
							$(rows[i]).addClass('ui-selecting');
						} else {
							$(rows[i]).removeClass('ui-selected');
						}
					}
				}
			} else {
				$(this).siblings('tr[data-prev-selected]').removeAttr('data-prev-selected');
				if ($(this).hasClass('ui-selected')) {
					$(this).removeClass('ui-selected');
					$(this).attr('data-prev-selected', false);
				} else {
					$(this).parents('table.lms-ui-selectable-draggable > tbody').children('tr').removeClass('ui-selected');
					$(this).addClass('ui-selecting');
					$(this).attr('data-prev-selected', true);
				}
			}
		} else {
			if ($(this).hasClass('ui-selected')) {
				$(this).removeClass('ui-selected');
			} else {
				$(this).addClass('ui-selecting');
			}
		}
		$(this).parents('table.lms-ui-selectable-draggable').data('ui-selectable')._mouseStop(null);
	}

	var draggable_options = {
		handle: "td",
		revert: "invalid",
		revertDuration: 300,
		helper: function() {
			var table = $(this).parents('table.lms-ui-selectable-draggable');
			if (!table.find('tr.ui-selected').length) {
				$(this).addClass('ui-selected');
			} else if (!$(this).hasClass('ui-selected')) {
				table.find('tr').removeClass('ui-selected');
				$(this).addClass('ui-selected');
			}
			table = table.clone();
			table.css('z-index', 100);
			table.find('tr:not(.ui-selected)').remove();
			table.find('tr').removeClass('ui-selected');
			return table;
		},
		start: function(event, ui) {
			$(this).parents('table.lms-ui-selectable-draggable').find('tr.ui-selected')
				.toggle();
		},
		stop: function(event, ui) {
			$(this).parents('table.lms-ui-selectable-draggable').find('tr.ui-selected:not(:visible)')
				.toggle();
		}
	};
	$('table.lms-ui-selectable-draggable > tbody > tr').draggable(draggable_options);

	var selectable_options = {
		filter: 'tbody > tr'
	};
	$('table.lms-ui-selectable-draggable').selectable(selectable_options);

	$('table.lms-ui-selectable-draggable > tbody > tr').click(selectableClickHandler);

	$('table.lms-ui-selectable-draggable:not(.lms-ui-not-droppable)').droppable({
		accept: function(draggable) {
			if (/lms-ui-droppable-([^\s]+)/.exec($(this).attr('class')) !== null) {
				var droppable = RegExp.$1;
				return $(draggable).parents('table.lms-ui-selectable-draggable')
					.hasClass('lms-ui-droppable-' + droppable);
			}
			return false;
		},
		classes: {
			"ui-droppable-hover": "lms-ui-selectable-draggable-hover",
			"ui-droppable-active": "lms-ui-selectable-draggable-active"
		},
		tolerance: "touch",
		drop: function(event, ui) {
			if (!ui.draggable.parents('table.lms-ui-selectable-draggable').is(this)) {
				var table = ui.draggable.parents('table.lms-ui-selectable-draggable');
				if (table.hasClass('lms-ui-datatable')) {
					$(this).DataTable().rows.add(table.DataTable().rows('.ui-selected').data()).draw();
					$(this).find('tbody > tr.ui-draggable').draggable('destroy');
					$(this).selectable('destroy');
					var rows = $(this).find('tbody > tr');
					rows.draggable(draggable_options);
					$(this).selectable(selectable_options);
					rows.unbind('click').click(selectableClickHandler);
					table.removeClass('ui-droppable-active').removeClass('lms-ui-selectable-draggable-active');
					table.DataTable().rows('.ui-selected').remove().draw();
				} else {
					var selected = ui.draggable.parent().find('.ui-selected');
					var handled = table.triggerHandler('lmsdrop', [ selected ]);
					if (handled === true) {
						selected.toggle().appendTo($(this));
					}
				}
			}
		}
	});

	function init_visual_editor(id) {
		tinymce.init({
			selector: '#' + id,
			init_instance_callback: function (ed) {
				var textarea = $(ed.settings.selector);
				if (textarea.hasClass('lms-ui-error') || textarea.hasClass('alert')) {
					textarea.siblings('.mce-tinymce').addClass('lms-ui-error');
				} else if (textarea.hasClass('lms-ui-warning')) {
					textarea.siblings('.mce-tinymce').addClass('lms-ui-warning');
				}
				if (elementsToInitiate > 0) {
					elementsToInitiate--;
					if (!elementsToInitiate) {
						show_pagecontent();
					}
				}
			},
			language: lmsSettings.language,
			language_url: lmsSettings.language == 'en' ? null : 'js/tinymce5/langs/' + lmsSettings.language + '.js',
			// TinyMCE 4
			skin_url: 'css/tinymce4',
			theme: "modern",
			plugins: "preview,autoresize,contextmenu,fullscreen,searchreplace,table,image,link,anchor,textcolor,autosave,paste",
			// TinyMCE 5
			//plugins: "preview,autoresize,fullscreen,searchreplace,table,image,link,anchor,autosave,paste",
			// #########
			toolbar1: 'formatselect | bold italic strikethrough forecolor backcolor | link anchor image ' +
				'| alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent ' +
				'| removeformat',
			image_advtab: true,
			height: 250,
			width: '100%',
			resize: 'both',
			branding: false,
			paste_data_images: true,
			relative_urls : false,
			remove_script_host : false,
			file_picker_callback: function(callback, value, meta) {
				if (meta.filetype == 'image') {
					$('#tinymce-image-upload').trigger('click');
					$('#tinymce-image-upload').on('change', function() {
						var file = this.files[0];
						var reader = new FileReader();
						reader.onload = function(e) {
							callback(e.target.result, {
								alt: ''
							});
						};
						reader.readAsDataURL(file);
					});
				}
			}
		});
	}

	function show_visual_editor(id) {
		var editor = tinymce.get(id);
		if (editor == null) {
			init_visual_editor(id);
		} else {
			if (editor.isHidden()) {
				editor.show();
			}
		}
	}

	function hide_visual_editor(id) {
		var editor = tinymce.get(id);
		if (editor != null) {
			editor.remove();
		}
	}

	function toggle_visual_editor(id) {
		var editor = tinymce.get(id);
		if (editor == null) {
			init_visual_editor(id);
		} else {
			editor.remove();
		}
	}

	var editors = $('textarea.lms-ui-wysiwyg-editor');
	if (editors.length) {
		editors.each(function() {
			var parent = $(this).parent();
			var textareaid = $(this).uniqueId().attr('id');
			var wysiwyg = $(this).attr('data-wysiwyg');
			var inputname;
			wysiwyg = (wysiwyg !== undefined && wysiwyg == 'true') || (wysiwyg === undefined && lmsSettings.wysiwygEditor);
			$(this).data('wysiwyg', wysiwyg);
			if ($(this).attr('name').match(/^([^\[]+)(\[[^\[]+\])$/i)) {
				inputname = RegExp.$1 + '[wysiwyg]' + RegExp.$2;
			} else {
				inputname = $(this).closest('form').attr('name') + '[wysiwyg]';
			}
			parent.append($('<div class="lms-ui-wysiwyg-editor"><label>' +
				'<input type="hidden" name="' + inputname + '" value="false">' +
				'<input type="checkbox" name="' + inputname +
				'" value="true"' + (wysiwyg ? ' checked' : '') + '>' + $t('visual editor') +
				'</label></div')).find('div.lms-ui-wysiwyg-editor').append(this);

			// it is required as textarea changed value is not propagated automatically to editor instance content
			$(this).change(function(e) {
				var editor = tinymce.get(textareaid);
				if (editor) {
					editor.setContent($(this).val());
				}
			});
			$('[name="' + inputname + '"]:checkbox', parent).click(function() {
				toggle_visual_editor(textareaid);
			});
			if (wysiwyg) {
				elementsToInitiate++;
				toggle_visual_editor(textareaid);
			}
			$(this).on('lms:visual_editor_change_required', function(e, data) {
				if (data.ifShow) {
					show_visual_editor($(this).attr('id'));
					$('[name="' + inputname + '"]').prop('checked', true);
				} else {
					hide_visual_editor($(this).attr('id'));
					$('[name="' + inputname + '"]').prop('checked', false);
				}
			});
		});

		editors.filter('[data-wysiwyg="true"]').each(function() {
			init_visual_editor($(this).attr('id'));
		});
	}

	if (!elementsToInitiate) {
		show_pagecontent();
	}

	// quick search input auto show/hide support
	var qs_fields = $('.lms-ui-quick-search-field');

	qs_fields.each(function(index, field) {
		var input = $(field).find('input');
		new AutoSuggest(
			$(field).closest('form').get(0),
			input.get(0),
			'?m=quicksearch&api=1&ajax=1&mode=' + $(field).attr('data-mode') + '&what=',
			lmsSettings.quickSearchAutoSubmit
		)
	});

	qs_fields.find('input').on('click', function() {
		qs_fields.find('input').removeClass('lms-ui-quick-search-active');
		$(this).addClass('lms-ui-quick-search-active').focus();
	}).siblings('i').on('click', function() {
		qs_fields.find('input').removeClass('lms-ui-quick-search-active');
		$(this).siblings('input').addClass('lms-ui-quick-search-active').focus();
	});
	if (!location.hash.length) {
		qs_fields.first().find('input').addClass('lms-ui-quick-search-active').focus();
	}

	$(document).keydown(function(e) {
		var new_field;
		if (e.keyCode != 9)
			return;
		var qs_field = $(e.target).closest('.lms-ui-quick-search-field');
		if (qs_fields.index(qs_field) == -1)
			return;
		if (e.shiftKey) {
			new_field = qs_field;
			do {
				new_field = new_field.prev('.lms-ui-quick-search-field');
			} while (new_field.length && !new_field.is(':visible'));
			if (!new_field.length) {
				new_field = qs_fields.last();
				while (new_field.length && !new_field.is(':visible')) {
					new_field = new_field.prev('.lms-ui-quick-search-field');
				}
			}
		} else {
			new_field = qs_field;
			do {
				new_field = new_field.next('.lms-ui-quick-search-field');
			} while (new_field.length && !new_field.is(':visible'));
			if (!new_field.length) {
				new_field = qs_fields.first();
				while (new_field.length && !new_field.is(':visible')) {
					new_field = new_field.next('.lms-ui-quick-search-field');
				}
			}
		}
		$(e.target).removeClass('lms-ui-quick-search-active');
		new_field.find('input').addClass('lms-ui-quick-search-active').focus();
		e.preventDefault();
	});

/*
	var matches = navigator.appVersion.match(/(chrome\/[0-9]+)/i);
	if (matches && typeof(matches) === 'object' && parseInt(matches[0].split('/')[1]) >= 69) {
		$('[autocomplete="off"]').attr('autocomplete', 'new-password');
	}
*/

	$(document).click(function(e) {
		if ($(e.target).is('.lms-ui-button') || $(e.target).closest('.lms-ui-suggestion-item').length) {
			return;
		}
		var list_container = $(e.target).closest('.lms-ui-list-container');

		if (list_container.length) {
			$('.lms-ui-list-suggestion-container input').each(function() {
				if (!$(this).closest('.lms-ui-list-container').is(list_container)) {
					// hide search input
					$(this).hide().prev().show().closest('.lms-ui-list-container').css('flex-direction', 'row')
						.find('ul').css({
							'margin-block-start': '0',
							'margin-block-end': '0'
						});
				}
			});

			if ($(e.target).is('.lms-ui-list-unlink')) {
				if (list_container.is('.disabled')) {
					return;
				}
				var unlink_button = $(e.target);
				var list_elem = $(unlink_button).closest('li');
				var list = $(unlink_button).closest('.lms-ui-list');
				list_elem.remove();
				list.toggle(list.find('li').length > 0);
				list_container.find('.lms-ui-list-suggestion').focus();

				list_container.trigger('lms:list_updated', { list: list.find('li') });
			}
			return;
		}
		$('.lms-ui-list-suggestion-container input').each(function() {
			$(this).keypress(function(e) {
				if (e.key == 'Enter') {
					e.preventDefault();
					e.stopPropagation();
				}
			});

			// hide search input
			$(this).hide().prev().show().closest('.lms-ui-list-container').css('flex-direction', 'row')
				.find('ul').css({
					'margin-block-start': '0',
					'margin-block-end': '0'
				});
		});
	});

	$('.lms-ui-list-suggestion-container a').click(function(e) {
		var list_container = $(e.target).closest('.lms-ui-list-container');
		if (list_container.is('.disabled')) {
			return;
		}

		// show search input
		$(this).hide().next().show().focus().closest('.lms-ui-list-container').css('flex-direction', 'column')
			.find('ul').css({
				'margin-block-start': '0.5em',
				'margin-block-end': '0.5em'
			});
	});

	// disables jquery-ui tooltip after any key press in ui control
	$(document).on('keypress', "[data-tooltip]", function() {
		$(this).tooltip('disable');
	});

	$('button[type="submit"]').each(function() {
		var form = $(this).attr('form') ? $('form#' + $(this).attr('form')) : $(this).closest('form');
		var button = $(this);
		form.submit(function() {
			button.attr('disabled', 'disabled');
		}).on('lms:form_validation_failed', function() {
			button.removeAttr('disabled');
		});
	});

	initAutoGrow('.lms-ui-autogrow');
});

function restoreStringSortable(sortable, value) {
	$.each(value.split(';'), function(key, value) {
		if (value.length && value.match(/^[a-z0-9\-]+$/i)) {
			$('#' + value).appendTo('#' + sortable);
		}
	});
}

function restoreSortable(sortable, value) {
	switch (typeof(value)) {
		case 'string':
			if (!value.length || value == 'null') {
				return;
			}
			restoreStringSortable(sortable, value);
			break;
		case 'object':
			$.each(value, function(key, item) {
				restoreStringSortable(key, item);
			});
			break;
		default:
			return;
	}
}
