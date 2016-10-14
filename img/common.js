// $Id$

function confirmLink(theLink, message)
{
	var is_confirmed = confirm(message);

	if (is_confirmed) {
		theLink.href += '&is_sure=1';
	}
	return is_confirmed;
}

function confirmForm(formField, message, okValue)
{
	var is_confirmed = confirm(message);
	if (is_confirmed) {
		formField.value = okValue;
	}
	return is_confirmed;
}

function addClass(theElem, theClass)
{
	theElem.className += ' ' + theClass;
}

function removeClass(theElem, theClass)
{
	regexp = new RegExp('\\s*' + theClass, 'i');
	var str = theElem.className;
	theElem.className = str.replace(regexp, '');
}

// LMS: function to autoresize iframe and parent div container (overlib)
function autoiframe_setsize(id, width, height)
{
	var doc = window.parent ? parent.document : document,
		frame = doc.getElementById(id);

    if (!frame)
        return 0;

	if (width) {
		frame.style.width = width + 'px';
		frame.parentNode.style.width = width + 'px';
	}
	else
	    width = frame.offsetWidth;

	if (height) {
		frame.style.height = height + 'px';
		frame.parentNode.style.height = height + 'px';
	}
	else
	    height = frame.offsetHeight;

    // move frame if it doesn't fit the screen
    var pos = get_object_pos(frame),
        parent_frame = doc.getElementById('overDiv'),
        dw = doc.body.offsetWidth,
        dh = doc.body.offsetWidth;

    if (width < dw && pos.x + width > dw - 15) {
        parent_frame.style.left = (dw - width - 15) + 'px';
    }
    if (height < dh && pos.y + height > dh - 15) {
        parent_frame.style.top = (dh - height - 15) + 'px';
    }
}

function openSelectWindow(theURL, winName, myWidth, myHeight, isCenter, formfield)
{
	targetfield = formfield;
	popup(theURL, 1, 1, 30, 15);
	autoiframe_setsize('autoiframe', myWidth, myHeight);

	return false;
}

function openSelectWindow2(theURL, winName, myWidth, myHeight, isCenter, formfield1, formfield2)
{
	targetfield1 = formfield1;
	targetfield2 = formfield2;
	popup(theURL, 1, 1, 30, 15);
	autoiframe_setsize('autoiframe', myWidth, myHeight);

	return false;
}

function ipchoosewin(formfield1, formfield2, netid, device)
{
	var url = '?m=chooseip' +  (netid ? '&netid=' + netid : '') + (device ? '&device=' + device : '');
	return openSelectWindow2(url, 'chooseip', 350, 380, 'true', formfield1, formfield2);
}

function macchoosewin(formfield)
{
	return openSelectWindow('?m=choosemac','choosemac',290,380,'true',formfield);
}

function customerchoosewin(formfield)
{
	return openSelectWindow('?m=choosecustomer','choosecustomer',450,250,'true',formfield);
}

function locationchoosewin(varname, formname, city, street, default_city)
{
        if(city == '' && default_city) city = default_city;
	return openSelectWindow('?m=chooselocation&name='+varname+'&form='+formname+'&city='+city+'&street='+street,'chooselocation',350,200,'true');
}

function netdevmodelchoosewin(varname, formname, netdevmodelid, producer, model)
{
	return openSelectWindow('?m=choosenetdevmodel&name='+varname+'&form='+formname+'&netdevmodelid='+netdevmodelid+'&producer='+producer+'&model='+model,'chooselocation',350,200,'true');
}

function gpscoordschoosewin(formfield1, formfield2)
{
	return openSelectWindow2('?m=choosegpscoords', 'choosegpscoords', 450, 300, 'true', formfield1, formfield2);
}

function netdevfrommapchoosewin(netdevid)
{
	return openSelectWindow('?m=choosenetdevfrommap', 'choosenetdevfrommap', 450, 300, 'true', netdevid);
}

function netlinkpropertieschoosewin(id, devid, isnetlink)
{
	return openSelectWindow('?m=netlinkproperties&id=' + id + '&devid=' + devid + '&isnetlink=' + (isnetlink ? 1 : 0), 'netlinkproperties', 350, 100, 'true');
}

function netDevChooseWin(formfield, netdevid) {
	return openSelectWindow('?m=choosenetdevice' + (netdevid !== undefined ? '&netdevid=' + netdevid : ''), 'choosenetdevice', 600, 250, 'true', formfield);
}

function nodeChooseWin(formfield) {
	return openSelectWindow('?m=choosenodedevice', 'choosenodedevice', 600, 250, 'true', formfield);
}

function netDevForNetNodeChooseWin(netnodeid) {
	return openSelectWindow('?m=choosenetdevfornetnode&id=' + netnodeid, 'choosenetdevfornetnode', 600, 250, 'true', netnodeid);
}

