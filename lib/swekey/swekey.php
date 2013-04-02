<?php
/**
 * Library that provides common functions that are used to help integrating Swekey Authentication in a PHP web site
 * Version 1.3
 *
 * History:
 * 1.3 Added support for backup severs
 *      OTP verification can be disabled when there is no internet connection (AllowWhenNoNetwork)
 * 1.2 Use curl (widely installed) to query the server
 *     Fixed a possible tempfile race attack
 *     Random token cache can now be disabled
 * 1.1 Added Swekey_HttpGet function that support faulty servers
 *     Support for custom servers
 * 1.0 First release
 *
 */

/**
 * Errors codes
 */
define("SWEKEY_ERR_INVALID_DEV_STATUS", 901);	// The satus of the device is not SWEKEY_STATUS_OK
define("SWEKEY_ERR_INTERNAL", 902);		// Should never occurd
define("SWEKEY_ERR_OUTDATED_RND_TOKEN", 910);	// You random token is too old
define("SWEKEY_ERR_INVALID_OTP", 911);		// The otp was not correct

/**
 * Those errors are considered as an attack and your site will be blacklisted during one minute
 * if you receive one of those errors
 */
define("SWEKEY_ERR_BADLY_ENCODED_REQUEST", 920);
define("SWEKEY_ERR_INVALID_RND_TOKEN", 921);
define("SWEKEY_ERR_DEV_NOT_FOUND", 922);

/**
 * The last error of an operation is alway put in this global var
 */
global $gSwekeyLastError;
$gSwekeyLastError = 0;

global $gSwekeyLastResult;
$gSwekeyLastResult = "<not set>";

/**
 * Servers addresses
 * Use the  Swekey_SetXxxServer($server) functions to set them
 */
global $gSwekeyCheckServer;
if (empty($gSwekeyCheckServer))
	$gSwekeyCheckServer = 'http://auth-check.musbe.net';

global $gSwekeyRndTokenServer;
if (empty($gSwekeyRndTokenServer))
	$gSwekeyRndTokenServer = 'http://auth-rnd-gen.musbe.net';

global $gSwekeyStatusServer;
if (empty($gSwekeyStatusServer))
	$gSwekeyStatusServer = 'http://auth-status.musbe.net';

global $gSwekeyCA;

global $gSwekeyTokenCacheEnabled;
if (!isset($gSwekeyTokenCacheEnabled))
	$gSwekeyTokenCacheEnabled = false;

global $gSwekeyAllowWhenNoNetwork;
if (!isset($gSwekeyAllowWhenNoNetwork))
	$gSwekeyAllowWhenNoNetwork = false;

/**
 *  Insert the plugin and the activex in the page.
 *  You should not need to include the plugin statically in the page since
 *  swekey.js creates it dynamically.
 *  Some browsers may however have trouble inserting dynamic content so you can use this method for those brownsers
 *  @access public
 */
function Swekey_PluginInnerHTML() {
	if(strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
		return '<object id="swekey_activex" style="display: none" CLASSID="CLSID:8E02E3F9-57AA-4EE1-AA68-A42DD7B0FADE"></object>' . "\n";
	} else {
		// do not use display:none beacause the plugin can not be scripted in that case for Firefox
		return '<embed type="application/fbauth-plugin" id="swekey_plugin" style="height: 0px; width: 0px" pluginspage="http://downloads.swekey.com?download_category=installer"/>' . "\n";
	}
}

/**
 *  Insert the plugin and the activex in the page.
 *  You should not need to include the plugin statically in the page since
 *  swekey.js creates it dynamically.
 *  Some browsers may however have trouble inserting dynamic content so you can use this method for those brownsers
 *  @access public
 */
function Swekey_InsertPlugin() {
	echo Swekey_PluginInnerHTML();
}

/**
 *  Change the address of the Check server.
 *  If $server is empty the default value 'http://auth-check.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetCheckServer($server) {
	global $gSwekeyCheckServer;
	if (empty($server))
		$gSwekeyCheckServer = 'http://auth-check.musbe.net';
	else
		$gSwekeyCheckServer = $server;
}

/**
 *  Change the address of the Random Token Generator server.
 *  If $server is empty the default value 'http://auth-rnd-gen.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetRndTokenServer($server) {
	global $gSwekeyRndTokenServer;
	if (empty($server))
		$gSwekeyRndTokenServer = 'http://auth-rnd-gen.musbe.net';
	else
		$gSwekeyRndTokenServer = $server;
}

/**
 *  Change the address of the Satus server.
 *  If $server is empty the default value 'http://auth-status.musbe.net' will be used
 *
 *  @param  server              The protocol and hostname to use
 *  @access public
 */
