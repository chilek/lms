<?


class Mikrotik {

	private static $ip;
	private static $mt;
	private static $login;
	private static $password;
	


	public function __construct($mtip) {
		require_once('routeros_api.class.php');
		self::$login=ConfigHelper::getConfig('mikrotik.user');
		self::$password=ConfigHelper::getConfig('mikrotik.password');
		self::$ip=$mtip;
		self::$mt = new RouterosAPI();
		self::$mt->debug=false;
	}	
	public function __destruct() {
		self::$mt->disconnect();
	}
	function GetChannel($interface) {
		if ($interface=='all') $interface='wlan1';
		self::$mt->connect(self::$ip,self::$login,self::$password);
		if (!self::$mt->connected) {
			echo "Not connected to ".self::$ip."\n";
			return('');
		}
		$iface=self::$mt->comm("/interface/wireless/print",array('?name'=>$interface));
		$channel=$iface[0]['frequency'];
		if ($channel=='') { $channel='0'; }
		elseif ($channel<=2500) $channel=$channel/5-481.4; 
		else $channel=$channel/5-1000; 
		return($channel);
	}	
	function GetRadiosectorConnected($interface) {
		#echo "GetRadiosectorConnected:$interface\n";
		self::$mt->connect(self::$ip,self::$login,self::$password);
		if (!self::$mt->connected) {
			echo "Not connected to ".self::$ip."\n";
			return(array());
		}
		if ($interface<>'all') {
			$arg=array('?interface'=>$interface);
		} else {
			$arg=array();
		}
		$array=self::$mt->comm("/interface/wireless/registration-table/print",$arg);
		return($array);	
	}

	public function get_connected() {
		self::$mt->connect(self::$ip,self::$login,self::$password);
		if (self::$mt->connected) 
			$array=self::$mt->comm("/interface/wireless/registration-table/print");
		else
			return(array());
		return($array);
	}
	public function get_ether_stats($i) {
		self::$mt->connect(self::$ip,self::$login,self::$password);
		#echo "$i<BR>";
		if (self::$mt->connected) {
			 $array=self::$mt->comm("/interface/ethernet/print",array('detail'=>'','?name'=>'ether'.$i));
			 #echo '<PRE>';print_r($array);echo '</PRE>';
			 #$arr2=self::$mt->comm("/interface/ethernet/poe/monitor",array('ether2'=>''));
			 #echo '<PRE>';print_r($arr2);echo '</PRE><HR>';
			 #$array['poe']=$arr2;
		} else 
			return(array());
		return($array);
	}
	public function wireless() {
		self::$mt->connect(self::$ip,self::$login,self::$password);
		$wireless=self::$mt->comm("/interface/wireless/print");
		return(isset($wireless[0]['name']));
	}
}


?>
