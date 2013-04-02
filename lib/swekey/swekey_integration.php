<?php

include_once(dirname(__FILE__) . '/swekey.php');

class SwekeyIntegration {
	var $ajax_test_mode;
	var $is_user_logged;
	var $swekey_dir_url;
	var $show_result_url;
	var $logout_url;
	var $swekey_id_of_logged_user;
	var $input_names;
	var $multiple_logos;
	var $dynamic_login_form;
	var $lang;
	var $logFile;
	var $show_debug_info;
	var $session_id;

	function LogStr($text) {
		if (!empty($this->logFile))
			error_log(">" . $text . "\n", 3, $this->logFile);
	}

	function LocalizedStr($strId) {
		return '';
	}

	function LoadSession($iCreateIfNotExist = false) {
		$dir_path = session_save_path();
		if (empty($dir_path))
			$dir_path = "/tmp";

		$path = null;
		if (!empty($_COOKIE['swekey_session_id']) && mb_ereg('^[a-f0-9]{32}$', $_COOKIE['swekey_session_id'])) {
			$path = $dir_path . "/" . $_COOKIE['swekey_session_id'] . ".swekey_session";
			$time = @filemtime($path);
			if ($time == FALSE) {
				$path = null;
			} else if ($time < time() - 60 * 5) {
				unlink($path);
				$path = null;
			}
		}

		if ($path != null) {
			$res = @unserialize(@file_get_contents($path));
			$this->session_id = $_COOKIE['swekey_session_id'];
			return $res == null ? array() : $res;
		}

		if (!$iCreateIfNotExist)
			return array();

		$this->session_id = md5("swekey_SESSION" . mt_rand() . date(DATE_ATOM) . time() . $_SERVER['REMOTE_ADDR']);
		setcookie ("swekey_session_id", $this->session_id, 0, '/');
		return array();
	}

	function SaveSession($data) {
		if (empty($this->session_id))
			return;

		$dir_path = session_save_path();
		if (empty($dir_path))
			$dir_path = "/tmp";

		@file_put_contents($dir_path . "/" . $this->session_id . ".swekey_session", serialize($data));
	}

	function DestroySession() {
		$dir_path = session_save_path();
		if (empty($dir_path))
			$dir_path = "/tmp";

		$path = $dir_path . "/" . @$_COOKIE['swekey_session_id'] . ".swekey_session";
		@unlink($path);
	}

	function GetConfig() {
		include(dirname(__FILE__) . '/swekey_config.php');
		return $config;
	}

	function AdditionalJavaScript() {
		return "";
	}

	function GetJavaScriptIncludes() {
		$res = '<script type="text/javascript" src="' . $this->swekey_dir_url . 'swekey.js"></script>' . "\n";
		$res .= '<script type="text/javascript" src="' . $this->swekey_dir_url . 'swekey_integration.js"></script>' . "\n";
		return $res;
	}

