<?php

include(dirname(__FILE__) . '/swekey_integration.php');

class LmsSwekeyIntegration extends SwekeyIntegration {
	var $DB;
	var $AUTH;
	var $LMS;

	function LmsSwekeyIntegration(&$DB, &$AUTH, &$LMS) {
		$this->DB = &$DB;
		$this->AUTH = &$AUTH;
		$this->LMS = &$LMS;

		$this->show_debug_info = true;
		$this->swekey_dir_url = 'img/swekey/';
		$this->input_names = array("loginform[login]");
		// w obiekcie utworzonym przez swekey_json_server.php nie ma obiektu $AUTH wiec nie mamy ustawionego
		// is_user_logged = true
		$this->is_user_logged = !empty($this->AUTH->id);

		if ($this->is_user_logged) {
			$this->swekey_id_of_logged_user = $this->DB->GetOne('SELECT swekey_id FROM users WHERE id = ?', array($this->AUTH->id));
			$this->logout_url = '?m=logout&is_sure=1';
		}

		$this->logFile = '/tmp/lms-swekey-integration.log';
	}

	function GetUserNameFromSwekeyId($swekey_id) {
		// w obiekcie utworzonym przez swekey_json_server.php nie ma obiektu $DB wiec nie mozemy polaczyc sie z baza
		return $this->DB->GetOne('SELECT login FROM users WHERE swekey_id = ?', array($swekey_id));
	}

	function AttachSwekeyToCurrentUser($swekey_id) {
		// w obiekcie utworzonym przez swekey_json_server.php nie ma obiektow $DB i $AUTH
		// i wywala bledem
		if (!$DB->Execute('UPDATE users SET swekey_id = ? WHERE id = ?', array($swekey_id, $this->AUTH->id)))
			return "Failed to attach the user";
	}

	function GetJavaScriptIncludes() {
		$res = parent::GetJavaScriptIncludes();
		$res .= '<script type="text/javascript" src="' . $this->swekey_dir_url . 'json/swekey_json_client.js"></script>' . "\n";

		return $res;
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
