<?
$addbalance = $_POST[addbalance];

$_SESSION[addbc] = $addbalance[comment];

$addbalance[value] = str_replace(",",".",$addbalance[value]);

if($addbalance[type]=="3"||$addbalance[type]=="4")
	{
		if(isset($addbalance[muserid]))
		{
			foreach($addbalance[muserid] as $value)
				if($LMS->UserExists($value))
				{
					$addbalance[userid] = $value;
					$LMS->AddBalance($addbalance);
				}
		}
		else
		{
			if($LMS->UserExists($addbalance[userid]))
				$LMS->AddBalance($addbalance);
		}
	}

	if($addbalance[type]=="2"||$addbalance[type]=="1")
	{
		$addbalance[userid] = "0";
		$LMS->AddBalance($addbalance);
	}

header("Location: ?".$_SESSION[backto]);

?>