function Swekey_SetStatusServer($server) {
	global $gSwekeyStatusServer;
	if (empty($server))
		$gSwekeyStatusServer = 'http://auth-status.musbe.net';
	else
		$gSwekeyStatusServer = $server;
}

/**
 *  Change the certificat file in case of the the severs use https instead of http
 *
 *  @param  cafile              The path of the crt file to use
 *  @access public
 */
function Swekey_SetCAFile($cafile) {
	global $gSwekeyCA;
	$gSwekeyCA = $cafile;
}

/**
 *  Set the allowWhenNoNetwork  setting
 *
 *  @param  allowWhenNoNetwork    When false (the default) users wont be able to login when the server is not connected to the internet
 *  @access public
 */
function Swekey_AllowWhenNoNetwork($allowWhenNoNetwork) {
	global $gSwekeyAllowWhenNoNetwork;
	$gSwekeyAllowWhenNoNetwork = $allowWhenNoNetwork;
}

/**
 *  Enable or disable the random token caching
 *  Because everybody has full access to the cache file, it can be a DOS vulnerability
 *  So disable it if you are running in a non secure enviromnement
 *
 *  @param  $enable
 *  @access public
 */
function Swekey_EnableTokenCache($enable) {
	global $gSwekeyTokenCacheEnabled;
	$gSwekeyTokenCacheEnabled = !empty($enable);
}

/**
 *  Return the last error.
 *
 *  @return                     The Last Error
 *  @access public
 */
function Swekey_GetLastError() {
	global $gSwekeyLastError;
	return $gSwekeyLastError;
}

/**
 *  Return the last result.
 *
 *  @return                     The Last Error
 *  @access public
 */
function Swekey_GetLastResult() {
	global $gSwekeyLastResult;
	return $gSwekeyLastResult;
}

/**
 *  Send a synchronous request to the  server.
 *  This function tries to coonect to the default server then switch to the backup one
 *
 *  @param  server              The server part of the URL
 *  @param  path                The path part of the URL
 *  @param  response_code       The response code or null in case of network error
 *  @return                     The body of the response or "" in case of error
 *  @access private
 */
function Swekey_HttpGet($server, $path, &$response_code) {
	$res = Swekey_HttpGet_Once($server, $path, 4, 4, $response_code);
	if ($response_code != null)
		return $res;

	error_log("SWEKEY_WARNING: Retrying $server");
	$res = Swekey_HttpGet_Once($server, $path, 8, 8, $response_code);
	if ($response_code != null)
		return $res;

	error_log("SWEKEY_WARNING: Trying auth-backup-1");
	$res = Swekey_HttpGet_Once("http://auth-backup-1.musbe.net:8080", $path, 8, 8, $response_code);
	if ($response_code != null)
		return $res;

	error_log("SWEKEY_WARNING: Trying auth-backup-2");
	Swekey_HttpGet_Once("http://auth-backup-2.musbe.net:8080", $path, 16, 16, $response_code);
	if ($response_code != null)
		return $res;

	error_log("SWEKEY_WARNING: Trying auth-backup-3");
	Swekey_HttpGet_Once("http://auth-backup-3.musbe.net:8080", $path, 16, 16, $response_code);
	if ($response_code != null)
		return $res;

	error_log("SWEKEY_ERROR: Request $path failed");
	return $res;
}

/**
 *  Send a synchronous request to the  server.
 *  This function manages timeout then will not block if one of the server is down
 *
 *  @param  server              The server part of the URL
 *  @param  path                The path part of the URL
 *  @param  response_code       The response code or null in case of network error
 *  @return                     The body of the response or "" in case of error
 *  @access private
 */