	function GetIntegrationScript($uid) {
		$config = $this->GetConfig();

		$require_js_includes = false;
		$force_swekey_emulation_update = "false";

		if (empty($this->show_result_url))
			$this->show_result_url = $this->swekey_dir_url . "show_result.php?result=%RESULT%";

		$js = "\t" . 'swekey_integration_params.swekey_dir_url = "' . $this->swekey_dir_url . '";' . "\n";

		if (!$this->is_user_logged) {
			if (!empty($_COOKIE['swekey_disabled_id'])) {
				$js .= "\t" . 'document.cookie = "swekey_disabled_id=; path=/;";' . "\n";
			}
		}

		// We are logged with a swekey
		if (isset($this->logout_url) && $this->is_user_logged && strlen($this->swekey_id_of_logged_user) == 32) {
			$disabled_swekey = '';

			if (empty($_COOKIE['swekey_disabled_id'])) {
				$status = Swekey_GetStatus($this->swekey_id_of_logged_user);
				if ($status == SWEKEY_STATUS_INACTIVE || $status == SWEKEY_STATUS_LOST || $status == SWEKEY_STATUS_STOLEN) {
					$disabled_swekey = $this->swekey_id_of_logged_user;
					$js .= "\t" . 'document.cookie = "swekey_disabled_id=' . $this->swekey_id_of_logged_user . '; path=/;";' . "\n";
				} else {
					$js .= "\t" . 'document.cookie = "swekey_disabled_id=none; path=/;";' . "\n";
				}
			} else {
				$disabled_swekey = $_COOKIE['swekey_disabled_id'];
			}

			if ($disabled_swekey != $this->swekey_id_of_logged_user) {
				$require_js_includes = true;
				$js .= "\t" . 'swekey_integration_params.logout_url = "' . $this->logout_url . '";' . "\n\t" . 'document.cookie = "swekey_proposed=' . $this->swekey_id_of_logged_user . '; path=/;";' . "\n\t" . 'setTimeout("check_swekey_presence(\'' . $this->swekey_id_of_logged_user . '\')", 1000);' . "\n";
			}
		}

		// We are logged but we don't use a swekey
		if ($this->is_user_logged && empty($this->swekey_id_of_logged_user)) {
			$require_js_includes = true;

			if (isset($config['brands']))
				$js .= "\t" . 'swekey_integration_params.brands = "' . $config['brands'] . '";' . "\n";

			foreach (array('attach_ask', 'attach_success', 'attach_failed') as $locstr) {
				$str = $this->LocalizedStr($locstr);
				if (!empty($str))
					$js .= "\t" . 'swekey_integration_params.' . $locstr . '_str = "' . $str . '";' . "\n";
			}

			$js .= "\t" . 'swekey_propose_to_attach(' . $uid . ');' . "\n";
		}

		// We are not logged
		if (empty($this->is_user_logged) && !empty($this->input_names)) {
			$require_js_includes = true;

			$names = "[";
			foreach ($this->input_names as $name)
				$names .= '"' . $name . '",';
			$names = substr($names, 0, strlen($names) - 1) . ']';

			$js .= "\t" . 'swekey_integration_params.user_input_names = ' . $names . ';' . "\n";

			if (!empty($this->multiple_logos))
				$js .= "\t" . 'swekey_integration_params.multiple_logos = true;' . "\n";
			if (!empty($this->dynamic_login_form))
				$js .= "\t" . 'swekey_integration_params.dynamic_login_form = true;' . "\n";

			if (!empty($config['logo_url'])) {
				if (strpos($config['logo_url'], '://') === false)
					$js .= "\t" . 'swekey_integration_params.logo_url = "http://www.swekey.com?promo=' . $config['logo_url'] . '";' . "\n";
				else
					$js .= "\t" . 'swekey_integration_params.logo_url = "' . $config['logo_url'] . '";' . "\n";
			} else if (!empty($config['promo'])) {
				$js .= "\t" . 'swekey_integration_params.logo_url = "http://www.swekey.com?promo=' . $config['promo'] . '";' . "\n";
			} else {
				$js .= "\t" . 'swekey_integration_params.logo_url = "http://www.swekey.com?promo=none";' . "\n";
			}

			if (isset($config['brands']))
				$js .= "\t" . 'swekey_integration_params.brands = "' . $config['brands'] . '";' . "\n";
			if (!empty($config['show_only_plugged']))
				$js .= "\t" . 'swekey_integration_params.show_only_plugged = true;' . "\n";
			if (isset($config['logo_xoffset']))
				$js .= "\t" . 'swekey_integration_params.logo_xoffset = "' . $config['logo_xoffset'] . '";' . "\n";
			if (isset($config['logo_xoffset']))
				$js .= "\t" . 'swekey_integration_params.logo_yoffset = "' . $config['logo_yoffset'] . '";' . "\n";
			if (isset($config['loginname_width_offset']))
				$js .= "\t" . 'swekey_integration_params.loginname_width_offset = "' . $config['loginname_width_offset'] . '";' . "\n";

			foreach (array('gray', 'orange', 'red', 'green') as $color) {
				$locstr = 'logo_' . $color;
				$str = $this->LocalizedStr($locstr);
				if (!empty($str))
					$js .= "\t" . 'swekey_integration_params.' . $locstr . '_str = "' . $str . '";' . "\n";
			}

			$js .= "\t" . 'swekey_login_integrate();' . "\n";
			$force_swekey_emulation_update = "true";
		}

		if (!empty($config['allow_mobile_emulation'])) {
			$js .= "Swekey_EnableMobileEmulation('" . $this->show_result_url . "', $force_swekey_emulation_update);\n";
		}

		// Debug Info
		if (!empty($this->show_debug_info) || !empty($_GET['swekey_debug_info'])) {
			ob_start();
			$require_js_includes = true;

			?>
			<div id="swekey_debug_div" style="display: block; background-color: rgb(204, 204, 204); width: 100%; color: rgb(0, 0, 0); font-family: Tahoma;">
			<big>SWEKEY INTEGRATION DEBUG INFO</big><br />
			<small><i>(To remove this text set "show_debug_info" to false in "lms_integration.php")</i></small><br />
			<br />
			<iframe id="swekey_iframe_tester" style="display: none" onload="swekey_iframe_tester_loaded()" src="<?php echo str_replace("%RESULT%", "swekey_iframe_test", $this->show_result_url); ?>"></iframe>
			<?php
				echo "swekey_dir_url: \"" . $this->swekey_dir_url . "\" <span id=swekey_dir_url_result></span><br />\n";
				echo "is_user_logged: " . (empty($this->is_user_logged) ? "false" : "true") . "<br />\n";
				if (!empty($this->is_user_logged)) {
					if (isset($this->logout_url))
						echo "logout_url: " . $this->logout_url . "<br />\n";
					echo "swekey_id_of_logged_user: " . $this->swekey_id_of_logged_user . "<br />\n";
				} else {
					echo "input_names: ";
					foreach ($this->input_names as $i)
						echo $i . " , ";
					echo "<br />\n";
					if (isset($this->multiple_logos))
						echo "multiple_logos: " . $this->multiple_logos . "<br />\n";
				}
				if (isset($this->lang))
					echo "lang: " . $this->lang . "<br />\n";
?>
			Testing iFrame: <span id=swekey_iframe_result></span><br />
			Testing Ajax: <span id=swekey_ajax_result></span><br /><br />
			</div>
			<script type="text/javascript">
			function getCookie(c_name) {
				var i, x, y, ARRcookies = document.cookie.split(";");
				for (i = 0; i < ARRcookies.length; i++) {
					x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
					y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
					x = x.replace(/^\s+|\s+$/g, "");
					if (x == c_name) {
						return unescape(y);
					}
				}
			}

			function swekey_iframe_tester_loaded() {
				var iframe_res;
				try {
					iframe_res = document.frames['swekey_iframe_tester'].document.body.innerHTML;
				} catch (e0) {
					try {
						iframe_res = document.getElementById('swekey_iframe_tester').contentDocument.body.innerHTML;
					} catch (e1) {
						iframe_res = "ERROR: " + e1.toString();
					}
				}

				var swekey_iframe_res = document.getElementById('swekey_iframe_result');
				if (iframe_res == "swekey_iframe_test") {
					swekey_iframe_res.style.color = "#00CC00";
					var text = "OK";
					swekey_iframe_res.appendChild(document.createTextNode(text));
				} else {
					swekey_iframe_res.style.color = "#FF0000";
					var text = "ERROR iFrame '<?php echo str_replace("%RESULT%", "swekey_iframe_test", $this->show_result_url); ?>' returned " + iframe_res;
					swekey_iframe_res.appendChild(document.createTextNode(text));
				}
			}

			var swekey_dir_url_res = document.getElementById('swekey_dir_url_result');
			if (typeof(set_swekey_logo) == 'function') {
				swekey_dir_url_res.style.color = "#00CC00";
				swekey_dir_url_res.appendChild(document.createTextNode("OK"));
			} else {
				swekey_dir_url_res.style.color = "#FF0000";
				swekey_dir_url_res.appendChild(document.createTextNode("<?php echo $this->swekey_dir_url; ?>" + "swekey_integration.js not found please check '$this->swekey_dir_url' in 'lms_integration.php'"));
			}

			var swekey_ajax_res = document.getElementById('swekey_ajax_result');

			function cb_my_ajax_test(result) {
				if (!result.ajax_performed || result.result != 'success' || result.session != 123456789) {
					swekey_ajax_res.style.color = "#FF0000";
					swekey_ajax_res.appendChild(document.createTextNode("Request returned bad result"));
					if (result != null) {
						if (result.error != null)
							result = result.error;
						var div = document.createElement("div");
						div.innerHTML = "<div style = 'position: absolute; left: 10%; height: 300px;'><small><small>" + result + "</small></small></div>";
						swekey_ajax_res.appendChild(div);
					}
				} else {
					swekey_ajax_res.style.color = "#00CC00";
					var text = "OK";
					if (getCookie("swekey_session_id") != result.session_id) {
						swekey_ajax_res.style.color = "#FF0000";
						text = "Communication is OK but session_id changed";
					}

					swekey_ajax_res.appendChild(document.createTextNode(text));
				}
			}

			try {
				swekey_ajax_caller({'action':'ajax_test', 'result':'success', 'session':123456789, 'arr':[1,2]}, cb_my_ajax_test);
			} catch (e) {
				swekey_ajax_res.style.color = "#FF0000";
				swekey_ajax_res.appendChild(document.createTextNode(e));
			}
			</script>
<?php
			$debug_info =  ob_get_clean();
		} else {
			$debug_info = "";
		}

		$output = "\n<!-- Swekey Integration Begin -->\n";
		$emul = empty($config['allow_mobile_emulation']) ? "off" : "on";
		$output .= "<!-- LMS-Integration 27/03/2013 (emulation:$emul) -->\n";

		if ($require_js_includes)
			$output .= $this->GetJavaScriptIncludes();

		$aj = $this->AdditionalJavaScript();
		if (!empty($aj))
			$js = "// Additional Script Begin\n$aj\n// Additional Script End\n\n$js";

		$output .= "<script type=\"text/javascript\">\n$js</script>\n";
		$output .= $debug_info;
		$output .= "<!-- Swekey Integration End -->\n";

		return $output;
	}

