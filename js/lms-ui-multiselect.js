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

function closeAllMultiSelectPopups(e) {
	var popup = $('.lms-ui-multiselect-popup:visible');
	if (!popup.length) {
		return;
	}
	var container = popup.closest('.lms-ui-multiselect-container');
	var old_element = container.find('select');
	var elem = $(e.target);
	if (!elem.is(old_element) &&
		!elem.is('.lms-ui-multiselect-launcher') &&
		!elem.closest('.lms-ui-multiselect-launcher').length &&
		!elem.closest('.lms-ui-multiselect-popup').length) {
		popup.hide();
		popup.removeClass('fullscreen-popup');
		disableFullScreenPopup();
		container.removeClass('open');
		old_element.trigger('lms:multiselect:change');
	}
}

// hide combobox after click out of the window
$(document).click(function(e) {
	closeAllMultiSelectPopups(e);
});

$(document).keydown(function(e) {
	var popup = $('.lms-ui-multiselect-popup:visible');
	if (!popup.length) {
		return;
	}

	var container = popup.closest('.lms-ui-multiselect-container');
	var launcher = container.find('.lms-ui-multiselect-launcher');
	var old_element = container.find('select');
	var ul = container.find('.lms-ui-multiselect-popup-list');
	var checkall = container.find('.lms-ui-multiselect-popup-checkall');
	var li;

	switch (e.key) {
		case 'Escape':
			e.preventDefault();
			$(popup).hide();
			container.removeClass('open');
			launcher.focus();
			$(old_element).trigger('lms:multiselect:change');
			break;
		case 'ArrowDown':
			li = $('input:focus', ul).closest('li');
			do {
				li = li.next();
			} while (li.length && li.is('.disabled'));
			$('li', ul).removeClass('active');
			if (li.length) {
				li.addClass('active').find('input').focus();
			} else {
				li = ul.find('li:not(.disabled)').first();
				li.addClass('active').find('input').focus();
			}
			e.preventDefault();
			break;
		case 'ArrowUp':
			li = $('input:focus', ul).closest('li');
			do {
				li = li.prev();
			} while (li.length && li.is('.disabled'));
			$('li', ul).removeClass('active');
			if (li.length) {
				li.addClass('active').find('input').focus();
			} else {
				li = ul.find('li:not(.disabled)').last();
				li.addClass('active').find('input').focus();
			}
			e.preventDefault();
			break;
		case 'a':
		case 'A':
			if (e.ctrlKey && checkall) {
				checkall.click();
			}
			e.preventDefault();
			break;
	}
});

$(window).resize(function() {
	var popup = $('.lms-ui-multiselect-popup:visible');
	if (!popup.length) {
		return;
	}

	var container = popup.closest('.lms-ui-multiselect-container');
	var launcher = container.find('.lms-ui-multiselect-launcher');
	if (parseInt($(this).outerWidth()) >= 800) {
		disableFullScreenPopup();
		popup.position({
			my: "left top",
			at: container.is('.tiny') || container.is('.bottom') ? 'left bottom' : 'right top',
			of: launcher
		});
	} else {
		enableFullScreenPopup();
		popup.css({
			'left': '',
			'top': ''
		});
	}
});