function sendvalue(targetfield, value)
{
	targetfield.value = value;
	// close popup
	window.parent.parent.popclick();
	targetfield.focus();
}

function showOrHide(elementslist)
{
	var elements_array = elementslist.split(" ");
	var part_num = 0;
	while (part_num < elements_array.length)
	{
		var elementid = elements_array[part_num];
		if(document.getElementById(elementid).style.display != 'none')
		{
			document.getElementById(elementid).style.display = 'none';
			setCookie(elementid, '0');
		}
		else
		{
			document.getElementById(elementid).style.display = '';
			setCookie(elementid, '1');
		}
		part_num += 1;
	}
}

timer_now = new Date();
timer_start = timer_now.getTime();

function getSeconds()
{
	var timer_now2 = new Date();
	return Math.round((timer_now2.getTime() - timer_start)/1000);
}

function getCookie(name)
{
	var cookies = document.cookie.split(';');
	for (var i=0; i<cookies.length; i++) {
		var a = cookies[i].split('=');
		if (a.length == 2) {
			a[0] = a[0].trim();
			a[1] = a[1].trim();
			if (a[0] == name)
				return unescape(a[1]);
		}
	}
	return null;
}

function setCookie(name, value, permanent)
{
	var cookie = name + '=' + escape(value);
	if (permanent != null) {
		var d = new Date();
		d.setTime(d.getTime() + 365 * 24 * 3600 * 1000);
		cookie += '; expires=' + d.toUTCString();
	}
	document.cookie = cookie;
}

if (typeof String.prototype.trim == 'undefined')
{
	String.prototype.trim = function()
	{
        	var s = this.replace(/^\s*/, '');
	        return s.replace(/\s*$/, '');
	};
}

function inArray(a, v)
{
    for (var i in a) {
        if (a[i] == v) {
            return true;
        }
    }
    return false;
}

function checkElement(id)
{
	var elem = document.getElementById(id);

	if (!elem) {
		var list = document.getElementsByName(id);
		if (list.length) {
			elem = list[0];
		}
	}

	if (elem) {
		elem.checked = !elem.checked;
		if (typeof elem.onchange === 'function')
			elem.onchange();
	}
}

function CheckAll(form, elem, excl)
{
    var i, len, n, e, f,
        form = document.forms[form] ? document.forms[form] : document.getElementById(form),
        //inputs = form.getElementsByTagName('INPUT');
        inputs = form.elements;

    for (i=0, len=inputs.length; i<len; i++) {
        e = inputs[i];

        if (e.tagName.toUpperCase() != 'INPUT' || e.type != 'checkbox' || e == elem)
            continue;

        if (excl && excl.length) {
            f = 0;
            for (n=0; n<excl.length; n++)
                if (e.name == excl[n])
                    f = 1;
            if (f)
                continue;
        }

        e.checked = elem.checked;
    }
}

function get_object_pos(obj) {
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

function multiselect(options) {
	var elemid = options.id;
	var def = options.defaultValue !== undefined ? options.defaultValue : '';
	var tiny = options.type !== undefined && options.type == 'tiny';
	var icon = options.icon !== undefined ? options.icon : 'img/settings.gif';
	var label = options.label !== undefined ? options.label : '';

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

	var old_selected = new_selected = generateSelectedString(elem);
	if (!tiny)
		new_element.html(old_selected);

	new_element.data('data-multiselect-object', this)
		.prop('style', old_element.prop('style'));
	// save onchange event handler
	if (typeof(onchange = old_element.prop('onchange')) == 'function')
		new_element.on('change', onchange);
	// save onitemclick event handler
	if (typeof(itemclick = old_element.prop('onitemclick')) == 'function')
		new_element.on('itemclick', onchange);

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

		// add some mouse/key events handlers
		li.click(function(e) {
			if ($(e.target).is('input'))
				return;

			$(this).toggleClass('selected');
			var box = $(':checkbox', this);
			box.prop('checked', !box.prop('checked'));

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
				elems[i].parentNode.className = '';
				elem[text] = 0;
			}
		});
		new_selected = selected.join(', ');
		if (!tiny)
			new_element.html(new_selected);
	}
}

var lms_login_timeout_value,
    lms_login_timeout,
    lms_sticky_popup;

function start_login_timeout(sec)
{
    if (!sec) sec = 600;
    lms_login_timeout_value = sec;
    lms_login_timeout = window.setTimeout('window.location.reload(true)', (sec + 5) * 1000);
}

function reset_login_timeout()
{
    window.clearTimeout(lms_login_timeout);
    start_login_timeout(lms_login_timeout_value);
}

