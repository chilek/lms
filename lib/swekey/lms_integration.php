<?php

include(dirname(__FILE__) . '/swekey_integration.php');

class LmsSwekeyIntegration extends SwekeyIntegration {
	var $DB = NULL;
	var $AUTH = NULL;
	var $LMS = NULL;

	function LmsSwekeyIntegration(&$DB, &$AUTH, &$LMS) {
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;
		$this->LMS = &$LMS;

		$this->show_debug_info = false;
		$this->swekey_dir_url = 'img/swekey/';
		$this->input_names = array("loginform[login]");
		$this->is_user_logged = !empty($this->AUTH->id);

		if ($this->is_user_logged) {
			$this->swekey_id_of_logged_user = $this->DB->GetOne('SELECT swekey_id FROM users WHERE id = ?', array($this->AUTH->id));
			$this->logout_url = '?m=logout&is_sure=1';
		}
	}

	function GetUserNameFromSwekeyId($swekey_id) {
		return $this->DB->GetOne('SELECT login FROM users WHERE swekey_id = ?', array($swekey_id));
	}

	function AttachSwekeyToCurrentUser($swekey_id) {
		if (!$this->DB->Execute('UPDATE users SET swekey_id = ? WHERE id = ?', array($swekey_id, $this->AUTH->id)))
			return "Failed to attach the user";
	}

	function GetJavaScriptIncludes() {
		$res = parent::GetJavaScriptIncludes();
		$res .= '<script type="text/javascript" src="' . $this->swekey_dir_url . 'json/swekey_json_client.js"></script>'."\n";

		return $res;
	}

	function AjaxHandler($params) {
		switch ($params['action']) {
			case 'attach_swekey':
				if (!empty($params['lms_user_id'])) {
					$this->AUTH->id = intval($params['lms_user_id']);
					$this->is_user_logged = true;
				}
				break;
			default:
				break;
		}

		return parent::AjaxHandler($params);
	}

	function LocalizedStr($strId) {
		global $swekey_lang;

		if (file_exists(dirname(__FILE__) . "/lang/" . $this->LMS->lang . ".php"))
			include_once(dirname(__FILE__) . "/lang/" . $this->LMS->lang . ".php");
		else
			include_once(dirname(__FILE__) . "/lang/en.php");

		return @$swekey_lang[strtoupper($strId)];
	}
}

?>
