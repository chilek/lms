<?php


// taken from:
//
// http://groups.google.pl/groups?hl=pl&lr=&ie=UTF-8&selm=1661991643.20030114210835%40poczta.onet.pl&rnum=3
//
// Copyringt information:
//
// "
// mo¿esz robiæ co chcesz z t± procedur± ;)
//
// pozdrawiam,
// Marek
//
// "


function slownie($x)
{
  $ss = array("","sto ","dwie¶cie ","trzysta ","czterysta ","piêcset ","sze¶æset ","siedemset ","osiemset ","dziewiêæset ");
  $dd = array("","dziesiêæ ","dwadzie¶cia ","trzydzie¶ci ","czterdzie¶ci ","piêædziesi±t ","sze¶ædziesi±t ","siedemdziesi±t ","osiemdziesi±t ","dziewiêædziesi±t ");
  $jj = array("","jeden ","dwa ","trzy ","cztery ","piêæ ","sze¶æ ","siedem ","osiem ","dziewiêæ ","dziesiêæ ","jedena¶cie ","dwana¶cie ","trzyna¶cie ","czterna¶cie ","piêtna¶cie ","szesna¶cie ","siedemna¶cie ","osiemna¶cie ","dziewiêtna¶cie ");

  $buf = split(",",str_replace(".",",",$x));

  $w = "";

  if($buf[0]>=1000000)
  {
    $l = (int)($buf[0]/1000000);
    $w .= $ss[$l/100];

    $k = $l ? ($l==1 ? "milon " : ($l%10>1 && $l%10<5 && ($l<10 || $l>20)) ? "miliony" : "milonów ") : "";

    $l = $l - 100*(int)($l/100);

    if((int)$l<20)
      $w .= $jj[$l];
    else
      $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];

    $w .= $k;
  }

  $buf[0] = $buf[0] - 1000000*(int)($buf[0]/1000000);

  if($buf[0]>=1000)
  {
    $l = (int)($buf[0]/1000);

    $w .= $ss[$l/100];

    $k = $l ? ( $l==1 ? "tysi±c " : (($l%10>1 && $l%10<5 && ($l<10 || $l>20)) ? "tysi±ce " : "tysiêcy ")) : "";

    $l = $l - 100*(int)($l/100);

    if((int)$l<20)
      $w .= $jj[$l];
    else
      $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];

    $w .= $k;
  }

  $buf[0] = $buf[0] - 1000*(int)($buf[0]/1000);

  if($buf[0]>=1)
  {
    $l = $buf[0];

    $w .= $ss[$l/100];

    $l = $l - 100*(int)($l/100);

    if((int)$l<20)
      $w .= $jj[$l];
    else
      $w .= $dd[substr($l,0,1)].$jj[substr($l,1,1)];
  } 

  if ($buf[0]==1) $w.=" z³oty ";
	else {
  	if ((($buf[0]%10)>1)&&($buf[0]%10<=4)) $w .=" z³ote ";
  	else if ($buf[0]>=5) $w .=" z³otych ";
	}
 	

  if ($buf[1]=="") { $buf[1]="0"; }
	else
  $w .= $buf[1]."/100";

  return $w;
}

?>