function multiselect(options) {
	var multiselect = this;
	var elemid = options.id;
	var def = typeof options.defaultValue !== 'undefined' ? options.defaultValue : '';
	var shorten_to_def = typeof options.shortenToDefaultValue === 'undefined' ||
		options.shortenToDefaultValue == 'true' ? true : false;
	var popupTitle = typeof options.popupTitle !== 'undefined' && options.popupTitle ? options.popupTitle : $t('Select options');
	var tiny = typeof options.type !== 'undefined' && options.type == 'tiny';
	var bottom = typeof options.bottom !== 'undefined' && options.bottom;
	var button = typeof options.button !== 'undefined' && options.button;
	var clearButton = typeof options.clearButton === 'undefined' || options.clearButton == 'true' ? true : false;
	var icon = typeof options.icon !== 'undefined' ? options.icon : 'img/settings.gif';
	var label = typeof options.label !== 'undefined' ? options.label : '';
	var separator = typeof options.separator !== 'undefined' ? options.separator : ', ';
	var maxVisible = typeof options.maxVisible !== 'undefined' ? parseInt(options.maxVisible) : 0;
	var substMessage = typeof options.substMessage !== 'undefined' ? options.substMessage
		: '- $a options selected -';
	var tooltipMessage = typeof options.tooltipMessage !== 'undefined' ? options.tooltipMessage : '';

	var old_element = $('#' + elemid);
	var form = (old_element.attr('form') ? $('#' + old_element.attr('form')) : old_element.closest('form'));

	if (!old_element.length || !form.length)
		return 0;

	var old_class = $(old_element).removeClass('lms-ui-multiselect').attr('class');
	var selection_group = $(old_element).hasClass('lms-ui-multiselect-selection-group');

	var container = $('<div class="lms-ui-multiselect-container' + (tiny ? ' tiny' : '') +
		(bottom ? ' bottom' : '') +
		(old_class && old_class.length ? ' ' + old_class : '') + '"/>');
	var launcher = $('<div class="lms-ui-multiselect-launcher" title="' + old_element.attr('title') + '" tabindex="0"/>')
		.attr('style', old_element.attr('style')).appendTo(container);

	if (tiny) {
		if (button) {
			launcher.append(button);
		} else {
			launcher.html(icon.match("img\/", icon) ? '<img src="' + icon + '">' + (label ? '&nbsp' + label : '') : '<i class="' + icon + '"/>');
		}
	} else {
		$('<i class="lms-ui-multiselect-launcher-toggle lms-ui-icon-customisation"></i>' +
			(clearButton ? '<i class="lms-ui-multiselect-clear-button lms-ui-icon-hide"></i>' : '') +
			'<div class="lms-ui-multiselect-launcher-label"></div>')
			.appendTo(launcher);
	}

	// save onitemclick event handler
	var itemclick = old_element.prop('onitemclick');
	if (typeof(itemclick) == 'function') {
		old_element.on('lms:itemclick', itemclick);
	}

	// replace select with multiselect
	old_element.replaceWith(container);

	container.closest('label').click(function(e) {
		if ($(this).find('.lms-ui-multiselect-container.open').length) {
			e.preventDefault();
			return;
		}
		launcher.click();
	});

	// create multiselect list div (hidden)
	var popup = $('<div class="lms-ui-multiselect-popup lms-ui-popup"></div>').hide().appendTo(container);
	$('<input type="checkbox" class="lms-ui-multiselect-label-workaround">').appendTo(popup);
	$('<div class="lms-ui-multiselect-popup-titlebar"><div class="lms-ui-multiselect-popup-title">' + popupTitle +
		'</div><i class="lms-ui-icon-hide close-button"></i></div>').appendTo(popup);
	$('<ul class="lms-ui-multiselect-popup-list"></ul>').appendTo(popup);

	// append original select element to container
	container.append(old_element);

	container.data('multiselect-object', this);
	old_element.data('multiselect-object', this);

	var ul = popup.find('.lms-ui-multiselect-popup-list');

	var new_selected;
	var old_selected;
	var all_items;
	var all_enabled_items;
	var all_checkboxes;
	var all_enabled_checkboxes;
	var checkall = null;

	this.generateSelectedString = function() {
		var selected = [];
		old_element.find('option').removeAttr('selected').prop('selected', false);
		$('input:checked', ul).each(function() {
			selected.push($(this).next().html());
			old_element.find('option[value="' + $(this).val() + '"]').attr('selected', 'selected').prop('selected', true);
		});
		if (selected.length) {
			if (def && shorten_to_def && def.length && selected.length == $('input', ul).length) {
				selected = [ def ];
			}
		} else {
			selected.push(def);
		}
		var selected_string = selected.join(separator);
		if (tiny) {
			if (tooltipMessage.length) {
				launcher.removeAttr('data-tooltip').attr('title', $t(tooltipMessage, selected_string.length ?
					selected_string : '-'));
			}
		} else {
			launcher.removeAttr('data-tooltip');
			if (maxVisible && selected.length > maxVisible) {
				launcher.find('.lms-ui-multiselect-launcher-label').html($t(substMessage, selected.length));
				launcher.attr('title', selected_string);
			} else {
				launcher.find('.lms-ui-multiselect-launcher-label').html(selected_string);
				launcher.attr('title', '');
			}
			if (popup.is(':visible')) {
				setTimeout(function() {
					if (parseInt($(window).outerWidth()) >= 800) {
						popup.position({
							my: 'left top',
							at: tiny || bottom ? 'left bottom' : 'right top',
							of: launcher
						});
					} else {
						enableFullScreenPopup();
						popup.css({
							'left': '',
							'top': ''
						});
					}
				}, 1);
			}
		}
		return selected_string;
	}

	function updateCheckAll() {
		var checkboxes = all_enabled_items.filter(':not(.exclusive)').filter('.visible').find(':checkbox');
		popup.find('.checkall').prop('checked', checkboxes.filter(':checked').length == checkboxes.length);
	}

	function buildPopupList() {
		var list = '';
		$('option', old_element).each(function () {
			var exclusive = $(this).attr('data-exclusive');
			var selected = $(this).is(':selected');
			var disabled = $(this).is(':disabled');
			var class_name = 'visible' + (exclusive === '' ? ' exclusive' : '');

			var data = '';
			$.each($(this).data(), function (key, value) {
				if (!data.length) {
					data = ' ';
				}
				data += 'data-' + key + '="' + value + '"';
			});

			list += '<li class="' + class_name + (selected ? ' selected' : '') +
				(disabled ? ' blend disabled' : '') + '"' + data + '>';

			list += '<input type="checkbox" value="' + $(this).val() + '" class="' + class_name +
				'"' + (selected ? ' checked' : '') + (disabled ? ' disabled' : '') + '/>';

			var text = $(this).attr('data-html-content');
			if (!text) {
				text = $(this).text();
			}
			list += '<span>' + text.trim() + '</span>';

			list += '</li>';
		});

		ul.html(list);

		all_items = ul.find('li');
		all_enabled_items = all_items.filter(':not(.disabled)');
		all_enabled_checkboxes = all_enabled_items.find(':checkbox');
	}

	function popupListItemClickHandler(e) {
		var item = $(this);
		var checkbox = item.find('input[type="checkbox"]');
		item.toggleClass('selected');

		if (!$(e.target).is('input')) {
			checkbox.prop('checked', !checkbox.prop('checked'));
		}
		if (item.is('.exclusive')) {
			all_items.not(this).removeClass('selected').find(':checkbox').prop('checked', false);
		} else {
			all_items.filter('.exclusive').removeClass('selected').find(':checkbox').prop('checked', false);
		}
		if (e.shiftKey && !all_enabled_items.filter('.exclusive[data-prev-checked]').length) {
			checkElements(this);
		} else {
			all_enabled_items.filter('[data-prev-checked]').removeAttr('data-prev-checked');
			$(this).attr('data-prev-checked', $(this).is('.selected'));
		}

		new_selected = multiselect.generateSelectedString();

		updateCheckAll();

		old_element.trigger('lms:multiselect:itemclick', {
			index: $(this).index(),
			value: checkbox.val(),
			checked: checkbox.is(':checked')
		});

		var checkboxes = all_enabled_items.filter(':not(.exclusive)').filter('.visible').find(':checkbox');
		old_element.trigger('lms:multiselect:checkall', {
			allChecked: checkboxes.filter(':checked').length == checkboxes.length
		});

		e.stopPropagation();
	}

	function popupListItemMouseEnterHandler() {
		$(this).addClass('active').find('input').focus().end().siblings('li').not(this).removeClass('active');
	}

	function popupListItemMouseLeaveHandler() {
		$(this).removeClass('active');
	}

	buildPopupList();

	// add some mouse/key events handlers
	ul.on('click', 'li:not(.disabled)', popupListItemClickHandler)
		.on('mouseenter', 'li:not(.disabled)', popupListItemMouseEnterHandler)
		.on('mouseleave', 'li:not(.disabled)', popupListItemMouseLeaveHandler);

	function checkAllElements() {
		var checked = ul.parent().find('.checkall').prop('checked');
		all_enabled_checkboxes.filter('.exclusive').prop('checked', false);
		all_enabled_checkboxes.filter(':not(.exclusive)').each(function() {
			var li = $(this).closest('li');
			if (li.is('.visible')) {
				if (checked) {
					li.addClass('selected');
				} else {
					li.removeClass('selected');
				}
				$(this).prop('checked', checked);
			} else {
				li.removeClass('selected');
				$(this).prop('checked', false);
			}
		});
		new_selected = multiselect.generateSelectedString();
	}

	new_selected = this.generateSelectedString();
	old_selected = new_selected;
	if (!tiny || selection_group) {
		checkall = $('<div class="lms-ui-multiselect-popup-checkall"></div>').appendTo(popup);
		$('<input type="checkbox" class="checkall" value="1"><span>' + $t('check all<!items>') + '</span>').appendTo(checkall);

		updateCheckAll();

		$(checkall).click(function(e) {
			if (!all_items.filter(':visible').length) {
				return;
			}
			if (!$(e.target).is('input.checkall')) {
				var checkbox = $('.checkall', this);
				checkbox.prop('checked', !checkbox.prop('checked'))
			}

			checkAllElements();

			container.trigger('lms:multiselect:checkall', {
				allChecked: $('.checkall', this).prop('checked')
			});

			e.stopPropagation();
		});
	}

	launcher.find('.lms-ui-multiselect-clear-button').click(function(e) {
		closeAllMultiSelectPopups($.Event({
			type: 'click',
			target: document
		}));

		multiselect.updateSelection([]);

		updateCheckAll();

		if (new_selected != old_selected) {
			old_element.trigger('change');
		}
		old_selected = new_selected;

		e.preventDefault();
		e.stopPropagation();
	});

	// add some mouse/key event handlers
	launcher.on('click keydown', function(e) {
		if (e.type == 'keydown') {
			switch (e.key) {
				case 'Enter':
				case ' ':
				case 'Escape':
					break;
				default:
					return;
			}
			e.preventDefault();
		}
		if (!popup.is(':visible') && (e.type != 'keydown' || e.key != 'Escape')) {
			if (e.type == 'click') {
				closeAllMultiSelectPopups($.Event({
					type: 'click',
					target: document
				}));
			}

			setTimeout(function() {
				popup.show();
				popup.addClass('fullscreen-popup');
				container.addClass('open');
				if (parseInt($(window).outerWidth()) >= 800) {
					popup.position({
						my: 'left top',
						at: tiny || bottom ? 'left bottom' : 'right top',
						of: launcher
					});
				} else {
					enableFullScreenPopup();
					popup.css({
						'left': '',
						'top': ''
					});
				}
				all_items.removeClass('active');
				all_enabled_items.first().addClass('active').find('input').focus();
			}, 1);
			e.stopPropagation();
		} else {
			popup.hide();
			popup.removeClass('fullscreen-popup');
			container.removeClass('open');
			disableFullScreenPopup();
			if (new_selected != old_selected) {
				old_element.trigger('change');
			}
			old_selected = new_selected;
			e.stopPropagation();
			e.preventDefault();
		}
	});

	popup.find('.close-button').click(function(e) {
		popup.hide();
		popup.removeClass('fullscreen-popup');
		container.removeClass('open');
		launcher.focus();
		if (new_selected != old_selected) {
			old_element.trigger('change');
		}
		old_selected = new_selected;
		disableFullScreenPopup();
		e.preventDefault();
		e.stopPropagation();
	});

	function checkElements(item) {
		var prev_checked_item = all_items.filter('[data-prev-checked]');
		var i = all_items.index(all_items.filter('[data-prev-checked]')),
			j = all_items.index(item);
		var one_item;
		if (i > -1) {
			var checked = prev_checked_item.attr('data-prev-checked') == 'true' ? true : false;

			var start = Math.min(i, j);
			var stop = Math.max(i, j);
			for (i = start; i <= stop; i++) {
				one_item = $(all_items[i]);
				if (one_item.is('.disabled')) {
					continue;
				}
				var optionValue = '';
				if (/<span>(.*?)<\/span>/i.exec(one_item.get(0).innerHTML) !== null)
					optionValue = RegExp.$1;

				if (checked) {
					one_item.addClass('selected');
				} else {
					one_item.removeClass('selected');
				}
				one_item.find(':checkbox').prop('checked', checked);
			}
		}

		updateCheckAll();
	}

	old_element.on('lms:multiselect:change', function() {
		if (new_selected != old_selected) {
			$(this).trigger('change');
		}
		old_selected = new_selected;
		disableFullScreenPopup();
	});

	old_element.on('lms:multiselect:updated', function() {
		buildPopupList();

		multiselect.generateSelectedString();

		updateCheckAll();
	});

	this.updateSelection = function(idArray) {
		var selected = [];
		$('.lms-ui-multiselect-popup-list input:checkbox', popup).each(function() {
			var text = $(this).siblings('span').html();
			if (idArray == null || idArray.indexOf($(this).val()) != -1) {
				$(this).prop('checked', true).parent().addClass('selected');
				selected.push(text);
			} else {
				$(this).prop('checked', false).parent().removeClass('selected');
			}
		});
		new_selected = this.generateSelectedString();
	}

	this.filterSelection = function(idArray) {
		var selected = [];
		$('.lms-ui-multiselect-popup-list input:checkbox', popup).each(function() {
			var text = $(this).siblings('span').html();
			if (idArray == null || idArray.indexOf($(this).val()) != -1) {
				$(this).parent().show();
				if ($(this).prop('checked')) {
					selected.push(text);
				}
			} else {
				$(this).prop('checked', false).parent().hide();
			}
		});
		new_selected = this.generateSelectedString();
	}

	this.getOptions = function() {
		return all_items;
	}

	this.showOption = function(index) {
		$(all_items.get(index)).show().addClass('visible');
	}

	this.hideOption = function(index) {
		$(all_items.get(index)).removeClass('selected').hide().removeClass('visible')
			.find('input:checkbox').prop('checked', false);
		updateCheckAll();
	}

	this.toggleCheckAll = function(checked) {
		if (typeof(checked) === 'undefined') {
			checked = true;
		}
		checkall.find('.checkall').prop('checked', checked);
		checkAllElements();
	}

	this.refreshSelection = function() {
		new_selected = this.generateSelectedString();
	}
}