function Swekey_HttpGet_Once($server, $path, $connect_timeout_in_secs, $timeout_in_secs, &$response_code) {
	$url = $server . $path;

	global $gSwekeyLastError;
	$gSwekeyLastError = 0;
	global $gSwekeyLastResult;
	$gSwekeyLastResult = "<not set>";

	if (substr($server, 0, 8) == "https://") {
		$timeout_in_secs += 4;
		$connect_timeout_in_secs += 4;
	}

	// use curl if available
	if (function_exists('curl_init')) {
		$sess = curl_init($url);
		if (substr($url, 0, 8) == "https://") {
			global $gSwekeyCA;
			$caFileOk = false;
			if (!empty($gSwekeyCA)) {
				if (file_exists($gSwekeyCA)) {
					if (!curl_setopt($sess, CURLOPT_CAINFO, $gSwekeyCA))
						error_log("SWEKEY_ERROR: Could not set CA file: " . curl_error($sess));
					else
						$caFileOk = true;
				} else {
					error_log("SWEKEY_ERROR: Could not find CA file $gSwekeyCA getting $url");
				}
			}

			if ($caFileOk) {
				curl_setopt($sess, CURLOPT_SSL_VERIFYHOST, '2');
				curl_setopt($sess, CURLOPT_SSL_VERIFYPEER, '2');
			} else {
				curl_setopt($sess, CURLOPT_SSL_VERIFYHOST, '0');
				curl_setopt($sess, CURLOPT_SSL_VERIFYPEER, '0');
			}
		}

		curl_setopt($sess, CURLOPT_CONNECTTIMEOUT, $connect_timeout_in_secs);
		curl_setopt($sess, CURLOPT_TIMEOUT, $timeout_in_secs);
		curl_setopt($sess, CURLOPT_RETURNTRANSFER, '1');
		$res = curl_exec($sess);
		$response_code = curl_getinfo($sess, CURLINFO_HTTP_CODE);
		$curlerr = curl_error($sess);
		curl_close($sess);

		if ($response_code == 200) {
			$gSwekeyLastResult = $res;
			return $res;
		}

		if (!empty($response_code)) {
			$gSwekeyLastError = $response_code;
			error_log("SWEKEY_WARNING: Error $gSwekeyLastError ($curlerr) getting $url");
			return "";
		}

		$response_code = null; // Request Timeout => retry
		$gSwekeyLastError = 408; // => Timeout
		error_log("SWEKEY_WARNING: Error $curlerr getting $url");
		return "";
	}

	// use pecl_http if available
	if (class_exists('HttpRequest')) {
		// retry if one of the server is down
		$r = new HttpRequest($url);
		$options = array('timeout' => $timeout_in_secs);

		if (substr($url,0, 6) == "https:") {
			$sslOptions = array();
			$sslOptions['verifypeer'] = true;
			$sslOptions['verifyhost'] = true;

			$capath = __FILE__;
			$name = strrchr($capath, '/');
			if (empty($name)) // windows
				$name = strrchr($capath, '\\');
			$capath = substr($capath, 0, strlen($capath) - strlen($name) + 1) . 'musbe-ca.crt';

			if (!empty($gSwekeyCA))
				$sslOptions['cainfo'] = $gSwekeyCA;

			$options['ssl'] = $sslOptions;
		}

		$r->setOptions($options);

		$reply = $r->send();
		$res = $reply->getBody();
		$info = $r->getResponseInfo();
		$response_code = $info['response_code'];

		if ($response_code == 200) {
			$gSwekeyLastResult = $res;
			return $res;
		}

		if (!empty($response_code)) {
			$gSwekeyLastError = $response_code;
			error_log("SWEKEY_WARNING: Error $gSwekeyLastError getting $url with HttpRequest");
			return "";
		}

		$response_code = null; // Request Timeout => retry
		$gSwekeyLastError = 408; // => Timeout
		error_log("SWEKEY_WARNING: Error $gSwekeyLastError getting $url with HttpRequest");
		return "";
	}

	global $http_response_header;

	$res = @file_get_contents($url);
	$response_code = substr($http_response_header[0], 9, 3); //HTTP/1.0

	if ($response_code == 200) {
		$gSwekeyLastResult = $res;
		return $res;
	}

	if (!empty($response_code)) {
		$gSwekeyLastError = $response_code;
		error_log("SWEKEY_WARNING: Error $gSwekeyLastError getting $url with file_get_contents");
		return "";
	}

	$response_code = null; // Request Timeout => retry
	$gSwekeyLastError = 408; // => Timeout
	error_log("SWEKEY_WARNING: Error $gSwekeyLastError getting $url with file_get_contents");
	return "";
}

/**
 *  Get a Random Token from a Token Server
 *  The RT is a 64 vhars hexadecimal value
 *  You should better use Swekey_GetFastRndToken() for performance
 *  @access public
 */
function Swekey_GetRndToken() {
	global $gSwekeyAllowWhenNoNetwork;
	global $gSwekeyRndTokenServer;

	$res = Swekey_HttpGet($gSwekeyRndTokenServer, '/FULL-RND-TOKEN', $response_code);
	if ($response_code == null && $gSwekeyAllowWhenNoNetwork)
		return "0000000000000000000000000000000000000000000000000000000000000000";

	return($res);
}

