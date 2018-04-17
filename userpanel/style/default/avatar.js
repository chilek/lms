// Relased under GPL 2.0
// Author: Marcin Król


var ie=document.all;
var ns6=document.getElementById&&!document.all;
var dragflag=false;
var offsetX,offsetY;

function displayhint(tip)
{
	if (ie) 
	{
		hinttext.innerHTML = tip;
		hint.style.visibility = 'visible';
	}
	if (ns6) 
	{
		document.getElementById("hinttext").innerHTML = tip;
		document.getElementById("hint").style.visibility = 'visible';
	}
}

function hidehint()
{
	if (ie) 
		hint.style.visibility='Hidden';
	if (ns6) 
	{
		document.getElementById("hint").style.visibility='hidden';
	}
}

function readcookie(name)
{
    name+="=";
    startCookie=document.cookie.indexOf(name);
    if (startCookie==-1) {return ""}
    startCookie+=name.length;
    if (document.cookie.indexOf(";",startCookie)==-1)
    {
	endCookie=document.cookie.length;
    }
    else
    {
	endCookie=document.cookie.indexOf(";",startCookie);
    }
    textCookie=document.cookie.substring(startCookie,endCookie);
    return textCookie;
}

function draginit()
{

    if (readcookie('AvatarX')) document.getElementById("drag").style.left=readcookie('AvatarX');
    if (readcookie('AvatarY')) document.getElementById("drag").style.top=readcookie('AvatarY');  
    document.getElementById("drag").style.visibility='visible';

    document.getElementById('avatar').onmousedown = function(e)	
    {
	ev=e||event;
        var pozycjaX = e?ev.pageX:ev.x, pozycjaY = e?ev.pageY:ev.y;
	offsetX = pozycjaX - parseInt(document.getElementById("drag").style.left);
        offsetY = pozycjaY - parseInt(document.getElementById("drag").style.top);
	dragflag = true;
	return false;
    }

    document.getElementById('avatar').onmouseup = function(e)	
    {
	dragflag = false;
	var expire=new Date();
	expire.setTime(expire.getTime()+1000*60*60*24*60);
	document.cookie="AvatarX="+document.getElementById("drag").style.left+"; expires="+expire.toGMTString();
	document.cookie="AvatarY="+document.getElementById("drag").style.top+"; expires="+expire.toGMTString();
    }

    document.onmousemove = function(e)	
    {
	if (dragflag) {
	    ev=e||event;
	    var pozycjaX = e?ev.pageX:ev.x, pozycjaY = e?ev.pageY:ev.y;
	    document.getElementById("drag").style.left=pozycjaX-offsetX+'px';
	    document.getElementById("drag").style.top=pozycjaY-offsetY+'px';
	    return false;
	}
    }
}

