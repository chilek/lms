var swekey_integration_params = {
	// strings
	'logo_gray_str'		: "No swekey plugged",
	'logo_orange_str'	: "Authenticating...",
	'logo_red_str'		: "Swekey authentication failed",
	'logo_green_str'	: "Swekey plugged and authentifid",
	'logo_green_str'	: "Swekey plugged and validated",
	'attach_ask_str'	: "A swekey authentication key has been detected. Do you want to associate it with your account?",
	'attach_success_str'	: "The plugged swekey is now attached to your account",
	'attach_failed_str'	: "Failed to attach the plugged swekey to your account",

	// logo url and position
	'logo_url'		: 'http://www.swekey.com',
	'logo_xoffset'		: '0px',
	'logo_yoffset'		: '0px',
	'loginname_width_offset': 0,
	'show_only_plugged'	: false,
	'multiple_logos'	: false,
	'dynamic_login_form'	: false,

	// branding
	'brands'		: '',

	// mandatory
	'swekey_dir_url'	: '',	// The URL of the swekey directory
	'user_input_names'	: [],	// The names of the login input fields
	'logout_url'		: null	// Page that must be called to logout the user
};

var swekey_ids = "undefined";
var swekey_logos = [];
var swekey_loginnames_input = [];
var swekey_mutltiple_loginnames_input = false;
var swekey_retry = 0;

/////////////////////////////
// Test
/////////////////////////////

function cb_ajax_test(result) {
	if (!result.ajax_performed || result.result != 'success' || result.session != 123456789) {
		alert("Ajax test returned bad result");
	} else {
		alert("Ajax test was successful");
	}
}

function test_swekey_ajax() {
	swekey_ajax_caller({'action':'ajax_test', 'result':'success', 'session':123456789, 'arr':[1,2]}, cb_ajax_test);
}

/////////////////////////////
// Login page
/////////////////////////////

var swekey_session = 0;
var swekey_ids = "unspecified";

function set_swekey_logo(color, error) {
	for (var i = 0; i < swekey_logos.length; i++) {
		if (swekey_integration_params.show_only_plugged && color == 'gray') {
			swekey_logos[i].style.display = 'none';
		} else {
			swekey_logos[i].style.display = 'none';
			swekey_logos[i].setAttribute('src', swekey_integration_params.swekey_dir_url + 'swekey-' + color + '-8x16.png');
			var title = eval('swekey_integration_params.logo_' + color + '_str');
			if (error)
				title += ' (' + error + ')';
			swekey_logos[i].setAttribute('title', title);
			swekey_logos[i].style.display = '';
		}
	}
}

function cb_swekey_validate(result) {
	if (result.session != swekey_session)
		return;

	if (result.error != null) {
		if (swekey_retry < 2) {
			swekey_retry++;
			swekey_ids = "";
			swekey_refresh_login();
		} else {
			set_swekey_logo('red', result.error);
		}

		return;
	}

	if (result.ids.length == 0) {
		set_swekey_logo('red', 'No swekey validated');
		Swekey_RefreshEmulator();
		return;
	}

	swekey_retry = 0;
	set_swekey_logo('green');

	if (result.user_name)
		for (var i = 0; i < swekey_loginnames_input.length; i++)
			swekey_loginnames_input[i].value = result.user_name;
}

function cb_get_rnd_token(result) {
	if (result.session != swekey_session)
		return;

	if (result.error != null) {
		set_swekey_logo('red', error);
		return;
	}

	if (result.rt.length != 64) {
		set_swekey_logo('red' , 'Bad RT:' + result.rt);
		return;
	}

	if (result.no_linked_otp) {
		Swekey_GetOtps(swekey_ids, result.rt, function(ids, rt, otps) {
			swekey_ajax_caller({'action':'swekey_validate', 'session':result.session, 'rt':rt, 'ids':ids, 'otps':otps}, cb_swekey_validate);
		});
	} else {
		Swekey_GetSmartOtps(swekey_ids, result.rt, function(ids, rt, otps) {
			swekey_ajax_caller({'action':'swekey_validate', 'session':result.session, 'rt':rt, 'ids':ids, 'otps':otps}, cb_swekey_validate);
		});
	}
}

function swekey_refresh_login() {
	if (swekey_integration_params.dynamic_login_form) {
		objects = document.getElementsByName('swekey_logos');
		if (objects == null || objects.length == 0) {
			setTimeout("swekey_login_onload()", 10);
			return;
		}
	}

	var ids = Swekey_ListBrandedKeyIds(swekey_integration_params.brands)
	if (ids != swekey_ids) {
		swekey_session++;
		swekey_ids = ids;

		if (ids == "") {
			set_swekey_logo('gray');
			swekey_ajax_caller({'action':'unplugged', 'session':swekey_session}, null);
		} else {
			set_swekey_logo('orange');
			swekey_ajax_caller({'action':'get_rnd_token', 'session':swekey_session}, cb_get_rnd_token);
		}
	}

	setTimeout("swekey_refresh_login()", 1000);
}

