//////////////////////////////////////////////////////////////////////////
// This is the implementation of the swekey ajax calls
// It uses JSON
//////////////////////////////////////////////////////////////////////////

function swekey_ajax_caller(xparams, cb) {
	var ajax_request = {}

	ajax_request.bindFunction = function(caller, object) {
		return function() {
			return caller.apply(object, [object]);
		};
	};

	ajax_request.stateChange = function(object) {
		if (ajax_request.request.readyState == 4 && cb != null) {
			try {
				ajax_request.callbackFunction(eval("(" + ajax_request.request.responseText + ")"));
			} catch (e) {
				alert("swekey ajax exception: '" + ajax_request.request.responseText + "' " + e);
			}
		}
	};

	ajax_request.getRequest = function() {
		if (window.ActiveXObject)
			return new ActiveXObject('Microsoft.XMLHTTP');
		else if (window.XMLHttpRequest)
			return new XMLHttpRequest();
		return false;
	};

	ajax_request.postBody = "";
	for (var item in xparams) {
		ajax_request.postBody += (ajax_request.postBody.length > 0 ? "&" : "") + item + "=" + encodeURI(xparams[item]);
	}

	ajax_request.callbackFunction = cb;
	ajax_request.url = swekey_integration_params.swekey_dir_url + "json/swekey_json_server.php";
	ajax_request.request = ajax_request.getRequest();

	if (ajax_request.request) {
		var req = ajax_request.request;
		req.onreadystatechange = ajax_request.bindFunction(ajax_request.stateChange, ajax_request);

		req.open("POST", ajax_request.url, true);
		req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		req.send(ajax_request.postBody);
	}
}