// Display overlib popup
function popup(content, frame, sticky, offset_x, offset_y)
{
	if (lms_sticky_popup)
		return 0;

	if (frame)
		content = '<iframe id="autoiframe" width=100 height=10 frameborder=0 scrolling=no '
			+'src="'+content+'&popup=1"></iframe>';

	if (!offset_x) offset_x = 15;
	if (!offset_y) offset_y = 15;

	if (sticky) {
		// let's check how people will react for this small change ;-)
		//overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y, STICKY, MOUSEOFF);
		overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y, STICKY);
		var body = document.getElementsByTagName('BODY')[0];
		body.onmousedown = function () { popclick(); };
		lms_sticky_popup = 1;
	}
	else {
		 overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y);
	}
}

// Hide non-sticky popup
function pophide()
{
    if (lms_sticky_popup) {
        return 0;
    }

    return nd();
}

// Hide sticky popup
function popclick()
{
    lms_sticky_popup = 0;
    o3_removecounter++;
    return nd();
}

function check_teryt(locid, init)
{
    var checked = document.getElementById('teryt').checked;

    if (locid) {
        var loc = document.getElementById(locid);
        if (checked) {
            //if (!init)
            //    loc.value = '';
            loc.setAttribute('readonly', true);
        }
        else {
            loc.removeAttribute('readonly');
        }
    }

    return checked;
}

function ping_popup(ip, type)
{
	popup('?m=ping&ip=' + ip + '&type=' + (type ? type : 0), 1, 1, 30, 30);
	autoiframe_setsize('autoiframe', 480, 300);
}

function changeMacFormat(id)
{
	if (!id) return 0;
	var elem = document.getElementById(id);
	if (!elem) return 0;
	var curmac = elem.innerHTML;
	var macpatterns = [ /^([0-9a-f]{2}:){5}[0-9a-f]{2}$/gi, /^([0-9a-f]{2}-){5}[0-9a-f]{2}$/gi,
		/^([0-9a-f]{4}\.){2}[0-9a-f]{4}$/gi, /^[0-9a-f]{12}$/gi ];
	for (var i in macpatterns)
		if (macpatterns[i].test(curmac))
			break;
	if (i >= macpatterns.length)
		return 0;
	i = parseInt(i);
	switch (i) {
		case 0:
			curmac = curmac.replace(/:/g, '-');
			break;
		case 1:
			curmac = curmac.replace(/-/g, '');
			curmac = curmac.toLowerCase();
			curmac = curmac.replace(/^([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})$/gi, '$1.$2.$3');
			break;
		case 2:
			curmac = curmac.replace(/\./g, '');
			curmac = curmac.toUpperCase();
			break;
		case 3:
			curmac = curmac.replace(/^([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/gi, '$1:$2:$3:$4:$5:$6');
	}
	elem.innerHTML = curmac;
}

function reset_customer(form, elemname1, elemname2) {
	if (document.forms[form].elements[elemname1].value)
		document.forms[form].elements[elemname2].value = document.forms[form].elements[elemname1].value;
}

function generate_random_string(length, characters) {
	if (length === undefined)
		length = 10;
	if (characters === undefined)
		characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	charactersLength = characters.length;
	randomString = '';
	for (var i = 0; i < length; i++)
		randomString += characters[Math.floor(Math.random() * charactersLength)];
	return randomString;
}

function get_size_unit(size) {
	if (size > 10 * 1024 * 1024 * 1024)
		return {
			size: (size / 1024 * 1024 * 1024).toFixed(2),
			unit: 'GiB'
		};
	else if (size > 10 * 1024 * 1024)
		return {
			size: (size / 1024 * 1024).toFixed(2),
			unit: 'MiB'
		};
	else if (size > 10 * 1024)
		return {
			size: (size / 1024).toFixed(2),
			unit: 'KiB'
		};
	else
		return {
			size: size,
			unit: 'B'
		};
}

function _getCustomerNames(ids, success) {
	$.ajax('?m=customerinfo&ajax=1', {
		async: true,
		method: 'POST',
		data: {
			id: ids
		},
		dataType: 'json',
		success: success
	});
}

function getCustomerName(elem) {
	_getCustomerNames([ $(elem).val() ], function(data, textStatus, jqXHR) {
		$(elem).nextAll('span').html(data.customernames[$(elem).val()] === undefined ? ''
			: data.customernames[$(elem).val()]);
	});
}

var customerinputs = [];

function getCustomerNameDeferred(elem) {
	customerinputs.push(elem);
}

if (typeof $ != 'undefined') {
	$(function() {
		var cids = [];
		$.each(customerinputs, function(index, elem) {
			cids.push($(elem).val());
		});
		_getCustomerNames(cids, function(data, textStatus, jqXHR) {
			$.each(customerinputs, function(index, elem) {
				$(elem).nextAll('span').html(data.customernames[$(elem).val()] === undefined ?
					'' : data.customernames[$(elem).val()]);
			});
		});

		for (i in document.links) {
			link = document.links[i];
			if (link.rel && link.rel.indexOf('external') != -1) {
				link.onclick = function() { window.open(this.href); return false; }
				link.onkeypress = function() { window.open(this.href); return false; }
			}
		}
	});
}
