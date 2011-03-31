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
function autoiframe_setsize(width, height)
{
    var doc = window.parent ? parent.document : document,
        frame = doc.getElementById('autoiframe');

    if (width) {
        frame.style.width = width + 'px';
        frame.parentNode.style.width = width + 'px';
    }
    if (height) {
        frame.style.height = height + 'px';
        frame.parentNode.style.height = height + 'px';
    }
}

function openSelectWindow(theURL, winName, myWidth, myHeight, isCenter, formfield)
{
	targetfield = formfield;
    popup(theURL, 1, 1, 30, 15);
    autoiframe_setsize(myWidth, myHeight);

	return false;
}

function ipchoosewin(formfield, netid, device)
{
    var url = '?m=chooseip' +  (netid ? '&netid=' + netid : '') + (device ? '&device=' + device : '');
	return openSelectWindow(url,'chooseip',350,380,'true',formfield);
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
	for (var i=0; i<cookies.length; i++)
	{
		var a = cookies[i].split('=');
                if (a.length == 2)
		{
            		a[0] = a[0].trim();
                	a[1] = a[1].trim();
                	if (a[0] == name)
			{
                    		return unescape(a[1]);
			}
                }
        }
        return null;
}

function setCookie(name, value)
{
        document.cookie = name + '=' + escape(value);
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
	}
}

function get_object_pos(obj)
{
	// get old select size/position
	var x = (document.layers) ? obj.x : obj.offsetLeft;
	var y = (document.layers) ? obj.y : obj.offsetTop;

	// calculate select position
	var elm = obj.offsetParent;
	while(elm && elm !== null) {
	        x += elm.offsetLeft;
		y += elm.offsetTop;
		elm = elm.offsetParent;
	}

	return {x:x, y:y};
}

function multiselect(formid, elemid, def)
{
	var old_element = document.getElementById(elemid);
	var form = document.getElementById(formid);

	if (!old_element || !form) {
		return;
	}

	// create new multiselect div
	var new_element = document.createElement('DIV');
	new_element.className = 'multiselect';
	new_element.id = elemid;
	new_element.innerHTML = def ? def : '';

	// save (overlib) popups
	new_element.onmouseover = old_element.onmouseover;
	new_element.onmouseout = old_element.onmouseout;

	// replace select with multiselect
	old_element.parentNode.replaceChild(new_element, old_element);

	// create multiselect list div (hidden)
	var div = document.createElement('DIV');
	var iframe = document.createElement('IFRAME');
	var ul = document.createElement('UL');

	div.className = 'multiselectlayer';
	div.id = elemid + '-layer';
	div.style.display = 'none';

	for(var i=0, len=old_element.options.length; i<len; i++)
	{
		var li = document.createElement('LI');
		var box = document.createElement('INPUT');
		var span = document.createElement('SPAN');

		box.type = 'checkbox';
		box.name = old_element.name;
		box.value = old_element.options[i].value;

		span.innerHTML = old_element.options[i].text;

		// add some mouse/key events handlers
		li.onclick = function() {
			var box = this.childNodes[0];
			var selected = this.className.match(/selected/);
			box.checked = selected ? false : true;

			if(selected) {
				removeClass(this, 'selected');
				if(def) {
					var xlen = this.parentNode.childNodes.length;
					for(var x=0; x<xlen; x++) {
						if(this.parentNode.childNodes[x].className.match(/selected/)) {
							break;
						}
					}
					if(x==xlen) {
						new_element.innerHTML = def;
					}
				}
			} else {
				addClass(this, 'selected');
				new_element.innerHTML = '';
			}
		};
		// TODO: keyboard events

		// add elements
		li.appendChild(box);
		li.appendChild(span);
		ul.appendChild(li);
	}

	// add list
	div.appendChild(iframe);
	div.appendChild(ul);
	form.appendChild(div);

	// add some mouse/key event handlers
	new_element.onclick = function() {
		var list = document.getElementById(this.id + '-layer');

		if(list.style.display == 'none') {
			var pos = get_object_pos(this);

			list.style.left = pos.x + 'px';
			list.style.top = this.offsetHeight + pos.y + 'px';
			list.style.display = 'block';
			// IE max-height hack
			if(document.all && list.childNodes[1].offsetHeight > 200) {
				list.childNodes[1].style.height = '200px';
			}
		} else {
			list.style.display = 'none';
		}
	};
	// TODO: keyboard events
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
        return;

    if (frame) {
        content = '<iframe id="autoiframe" width=100 height=10 frameborder=0 scrolling=no '
            +'src="'+content+'&popup=1"></iframe>';
    }

    if (!offset_x) offset_x = 15;
    if (!offset_y) offset_y = 15;

    if (sticky) {
        overlib(content, HAUTO, VAUTO, OFFSETX, offset_x, OFFSETY, offset_y, STICKY, MOUSEOFF);
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
        return;
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
