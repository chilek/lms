// $Id$

function multiselect(options) {
	var elemid = options.id;
	var def = typeof options.defaultValue !== 'undefined' ? options.defaultValue : '';
	var tiny = typeof options.type !== 'undefined' && options.type == 'tiny';
	var icon = typeof options.icon !== 'undefined' ? options.icon : 'img/settings.gif';
	var label = typeof options.label !== 'undefined' ? options.label : '';
	var separator = typeof options.separator !== 'undefined' ? options.separator : ', ';

	var old_element = $('#' + elemid);
	var form = old_element.closest('form');

	if (!old_element.length || !form.length)
		return 0;

	var old_class = $(old_element).removeClass('lms-ui-multiselect').attr('class');
	var new_class = 'lms-ui-multiselect' + (tiny ? '-tiny' : '') + ' ' + old_class;
	// create new multiselect div
	var new_element = $('<div/>', {
		class: new_class,
		id: elemid,
		// save title for tooltips
		title: old_element.attr('title')
	});
	if (tiny)
		new_element.html('<img src="' + icon + '">&nbsp' + label);

	var elem = [];
	$('option', old_element).each(function(index) {
		elem[$(this).text().replace(' ', '&nbsp;')] =
			$(this).prop('selected') ? 1 : 0;
	});

	var new_selected = generateSelectedString(elem);
	var old_selected = new_selected;
	if (!tiny)
		new_element.html(old_selected);

	new_element.data('multiselect-object', this)
		.attr('style', old_element.attr('style'));
	// save onchange event handler
	var onchange = old_element.prop('onchange');
	if (typeof(onchange) == 'function')
		new_element.on('change', onchange);
	// save onitemclick event handler
	var itemclick = old_element.prop('onitemclick');
	if (typeof(itemclick) == 'function')
		new_element.on('itemclick', itemclick);

	// replace select with multiselect
	old_element.replaceWith(new_element);

	// create multiselect list div (hidden)
	var div = $('<div/>', {
		class: 'lms-ui-multiselectlayer',
		id: elemid + '-layer'
	}).hide().appendTo(form);
	var ul = $('<ul/>').appendTo(div);

	$('option', old_element).each(function(i) {
		var li = $('<li/>').appendTo(ul);

		// add elements
		var box = $('<input/>', {
			type: 'checkbox',
			name: old_element.attr('name'),
			value: $(this).val()
		}).appendTo(li);

		var text = $(this).text().replace(' ', '&nbsp;');
		$('<span/>').html(text)
			.appendTo(li);

		$.each($(this).data(), function(key, value) {
			li.attr('data-' + key, value);
		});

		if (elem[text]) {
			box.prop('checked', true);
			li.addClass('selected');
		}

		if ($(this).is(':disabled')) {
			li.addClass('blend');
			box.prop('disabled', true);
			return;
		}

		// add some mouse/key events handlers
		li.click(function(e) {
			$(this).toggleClass('selected');

			var box = $(':checkbox', this);
			if (!$(e.target).is('input')) {
				box.prop('checked', !box.prop('checked'));
			}
			if (e.shiftKey) {
				checkElements(box);
			} else {
				ul.find('[data-prev-checked]:checkbox').removeAttr('data-prev-checked');
				box.attr('data-prev-checked', box.prop('checked'));
			}

			var optionValue = '';
			if (/<span>(.*?)<\/span>/i.exec(this.innerHTML) !== null)
				optionValue = RegExp.$1;

			if (box.is(':checked'))
				elem[optionValue] = 1; //mark option as selected
			else
				elem[optionValue] = 0; //mark option as unselected

			new_selected = generateSelectedString(elem);
			if (!tiny)
				new_element.html(new_selected);

			new_element.triggerHandler('itemclick', {
				index: $(this).index(),
				value: box.val(),
				checked: box.is(':checked')
			});
			e.stopPropagation();
		});
		// TODO: keyboard events
	});

	// add some mouse/key event handlers
	new_element.click(function() {
		var list = $('#' + this.id + '-layer');
		if (!list.is(':visible')) {
			var pos = get_object_pos(this);
			list.css('left', (pos.x + this.offsetWidth) + 'px')
				.css('top', pos.y + 'px').show();
/*
			list.position({
				my: 'left top',
				at: 'right top',
				of: new_element
			});
*/
		} else {
			list.hide();
			if (new_selected != old_selected)
				new_element.triggerHandler('change');
			old_selected = new_selected;
		}
	});

	// hide combobox after click out of the window
	$(document).click(function(e) {
		var elem = e.target;
		if (tiny)
			while (elem && (elem.nodeName != 'DIV' || elem.className.match(/^lms-ui-multiselect/) === null))
				elem = elem.parentNode;

		if (!$(div).is(':visible') || (elem && elem.id == old_element.attr('id')))
			return 0;

		var parent = $(e.target).parent().html().indexOf(old_element.attr('name'));

		if ($(e.target).html().indexOf("<head>") > -1 || parent == -1
			|| (parent > -1 && e.target.nodeName != 'INPUT' && e.target.nodeName != 'LI' && e.target.nodeName != 'SPAN')) {
			$(div).hide();
			if (new_selected != old_selected)
				new_element.triggerHandler('change');
			old_selected = new_selected;
		}
	});

	// TODO: keyboard events

	function checkElements(checkbox) {
		var allcheckboxes = ul.find(':checkbox');
		var i = allcheckboxes.index(allcheckboxes.filter('[data-prev-checked]')),
			j = allcheckboxes.index(checkbox);
		if (i > -1) {
			var checked = $(allcheckboxes[i]).attr('data-prev-checked') == 'true' ? true : false;
			var start = Math.min(i, j);
			var stop = Math.max(i, j);
			for (i = start; i <= stop; i++) {
				var li = $(allcheckboxes[i]).closest('li');
				var optionValue = '';
				if (/<span>(.*?)<\/span>/i.exec(li.get(0).innerHTML) !== null)
					optionValue = RegExp.$1;

				if (checked) {
					li.addClass('selected');
					elem[optionValue] = 1; //mark option as selected
				} else {
					li.removeClass('selected');
					elem[optionValue] = 0; //mark option as unselected
				}
				$(allcheckboxes[i]).prop('checked', checked);
			}
		}
	}

	function generateSelectedString(objArray) {
		var selected = [];

		for (var k in objArray)
			if (objArray.hasOwnProperty(k) && objArray[k] == 1)
				selected.push(k);

		if (!selected.length)
			return def;

		return selected.join(separator);
	}

	this.updateSelection = function(idArray) {
		var selected = [];
		$('input:checkbox', div).each(function() {
			var text = $(this).siblings('span').html();
			if (idArray == null || idArray.indexOf($(this).val()) != -1) {
				$(this).prop('checked', true).parent().addClass('selected');
				selected.push(text);
				elem[text] = 1;
			} else {
				$(this).prop('checked', false).parent().removeClass('selected');
				elem[text] = 0;
			}
		});
		new_selected = selected.join(separator);
		if (!tiny)
			new_element.html(new_selected);
	}

	this.filterSelection = function(idArray) {
		var selected = [];
		$('input:checkbox', div).each(function() {
			var text = $(this).siblings('span').html();
			if (idArray == null || idArray.indexOf($(this).val()) != -1) {
				$(this).parent().show();
				if ($(this).prop('checked')) {
					elem[text] = 1;
					selected.push(text);
				}
			} else {
				$(this).prop('checked', false).parent().hide();
				elem[text] = 0;
			}
		});
		new_selected = selected.join(separator);
		if (!tiny)
			new_element.html(new_selected);
	}

	this.getOptions = function() {
		new_element.html('');
		return $('li', div);
	}

	this.showOption = function(index) {
		$($('li', div).get(index)).show();
	}

	this.hideOption = function(index) {
		$($('li', div).get(index)).removeClass('selected').hide()
			.find('input:checkbox').prop('checked', false);
	}

	this.refreshSelection = function() {
		var selected = [];
		$('input:checkbox', div).each(function() {
			var text = $(this).siblings('span').html();
			if ($(this).prop('checked')) {
				elem[text] = 1;
				selected.push(text);
			}
		});
		new_selected = selected.join(separator);
		if (!tiny) {
			new_element.html(new_selected);
		}
	}
}
