<?php

/*
Instalacja polega na dodaniu do pliku LocalSettings.php:

require_once( "$IP/extensions/authLMS/authLMS.php" );
$wgAuth = new authLMS('lms', $wgDBserver, $wgDBtype, $wgDBuser, $wgDBpassword);

Trzeba wiedzieć, że wg. mediawiki login może zawierać tylko litery i cyfry,
więc należy zmienić loginy kont LMSowych, które zawierają inne znaki, np. '_'.
*/

require_once('includes/AuthPlugin.php');

$wgExtensionCredits['other'][] = array(
	'name' => 'LMS Authentication Plugin',
	'author' => 'Marek Siba',
	'description' => 'Plugin autoryzacji, który wymuszna MediaWiki do korzystania z kont LMSowych'
);

class authLMS extends AuthPlugin {
	var $dbname;
	var $host;
	var $dbtype;
	var $dbuser;
	var $dbpass;

	var $ip = FALSE;
	var $passverified = FALSE;
	var $access = FALSE;
	var $accessfrom = FALSE;
	var $accessto = FALSE;

	public function __construct($dbname, $host='localhost',$dbtype='', $user='', $password='') {
		$this->dbname = $dbname;
		$this->host = $host;
		$this->dbtype = $dbtype;
		$this->dbuser = $user;
		$this->dbpass = $password;

		if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$this->ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (isset($_SERVER['HTTP_CLIENT_IP']))
			$this->ip = $_SERVER['HTTP_CLIENT_IP'];
		else
			$this->ip = $_SERVER['REMOTE_ADDR'];
	}
	
	public function userExists($username) {
		$username = strtolower($username);
		$db = $this->getDatabase();
		$sql = "SELECT login FROM users where LOWER(login) = '".$username."'";
		$res = $db->query($sql);
		$val = $db->fetchObject($res);
		$db->close();
		if (!empty($val)) {
			return true;
		}
		return false;
	}

	public function authenticate($username, $password) {
		$username = strtolower($username);
		$db = $this->getDatabase();
		$sql = "SELECT passwd AS password, access, accessfrom, accessto, hosts FROM users where LOWER(login) = '".$username."' and deleted != 1";
		$res = $db->query($sql);
		$val = $db->fetchObject($res);
		$db->close();
		if (!empty($val)) {
			$this->passverified = $this->VerifyPassword($val->password, $password);
			$this->hostverified = $this->VerifyHost($val->hosts);
			$this->access = $this->VerifyAccess($val->access);
			$this->accessfrom = $this->VerifyAccessFrom($val->accessfrom);
			$this->accessto = $this->VerifyAccessTo($val->accessto);

			if($this->passverified && $this->hostverified && $this->access && $this->accessfrom && $this->accessto) return TRUE;
		}
		return FALSE;
	}

	function VerifyPassword($pass1 = '', $pass2) {
			if (empty($pass1)) return FALSE;
			if (crypt($pass2, $pass1) == $pass1)
				return TRUE;
	}

	function VerifyHost($hosts = '') {
		if (!$hosts)
			return TRUE;

		$allowedlist = explode(',', $hosts);
		$isin = FALSE;

		foreach ($allowedlist as $value)
		{
			$net = '';
			$mask = '';

			if (strpos($value, '/') === FALSE)
				$net = $value;
			else
				list($net, $mask) = explode('/', $value);

			$net = trim($net);
			$mask = trim($mask);

			if ($mask == '')
				$mask = '255.255.255.255';
			elseif (is_numeric($mask))
				$mask = prefix2mask($mask);

			if (isipinstrict($this->ip, $net, $mask))
				return TRUE;
		}

		return FALSE;
	}

	function VerifyAccess($access) {
		$access = intval($access);
		if (empty($access)) return FALSE;
		else return TRUE;
	}

	function VerifyAccessFrom($access) {
		$access = intval($access);
		if (empty($access)) return TRUE;
		if ($access < time()) return TRUE;
		if ($access > time()) return FALSE;
	}

	function VerifyAccessTo($access) {
		$access = intval($access);
		if (empty($access)) return TRUE;
		if ($access > time()) return TRUE;
		if ($access < time())	return FALSE;
	}

	public function initUser(&$user, $autocreate=false) {
		return $this->updateUser($user);
	}

	public function updateUser(&$user) {
		$db = $this->getDatabase();
		$sql = "SELECT id, login, email, name FROM vusers where LOWER(login) = LOWER('".$user->mName."')";
		$res = $db->query($sql);
		$val = $db->fetchObject($res);
		$db->close();
		$user->setOption('nickname',$val->name);
		$user->setRealName($val->name);
		$user->setEmail($val->email);
		$user->confirmEmail();
		$user->setOption('enotifwatchlistpages', 1);
		$user->setOption('enotifusertalkpages', 1);
		$user->setOption('enotifminoredits', 1);
		$user->setOption('enotifrevealaddr', 1);
//		$user->addGroup('lms');
		return true;
	}


	public function allowPasswordChange() {
		return false;
	}

	public function allowSetLocalPassword() {
		return false;
	}

	public function autoCreate() {
		return true;
	}

	public function setPassword($user, $password) {
		return false;
	}

	public function updateExternalDB($user) {
		return false;
	}

	public function canCreateAccounts() {
		return false;
	}

	public function addUser($user, $password, $email='', $realname='') {
		return false;
	}

	public function strict() {
		return true;
	}

	public function strictUserAuth($username) {
		return true;
	}

	private function getDatabase() {
		if (empty($this->dbtype)) {
			return false;
		}
		switch ($this->dbtype) {
			case 'mysql':
				return new DatabaseMysql($this->host,$this->dbuser,$this->dbpass,$this->dbname);
			case 'postgres':
			default:
				return new DatabasePostgres($this->host,$this->dbuser,$this->dbpass,$this->dbname);
		}
		return false;
	}
}
