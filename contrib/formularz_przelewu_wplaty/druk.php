<HTML>
<HEAD>

<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
</HEAD>
<BODY>

<?php 

$KONTO_DO="1234567890123456890123456";

?>

<div style="position: absolute; top: 60px; left: 10px"><img src="przelew.png" border=0 alt="wpłata gotówkowa"></div><form>W zale�no�ci od poczty/Banku wpisz 1,2 lub nawet 4 kopie. To nie nasz pomys�, tylko Poczty polskiej [tm]<input type="button" value="Drukuj" onClick="top.print();"></form><span style="position: absolute; top: 63px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">nazwa odbiorcy</span><span style="position: absolute; top: 96px; left: 62px; font-family: Arial, Helvetica; color: #FF0000; font-size: 6pt;">nazwa odbiorcy cd.</span><span style="position: absolute; top: 131px; left: 62px; font-family: Arial, Helvetica;color: #FF0000;font-size: 6pt;">l.k.</span><span style="position: absolute; top: 131px; left: 102px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nr rachunku odbiorcy</span><span style="position: absolute; top: 163px; left: 352px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota</span><span style="position: absolute; top: 194px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">kwota słownie</span><span style="position: absolute; top: 222px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy</span><span style="position: absolute; top: 253px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">nazwa zleceniodawcy cd.</span><span style="position: absolute; top: 284px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem</span><span style="position: absolute; top: 317px; left: 72px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">tytułem cd.</span><span style="position: absolute; top: 395px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Opłata</span><span style="position: absolute; top: 425px; left: 337px; font-family: Arial, Helvetica; color: #FF0000;font-size: 6pt;">Podpis</span><span style="position: absolute; top: 75px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">N</SPAN><span style="position: absolute; top: 75px; left: 81px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">I</SPAN><span style="position: absolute; top: 75px; left: 100px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">T</SPAN>
<span style="position: absolute; top: 75px; left: 119px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">R</SPAN>
<span style="position: absolute; top: 75px; left: 138px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">O</SPAN>
<span style="position: absolute; top: 75px; left: 157px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">N</SPAN>
<span style="position: absolute; top: 75px; left: 176px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">E</SPAN>
<span style="position: absolute; top: 75px; left: 195px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">T</SPAN>
<span style="position: absolute; top: 75px; left: 214px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 233px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">S</SPAN>
<span style="position: absolute; top: 75px; left: 252px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">P</SPAN>
<span style="position: absolute; top: 75px; left: 271px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 290px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">J</SPAN>
<span style="position: absolute; top: 75px; left: 309px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">.</SPAN>
<span style="position: absolute; top: 75px; left: 328px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 347px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 366px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 385px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 404px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 423px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<span style="position: absolute; top: 75px; left: 442px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"> </SPAN>
<!--ULICA odbiorcy:-->
<span style="position: absolute; top: 109px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 11pt; font-weight: bold;">%%WYDYMANA%%</span>

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
<span style="position: absolute; top: 172px; left: 347px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">1</SPAN>
<span style="position: absolute; top: 172px; left: 366px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">3</SPAN><span style="position: absolute; top: 172px; left: 385px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">,</SPAN><span style="position: absolute; top: 172px; left: 404px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">0</SPAN><span style="position: absolute; top: 172px; left: 423px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">0</SPAN><span style="position: absolute; top: 203px; left: 72px; font-family: Courier, Arial, Helvetica; font-size: 8pt; font-weight: bold;"> %%SLOWNIE%%</span> <span style="position: absolute; top: 235px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 10pt; font-weight: bold;">%%NAZWISKO%%</span> <span style="position: absolute; top: 265px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 10pt; font-weight: bold;"></span> <span style="position: absolute; top: 298px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;">WPŁATA </span> <span style="position: absolute; top: 327px; left: 62px; font-family: Courier, Arial, Helvetica; font-size: 12pt; font-weight: bold;"></span>



</HTML>