function insert_key_logo() {
	if (swekey_loginnames_input[0].offsetWidth != 0) {
		for (var i = 0; i < swekey_loginnames_input.length; i++) {
			var isIE = (navigator.userAgent.toLowerCase().indexOf('msie') >= 0);

			if (swekey_integration_params.loginname_width_offset != 0)
				swekey_loginnames_input[i].style.width = (swekey_loginnames_input[i].offsetWidth - swekey_integration_params.loginname_width_offset) + 'px';

			if (swekey_loginnames_input[i].parentNode != null) {
				if (swekey_loginnames_input[i].nextSibling == null)
					swekey_loginnames_input[i].parentNode.appendChild(swekey_logos[i]);
				else
					swekey_loginnames_input[i].parentNode.insertBefore(swekey_logos[i], swekey_loginnames_input[i].nextSibling);
			}

			try {
				if (isIE) {
					swekey_logos[i].style.verticalAlign = 'middle';
					swekey_logos[i].style.position = 'relative';
					swekey_logos[i].style.left = swekey_integration_params.logo_xoffset;
					swekey_logos[i].style.top = swekey_integration_params.logo_yoffset;
					if (swekey_loginnames_input[i].currentStyle.styleFloat == "right")
						swekey_logos[i].style.styleFloat = "right";
				} else {
					if (window.getComputedStyle(swekey_loginnames_input[i], "").cssFloat == "right")
						swekey_logos[i].style.cssFloat = "right";
				}
			} catch (e) { }
		}
		swekey_ids = "";
		setTimeout("swekey_refresh_login()", 1);
	} else {
		setTimeout("insert_key_logo()", 10);
	}
}

function swekey_login_onload() {
	swekey_loginnames_input = [];

	for (var i = 0; i < swekey_integration_params.user_input_names.length; i++) {
		if (swekey_integration_params.user_input_names[i] != "") {
			objects = document.getElementsByName(swekey_integration_params.user_input_names[i]);
			if (objects != null)
				for (var j = 0; j < objects.length; j++)
					if (objects[j].tagName.toLowerCase() == 'input')
						swekey_loginnames_input[swekey_loginnames_input.length] = objects[j];
		}
	}

	if (!swekey_integration_params.multiple_logos && swekey_loginnames_input.length > 0)
		swekey_loginnames_input = [swekey_loginnames_input[0]];

	swekey_logos = [];

	if (swekey_loginnames_input.length > 0) {
		for (var i = 0; i < swekey_loginnames_input.length; i++) {
			swekey_logos[i] = document.createElement('img');
			swekey_logos[i].setAttribute('id', 'swekey_logos');
			swekey_logos[i].setAttribute('name', 'swekey_logos');
			swekey_logos[i].setAttribute('onClick', 'window.open("' + swekey_integration_params.logo_url + '")');
			swekey_logos[i].setAttribute('style', 'width: 8px; height: 16px; padding: 0px 2px 0px 2px; border-spacing: 0px; margin: 0px; vspace: 0px; hspace: 0px; frameborder: no; vertical-align: middle;' + 'position: relative;  left: ' + swekey_integration_params.logo_xoffset + '; top: ' + swekey_integration_params.logo_yoffset + ';');
			swekey_logos[i].setAttribute('src', swekey_integration_params.swekey_dir_url + 'swekey-gray-8x16.png');
		}

		insert_key_logo();
	} else if (swekey_integration_params.dynamic_login_form) {
		setTimeout("swekey_login_onload()", 1000);
	}
}

function swekey_login_integrate() {
	document.cookie = "swekey_proposed=''; path=/;";
	swekey_add_load_event(swekey_login_onload);
}

/////////////////////////////
// The user is logged but not attached
/////////////////////////////

function get_cookie(cookie_name) {
	var results = document.cookie.match('(^|;) ?' + cookie_name + '=([^;]*)(;|$)');

	if (results)
		return(unescape(results[2]));
	else
		return null;
}

function isTemporaryPage() {
	try {
		var metaElements = document.getElementsByTagName('META');
		for (var i = 0; i < metaElements.length; i++) {
			var attrs = metaElements[i].attributes;
			for (var j = 0; j < attrs.length; j++)
				if (attrs[j].name.toLowerCase() == 'http-equiv' && attrs[j].value.toLowerCase() == 'refresh')
					return true;
		}
	} catch (e) { }

	return false;
}

function cb_attach_swekey(result) {
	if (result.error != null) {
		alert(swekey_integration_params.attach_failed_str + "\n" + result.error);
	} else {
		alert(swekey_integration_params.attach_success_str);
	}
}

function swekey_propose_to_attach(uid) {
	if (isTemporaryPage())
		return;

	var id = Swekey_ListBrandedKeyIds(swekey_integration_params.brands).substring(0, 32);
	if (id != "") {
		if (get_cookie('swekey_proposed') == id)
			return;

		document.cookie = "swekey_proposed=" + id + "; path=/;";
		if (confirm(swekey_integration_params.attach_ask_str)) {
			swekey_ajax_caller({'action':'attach_swekey', 'swekey_id':id, 'lms_user_id':uid}, cb_attach_swekey);
		}
	} else {
		setTimeout("swekey_propose_to_attach(uid)", 2000);
	}
}

//////////////////////////////////////////////////////////////
// This part is called when a user is logged with a swekey.
// We check that the swekey is still plugged otherwise we
// logout the user
//////////////////////////////////////////////////////////////

var swekey_max_tries = 3;

function check_swekey_presence(swekey_id) {
	if (swekey_integration_params.logout_url == null || swekey_id == null || swekey_id.length != 32)
		return;

	if (Swekey_ListKeyIds().indexOf(swekey_id) < 0) {
		if (Swekey_Loaded())
			swekey_max_tries --;
	} else {
		swekey_max_tries = 2;
	}

	if (swekey_max_tries < 0) {
		swekey_ajax_caller({'action':'unplugged', 'session':0}, function() {
			top.location = swekey_integration_params.logout_url;
		});
	} else {
		setTimeout("check_swekey_presence('" + swekey_id + "')", 1000);
	}
}

//////////////////////////////////////////////////////////////
// Utilities
//////////////////////////////////////////////////////////////

function swekey_add_load_event(func) {
	var oldonload = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = func;
	} else {
		window.onload = function() {
			if (oldonload) {
				oldonload();
			}
			func();
		}
	}
}
