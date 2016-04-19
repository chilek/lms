// $Id: common.js,v 1.2 2003/04/12 22:31:06 lukasz Exp $

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

function nodechoosewin(formfield, customerid)
{
	return openSelectWindow('?m=choosenode&id='+customerid,'choosenode',350,200,'true',formfield);
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
	return openSelectWindow('?m=netlinkproperties&id=' + id + '&devid=' + devid + '&isnetlink=' + (isnetlink ? 1 : 0),
		'netlinkproperties', 350, 100, 'true');
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

function get_object_pos(obj)
{
	// get old element size/position
	var x = (document.layers) ? obj.x : obj.offsetLeft;
	var y = (document.layers) ? obj.y : obj.offsetTop;

	// calculate element position
	var elm = obj.offsetParent;
	while (elm) {
	    x += elm.offsetLeft;
		y += elm.offsetTop;
		elm = elm.offsetParent;
	}

	return {x:x, y:y};
}

function multiselect(formid, elemid, def, selected)
{
	var old_element = document.getElementById(elemid);
	var form = document.getElementById(formid);

	if (!old_element || !form) {
		return 0;
	}

	var selected_elements = null;
	if (selected)
		selected_elements = '|' + selected.join('|') + '|';

	// create new multiselect div
	var new_element = document.createElement('DIV');
	new_element.className = 'multiselect';
	new_element.id = elemid;

	var elem = [];
	for (var i = 0; i < old_element.options.length; i++)
		if (old_element.options[i].selected)
			elem[old_element.options[i].text.replace(' ', '&nbsp;')] = 1;
		else
			elem[old_element.options[i].text.replace(' ', '&nbsp;')] = 0;

	new_element.innerHTML =  generateSelectedString(elem);

	if (old_element.style.cssText)
		new_element.style.cssText = old_element.style.cssText;

	// save (overlib) popups
	new_element.onmouseover = old_element.onmouseover;
	new_element.onmouseout = old_element.onmouseout;

	// replace select with multiselect
	old_element.parentNode.replaceChild(new_element, old_element);

	// create multiselect list div (hidden)
	var div = document.createElement('DIV');
	var ul = document.createElement('UL');

	div.className = 'multiselectlayer';
	div.id = elemid + '-layer';
	div.style.display = 'none';

	for (var i=0, len=old_element.options.length; i<len; ++i)
	{
		var li = document.createElement('LI');

		var box = document.createElement('INPUT');
		box.type = 'checkbox';
		box.name = old_element.name;
		box.value = old_element.options[i].value;

		var span = document.createElement('SPAN');
		span.innerHTML = old_element.options[i].text.replace(' ', '&nbsp;');

		if (elem[span.innerHTML]) {
			box.checked = true;
			addClass(li, 'selected');
		}

		// add some mouse/key events handlers
		li.onclick = function() {
			var userName = '';
			var box = this.childNodes[0];
			var selected = this.className.match(/selected/);
			box.checked = selected ? false : true;

			if (/<span>(.*?)<\/span>/i.exec(this.innerHTML) !== null)
				userName = RegExp.$1;

			if (selected) {
				elem[userName] = 0; //mark user as unselected

				removeClass(this, 'selected');
				if (def) {
					var xlen = this.parentNode.childNodes.length;

					for (var x=0; x<xlen; ++x)
						if (this.parentNode.childNodes[x].className.match(/selected/))
							break;
				}
			} else {
				elem[userName] = 1; //mark user as selected
				addClass(this, 'selected');
			}

			new_element.innerHTML = generateSelectedString(elem);
		};
		// TODO: keyboard events

		// add elements
		li.appendChild(box);
		li.appendChild(span);
		ul.appendChild(li);
	}

	// add list
	div.appendChild(ul);
	form.appendChild(div);

	// add some mouse/key event handlers
	new_element.onclick = function() {
		var list = document.getElementById(this.id + '-layer');

		if(list.style.display == 'none') {
			var pos = get_object_pos(this);

			list.style.left = (pos.x + this.offsetWidth) + 'px';
			list.style.top = pos.y + 'px';
			list.style.display = 'block';
			// IE max-height hack
			if(document.all && list.childNodes[1].offsetHeight > 200) {
				list.childNodes[1].style.height = '200px';
			}
		} else {
			list.style.display = 'none';
		}
	};

	// hide combobox after click out of the window
	document.onclick = function(e) {
		if (div.style.display == 'none' || e.target.id == old_element.id)
			return 0;
		
		var parent = e.target.parentNode.innerHTML.indexOf(old_element.name);

		if (e.target.innerHTML.indexOf("<head>") > -1 || parent == -1 || (parent > -1 && e.target.nodeName != 'INPUT' && e.target.nodeName != 'LI' && e.target.nodeName != 'SPAN'))
			div.style.display = 'none';
	}

	// TODO: keyboard events

	function generateSelectedString(objArray) {
		var selected = [];

		for (var k in objArray)
			if (objArray.hasOwnProperty(k) && objArray[k] == 1)
				selected.push(k);

		if (selected.length == 0)
			return def;

		return selected.join(', ');
	}

	this.updateSelection = function(idArray) {
		var elems = div.childNodes[0].getElementsByTagName('input');
		var selected = [];
		for (var i = 0; i < elems.length; i++) {
			var text = elems[i].parentNode.getElementsByTagName('span')[0].innerHTML;
			if (idArray == null || idArray.indexOf(elems[i].value) != -1) {
				elems[i].checked = true;
				elems[i].parentNode.className = 'selected';
				selected.push(text);
				elem[text] = 1;
			} else {
				elems[i].checked = false;
				elems[i].parentNode.className = '';
				elem[text] = 0;
			}
		}
		new_element.innerHTML = selected.join(', ');
	}

	this.filterSelection = function(idArray) {
		var elems = div.childNodes[0].getElementsByTagName('input');
		var selected = [];
		for (var i = 0; i < elems.length; i++) {
			var text = elems[i].parentNode.getElementsByTagName('span')[0].innerHTML;
			if (idArray == null || idArray.indexOf(elems[i].value) != -1) {
				elems[i].parentNode.style.display = '';
				if (elems[i].checked) {
					elem[text] = 1;
					selected.push(text);
				}
			} else {
				elems[i].checked = false;
				elems[i].parentNode.className = '';
				elems[i].parentNode.style.display = 'none';
				elem[text] = 0;
			}
		}
		new_element.innerHTML = selected.join(', ');
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

function tinymce_init(ui_language) {
	tinyMCE.init({
		setup : function(ed) {
			ed.onBeforeSetContent.add(function(ed, o) {
				if (o.initial) {
					o.content = o.content.replace(/\r?\n/g, '<br />');
				}
			});
		},
		mode: "none",
		language: ui_language,
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
}

function toggle_visual_editor(id) {
	if (document.getElementById(id) == undefined)
		return 0;
	if (tinymce.get(id))
		tinyMCE.execCommand('mceToggleEditor', false, id);
	else
		tinyMCE.execCommand('mceAddControl', true, id);
}

function init_links() {
	for (i in document.links) {
		link = document.links[i];
		if (link.rel && link.rel.indexOf('external') != -1) {
			link.onclick = function() { window.open(this.href); return false; }
			link.onkeypress = function() { window.open(this.href); return false; }
		}
	}
}

function reset_customer(form, elemname1, elemname2) {
	if (document.forms[form].elements[elemname1].value)
		document.forms[form].elements[elemname2].value = document.forms[form].elements[elemname1].value;
}

if (window.addEventListener) window.addEventListener("load", init_links, false);
else if (window.attachEvent) window.attachEvent("onload", init_links);

function choosenetdevice(formfield){
    return openSelectWindow('?m=choosenetdevice','choosenetdevice',600,250,'true',formfield);
}