	function AjaxHandler($params) {
		if (!empty($this->logFile)) {
			$this->LogStr("AjaxHandler(" . var_export($params, true) . ")");
		}

		$config = $this->GetConfig();

		if (empty($params['action']))
			return "error: no action defined";

		$session = $this->LoadSession(true);

		switch ($params['action']) {
			case 'ajax_test':
				$result = array(
					'ajax_performed' => true,
					'result' => $params['result'],
					'session' => $params['session'],
					'session_id' => $this->session_id,
					'arr' => $params['arr'],
				);
				break;

			case 'unplugged':
				$result = array('session' => $params['session']);
				$session = array();
				break;

			case 'get_rnd_token':
				$result = array(
					'session' => $params['session'],
				);
				if (!empty($config['rndtoken_server']))
					Swekey_SetRndTokenServer($config['rndtoken_server']);

				if (!empty($config['allow_when_no_network']))
					Swekey_AllowWhenNoNetwork($config['allow_when_no_network']);

				$rt = Swekey_GetFastRndToken();
				if (empty($session))
					$session = array();
				$session[$rt] = true;
				$result['rt'] = $rt;
				if (!empty($config['no_linked_otp']))
					$result['no_linked_otp'] = true;
				break;

			case 'swekey_validate':
				$params['ids'] = explode(",", $params['ids']);
				$params['otps'] = explode(",", $params['otps']);

				$result = array('session' => $params['session']);
				if (empty($session[$params['rt']])) {
					$result['error'] = "This RT was not generated here";
					break;
				}

				unset($session[$params['rt']]);

				if (!empty($config['check_server']))
					Swekey_SetCheckServer($config['check_server']);

				if (!empty($config['allow_when_no_network']))
					Swekey_AllowWhenNoNetwork($config['allow_when_no_network']);

				$ids = array();
				for ($i = 0; $i < sizeof($params['ids']); $i ++) {
					if (!empty($config['no_linked_otp'])) {
						$res = Swekey_CheckOtp($params['ids'][$i], $params['rt'], $params['otps'][$i]);
					} else if (!empty($config['https_server_hostname'])) {
						$res = Swekey_CheckLinkedOtp($params['ids'][$i], $params['rt'], $config['https_server_hostname'], $params['otps'][$i]);
					} else {
						$res = Swekey_CheckSmartOtp($params['ids'][$i], $params['rt'], $params['otps'][$i]);
					}

					if (!empty($res))
						$ids[] = $params['ids'][$i];
				}

				$session['ids'] = $ids;
				$result['ids'] = $ids;

				foreach($ids as $swekey_id) {
					$user_name = $this->GetUserNameFromSwekeyId($swekey_id);
					if (!empty($user_name)) {
						$result['user_name'] = $user_name;
						break;
					}
				}

				break;

			case 'attach_swekey':
				$result = array();
				if (!mb_ereg('^[A-F0-9]{32}$', $params['swekey_id'])) {
					$result['error'] = "Invalid swekey id";
				} else if (!$this->is_user_logged) {
					$result['error'] = "No user logged";
				} else {
					$error = $this->AttachSwekeyToCurrentUser($params['swekey_id']);
					if (!empty($error))
						$result['error'] = $error;
				}
				break;

			case 'show_result':
				if (get_magic_quotes_gpc())
					$params['result'] = stripslashes(@$params['result']);
				echo htmlentities (@$params['result']);
				exit;

			default:
				$result['error'] = "Call '" . $params['action'] . "' is not implemented";
				break;
		}

		$this->SaveSession($session);

		return $result;
	}

	function IsSwekeyAuthenticated($swekey_id) {
		$config = $this->GetConfig();

		// delete the cookie
		@setcookie('swekey_dont_verify_'.$swekey_id, "0", time() - 60000);

		$session = $this->LoadSession();
		$ids = array();
		if (!empty($session))
			if (!empty($session['ids']))
				$ids = $session['ids'];

		if (is_array($ids) && in_array($swekey_id, $ids, true)) {
			if (!empty($config['allow_disabled']))
				@setcookie('swekey_disabled_id', 'none', 0, '/');
			return true;
		}

		if (!empty($config['allow_disabled'])) {
			if (!empty($config['status_server']))
				Swekey_SetStatusServer($config['status_server']);

			$status = Swekey_GetStatus($swekey_id);
			if ($status == SWEKEY_STATUS_INACTIVE || $status == SWEKEY_STATUS_LOST || $status == SWEKEY_STATUS_STOLEN) {
				@setcookie('swekey_disabled_id', $swekey_id, 0, '/');
				return true;
			}
		}

		return false;
	}
}
