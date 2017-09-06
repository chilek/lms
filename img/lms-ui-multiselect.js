// $Id$

function multiselect(options) {
	var multiselect_obj = this;

	this.get_object_pos = function(obj) {
		// get old element size/position
		var x = obj.offsetLeft;
		var y = obj.offsetTop;

		// calculate element position
		var elm = obj.offsetParent;
		while (elm && window.getComputedStyle(elm).position != 'relative') {
			x += elm.offsetLeft;
			y += elm.offsetTop;
			elm = elm.offsetParent;
		}

		return { x: x, y: y };
	}

	var elemid = options.id;
	var def = typeof options.defaultValue !== 'undefined' ? options.defaultValue : '';
	var tiny = typeof options.type !== 'undefined' && options.type == 'tiny';
	var icon = typeof options.icon !== 'undefined' ? options.icon : 'img/settings.gif';
	var label = typeof options.label !== 'undefined' ? options.label : '';

	var old_element = $('#' + elemid);
	var form = old_element.closest('form');

	if (!old_element.length || !form.length)
		return 0;

	// create new multiselect div
	var new_element = $('<div/>', {
		class: 'lms-ui-multiselect' + (tiny ? '-tiny' : ''),
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

	new_element.data('data-multiselect-object', this)
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
		var span = $('<span/>').html(text)
			.appendTo(li);

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
			var pos = multiselect_obj.get_object_pos(this);
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

	function generateSelectedString(objArray) {
		var selected = [];

		for (var k in objArray)
			if (objArray.hasOwnProperty(k) && objArray[k] == 1)
				selected.push(k);

		if (!selected.length)
			return def;

		return selected.join(', ');
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
		new_selected = selected.join(', ');
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
		new_selected = selected.join(', ');
		if (!tiny)
			new_element.html(new_selected);
	}
}
