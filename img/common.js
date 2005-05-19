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

function setPointer(theRow, thePointerColor)
{
	if (thePointerColor == '' || typeof(theRow.style) == 'undefined') {
		return false;
	}
	if (typeof(document.getElementsByTagName) != 'undefined') {
		var theCells = theRow.getElementsByTagName('td');
	}
	else if (typeof(theRow.cells) != 'undefined') {
		var theCells = theRow.cells;
	}
	else {
		return false;
	}
	
	var rowCellsCnt  = theCells.length;
	for (var c = 0; c < rowCellsCnt; c++) {
		theCells[c].style.backgroundColor = thePointerColor;
	}
	
	return true;
}

function setPointerTD(theCell, thePointerColor)
{
	if (thePointerColor == '' || typeof(theCell.style) == 'undefined') {
		return false;
	}
	theCell.style.backgroundColor = thePointerColor;
	return true;
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

	targetfield = formfield;

	okno = window.open(theUrl, winName, 'location=0,directories=0,scrollbars=no,toolbar=0,menubar=0,resizable=0,status=0,width='+myWidth+',height='+myHeight+',left=' + myLeft+ ',top=' + myTop);

	return false;
}

function ipchoosewin(formfield,netid){

	if(netid)  
		okno = openSelectWindow('?m=chooseip&netid=' + netid,'chooseip',250,300,'true',formfield)
	else
		okno = openSelectWindow('?m=chooseip','chooseip',250,300,'true',formfield)
	return false;

}

function macchoosewin(formfield){

	okno = openSelectWindow('?m=choosemac','choosemac',250,300,'true',formfield)
	return false;
}

function sendvalue(targetfield,ipaddr)
{
	targetfield.value = ipaddr;
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

