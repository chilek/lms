<?php 

/*
 * LMS version 1.4-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

include_once('class.php');
$error = $_GET['error'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html><head>
<meta name="GENERATOR" content="LMS">
<meta http-equiv="Content-Language" content="pl">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2">
<title>::: Witamy w LMS  :::</title>
<STYLE type="text/css"><!--
BODY		{ font-size: 8pt; font-family: Tahoma, Verdana, Arial, Helvetica; }
TD		{ font-size: 8pt; font-family: Tahoma, Verdana, Arial, Helvetica; }
TABLE		{ border-collapse: collapse; }
A		{ text-decoration: none; }
A:hover		{ text-decoration: underline; color: #336600; }
P		{ font-size:  8pt; font-family: Tahoma, Verdana, Arial, Helvetica; text-align: center; }
INPUT		{ font-size:  8pt; font-family: Tahoma, Verdana, Arial, Helvetica; border-style: solid; border-width: 1pt; font-weight: bold; background-color: #EBE4D6; }
//-->
</STYLE>
</head>
<body topmargin="0" leftmargin="0" marginheight="0" marginwidth="0" bgcolor="#EBE4D6" link="#800000" vlink="#800000" alink="#336600">
	<TABLE WIDTH="100%" HEIGHT="100%">
		<TR>
			<TD>
   <P>
                                        <IMG SRC="http://lms.rulez.pl/img/logo.png" BORDER="0" ALT="[ Witamy w LMS 1.5 ]">
                                </P>
                                <P>
                                <B>
                                        &copy; 2001-2005 LMS Developers - <a href="http://lms.rulez.pl/" target="_blank">http://lms.rulez.pl</A><BR>
                                        &copy; 2001-2003 ASK NetX - <a href="http://www.netx.waw.pl/" target="_blank">http://www.netx.waw.pl</A><BR>
				</B>
				</P>
				<P><DIV ALIGN="CENTER"><CENTER>
		    <H2>W celu sprawdzenia stanu konta, prosimy wype³niæ poni¿szy formularz podaj±c numer umowy oraz PIN, który przes³any zosta³ na Pañstwa skrzynkê e-mail.</H2>
<?php 
if($error)
echo '<FONT COLOR="red"><B>Wprowadzono b³êdne dane!</B></FONT><BR>';
?>
						<P><FORM METHOD="POST" NAME="loginform" ACTION="balanceview.php">
							<B>Nr Umowy:</B><BR>
							<INPUT TYPE="TEXT" NAME="loginform[login]" SIZE="20" ACCESSKEY="l"><BR>
							<B>PIN:</B><BR>
							<INPUT TYPE="PASSWORD" NAME="loginform[pwd]" SIZE="20"><BR><BR>
                                                        <INPUT TYPE="SUBMIT" NAME="loginform[submit]" VALUE="Logowanie">
						</FORM></P>
				</CENTER></DIV></P>
				<P>
					<BR>
					<BR>
					<BR>
					<A HREF="http://lms.rulez.pl"><FONT COLOR="#666666">strona domowa</FONT></A>
				</p>
			</TD>
		</TR>
	</TABLE>
</body></html>
