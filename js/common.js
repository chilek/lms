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
        dh = doc.body.offsetHeight;

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

function ipchoosewin(hostparams) {
	var ipelem = hostparams.ipelem;
	var netelem = hostparams.netelem;
	var ip = (typeof hostparams.ip === 'undefined' ? '' : hostparams.ip);
	var netid = (typeof hostparams.netid === 'undefined' ? '' : hostparams.netid);
	var privnetid = (typeof hostparams.privnetid === 'undefined' ? '' : hostparams.privnetid);
	var device = (typeof hostparams.device === 'undefined' ? '' : hostparams.device);
	var url = '?m=chooseip' +  (netid ? '&netid=' + netid : '') + (ip ? '&ip=' + ip : '') +
		(privnetid ? '&privnetid=' + privnetid : '') + (device ? '&device=' + device : '');
	return openSelectWindow2(url, 'chooseip', 350, 380, 'true', ipelem, netelem);
}

function long2ip(ip) {
    if (!isFinite(ip)) {
        return false;
    }

    return [ip >>> 24, ip >>> 16 & 0xFF, ip >>> 8 & 0xFF, ip & 0xFF].join('.');
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
    if (city == '' && default_city)
        city = default_city;

    return openSelectWindow('?m=chooselocation&name='+varname+'&form='+formname+'&city='+city+'&street='+street,'chooselocation',350,200,'true');
}

if ( typeof $ !== 'undefined' ) {
    $(function() {
        // open location dialog window if teryt is checked
        $('body').on('click', '.teryt-address-button', function() {

            var box = $( this ).closest( ".lms-ui-address-box" );

            // if teryt checkbox is not checked during teryt button click then
            // we check it automatically for user convenience
            if ( ! box.find("input[data-address='teryt-checkbox']").is(':checked') ) {
                box.find("input[data-address='teryt-checkbox']").prop('checked', true);
                // simulate click for update input state
                $( '.lms-ui-address-teryt-checkbox' ).trigger( 'change' );
            }

            var city   = box.find("input[data-address='city-hidden']").val();
            if (city == '' && lmsSettings.defaultTerytCity) {
                city = lmsSettings.defaultTerytCity;
            }
            var street = box.find("input[data-address='street-hidden']").val();

            openSelectWindow('?m=chooselocation&city=' + city + '&street=' + street + "&boxid=" + box.attr('id'), 'chooselocation', 350, 200, 'true');
        });

        // disable and enable inputs after click
        $('body').on('change', '.lms-ui-address-teryt-checkbox', function() {
            var boxid = $( this ).closest( ".lms-ui-address-box" ).attr( 'id' );

            if ( $( this ).is(':checked') ) {
                $("#" + boxid + " input[type=text]").prop("readonly", true);
                $("#" + boxid).find("input[data-address='zip']").attr('readonly', false);
                $("#" + boxid).find("input[data-address='postoffice']").attr('readonly', false);
                $("#" + boxid).find("input[data-address='location-name']").attr('readonly', false);
                $("#" + boxid).find("select[data-address='state-select']").css('display', 'none').attr('disabled', true);
                $("#" + boxid).find("input[data-address='state']").css('display', 'block').attr('disabled', false);
            } else {
                $("#" + boxid + " input[type=text]").prop("readonly", false);
                $("#" + boxid).find("select[data-address='state-select']").css('display', 'inline-block').attr('disabled', false);
                $("#" + boxid).find("input[data-address='state']").css('display', 'none').attr('disabled', true);
            }
        });

        // simulate click for update input state
        $( '.lms-ui-address-teryt-checkbox' ).trigger( 'change' );
    });
}

function netdevmodelchoosewin(varname, formname, netdevmodelid, producer, model)
{
	return openSelectWindow('?m=choosenetdevmodel&name='+varname+'&form='+formname+'&netdevmodelid='+netdevmodelid+'&producer='+producer+'&model='+model,'chooselocation',350,200,'true');
}

function gpscoordschoosewin(formfield1, formfield2)
{
	return openSelectWindow2('?m=choosegpscoords', 'choosegpscoords',
		window.screen.availWidth * 0.4,
		window.screen.availHeight * 0.4,
		'true', formfield1, formfield2);
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
				return decodeURIComponent(a[1]);
		}
	}
	return null;
}

