<HTML>
<HEAD>

<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-2">
</HEAD>
<BODY>

<?php 
//  NRB 26 cyfr, 2 kontrolne, 8 nr banku, 16 nr konta 

$KONTO_DO="23213000042001020318210001";

$ISP1_DO="XXXXXXXXXXXXXXXXXXXXXXXXXKX";
$ISP1_DO="NITRONET SP.J P.TYLL M.KWAT";

$ISP2_DO="XXXXXXXXXXXXXXXXXXXXXXXXXKX";
$ISP2_DO="EK ZADUMANA 11m4 02-206W-WA";

$KWOTA=$_GET["ILE"];
$USER_OD=$_GET["OD"];
$USER_ID=$_GET["UID"];

?>

<div style="position: absolute; top: 60px; left: 10px"><img src="przelew.png" border=0 alt="wpłata gotówkowa"></div><form>W zależności od poczty/Banku wpisz 1,2 lub nawet 4 kopie. To nie nasz pomysł, tylko Poczty polskiej [tm]<input type="button" value="Drukuj" onClick="top.print();"></form>
<span style="position: absolute; top: 63px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">nazwa odbiorcy</span>
<span style="position: absolute; top: 96px; left: 62px; font-family: Arial, Helvetica; color: #FF0000; font-size: 6pt;">nazwa odbiorcy cd.</span>
<span style="position: absolute; top: 131px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">l.k.</span>
<span style="position: absolute; top: 131px; left: 102px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nr rachunku odbiorcy</span>
<span style="position: absolute; top: 163px; left: 352px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota</span>
<span style="position: absolute; top: 194px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota słownie</span>
<span style="position: absolute; top: 222px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy</span>
<span style="position: absolute; top: 253px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy cd.</span>
<span style="position: absolute; top: 284px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem</span>
<span style="position: absolute; top: 317px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem cd.</span>
<span style="position: absolute; top: 395px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Opłata</span>
<span style="position: absolute; top: 425px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Podpis</span>
<span style="position: absolute; top: 75px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"></SPAN>

<?php
//
// NAZWA beneficjenta:

for ( $i=0; $i<27; $i++ ) {
	$pos=62+$i*19;
	echo('<span style="position: absolute; top: 75px; left: '. $pos. ' px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP1_DO[$i].'</SpAn>');
	}

?>
<?php

for ( $i=0; $i<27; $i++ ) {
	$pos=62+$i*19;
	echo('<span style="position: absolute; top: 109px; left: '. $pos. ' px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$ISP2_DO[$i].'</SpAn>');
	}

?>
<!--nr konta-->

<?php

for ( $i=0; $i<26; $i++ ) {
	$pos=62+$i*19;
	echo('<span style="position: absolute; top: 141px; left: '. $pos. ' px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'.$KONTO_DO[$i].'</SpAn>');
	}

?>


<!--KWOTA-->
<span style="position: absolute; top: 172px; left: 272px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">P</SPAN>
<span style="position: absolute; top: 172px; left: 291px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">L</SPAN>
<span style="position: absolute; top: 172px; left: 310px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">N</SPAN>
<span style="position: absolute; top: 172px; left: 329px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"></SPAN>

<span style="position: absolute; top: 172px; left: 242px; font-family: Courier, Arial, Helvetica; font-size: 16pt; font-weight: bold;">X</SPAN>

<?php
$KWOTA_SL=sprintf("%0'--12.2f",$KWOTA);
for ( $i=0; $i<12; $i++ ) {
	$pos=347+$i*19;
	echo('<SPAN style="position: absolute; top: 172px; left: ' . $pos . ' px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">'. $KWOTA_SL[$i] . '</SPAN>');
	}
?>


<?php require("slownie.inc"); ?>

<span style="position: absolute; top: 203px; left: 72px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold;"><?php echo(slownie($KWOTA));?></span>

<span style="position: absolute; top: 235px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 10pt; font-weight: bold;"><?php echo($USER_OD); ?></span>
<span style="position: absolute; top: 265px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 10pt; font-weight: bold;">A</span>
<span style="position: absolute; top: 298px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"><?php echo($USER_ID); ?></span>
<span style="position: absolute; top: 327px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"></span>



</HTML>
