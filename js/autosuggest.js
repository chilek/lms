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
		this.method = form.hasOwnProperty('method') ? form.method : "GET";
		this.uri = form.uri;
		this.formData = form.hasOwnProperty('formData') ? form.formData : {};
		this.autosubmit = form.hasOwnProperty('autosubmit') && (form.autosubmit == 1 || form.autosubmit == 'true');
		this.onSubmit = form.hasOwnProperty('onSubmit') ? form.onSubmit : null;
		this.onLoad = form.hasOwnProperty('onLoad') ? form.onLoad : null;
		this.onAjax = form.hasOwnProperty('onAjax') ? form.onAjax : '';
		this.class = form.hasOwnProperty('class') ? form.class : '';
		this.emptyValue = form.hasOwnProperty('emptyValue') && (form.emptyValue == 1 || form.emptyValue || form.emptyValue == 'true');
		this.suggestionContainer = form.hasOwnProperty('suggestionContainer') ? form.suggestionContainer : '#autosuggest';
		this.activeDescription = form.hasOwnProperty('activeDescription') ? form.activeDescription : false;
	} else {
		//A reference to the element we're binding the list to.
		this.elem = elem;
		this.form = form;
		this.method = "GET";
		this.uri = uri;
		this.formData = {};
		this.autosubmit = (typeof(autosubmit) !== 'undefined' && (autosubmit == 1 || autosubmit == 'true'));
		this.onSubmit = onSubmit;
		this.onLoad = onLoad;
		this.class = '';
		this.suggestionContainer = '#autosuggest';
		this.activeDescription = false;
	}
	this.class = 'lms-ui-suggestion-container ' + this.class;

	this.my_at_map = {
		left: {
			my: 'right top',
			at: 'left top'
		},
		right: {
			my: 'left top',
			at: 'right top'
		},
		top: {
			my: 'left bottom',
			at: 'left top'
		},
		bottom: {
			my: 'left top',
			at: 'left bottom'
		}
	}

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
	this.div = $(this.suggestionContainer)[0];

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
		var key = ev.keyCode;
		var suggest;

		if (/autosuggest-(left|top|right|bottom)/i.exec(me.elem.className) !== null)
			suggest = RegExp.$1;
		else
			suggest = 'bottom';

		switch (key) {
			case ENT:
			case RET:
				clearTimeout(me.timer);
				me.useSuggestion();
			break;

			case TAB:
				if (me.highlighted == -1)
					me.hideDiv();
				else
					me.useSuggestion();
			break;

			case ESC:
				if ($(me.div).is(':visible')) {
					me.hideDiv();
					ev.stopPropagation();
				}
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
		var key = ev.keyCode;
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
				me.timer = setTimeout(function() {
						me.getSuggestions();
					}, me.request_delay);
			} else {
				if (!this.value.length) {
					me.inputText = '';
				}
				me.hideDiv();
			}
		}
	};

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
		$(this.div).show().data('autosuggest-input', this.elem);
		if (!$('body').is('.lms-ui-mobile')) {
			$(this.div).position($.extend(this.my_at_map[this.placement], {of: this.elem}));
		} else {
			$(this.div).position(null);
		}
	};

	/********************************************************
	Hide the dropdown and clear any highlight.
	********************************************************/
	this.hideDiv = function() {
		$(this.div).hide().removeData('autosuggest-input', null);
		this.highlighted = -1;
	};

	/********************************************************
	Modify the HTML in the dropdown to move the highlight.
	********************************************************/
	this.changeHighlight = function(key) {
		var items = $('li', this.div);
		items.each(function(i, elem) {
			if (me.highlighted == i) {
				$(elem).addClass('selected');

				var meDiv = $(me.div);
				var container_height = meDiv.height();
				var container_top = meDiv.offset().top;
				var elem_height = $(elem).outerHeight();
				var elem_top = $(elem).offset().top;
				if (key == KEYDN) {
					if (!i) {
						me.div.scrollTop = 0;
					} else if (elem_top - container_top > container_height - elem_height) {
						me.div.scrollTop += elem_height;
					}
				} else if (key == KEYUP) {
					if (i == items.length - 1) {
						me.div.scrollTop = elem_height * items.length;
					} else {
						if (elem_top - container_top < elem_height) {
							me.div.scrollTop -= elem_height;
						}
					}
				}
			} else {
				$(elem).removeClass('selected');
			}
		});
	};

	/********************************************************
	Build the HTML for the dropdown div
	********************************************************/
	this.createDiv = function() {
		var ul = $('<ul class="lms-ui-suggestion-list" />').get(0);

		//Create an array of LI's for the words.
		$.each(this.suggestions, function(i, elem) {
			var icon = elem.hasOwnProperty('icon') ? elem.icon : null;
			var name = elem.name;
			var name_class = elem.name_class;
			var desc = elem.description ? elem.description : '';
			var desc_class = elem.description_class;
			var action = elem.action ? elem.action : '';
			var tip = elem.hasOwnProperty('tip') ? elem.tip : null;

			var name_elem = $('<div class="lms-ui-suggestion-name ' + name_class +'" />').get(0);
			var desc_elem = $('<div class="lms-ui-suggestion-description ' + desc_class + '">' +
				(me.activeDescription && action ? '<a href="' + action + '">' : '') + desc + (me.activeDescription && action ? '</a>' : '') + '</div>').get(0);
			var li = $('<li class="lms-ui-suggestion-item" />').attr('title', tip).get(0);

			name_elem.innerHTML = (icon ? '<i class="' + icon + '"></i>' : '') + (name.length > AUTOSUGGEST_MAX_LENGTH ?
				name.substring(0, AUTOSUGGEST_MAX_LENGTH) + " ..." : name);

			if (action && !me.autosubmit && !me.onSubmit) {
				var a = $('<a href="' + action + '"/>').get(0);
				a.appendChild(name_elem);
				a.appendChild(desc_elem);
				li.appendChild(a);
			} else {
				li.appendChild(name_elem);
				li.appendChild(desc_elem);
			}

			if (me.highlighted == i) {
				$(li).addClass('selected');
			}

			ul.appendChild(li);
		});

		$(ul).appendTo($(this.div).empty()).find('.lms-ui-suggestion-item').click(function() {
			me.useSuggestion();
		});

		/********************************************************
		mouseover handler for the dropdown ul
		move the highlighted suggestion with the mouse
		********************************************************/
		ul.onmouseover = function(ev) {
			//Walk up from target until you find the LI.
			var target = ev.target;
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

		$(this.div).addClass(this.class);
	};

	this.getSuggestions = function() {
		var uri = this.uri + encodeURIComponent(this.elem.value);
		if (this.onAjax) {
			uri = this.onAjax(uri);
		}
		$.ajax({
			method: me.method,
			url: uri,
			data: me.formData,
			dataType: "json",
			success: function(data) {
				me.inputText = $(me.elem).val();
				me.parseSuggestions(data);
				if (me.suggestions.length) {
					me.createDiv();
					me.showDiv();
				} else {
					me.hideDiv();
				}
			}
		});
	}

	this.parseSuggestions = function(data) {
		me.suggestions = data ? data : [];
		if (me.suggestions.length) {
/*
			$.each(me.suggestions, function(i, elem) {
				var name = elem.name;
				if (me.inputText && !name.toLowerCase().indexOf(me.inputText.toLowerCase())) {
					me.suggestions.push(elem);
				}
			});
*/
			if (this.onLoad) {
				var suggestions = (me.onLoad)(me.suggestions);
				if (typeof(suggestions) === 'object') {
					me.suggestions = suggestions;
				}
			}
		}
	};
}

//counter to help create unique ID's
var idCounter = 0;


// hide autosuggest after click out of the window
$(document).click(function(e) {
	var autosuggest = $('.lms-ui-suggestion-container:visible');
	if (!autosuggest.length || $(e.target).is(autosuggest.data('autosuggest-input'))) {
		return;
	}

	autosuggest.hide().closest('.lms-ui-popup').removeClass('fullscreen-popup').hide();
	disableFullScreenPopup();
});