/**
 *  Get a Half Random Token from a Token Server
 *  The RT is a 64 vhars hexadecimal value
 *  Use this value if you want to make your own Swekey_GetFastRndToken()
 *  @access public
 */
function Swekey_GetHalfRndToken() {
	global $gSwekeyAllowWhenNoNetwork;
	global $gSwekeyRndTokenServer;

	$res = Swekey_HttpGet($gSwekeyRndTokenServer, '/HALF-RND-TOKEN', $response_code);
	if ($response_code == null && $gSwekeyAllowWhenNoNetwork)
		return "0000000000000000000000000000000000000000000000000000000000000000";

	return($res);
}

/**
 *  Get a Half Random Token
 *  The RT is a 64 vhars hexadecimal value
 *  This function get a new random token and reuse it.
 *  Token are refetched from the server only once every 30 seconds.
 *  You should always use this function to get half random token.
 *  @access public
 */
function Swekey_GetFastHalfRndToken() {
	global $gSwekeyTokenCacheEnabled;

	$res = "";
	$cachefile = "";

	// We check if we have a valid RT is the session
	if (isset($_SESSION['-swekey-rnd-token-date']))
		if (time() - $_SESSION['-swekey-rnd-token-date'] < 30)
			$res = $_SESSION['-swekey-rnd-token'];

	// If not we try to get it from a temp file (PHP >= 5.2.1 only)
	if (strlen($res) != 32 && $gSwekeyTokenCacheEnabled) {
		$tempdir = '';
		if (function_exists('sys_get_temp_dir'))
			$tempdir = sys_get_temp_dir();
		else
			$tempdir = '/tmp';

		if (is_dir($tempdir)) {
			$cachefile = $tempdir . "/swekey-rnd-token-" . get_current_user();
			$modif = filemtime($cachefile);
			if ($modif != false) {
				if (time() - $modif < 30) {
					$res = @file_get_contents($cachefile);
					if (strlen($res) != 32) {
						$res = "";
					} else {
						$_SESSION['-swekey-rnd-token'] = $res;
						$_SESSION['-swekey-rnd-token-date'] = $modif;
					}
				}
			}
		}
	}

	// If we don't have a valid RT here we have to get it from the server
	if (strlen($res) != 32) {
		$res = substr(Swekey_GetHalfRndToken(), 0, 32);
		$_SESSION['-swekey-rnd-token'] = $res;
		$_SESSION['-swekey-rnd-token-date'] = time();
		if (!empty($cachefile)) {
			// we unlink the file so no possible tempfile race attack (thanks Thijs)
			unlink($cachefile);
			$file = fopen($cachefile, "x");
			if ($file != FALSE) {
				@fwrite($file, $res);
				@fclose($file);
			}
		}
	}

	return $res."00000000000000000000000000000000";
}

/**
 *  Get a Random Token
 *  The RT is a 64 vhars hexadecimal value
 *  This function generates a unique random token for each call but call the
 *  server only once every 30 seconds.
 *  You should always use this function to get random token.
 *  @access public
 */
function Swekey_GetFastRndToken() {
	$res = Swekey_GetFastHalfRndToken();

	if (strlen($res) == 64) {
		// Avoid a E_NOTICE when strict is enabled
		if (function_exists('date_default_timezone_set'))
			date_default_timezone_set('Greenwich');

		return substr($res, 0, 32).strtoupper(md5("Musbe Authentication Key".mt_rand().date(DATE_ATOM)));
	}

	return "";
}

/**
 *  Checks that an OTP generated by a Swekey is valid
 *
 *  @param  id                  The id of the swekey
 *  @param rt                   The random token used to generate the otp
 *  @param otp                  The otp generated by the swekey
 *  @return                     true or false
 *  @access public
 */
function Swekey_CheckOtp($id, $rt, $otp) {
	global $gSwekeyAllowWhenNoNetwork;
	global $gSwekeyCheckServer;

	$res = Swekey_HttpGet($gSwekeyCheckServer, '/CHECK-OTP/' . $id . '/' . $rt . '/' . $otp, $response_code);
	if ($response_code == null && $gSwekeyAllowWhenNoNetwork)
		return true;

	return $response_code == 200 && $res == "OK";
}

/**
 *  Checks that an OTP generated by a Swekey for the specified host is valid
 *
 *  @param  id                  The id of the swekey
 *  @param rt                   The random token used to generate the otp
 *  @param host                 The hostname of the page the otp was calculated
 *  @param otp                  The otp generated by the swekey
 *  @return                     true or false
 *  @access public
 */
