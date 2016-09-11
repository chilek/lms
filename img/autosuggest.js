/*******************************************************
AutoSuggest - a javascript automatic text input completion component
Copyright (C) 2005 Joe Kepley, The Sling & Rock Design Group, Inc.

WWW: http://www.gadgetopia.com/autosuggest/

Licensed under GNU Lesser General Public License (LGPL).
Modified by kondi for LMS project (mailto:lms@kondi.net).
*******************************************************/

function AutoSuggest(form,elem,uri,autosubmit, onsubmit) {

	//The 'me' variable allow you to access the AutoSuggest object
	//from the elem's event handlers defined below.
	var me = this;

	//A reference to the element we're binding the list to.
	this.elem = elem;

	if (/autosuggest-(left|top|right|bottom)/i.exec(elem.className) !== null)
		this.placement = RegExp.$1;
	else
		this.placement = 'bottom';

	this.form = form;
	this.uri = uri;
	this.autosubmit = autosubmit;

	//Arrow to store a subset of eligible suggestions that match the user's input
	this.eligible = [];

	//The text input by the user.
	this.inputText = null;

	//A pointer to the index of the highlighted eligible item. -1 means nothing highlighted.
	this.highlighted = -1;

	//A div to use to create the dropdown.
	this.div = document.getElementById("autosuggest");

	//Do you want to remember what keycode means what? Me neither.
	var ENT = 3;
	var RET = 13;
	var TAB = 9;
	var ESC = 27;
	var KEYLEFT = 37;
	var KEYUP = 38;
	var KEYRIGHT = 39;
	var KEYDN = 40;

	//The browsers' own autocomplete feature can be problematic, since it will 
	//be making suggestions from the users' past input.
	//Setting this attribute should turn it off.
	elem.setAttribute("autocomplete","off");

	//We need to be able to reference the elem by id. If it doesn't have an id, set one.
	if(!elem.id) {
		var id = "autosuggest" + idCounter;
		idCounter++;

		elem.id = id;
	}

	/********************************************************
	onkeydown event handler for the input elem.
	Tab key = use the highlighted suggestion, if there is one.
	Esc key = get rid of the autosuggest dropdown
	Up/down arrows = Move the highlight up and down in the suggestions.
	********************************************************/
	elem.onkeydown = function(ev) {
		var key = me.getKeyCode(ev);
		
		if (/autosuggest-(left|top|right|bottom)/i.exec(elem.className) !== null)
			var suggest = RegExp.$1;
		else
			var suggest = 'bottom';

		switch(key) {
			case ENT:
			case RET:
				me.useSuggestion();
			break;

			case TAB:
				if (me.highlighted == -1)
					me.hideDiv();
				else
					me.useSuggestion();
			break;

			case ESC:
				me.hideDiv();
			break;

			case KEYUP:
				if ((suggest == 'top' || suggest == 'bottom') && me.highlighted == -1)
					me.highlighted = me.eligible.length - 1;
				else if (me.highlighted > 0)
					--me.highlighted;
				else if (me.highlighted == 0)
					me.highlighted = (me.eligible.length - 1);

				me.changeHighlight(key);
			break;

			case KEYDN:
				if ((suggest == 'top' || suggest == 'bottom') && me.highlighted < (me.eligible.length - 1))
					++me.highlighted;
				else if (me.highlighted != -1 && me.highlighted < (me.eligible.length - 1))
					++me.highlighted;
				else if(me.highlighted == (me.eligible.length - 1))
					me.highlighted = 0;

				me.changeHighlight(key);
			break;
			
			case KEYLEFT:
				if (suggest == 'left' && me.highlighted == -1 && me.highlighted < (me.eligible.length - 1)) {
					me.highlighted++;
					me.changeHighlight(key);
				}
				else if (suggest == 'right') {
					me.highlighted = -1;
					me.changeHighlight(key);
				}
			break;
			
			case KEYRIGHT:
				if (suggest == 'right' && me.highlighted == -1 && me.highlighted < (me.eligible.length - 1)) {
					me.highlighted++;
					me.changeHighlight(key);
				}
				else if (suggest == 'left') {
					me.highlighted = -1;
					me.changeHighlight(key);
				}
			break;
		}
	};

	/********************************************************
	onkeyup handler for the elem
	If the text is of sufficient length, and has been changed,
	then display a list of eligible suggestions.
	********************************************************/
	elem.onkeyup = function(ev) {
		var key = me.getKeyCode(ev);
		switch(key) {
		//The control keys were already handled by onkeydown, so do nothing.
		case ENT:
		case RET:
		case TAB:
		case ESC:
		case KEYUP:
		case KEYDN:
			return;
		default:

			if (this.value != me.inputText && this.value.length > 0) {
				me.HTTPpreload();
			} else {
				me.hideDiv();
			}
		}
	};

	this.HTTPloaded = function () {
		if ((xmlhttp) && (xmlhttp.readyState == 4)) {
			me.inputText = this.value;
			me.getEligible();
			if (me.eligible.length>0) {
				me.createDiv();
				me.positionDiv();
				me.showDiv();
			} else {
				me.hideDiv();
			}
		}
	}

	/********************************************************
	Insert the highlighted suggestion into the input box, and 
	remove the suggestion dropdown.
	********************************************************/
	this.useSuggestion = function() {
		if (this.highlighted > -1 && this.div.style.display != 'none') {
			this.elem.value = this.eligible[this.highlighted];
			var gotothisuri = this.actions[this.highlighted];
			this.hideDiv();
			//It's impossible to cancel the Tab key's default behavior. 
			//So this undoes it by moving the focus back to our field right after
			//the event completes.
			setTimeout("document.getElementById('" + this.elem.id + "').focus()",0);
			//Same applies to Enter key.
			this.form.onsubmit = function () { return false; };
			setTimeout("document.getElementById('" + this.form.id + "').onsubmit = function () { return true; }",10);
			//Go to search results.
			if (this.autosubmit == 1) location.href = gotothisuri;
			if (this.onsubmit !== undefined)
				eval(this.onsubmit);
		}
	};

	/********************************************************
	Display the dropdown. Pretty straightforward.
	********************************************************/
	this.showDiv = function() {	
		this.div.style.visibility = 'hidden';
		this.div.style.display = 'block';

		var x = parseInt( this.div.style.left );
		var y = parseInt( this.div.style.top );

		switch (this.placement) {
			case 'left':
				x -= this.div.offsetWidth;
				break;
			case 'right':
				x += this.elem.offsetWidth;
				break;
			case 'top':
				y -= this.div.offsetHeight;
				break;
			default: // bottom
				y += this.elem.offsetHeight;
				break;
		}

		this.div.style.left = x + "px";
		this.div.style.top = y + "px";
		this.div.style.visibility = 'visible';
	};

	/********************************************************
	Hide the dropdown and clear any highlight.
	********************************************************/
	this.hideDiv = function() {
		this.div.style.display = 'none';
		this.highlighted = -1;
	};

	/********************************************************
	Modify the HTML in the dropdown to move the highlight.
	********************************************************/
	this.changeHighlight = function() {
		var lis = this.div.getElementsByTagName('LI');
		for (var i=0, len=lis.length; i<len; i++) {
			var li = lis[i];

			if (this.highlighted == i) {
				li.className = "selected";
			} else {
				li.className = "";
			}
		}
	};

	/********************************************************
	Position the dropdown div below the input text field.
	********************************************************/
	this.positionDiv = function() {
		var el = this.elem;
		var x = 0;
		var y = 0;

		//Walk up the DOM and add up all of the offset positions.
		while (el.offsetParent && el.tagName.toUpperCase() != 'BODY') {
			x += el.offsetLeft;
			y += el.offsetTop;
			el = el.offsetParent;
		}

		x += el.offsetLeft;
		y += el.offsetTop;

		this.div.style.left = x + 'px';
		this.div.style.top = y + 'px';
	};

	/********************************************************
	Build the HTML for the dropdown div
	********************************************************/
	this.createDiv = function() {
		var ul = document.createElement('ul');

		//Create an array of LI's for the words.
		for (var i=0, len=this.eligible.length; i<len; i++) {
			var word = this.eligible[i];
			var desc = (this.descriptions[i])?this.descriptions[i]:'';
			var dest = (this.actions[i])?this.actions[i]:'';

			var ds = document.createElement('span');
			var li = document.createElement('li');
			var a = document.createElement('a');
			if ((dest)&&(!this.autosubmit)) {
				a.href = dest;
				a.innerHTML = word;
				ds.innerHTML = desc;
				a.appendChild(ds);
				li.onclick = function() { me.useSuggestion(); }
				li.appendChild(a);
			} else {
				word_len = word.length;

				if (word_len > AUTOSUGGEST_MAX_LENGTH)
					li.innerHTML = word.substring(0, AUTOSUGGEST_MAX_LENGTH) + " ...";
				else
					li.innerHTML = word;

				li.onclick = function() { me.useSuggestion(); }
				ds.innerHTML = desc;
				li.appendChild(ds);
			}

			if (me.highlighted == i) {
				li.className = "selected";
			}

			ul.appendChild(li);
		}

		this.div.replaceChild(ul,this.div.childNodes[0]);


		/********************************************************
		mouseover handler for the dropdown ul
		move the highlighted suggestion with the mouse
		********************************************************/
		ul.onmouseover = function(ev) {
			//Walk up from target until you find the LI.
			var target = me.getEventSource(ev);
			while (target.parentNode && target.tagName.toUpperCase() != 'LI') {
				target = target.parentNode;
			}

			var lis = me.div.getElementsByTagName('LI');

		    for (var i=0, len=lis.length; i<len; i++) {
				var li = lis[i];
				if(li == target) {
					me.highlighted = i;
					break;
				}
			}
			me.changeHighlight();
		};

		this.div.className = "suggestion_list";
		this.div.style.position = 'absolute';

	};

	/********************************************************
	determine which of the suggestions matches the input (ajaxized)
	********************************************************/
	//Construct XMLHTTP handler.
	this.setXMLHTTP = function () {
  		var x = null;
		try { x = new ActiveXObject("Msxml2.XMLHTTP") }
  		  catch(e) {
			try { x = new ActiveXObject("Microsoft.XMLHTTP") }
			  catch(ee) { x = null; }
		  }
		if(!x && typeof XMLHttpRequest != "undefined") {
			x = new XMLHttpRequest();
  		}
		return x;
	}

	this.HTTPpreload = function() {
		xmlhttp=me.setXMLHTTP();
		xmlhttp.onreadystatechange = this.HTTPloaded;
		xmlhttp.open("GET", this.uri + encodeURI(this.elem.value), true);
		xmlhttp.send(null);
	}

	this.getEligible = function() {
		this.eligible = Array();
		this.descriptions = Array();
		this.actions = Array();

		try { eval(xmlhttp.responseText); }
		  catch(x) { this.eligible = Array(); }

        if (this.suggestions) {
    		for (var i=0, len=this.suggestions.length; i<len; i++) {
	    		var suggestion = this.suggestions[i];

		    	if(suggestion.toLowerCase().indexOf(this.inputText.toLowerCase()) == "0") {
			    	this.eligible[this.eligible.length] = suggestion;
			    }
		    }
		}
	};

	/********************************************************
	Helper function to determine the keycode pressed in a 
	browser-independent manner.
	********************************************************/
	this.getKeyCode = function(ev) {
		if(ev) {		//Moz
			return ev.keyCode;
		}
		if(window.event) {	//IE
			return window.event.keyCode;
		}
	};

	/********************************************************
	Helper function to determine the event source element in a
	browser-independent manner.
	********************************************************/
	this.getEventSource = function(ev) {
		if(ev) {		//Moz
			return ev.target;
		}
		if(window.event) {	//IE
			return window.event.srcElement;
		}
	};

	/********************************************************
	Helper function to cancel an event in a 
	browser-independent manner.
	(Returning false helps too).
	********************************************************/
	this.cancelEvent = function(ev) {
		if(ev) {		//Moz
			ev.preventDefault();
			ev.stopPropagation();
		}
		if(window.event) {	//IE
			window.event.returnValue = false;
		}
	}
}

//counter to help create unique ID's
var idCounter = 0;
