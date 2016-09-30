/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2016 LMS Developers
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
	$.ajax('?m=persistentsetting', {
		async: true,
		method: 'POST',
		data: data,
		dataType: 'json',
		error: function(jqXHR, textStatus, errorThrown) {
			if (errorThrown != 'abort') {
				alert(lmsMessages.persistentSettingSaveError + ' ' + errorThrown);
			}
		}
	});
}

var dataTablesLanguage = {};
$.ajax("img/jquery-datatables-i18n/" + lmsSettings.language + ".json", {
	method: "GET",
	success: function(data, textStatus, jqXHR) {
		dataTablesLanguage = data;
	}
});

$(function() {
	var autocomplete = "off";

	$('.calendar').datepicker({
		dateFormat: "yy/mm/dd",
		changeYear: true,
		beforeShow: function(input, inst) {
			if ($(input).hasClass('ui-tooltip')) {
				$(input).tooltip('disable');
				$(this).data('tooltip', input);
			}
		},
		onClose: function(dateText, inst) {
			if ($(this).data('tooltip') !== undefined) {
				$(this).tooltip('enable');
			}
		}
	})
	.attr("autocomplete", autocomplete);

	$.datetimepicker.setLocale(lmsSettings.language);
	$('.calendar-time').datetimepicker({
		step: 30,
		closeOnDateSelect: true
	})
	.attr("autocomplete", autocomplete);

	$('select.lms-ui-multiselect').each(function() {
		multiselect({
			id: $(this).uniqueId().attr('id'),
			defaultValue: $(this).attr('data-default-value'),
			type: $(this).attr('data-type')
		});
	});

	$('[title]').each(function() {
		$(this).one('mouseenter', function() {
			tooltipClass = '';
			if ($(this).hasClass('alert')) {
				tooltipClass += ' alert';
				if ($(this).hasClass('bold')) {
					tooltipClass += ' bold';
				}
			} else if ($(this).hasClass('bold')) {
				tooltipClass += 'bold';
			}

			var title = $(this).attr('title');
			$(this).attr('data-tooltip', title).removeAttr('title');
			$(this).tooltip({
				items: '[data-tooltip]',
				content: title,
				show: { delay: 500 },
				track: true,
				classes: {
					'ui-tooltip': tooltipClass
				},
				create: function() {
					$(this).tooltip('open');
				}
			});
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
		{ class: 'lms-ui-tooltip-netdevlist', url: '?m=ewxdevlist&id='}
	].forEach(function(popup) {
		$('.' + popup.class).tooltip({
			items: '.' + popup.class,
			track: true,
			tooltipClass: popup.class,
			content: function(callback) {
				var elem = $(this);
				var resourceid = elem.attr('data-resourceid');
				$.ajax(popup.url + resourceid, {
					async: true,
					success: function(data) {
						callback(data);
					}
				});
			}
		});
	});

	var documentviews = $('.documentview');

	documentviews.tooltip({
		track: true,
		items: '.documentview-image',
		tooltipClass: 'documentview',
		content: function() {
			var href = $(this).attr('href');
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
				lmsMessages.noAudioSupport + '</audio>');
			var audioelem = dialog.find('audio').get(0);
			audioelem.currentTime = 0;
			audioelem.play();
		} else if ($(this).hasClass('documentview-video')) {
			dialog.html('<video src="' + url + '" style="width: 100%;" controls preload="none">' +
				lmsMessages.noVideoSupport + '</video>');
			var videoelem = dialog.find('video').get(0);
			videoelem.currentTime = 0;
			videoelem.play();
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
				.dialog('option', 'width', '70%');
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

	var tbodies = $('tbody.lms-ui-multi-check');
	$.each(tbodies, function(index, elem) {
		var tbody = $(elem);
		var checkboxes = tbody.parent().find(':checkbox');
		var allcheckboxes = checkboxes.filter('.lms-ui-multi-check');

		var checkall = checkboxes.filter('.lms-ui-multi-check-all');
		if (!checkall.length) {
			checkall = tbody.siblings('tfoot').filter('.lms-ui-multi-check-all');
		}
		if (checkall.length) {
			checkall.parent().addClass('lms-ui-multi-check-all');
			checkall.click(function(e) {
				allcheckboxes.each(function(index, elem) {
					this.checked = checkall.checked;
				});
			});
			checkall = checkall.get(0);
		} else {
			checkall = null;
		}

		function checkElements(checkbox) {
			var i = allcheckboxes.index(allcheckboxes.filter('[data-prev-checked]')),
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
				if (allcheckboxes.filter(':checked').length == allcheckboxes.length) {
					checkall.checked = true;
				} else if (allcheckboxes.filter(':not(:checked)').length == allcheckboxes.length) {
					checkall.checked = false;
				}
			}
		}

		$.each(allcheckboxes, function(index, elem) {
			var checkbox = $(elem)[0];
			var row = $(checkbox.closest('tr'));
			row.click(function(e) {
				if (e.shiftKey) {
					checkElements(checkbox);
			} 	else {
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
			row.find('a').click(function(e) {
				e.stopPropagation();
			});
		});
	});

	var dataTables = $('.lms-ui-datatable');
	var elementsToInitiate = 0;
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
			var tr = $('thead tr', elem).clone();
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
							content = '<select><option value="">'  + lmsMessages.selectionAny + '</option>';
							selectValues.sort().forEach(function(value, index) {
								content += '<option value="' + value + '">' + value + '</option>';
							});
							content += '</select>';
						}
					} else {
						content = '<input type="search" placeholder="' + lmsMessages.search + '">';
					}
				} else {
					content = '';
				}
				$(th).html(content);
			});
			$('thead input', elem).on('keyup change search', function() {
				$(elem).DataTable().column($(this).parent().index() + ':visible')
					.search(this.value).draw();
			});
			$('thead select', elem).on('change', function() {
				var value = this.value;
				$(elem).DataTable().column($(this).parent().index() + ':visible')
					.search(value).draw();
			});
		}

		$(elem).on('init.dt', function(e, settings) {
			var api = new $.fn.dataTable.Api(settings);
			var state = api.state.loaded();
			if (state && columnSearch) {
				$('thead input[type="search"]', elem).each(function(index, input) {
					var column = $(input).parent().index();
					$(input).attr('value', state.columns[column].search.search);
				});
				$('thead select', elem).each(function(index, select) {
					var column = $(select).parent().index();
					$(select).val(state.columns[column].search.search);
				});
			}

			if (columnToggle) {
				var toggle = $(elem).siblings('div.top').find('div.lms-ui-datatable-column-toggle');
				var content = '<form name="' + $(elem).attr('id') + '" class="column-toggle">' +
					'<select class="column-toggle" class="lms-ui-multiselect" name="' +
					$(elem).attr('id') + '-column-toggle[]" multiple' +
					' title="' + lmsMessages.columnVisibility + '">';
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
				multiselect({
					id: multiselectId,
					defaultValue: null,
					icon: 'img/settings.gif',
					type: 'tiny'
				});
				toggle.find('#' + multiselectId).on('itemclick', function(e, data) {
					api.column(data.index).visible(data.checked);
				});
			}

			var clearSettings = $(elem).siblings('div.top').find('div.lms-ui-datatable-clear-settings');
			clearSettings.html('<img src="img/delete.gif" title="' + lmsMessages.clearSettings + '">');
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
				$(elem).parent().find('div.lms-ui-multiselectlayer li:not(.selected)').addClass('selected')
					.find(':checkbox').prop('checked', true);

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
					$('div#lms-ui-spinner').hide();
					$('div#pagecontent').show();
				}
			}
		});

		$(elem).DataTable({
//			language: {
//				url: "img/jquery-datatables-i18n/" + lmsSettings.language + ".json"
//			},
			language: dataTablesLanguage,
			initComplete: function(settings, json) {
				$(elem).show();
			},
			dom: init.dom,
			stripeClasses: [],
			//deferRender: true,
			processing: true,
			stateDuration: lmsSettings.settingsTimeout,
			lengthMenu: [[ 10, 25, 50, 100, -1 ], [ 10, 25, 50, 100, lmsMessages.all ]],
			displayStart: init.displayStart,
			stateSave: init.stateSave,
			ordering: init.ordering,
			orderCellsTop: init.orderCellsTop
		})
		.on('mouseenter', 'tbody > tr', function() {
			$(this).siblings('tr').removeClass('highlight');
			$(this).addClass('highlight');
		});

	}

	dataTables.each(function() {
		var init = {};
		init.displayStart = $(this).attr('data-display-start');
		if (init.displayStart === undefined) {
			init.displayStart = 0;
		}
		init.stateSave = $(this).attr('data-state-save');
		if (init.stateSave === undefined) {
			init.stateSave = false;
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

	$('.lmsbox-titlebar').each(function() {
		$(this).prop('onclick', null);
		$(this).click(function() {
			var elemid = $(this).attr('data-lmsbox-content');
			showOrHide(elemid);
			$('#' + elemid).find('.lms-ui-datatable').each(function() {
				if (!$.fn.dataTable.isDataTable(this)) {
					initDataTable(this);
				}
			});
		});
		$(this).find('td a,td :input').click(function(e) {
			e.stopPropagation();
		});
	});

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
		handle: "tr.lmsbox-titlebar",
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

	var editors = $('textarea.lms-ui-wysiwyg-editor');
	if (editors.length) {
		tinyMCE.init({
			setup : function(ed) {
				ed.onBeforeSetContent.add(function(ed, o) {
					if (o.initial) {
						o.content = o.content.replace(/\r?\n/g, '<br />');
					}
				});
				ed.onInit.add(function(ed) {
					if (elementsToInitiate > 0) {
						elementsToInitiate--;
						if (!elementsToInitiate) {
							$('div#lms-ui-spinner').hide();
							$('div#pagecontent').show();
						}
					}
				});
			},
			mode: "none",
			language: lmsSettings.language,
			theme: "advanced",
			plugins: "advimage,advlink,preview,autoresize,contextmenu,fullscreen,inlinepopups,searchreplace,style,table",
			theme_advanced_buttons1_add: "|,forecolor,backcolor,|,styleprops",
			theme_advanced_buttons2_add: "|,preview,fullscreen",
			theme_advanced_buttons3_add: "|,search,replace,|,tablecontrols",
			//theme_advanced_toolbar_location: "external",
			theme_advanced_toolbar_align: "left",
			//theme_advanced_statusbar_location: "bottom",
			theme_advanced_statusbar_location: "none",
			theme_advanced_resizing: true,
			autoresize_max_height: 250,
			dialog_type: "window",
			skin: "lms",
		});

		editors.each(function() {
			function toggle_visual_editor(id) {
				if (tinyMCE.get(id)) {
					tinyMCE.execCommand('mceToggleEditor', false, id);
				} else {
					tinyMCE.execCommand('mceAddControl', true, id);
				}
			}

			var parent = $(this).parent();
			var textareaid = $(this).uniqueId().attr('id');
			var wysiwyg = $(this).attr('data-wysiwyg');
			wysiwyg = (wysiwyg !== undefined && wysiwyg == '1') || (wysiwyg === undefined && lmsSettings.wysiwygEditor);
			var textarea = parent.html();
			if ($(this).attr('name').match(/^([^\[]+)(\[[^\[]+\])$/i)) {
				inputname = RegExp.$1 + '[wysiwyg]' + RegExp.$2;
			} else {
				inputname = $(this).closest('form').attr('name') + '[wysiwyg]';
			}
			$(this).replaceWith($('<TABLE/>').addClass('lmsbox-inner').html('<TBODY><TR><TD>' +
				'<label><input type="checkbox" name="' + inputname + '" value="1"' + (wysiwyg ? ' checked' : '') + '>' +
				lmsMessages.visualEditor + '</label></TD></TR>' +
				'<TR><TD>' + textarea + '</TD></TR>' +
				'</TBODY>'));
			$('[name="' + inputname + '"]:checkbox', parent).click(function() {
				toggle_visual_editor(textareaid);
			});
			if (wysiwyg) {
				elementsToInitiate++;
				toggle_visual_editor(textareaid);
			}
		});
	}

	if (!elementsToInitiate) {
		$('div#lms-ui-spinner').hide();
		$('div#pagecontent').show();
	}

	// quick search input auto show/hide support
	var qs_inputs = $('input.lms-ui-quick-search');
	var qs_timers = [];

	qs_inputs.each(function(index, input) {
		new AutoSuggest($(input).closest('form').get(0), input,
			'?m=quicksearch&ajax=1&mode=' + $(input).attr('data-mode') + '&what=', lmsSettings.quickSearchAutoSubmit);
	});

	function clearTimers() {
		$.each(qs_timers, function(index, timer) {
			clearTimeout(timer);
		});
		qs_timers = [];
	}

	// this = img
	function onMouseEnter() {
		clearTimers();
		var input = $(this).next().show().focus();
		qs_inputs.each(function() {
			if (!input.is(this)) {
				$(this).hide();
			}
			input.prev().unbind('mouseenter')
				.one('mouseenter', onMouseEnter);
		});
	}

	qs_inputs.hide().mouseleave(function() {
		var input = $(this);
		qs_timers.push(setTimeout(function() {
			input.hide();
		}, 500));
	}).mouseenter(function() {
		clearTimers();
	}).focusout(function() {
		var input = $(this);
		qs_timers.push(setTimeout(function() {
			input.hide();
		}, 500));
	}).prev().one('mouseenter', onMouseEnter)
		.mouseleave(function() {
			var input = $(this).next();
			qs_timers.push(setTimeout(function() {
				input.hide();
			}, 500));
		});

	$(document).keydown(function(e) {
		if (e.keyCode != 9)
			return;
		var input = $(e.target);
		if (qs_inputs.index(input) == -1)
			return;
		if (e.shiftKey) {
			newInput = input.prev().prev('input.lms-ui-quick-search');
			if (!newInput.length) {
				newInput = qs_inputs.last();
			}
		} else {
			newInput = input.next().next('input.lms-ui-quick-search');
			if (!newInput.length) {
				newInput = qs_inputs.first();
			}
		}
		input.hide();
		clearTimers();
		newInput.show().focus();
		e.preventDefault();
	});
});

function restoreSortable(sortable, value) {
	if (typeof value != 'string' || !value.length || value == 'null') {
		return;
	}
	$.each(value.split(';'), function(key, value) {
		if (value.length && value.match(/^[a-z0-9\-]+$/i)) {
			$('#' + value).appendTo('#' + sortable);
		}
	});
}
