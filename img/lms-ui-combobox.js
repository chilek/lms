/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2018 LMS Developers
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

$.widget( "custom.combobox", {
	_create: function() {
		this.wrapper = $( "<span>" )
			.addClass( "lms-ui-combobox-wrapper" )
			.insertAfter( this.element );

		this.element.hide();
		this._createAutocomplete();
		this._createShowAllButton();
	},

	_createAutocomplete: function() {
		var selected = this.element.children( ":selected" ),
			value = selected.val() ? selected.text() : "";

		this.input = $( "<input>" )
			.appendTo( this.wrapper )
			.val( value )
			.attr( "title", this.element.attr('title') )
			.attr( "size", this.element.attr("size") )
			.addClass( "lms-ui-combobox-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
			.autocomplete({
				classes: {
					"ui-autocomplete" : "lms-ui-autocomplete"
				},
				delay: 0,
				minLength: 0,
				source: $.proxy( this, "_source" ),
				open: function(evvent, ui) {
					var acData = $(this).data('ui-autocomplete');
					var keyword = acData.term.replace(' ', '|');
					acData.menu.element.find('li').each(function () {
						var me = $(this);
						var text = me.text().replace(' ', '|');
						if (keyword == text) {
							me.addClass('matched');
						}
					});
				}
			})
			.tooltip({
				show: { delay: 500 },
				track: true
			});

		this._on( this.input, {
			autocompleteselect: function( event, ui ) {
				ui.item.option.selected = true;
				this._trigger( "select", event, {
					item: ui.item.option
				});
				this.element.trigger('onchange');
			},

			autocompletechange: "_removeIfInvalid"
		});
	},

	_createShowAllButton: function() {
		var input = this.input,
			wasOpen = false;

		$( "<a>" )
			.attr( "tabIndex", -1 )
			.attr( "title", lmsMessages.showAllItems )
			.tooltip({
				show: {delay: 500},
				track: true
			})
			.appendTo( this.wrapper )
			.button({
				icons: {
					primary: "lms-ui-icon-combobox-toggle"
				},
				text: false
			})
			.removeClass( "ui-corner-all" )
			.addClass( "lms-ui-combobox-toggle ui-corner-right" )
			.on( "mousedown", function() {
				wasOpen = input.autocomplete( "widget" ).is( ":visible" );
			})
			.on( "click", function() {
				input.trigger( "focus" );

				// Close if already visible
				if ( wasOpen ) {
					return;
				}

				// Pass empty string as value to search for, displaying all results
				input.autocomplete( "search", "" );
			});
	},

	_source: function( request, response ) {
		var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
		response( this.element.children( "option" ).map(function() {
			var text = $( this ).text();
			if ( this.value && ( !request.term || matcher.test(text) ) )
				return {
					label: text,
					value: text,
					option: this
				};
		}) );
	},

	_removeIfInvalid: function( event, ui ) {

		// Selected an item, nothing to do
		if ( ui.item ) {
			return;
		}

		// Search for a match (case-insensitive)
		var value = this.input.val(),
			valueLowerCase = value.toLowerCase(),
			valid = false;
		this.element.children( "option" ).each(function() {
			if ( $( this ).text().toLowerCase() === valueLowerCase ) {
				this.selected = valid = true;
				return false;
			}
		});

		// Found a match, nothing to do
		if ( valid ) {
			return;
		}

		// Remove invalid value
		this.input
			.val( "" )
			.attr( "title", lmsMessages.valueDoesntMatchAnyItem.replace('$a', value) )
			.tooltip( "open" );
		this.element.val( "" );
		this._delay(function() {
			this.input.tooltip( "close" ).attr( "title", "" );
		}, 2500 );
		this.input.autocomplete( "instance" ).term = "";
	},

	_destroy: function() {
		this.wrapper.remove();
		this.element.show();
	}
});
