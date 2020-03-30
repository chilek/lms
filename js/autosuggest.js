/*******************************************************
AutoSuggest - a javascript automatic text input completion component
Copyright (C) 2005 Joe Kepley, The Sling & Rock Design Group, Inc.

WWW: http://www.gadgetopia.com/autosuggest/

Licensed under GNU Lesser General Public License (LGPL).
Modified by kondi for LMS project (mailto:lms@kondi.net).
*******************************************************/

function AutoSuggest(form, elem, uri, autosubmit, onSubmit, onLoad) {
	//The 'me' variable allow you to access the AutoSuggest object
	//from the elem's event handlers defined below.
	var me = this;

	if (form.constructor.name !== 'HTMLFormElement') {
		this.elem = $(form.elem)[0];
		this.form = $(form.form)[0];
		this.uri = form.uri;
		this.autosubmit = form.hasOwnProperty('autosubmit') && (form.autosubmit == 1 || form.autosubmit == 'true');
		this.onSubmit = form.hasOwnProperty('onSubmit') ? form.onSubmit : null;
		this.onLoad = form.hasOwnProperty('onLoad') ? form.onLoad : null;
		this.onAjax = form.hasOwnProperty('onAjax') ? form.onAjax : '';
		this.class = form.hasOwnProperty('class') ? form.class : '';
		this.emptyValue = form.hasOwnProperty('emptyValue') && (form.emptyValue == 1 || form.emptyValue || form.emptyValue == 'true')
	} else {
		//A reference to the element we're binding the list to.
		this.elem = elem;
		this.form = form;
		this.uri = uri;
		this.autosubmit = (typeof(autosubmit) !== 'undefined' && (autosubmit == 1 || autosubmit == 'true'));
		this.onSubmit = onSubmit;
		this.onLoad = onLoad;
		this.class = '';
	}
	this.class = 'suggestion_list ' + this.class;

	this.request_delay = 250; // time in milliseconds

	if (/autosuggest-(left|top|right|bottom)/i.exec(this.elem.className) !== null) {
		this.placement = RegExp.$1;
	} else {
		this.placement = 'bottom';
	}

	//Arrow to store a subset of eligible suggestions that match the user's input
	this.suggestions = [];

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
	this.elem.setAttribute("autocomplete","off");

	//We need to be able to reference the elem by id. If it doesn't have an id, set one.
	if (!this.elem.id) {
		var id = "autosuggest" + idCounter;
		idCounter++;

		this.elem.id = id;
	}

	/********************************************************
	onkeydown event handler for the input elem.
	Tab key = use the highlighted suggestion, if there is one.
	Esc key = get rid of the autosuggest dropdown
	Up/down arrows = Move the highlight up and down in the suggestions.
	********************************************************/
	this.elem.onkeydown = function(ev) {
		var key = me.getKeyCode(ev);
		var suggest;

		if (/autosuggest-(left|top|right|bottom)/i.exec(me.elem.className) !== null)
			suggest = RegExp.$1;
		else
			suggest = 'bottom';

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
					me.highlighted = me.suggestions.length - 1;
				else if (me.highlighted > 0)
					--me.highlighted;
				else if (me.highlighted == 0)
					me.highlighted = me.suggestions.length - 1;

				me.changeHighlight(key);
			break;

			case KEYDN:
				if ((suggest == 'top' || suggest == 'bottom') && me.highlighted < (me.suggestions.length - 1))
					++me.highlighted;
				else if (me.highlighted != -1 && me.highlighted < (me.suggestions.length - 1))
					++me.highlighted;
				else if(me.highlighted == (me.suggestions.length - 1))
					me.highlighted = 0;

				me.changeHighlight(key);
			break;

			case KEYLEFT:
				if (suggest == 'left' && me.highlighted == -1 && me.highlighted < (me.suggestions.length - 1)) {
					me.highlighted++;
					me.changeHighlight(key);
				}
				else if (suggest == 'right') {
					me.highlighted = -1;
					me.changeHighlight(key);
				}
			break;

			case KEYRIGHT:
				if (suggest == 'right' && me.highlighted == -1 && me.highlighted < (me.suggestions.length - 1)) {
					me.highlighted++;
					me.changeHighlight(key);
				}
				else if (suggest == 'left') {
					me.highlighted = -1;
					me.changeHighlight(key);
				}
			break;

			default:
				clearTimeout(me.timer);
		}
	};

	/********************************************************
	onkeyup handler for the elem
	If the text is of sufficient length, and has been changed,
	then display a list of eligible suggestions.
	********************************************************/
	this.elem.onkeyup = function(ev) {
		var key = me.getKeyCode(ev);
		switch (key) {
			//The control keys were already handled by onkeydown, so do nothing.
			case ENT:
			case RET:
			case TAB:
			case ESC:
			case KEYUP:
			case KEYDN:
				return;

		default:
			if (this.value != me.inputText && (me.emptyValue || this.value.length > 0)) {
				clearTimeout(me.timer);
				me.timer = setTimeout(function(){ me.HTTPpreload(); }, me.request_delay);
			} else {
				me.hideDiv();
			}
		}
	};

	this.HTTPloaded = function () {
		if ((xmlhttp) && (xmlhttp.readyState == 4)) {
			me.inputText = this.value;
			me.getSuggestions();
			if (me.suggestions.length) {
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
			var submit_data = this.suggestions[this.highlighted];
			this.elem.value = this.suggestions[this.highlighted].name;
			var gotothisuri = this.suggestions[this.highlighted].action;
			this.hideDiv();
			//It's impossible to cancel the Tab key's default behavior.
			//So this undoes it by moving the focus back to our field right after
			//the event completes.
			setTimeout(function() {
				$(me.elem).focus();
			},0);
			//Same applies to Enter key.
			this.form.onsubmit = function () { return false; };
			setTimeout(function() {
				me.form.onsubmit = function() {
					return true;
				}
			}, 10);
			//Go to search results.
			if (this.autosubmit) {
				location.href = gotothisuri;
			}
			if (this.onSubmit) {
				(this.onSubmit)(submit_data);
			}
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
		$('li', this.div).each(function(i, elem) {
			if (me.highlighted == i) {
				$(elem).addClass('selected');
			} else {
				$(elem).removeClass('selected');
			}

		});
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
		var ul = $('<ul class="lms-ui-suggestion-list" />').get(0);

		function onClick() {
			me.useSuggestion();
		}

		//Create an array of LI's for the words.
		$.each(this.suggestions, function(i, elem) {
			var name = elem.name;
			var name_class = elem.name_class;
			var desc = elem.description ? elem.description : '';
			var desc_class = elem.description_class;
			var action = elem.action ? elem.action : '';
			var tip = elem.hasOwnProperty('tip') ? elem.tip : null;

			var name_elem = $('<div class="lms-ui-suggestion-name ' + name_class +'" />').get(0);
			var desc_elem = $('<div class="lms-ui-suggestion-description ' + desc_class + '">' + desc + '</div>').get(0);
			var li = $('<li class="lms-ui-suggestion-item" />').attr('title', tip).get(0);

			name_elem.innerHTML = name.length > AUTOSUGGEST_MAX_LENGTH ?
				name.substring(0, AUTOSUGGEST_MAX_LENGTH) + " ..." : name;

			if (action && !me.autosubmit && !me.onSubmit) {
				var a = $('<a href="' + action + '"/>').get(0);
				a.appendChild(name_elem);
				a.appendChild(desc_elem);
				li.appendChild(a);
			} else {
				li.appendChild(name_elem);
				li.appendChild(desc_elem);
			}
			li.onclick = onClick;

			if (me.highlighted == i) {
				$(li).addClass('selected');
			}

			ul.appendChild(li);
		});

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

		this.div.className = this.class;
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
		var uri = this.uri + encodeURIComponent(this.elem.value);
		xmlhttp = me.setXMLHTTP();
		xmlhttp.onreadystatechange = this.HTTPloaded;
		if (this.onAjax) {
			uri = this.onAjax(uri);
		}
		xmlhttp.open("GET", uri, true);
		xmlhttp.send(null);
	}

	this.getSuggestions = function() {
		try {
			this.suggestions = JSON.parse(xmlhttp.responseText);
		} catch(x) {
			this.suggestions = [];
		}

		if (this.suggestions.length) {
			$.each(this.suggestions, function(i, elem) {
				var name = elem.name;
				if (me.inputText && !name.toLowerCase().indexOf(me.inputText.toLowerCase())) {
					me.suggestions.push(elem);
				}
			});
			if (this.onLoad) {
				var suggestions = (this.onLoad)(this.suggestions);
				if (typeof(suggestions) === 'object') {
					this.suggestions = suggestions;
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

// hide autosuggest after click out of the window
$(document).click(function(e) {
	var elem = e.target;
	if (!$(elem).is('.lms-ui-quick-search,.lms-ui-suggestion-list *')) {
		$('#autosuggest:visible').hide();
	}
	return;
});

// hide autosuggest after escape key press
$(document).keydown(function(e) {
	var key = e.keyCode;
	var elem = e.target;
	if (key == 27 && !$(elem).is('.lms-ui-quick-search,.lms-ui-suggestion-list *')) {
		$('#autosuggest:visible').hide();
	}
});