function Swekey_CheckLinkedOtp($id, $rt, $host, $otp) {
	global $gSwekeyAllowWhenNoNetwork;
	global $gSwekeyCheckServer;

	$res = Swekey_HttpGet($gSwekeyCheckServer, '/CHECK-LINKED-OTP/' . $id . '/' . $rt . '/' . $otp . '/' . $host, $response_code);
	if ($response_code == null && $gSwekeyAllowWhenNoNetwork)
		return true;

	return $response_code == 200 && $res == "OK";
}

/**
 *  Calls Swekey_CheckOtp or Swekey_CheckLinkedOtp depending if we are in
 *  an https page or not
 *
 *  @param  id                  The id of the swekey
 *  @param rt                   The random token used to generate the otp
 *  @param otp                  The otp generated by the swekey
 *  @return                     true or false
 *  @access public
 */
function Swekey_CheckSmartOtp($id, $rt, $otp) {
	if (!empty($_SERVER['HTTPS']))
		return Swekey_CheckLinkedOtp($id, $rt, $_SERVER['HTTP_HOST'], $otp);

	return Swekey_CheckOtp($id, $rt, $otp);
}

/**
 * Values that are associated with a key.
 * The following values can be returned by the Swekey_GetStatus() function
 */
define("SWEKEY_STATUS_OK", 0);
define("SWEKEY_STATUS_NOT_FOUND", 1);	// The key does not exist in the db
define("SWEKEY_STATUS_INACTIVE", 2);	// The key has never been activated
define("SWEKEY_STATUS_LOST", 3);	// The user has lost his key
define("SWEKEY_STATUS_STOLEN", 4);	// The key was stolen
define("SWEKEY_STATUS_FEE_DUE", 5);	// The annual fee was not paid
define("SWEKEY_STATUS_OBSOLETE", 6);	// The hardware is no longer supported
define("SWEKEY_STATUS_UNKOWN", 201);	// We could not connect to the authentication server

/**
 * Values that are associated with a key.
 * The Javascript Api can also return the following values
 */
define("SWEKEY_STATUS_REPLACED", 100);		// This key has been replaced by a backup key
define("SWEKEY_STATUS_BACKUP_KEY", 101);	// This key is a backup key that is not activated yet
define("SWEKEY_STATUS_NOTPLUGGED", 200);	// This key is not plugged in the computer

/**
 *  Return the text corresponding to the integer status of a key
 *
 *  @param  status              The status
 *  @return                     The text corresponding to the status
 *  @access public
 */
function Swekey_GetStatusStr($status) {
	switch($status) {
		case SWEKEY_STATUS_OK: return 'OK';
		case SWEKEY_STATUS_NOT_FOUND: return 'Key does not exist in the db';
		case SWEKEY_STATUS_INACTIVE: return 'Key not activated';
		case SWEKEY_STATUS_LOST: return 'Key was lost';
		case SWEKEY_STATUS_STOLEN: return 'Key was stolen';
		case SWEKEY_STATUS_FEE_DUE: return 'The annual fee was not paid';
		case SWEKEY_STATUS_OBSOLETE: return 'Key no longer supported';
		case SWEKEY_STATUS_REPLACED: return 'This key has been replaced by a backup key';
		case SWEKEY_STATUS_BACKUP_KEY: return 'This key is a backup key that is not activated yet';
		case SWEKEY_STATUS_NOTPLUGGED: return 'This key is not plugged in the computer';
		case SWEKEY_STATUS_UNKOWN: return 'Unknow Status, could not connect to the authentication server';
	}
	return 'unknown status ' . $status;
}

/**
 *  If your web site requires a key to login you should check that the key
 *  is still valid (has not been lost or stolen) before requiring it.
 *  A key can be authenticated only if its status is SWEKEY_STATUS_OK
 *  @param  id                  The id of the swekey
 *  @return                     The status of the swekey
 *  @access public
 */
function Swekey_GetStatus($id) {
	global $gSwekeyStatusServer;

	$res = Swekey_HttpGet($gSwekeyStatusServer, '/GET-STATUS/' . $id, $response_code);
	if ($response_code == 200)
		return intval($res);

	global $gSwekeyAllowWhenNoNetwork;
	if ($gSwekeyAllowWhenNoNetwork)
		return SWEKEY_STATUS_OK;

	return SWEKEY_STATUS_UNKOWN;
}

?>