function setCookie(name, value, permanent)
{
	var cookie = name + '=' + encodeURIComponent(value);
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
    var i, len, n, e, f;
    var formelem = document.forms[form] ? document.forms[form] : document.getElementById(form);
    var inputs = formelem.elements;

    for (i=0, len=inputs.length; i<len; i++) {
        e = inputs[i];

        if (e.tagName.toUpperCase() != 'INPUT' || e.type != 'checkbox' || e == elem)
            continue;

        if (typeof(excl) !== 'undefined' && excl.length) {
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

var lms_login_timeout_value,
    lms_login_timeout,
    lms_login_timeout_update = 0,
	lms_login_timeout_ts,
    lms_sticky_popup,
	lms_session_expire_elem;

function start_login_timeout(sec)
{
    if (!sec) sec = 600;
    lms_login_timeout_value = sec;
    lms_login_timeout_ts = Date.now() + sec * 1000;
    lms_login_timeout = setTimeout(function() {
            window.location.assign(window.location.href);
        }, (sec + 1) * 1000);
	lms_session_expire_elem = $('#lms-ui-session-expire');
    if (lms_session_expire_elem.length) {
	    lms_login_timeout_update = setInterval(function() {
				var time_to_expire = lms_login_timeout_ts - Date.now();
				if (time_to_expire < 0) {
					time_to_expire = 0;
				}
				time_to_expire = Math.round(time_to_expire / 1000);
	    		lms_session_expire_elem.text(sprintf("%02d:%02d",
					Math.floor(time_to_expire / 60),
					time_to_expire % 60
				));
	    		if (typeof(session_expiration_warning_handler) == 'function') {
	    			session_expiration_warning_handler(time_to_expire);
				}
	    }, 1000);
	}
}

function reset_login_timeout()
{
    clearTimeout(lms_login_timeout);
    if (lms_login_timeout_update) {
		clearInterval(lms_login_timeout_update);
		lms_login_timeout_update = 0;
		if (lms_session_expire_elem.length) {
			lms_session_expire_elem.text(sprintf("%02d:%02d",
				Math.floor(lms_login_timeout_value / 60),
				lms_login_timeout_value % 60
			));
		}
	}

    if (typeof(session_expiration_warning_reset) == 'function') {
    	session_expiration_warning_reset();
	}

    start_login_timeout(lms_login_timeout_value);
}

// Display overlib popup
function popup(content, frame, sticky, offset_x, offset_y)
{
	if (lms_sticky_popup)
		return 0;

	if (frame)
		content = '<iframe id="autoiframe" width=100 height=10 frameborder=0 scrolling=no ' +
			'src="'+content+'&popup=1"></iframe>';

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

	if (typeof locid == 'undefined')
		return checked;

	if (Array.isArray(locid))
		locids = locid;
	else
		locids = [ locid ];

    if (locids) {
		locids.forEach(function(locid) {
			var loc = document.getElementById(locid);
			if (checked) {
				//if (!init)
				//    loc.value = '';
				loc.setAttribute('readonly', true);
			} else {
				loc.removeAttribute('readonly');
			}
		});
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

	if (document.forms[form].elements[elemname1].value) {
		document.forms[form].elements[elemname2].value = document.forms[form].elements[elemname1].value;

		$( document.forms[form].elements[elemname1] ).trigger( 'keyup' );
		$( document.forms[form].elements[elemname2] ).trigger( 'reset_customer' );
	}
}

function generate_random_string(length, characters) {
	if (typeof length === 'undefined')
		length = 10;
	if (typeof characters === 'undefined')
		characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	charactersLength = characters.length;
	randomString = '';
	for (var i = 0; i < length; i++)
		randomString += characters[Math.floor(Math.random() * charactersLength)];
	return randomString;
}

function validate_random_string(string, min_size, max_size, characters) {
	if (string.length < min_size || string.length > max_size) {
		return false;
	}
	for (var i = 0; i < characters.length; i++) {
		string = string.split(characters[i]).join('');
	}
	return !string.length;
}

function get_size_unit(size) {
	if (size > 10 * 1024 * 1024 * 1024)
		return {
			size: (size / (1024 * 1024 * 1024)).toFixed(2),
			unit: 'GiB'
		};
	else if (size > 10 * 1024 * 1024)
		return {
			size: (size / (1024 * 1024)).toFixed(2),
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
	if (!ids || String(ids).length == 0)
		return 0;

	$.ajax('?m=customerinfo&api=1&ajax=1', {
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
	if ( $(elem).val().length == 0 ) {
		$(elem).nextAll('.customername').html('');
		return 0;
	}

	_getCustomerNames([ $(elem).val() ], function(data, textStatus, jqXHR) {
		if (typeof data.error !== 'undefined') {
			$(elem).nextAll('.customername').html( data.error );
			return 0;
		}

		$(elem).nextAll('.customername').html(data.customernames[$(elem).val()] === undefined ? ''
			: '<a href="?m=customerinfo&id=' + $(elem).val() + '">' + data.customernames[$(elem).val()] + '</a>');
	});

	$(elem).trigger('reset_customer');
	$(elem).trigger('change');
}

var customerinputs = [];

function getCustomerNameDeferred(elem) {
	customerinputs.push(elem);
}

if (typeof $ !== 'undefined') {
	$(function() {
		var cids = [];
		$.each(customerinputs, function(index, elem) {
			cids.push($(elem).val());
		});
		_getCustomerNames(cids, function(data, textStatus, jqXHR) {
			$.each(customerinputs, function(index, elem) {
				if ( $(elem).val().length == 0 ) {
					$(elem).nextAll('.customername').html('');
					return 0;
				}

				if (data.error != undefined) {
					$(elem).nextAll('.customername').html( data.error );
					return 0;
				}

				$(elem).nextAll('.customername').html(data.customernames[$(elem).val()] === undefined ?
					'' : '<a href="?m=customerinfo&id=' + $(elem).val() + '">' + data.customernames[$(elem).val()] + '</a>');
			});
		});

		$('a[rel="external"]')
			.on('click keypress', function() {
				window.open(this.href);
				return false;
			});
	});
}

/*!
 * \brief Auto hide left vertical menu on print
 */
var show_menu_after_print = -1;

var LMS_beforePrintEvent = function() {
	if (typeof $ === 'undefined' || show_menu_after_print > -1) {
		return;
	}
	if ($('#pageleftbar').hasClass('pageleftbar-hidden')) {
		show_menu_after_print = 0;
	} else {
		$( "#lms-ui-main-menu-toggle" ).trigger( "click" );
		show_menu_after_print = 1;
	}
};

var LMS_afterPrintEvent = function() {
	if (typeof $ === 'undefined' || show_menu_after_print == -1) {
		return;
	}
	if (show_menu_after_print == 1) {
		$( "#lms-ui-main-menu-toggle" ).trigger( "click" );
		show_menu_after_print = -1;
	}
};

if (window.matchMedia) {
    var mediaQueryList = window.matchMedia('print');

    mediaQueryList.addListener(function(mql) {
        if (mql.matches) {
            LMS_beforePrintEvent();
        } else {
            LMS_afterPrintEvent();
        }
    });
}

window.onbeforeprint = LMS_beforePrintEvent;
window.onafterprint  = LMS_afterPrintEvent;

/*!
 * \brief Returns customer addresses by id.
 *
 * \param  int   id customer id
 * \return json     customer addresses
 * \return false    if id is incorrect
 */
function getCustomerAddresses( id, on_success ) {
    return _getAddressList( 'customeraddresses', id, on_success );
}

/*!
 * \brief Returns single address by id.
 *
 * \param  int   id address id
 * \return json     address data
 * \return false    if id is incorrect
 */
function getSingleAddress( address_id, on_success ) {
    return _getAddressList( 'singleaddress', address_id, on_success );
}

function _getAddressList( action, v, on_success ) {
    action = action.toLowerCase();

    var addresses = [];
    var async = typeof on_success === 'function';

    // test to check if 'id' is integer
    if ( Math.floor(v) != v || !$.isNumeric(v) ) {
        if (async) {
            on_success(addresses);
        }
        return addresses;
    }

    // check id value
    if ( v <= 0 ) {
        if (async) {
            on_success(addresses);
        }
        return addresses;
    }

    var url;

    switch ( action ) {
        case 'customeraddresses':
            url = "?m=customeraddresses&action=getcustomeraddresses&api=1&id=" + v;
        break;

        case 'singleaddress':
            url = "?m=customeraddresses&action=getsingleaddress&api=1&id=" + v;
        break;
    }


    $.ajax({
        url    : url,
        dataType: "json",
        async  : async
    }).done(function(data) {
        $.each( data, function( i, v ) {
            data[i].location = $("<div/>").html( v.location ).text();
        });
        if (async) {
            on_success(data);
        } else {
            addresses = data;
        }
    });

    return addresses;
}

/*!
 * \brief Concatenate address fields to one string.
 *
 * \param string address
 * \param string latitude_id id of latitude input
 * \param string latitude_id id of longitude input
 */
function location_str(data) {
	city = data.city;
	street = data.street;
	house = data.house;
	flat = data.flat;
	if (data.hasOwnProperty('zip'))
		zip = data.zip;
	else
		zip = null;
	if (data.hasOwnProperty('postoffice'))
		postoffice = data.postoffice;
	else
		postoffice = null;
	if (data.hasOwnProperty('state'))
		state = data.state;
	else
		state = null;

	var location = '';

	if (state && state.length) {
		location = state + ", ";
	}

	if (zip && zip.length) {
		location += zip + " ";
	}

	if (postoffice && postoffice.length && postoffice != city) {
		location += postoffice + ", ";
	}

	if (city.length && (!postoffice || !postoffice.length || postoffice == city || street.length)) {
		location += city + ", ";
	}
	if (street.length) {
		location += street;
	} else
		location += city;

	if (location.length) {
		if (house.length && flat.length) {
			location += " " + house + "/" + flat;
		} else if (house.length) {
			location += " " + house;
		}
	}

	return location;
}

/*!
 * \brief Generate unique id.
 *
 * \return int
 */
function lms_uniqid() {
    var uid = Date.now();

    // do nothing, only wait
    // secure for use two times in a row
    while ( uid == Date.now() ) {}

    return uid;
}

function GusApiGetCompanyDetails(searchType, searchData, on_success) {
	$.ajax({
		url: '?m=gusapi',
		data: {
			searchtype: searchType,
			searchdata: searchData
		}
	}).done(function(data) {
		if (data.hasOwnProperty('error')) {
			alert(data.error);
			return;
		}
		if (data.hasOwnProperty('warning')) {
			return;
		}
		on_success(data);
	});
}

function GusApiFinished(fieldPrefix, details) {
	$.each(details, function(key, value) {
		if (key == 'addresses') {
			$.each(value, function(idx, addresses) {
				if (!Array.isArray(addresses)) {
					addresses = [ addresses ];
				}
				$.each(addresses, function(addressnr, address) {
					$.each(address, function (addresskey, addressvalue) {
						$('[name="' + fieldPrefix + '[addresses][' + addressnr + '][' + addresskey + ']"]').val(
							typeof(addressvalue) == 'string' ? addressvalue : '');
					});
					if ((address.location_state > 0) != $('[name="' + fieldPrefix + '[addresses][' + addressnr + '][teryt]"]').prop('checked')) {
						$('[name="' + fieldPrefix + '[addresses][' + addressnr + '][teryt]"]').click();
					}
					$('[name="' + fieldPrefix + '[addresses][' + addressnr + '][location_city_name]"]').trigger('input');
				});
			});
		} else {
			$('[name="' + fieldPrefix + '[' + key + ']"]').val(typeof(value) == 'string' ? value : '');
		}
	});
}

function osm_get_zip_code(search, on_success) {
	var data = {
		format: 'json',
		city: search.city,
		street: search.house + (search.street.length ? ' ' + search.street : ''),
		addressdetails: 1
	}
	if (search.countryid.length) {
		data.country = search.country;
	}
	$.ajax({
		url: 'https://nominatim.openstreetmap.org/search',
		"data": data
	}).done(function(data) {
		if (typeof(on_success) == 'function') {
			if (data.length && data[0].hasOwnProperty('address') && data[0].address.hasOwnProperty('postcode')) {
				on_success(data[0].address.postcode);
			}
		}
	});
}

function pna_get_zip_code(search, on_success) {
	$.ajax({
		url: '?m=zipcode&api=1',
		data: search
	}).done(function(data) {
		if (typeof(on_success) == 'function') {
			on_success(data);
		}
	});
}

function autoiframe_correct_size() {
	$('#autoiframe', window.parent.document).on('load', function() {
		var width = 0;
		var scrollBarWidth = 0;
		$('frame', this.contentDocument).each(function(key, elem) {
			if (elem.contentDocument.body.scrollWidth > width) {
				width = elem.contentDocument.body.scrollWidth;
			}
			if ($(elem).is('[scrolling="always"]')) {
				scrollBarWidth = elem.clientWidth - elem.contentDocument.body.clientWidth;
			}
		});
		width += scrollBarWidth;
		$(this).css('width', width).parent().css('width', '');
	});
}

function convert_to_units(value, threshold, multiplier) {
	if (typeof(threshold) == 'undefined') {
		threshold = 5;
	}
	if (typeof(multiplier) == 'undefined') {
		multiplier = 1000;
	}
	var unit_suffix = (multiplier == 1024 ? 'ibit' : 'bit');
	threshold = parseFloat(threshold);
	multiplier = parseFloat(multiplier);
	if (value < multiplier * multiplier * threshold) {
		return (Math.round(value * 100 / multiplier) / 100) + ' k' + unit_suffix;
	} else if (value < multiplier * multiplier * multiplier * threshold) {
		return (Math.round(value * 100 / multiplier / multiplier) / 100) + ' M' + unit_suffix;
	} else {
		return (Math.round(value * 100 / multiplier / multiplier / multiplier) / 100) +
			' G' + unit_suffix;
	}
}

function get_revdns(search) {
	$.ajax({
		url: '?m=dns&type=revdns&api=1',
		method: "POST",
		dataType: 'json',
		data: search
	}).done(function(data) {
		$.each(data, function(key, value) {
			$(key).html(value);
		});
	});
}
