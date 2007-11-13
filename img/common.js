// $Id: common.js,v 1.2 2003/04/12 22:31:06 lukasz Exp $

function confirmLink(theLink, message)
{
	var is_confirmed = confirm(message);

	if (is_confirmed) {
		theLink.href += '&is_sure=1';
	}
	return is_confirmed;
}

function confirmForm(formField, message, okValue)
{
	var is_confirmed = confirm(message);
	if (is_confirmed) {
		formField.value = okValue;
	}
	return is_confirmed;
}

function addClass(theElem, theClass)
{
	theElem.className += " " + theClass;
}

function removeClass(theElem, theClass)
{
	regexp = eval("/" + theClass + "/i");
	str = theElem.className;
	theElem.className = str.replace(regexp, "");
}

function openSelectWindow(theURL,winName,myWidth, myHeight, isCenter, formfield)
{
	if(window.screen)
		if(isCenter)
			var myLeft = 5;
	var myTop = 5;
	if(isCenter=="true"){
		myLeft = (screen.width-myWidth)/2;
		myTop = (screen.height-myHeight)/2;
	}
	
	targetfield = formfield;
	
	okno = window.open(theURL,winName,'location=0,directories=0,scrollbars=no,toolbar=0,menubar=0,resizable=0,status=0,width='+myWidth+',height='+myHeight+',left=' + myLeft+ ',top=' + myTop);
	
	return false;
}

function openWindow(theURL,winName,myWidth,myHeight,isCenter)
{
	if(window.screen)
		if(isCenter)
			var myLeft = 5;
	var myTop = 5;
	if(isCenter == "true")
	{
		myLeft = (screen.width-myWidth)/2;
		myTop = (screen.height-myHeight)/2;
	}

	okno = window.open(theURL, winName, 'location=0,directories=0,scrollbars=no,toolbar=0,menubar=0,resizable=0,status=0,titlebar=0,width='+myWidth+',height='+myHeight+',left=' + myLeft+ ',top=' + myTop);

	return false;
}

function ipchoosewin(formfield,netid,device)
{
	okno = openSelectWindow('?m=chooseip' +  (netid ? '&netid=' + netid : '') + (device ? '&device=' + device : ''),'chooseip',250,300,'true',formfield)
	return false;
}

function macchoosewin(formfield)
{
	okno = openSelectWindow('?m=choosemac','choosemac',250,300,'true',formfield)
	return false;
}

function customerchoosewin(formfield)
{
	okno = openSelectWindow('?m=choosecustomer','choosecustomer',450,250,'true',formfield)
	return false;
}

function nodechoosewin(formfield, customerid)
{
	myWidth = 350;
	myHeight = 200;
	myLeft = (screen.width-myWidth)/2;
	myTop = (screen.height-myHeight)/2;
	
	targetfield = formfield;
	
	okno = window.open('?m=choosenode&id='+customerid,'choosenode','location=0,directories=0,scrollbars=yes,toolbar=0,menubar=0,resizable=0,status=0,width='+myWidth+',height='+myHeight+',left='+myLeft+',top='+myTop);
	
	return false;

//	okno = openSelectWindow('?m=choosenode&id='+customerid,'choosenode',350,250,'true',formfield)
//	return false;
}

function sendvalue(targetfield,value)
{
	targetfield.value = value;
	window.close();
	parent.window.close();
}

function showOrHide(elementslist)
{
	var elements_array = elementslist.split(" ");
	var part_num = 0;
	while (part_num < elements_array.length)
	{
		var elementid = elements_array[part_num];
		if(document.getElementById(elementid).style.display != 'none')
		{
			document.getElementById(elementid).style.display = 'none';
			setCookie(elementid, '0');
		}
		else
		{
			document.getElementById(elementid).style.display = '';
			setCookie(elementid, '1');
		}
		part_num += 1;
	}
};

timer_now = new Date();
timer_start = timer_now.getTime();

function getSeconds()
{
	var timer_now2 = new Date();
	return Math.round((timer_now2.getTime() - timer_start)/1000);
}

function getCookie(name) 
{
        var cookies = document.cookie.split(";");
	for (var i=0; i<cookies.length; i++) 
	{
    		var a = cookies[i].split("=");
                if (a.length == 2)
		{
            		a[0] = a[0].trim();
                	a[1] = a[1].trim();
                	if (a[0] == name)
			{
                    		return unescape(a[1]);
			}
                }
        }
        return null;
}

function setCookie(name, value)
{
        document.cookie = name + "=" + escape(value);
}

if (typeof String.prototype.trim == "undefined") 
{
	String.prototype.trim = function()
	{
        	var s = this.replace(/^\s*/, "");
	        return s.replace(/\s*$/, "");
	}
}

function checkElement(id)
{
        document.getElementById(id).checked = !document.getElementById(id).checked;
}
