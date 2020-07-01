// $Id$

function multiselect(options) {
	var multiselect = this;
	var elemid = options.id;
	var def = typeof options.defaultValue !== 'undefined' ? options.defaultValue : '';
	var shorten_to_def = typeof options.shortenToDefaultValue === 'undefined' ||
		options.shortenToDefaultValue == 'true' ? true : false;
	var tiny = typeof options.type !== 'undefined' && options.type == 'tiny';
	var bottom = typeof options.bottom !== 'undefined' && options.bottom;
	var button = typeof options.button !== 'undefined' && options.button;
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
	var new_class = 'lms-ui-multiselect' + (tiny ? '-tiny' : '') + ' ' + old_class;
	// create new multiselect div
	var wrapper = $('<div/>' , {
		class: tiny ? 'lms-ui-multiselect-tiny-wrapper' : 'lms-ui-multiselect-wrapper',
		id: elemid,
		tabindex: 0
	});
	var new_element = $('<div/>', {
		class: new_class,
		// save title for tooltips
		title: old_element.attr('title')
	}).appendTo(wrapper);

	if (tiny) {
		if (button) {
			new_element.append(button);
		} else {
			new_element.html(icon.match("img\/", icon) ? '<img src="' + icon + '">' + (label ? '&nbsp' + label : '') : '<i class="' + icon + '"/>');
		}
	} else {
		$('<span/>')
			.addClass('lms-ui-icon-multiselect')
			.appendTo(wrapper);
	}

	new_element.data('multiselect-object', this)
		.attr('style', old_element.attr('style'));

	// save onchange event handler
	var onchange = old_element.prop('onchange');
	if (typeof(onchange) == 'function') {
		wrapper.on('change', onchange);
	}
	// save onitemclick event handler
	var itemclick = old_element.prop('onitemclick');
	if (typeof(itemclick) == 'function') {
		wrapper.on('itemclick', itemclick);
	}

	// replace select with multiselect
	//old_element.replaceWith(new_element);
	old_element.replaceWith(wrapper);

	// create multiselect list div (hidden)
	var div = $('<div/>', {
		class: 'lms-ui-multiselect-popup-container',
		id: elemid + '-popup'
	}).hide().appendTo(form);
	var div_container = $('<div/>', {
		class: 'lms-ui-multiselect-popup'
	}).appendTo(div);
	var titlebar = $('<div/>', {
		class: 'lms-ui-multiselect-popup-titlebar'
	}).html('<div id="lms-ui-multiselect-popup-title">Tytuł</div><i class="lms-ui-icon-hide close-button"></i>')
		.appendTo(div_container);
	var ul = $('<ul/>', {
		class: 'lms-ui-multiselect-popup-list'
	}).appendTo(div_container);

	var new_selected;
	var old_selected;
	var all_items;
	var all_enabled_items;
	var all_checkboxes;
	var all_enabled_checkboxes;
	var checkall_div = null;

	this.generateSelectedString = function() {
		var selected = [];
		$('input:checked', ul).next().each(function(key, value) {
			selected.push($(this).html());
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
				new_element.removeAttr('data-tooltip').attr('title', $t(tooltipMessage, selected_string.length ?
					selected_string : '-'));
			}
		} else {
			if (maxVisible && selected.length > maxVisible) {
				new_element.html($t(substMessage, selected.length));
				new_element.attr('title', selected_string);
			} else {
				new_element.html(selected_string);
				new_element.attr('title', '');
			}
			var list = $('#' + wrapper.attr('id') + '-popup');
			if (list.is(':visible')) {
				setTimeout(function() {
					if (parseInt($(window).outerWidth()) >= 800) {
						list.position({
							my: 'left top',
							at: tiny || bottom ? 'left bottom' : 'right top',
							of: wrapper
						});
					} else {
						hideMainScrollbars();
						list.css({
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
		ul.parent().find('.checkall').prop('checked', checkboxes.filter(':checked').length == checkboxes.length);
	}

	$('option', old_element).each(function(i) {
		var exclusive = $(this).attr('data-exclusive');
		var class_name = (exclusive == '' ? 'exclusive' : '');
		class_name += ' visible';
		var li = $('<li/>').addClass(class_name).appendTo(ul);

		// add elements
		var box = $('<input/>', {
			type: 'checkbox',
			name: old_element.attr('name'),
			value: $(this).val(),
			class: class_name
		}).appendTo(li);

		var text = $(this).attr('data-html-content');
		if (!text) {
			text = $(this).text();
		}
		$('<span/>').html(text).appendTo(li);

		$.each($(this).data(), function(key, value) {
			li.attr('data-' + key, value);
		});

		if ($(this).is(':selected')) {
			li.addClass('selected');
			box.prop('checked', true);
		}

		if ($(this).is(':disabled')) {
			li.addClass('blend disabled');
			box.prop('disabled', true);
			return;
		}

		// add some mouse/key events handlers
		li.click(function(e) {
			$(this).toggleClass('selected');

			if (!$(e.target).is('input')) {
				box.prop('checked', !box.prop('checked'));
			}
			if ($(this).is('.exclusive')) {
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

			wrapper.triggerHandler('itemclick', {
				index: $(this).index(),
				value: box.val(),
				checked: box.is(':checked')
			});

			var checkboxes = all_enabled_items.filter(':not(.exclusive)').filter('.visible').find(':checkbox');
			wrapper.trigger('checkall', {
				allChecked: checkboxes.filter(':checked').length == checkboxes.length
			});

			e.stopPropagation();
		}).mouseenter(function() {
			$(this).addClass('active').find('input').focus().end().siblings('li').not(this).removeClass('active');
		}).mouseleave(function() {
			$(this).removeClass('active');
		});
		// TODO: keyboard events
	});

	all_items = ul.find('li');
	all_enabled_items = all_items.filter(':not(.disabled)');
	all_enabled_checkboxes = all_enabled_items.find(':checkbox');

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
		checkall_div = $('<div/>', {
			class: 'lms-ui-multiselect-popup-checkall'
		}).appendTo(div_container);
		$('<input type="checkbox" class="checkall" value="1"><span>' + $t('check all<!items>') + '</span>').appendTo(checkall_div);

		updateCheckAll();

		$(checkall_div).click(function(e) {
			if (!all_items.filter(':visible').length) {
				return;
			}
			if (!$(e.target).is('input.checkall')) {
				var checkbox = $('.checkall', this);
				checkbox.prop('checked', !checkbox.prop('checked'))
			}

			checkAllElements();

			wrapper.trigger('checkall', {
				allChecked: $('.checkall', this).prop('checked')
			});

			e.stopPropagation();
		});
	}

	// add some mouse/key event handlers
	wrapper.on('click keydown', function(e) {
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
		var list = $('#' + this.id + '-popup');
		if (!list.is(':visible') && (e.type != 'keydown' || e.key != 'Escape')) {
			setTimeout(function() {
				list.show();
				if (parseInt($(window).outerWidth()) >= 800) {
					list.position({
						my: 'left top',
						at: tiny || bottom ? 'left bottom' : 'right top',
						of: wrapper
					});
				} else {
					hideMainScrollbars();
					list.css({
						'left': '',
						'top': ''
					});
				}
				all_items.removeClass('active');
				all_enabled_items.first().addClass('active').find('input').focus();
			}, 1);
		} else {
			list.hide();
			showMainScrollbars();
			if (new_selected != old_selected) {
				wrapper.triggerHandler('change');
			}
			old_selected = new_selected;
		}
	});

	$(document).on('keydown', function(e) {
		var popup_container = $(e.target).closest('.lms-ui-multiselect-popup-container');
		if (!popup_container.length || !popup_container.is(div)) {
			return;
		}
		var li;
		switch (e.key) {
			case 'Escape':
				e.preventDefault();
				$(div).hide();
				wrapper.focus();
				if (new_selected != old_selected) {
					wrapper.triggerHandler('change');
				}
				old_selected = new_selected;
				showMainScrollbars();
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
				if (e.ctrlKey && checkall_div) {
					checkall_div.click();
				}
				e.preventDefault();
				break;
		}
	});

	// hide combobox after click out of the window
	$(document).click(function(e) {
		var elem = $(e.target);
		if ($(div).is(':visible') &&
			!elem.is('.lms-ui-multiselect-wrapper,.lms-ui-multiselect-tiny-wrapper') &&
			!elem.closest('.lms-ui-multiselect-wrapper,.lms-ui-multiselect-tiny-wrapper').length &&
			!elem.closest('.lms-ui-multiselect-popup-container').length) {
			$(div).hide();
			if (new_selected != old_selected) {
				wrapper.triggerHandler('change');
			}
			old_selected = new_selected;
			showMainScrollbars();
		}
	});

	div_container.find('.close-button').click(function() {
		$(div).hide();
		wrapper.focus();
		if (new_selected != old_selected) {
			wrapper.triggerHandler('change');
		}
		old_selected = new_selected;
		showMainScrollbars();
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

	this.updateSelection = function(idArray) {
		var selected = [];
		$('input:checkbox:not(.checkall)', div).each(function() {
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
		$('input:checkbox:not(.checkall)', div).each(function() {
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

	this.toggleCheckAll = function(checkall) {
		if (typeof(checkall) === 'undefined') {
			checkall = true;
		}
		checkall_div.find('.checkall').prop('checked', checkall);
		checkAllElements();
	}

	this.refreshSelection = function() {
		new_selected = this.generateSelectedString();
	}
}
