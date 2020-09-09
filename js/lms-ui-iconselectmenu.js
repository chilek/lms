/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2017 LMS Developers
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

$.widget( "custom.iconselectmenu", $.ui.selectmenu, {
	_renderItem: function( ul, item ) {
	    var li = $('<li' + (item.disabled ? ' class="ui-state-disabled"' : '') +
            '>');
	    var wrapper = '<div>' + (item.element.attr("data-icon") ? '<i class="' +
            (item.element.attr("data-class") ? item.element.attr("data-class") : '') +
            ' ' + item.element.attr("data-icon") + '"></i>' : '') + item.label + '</div>';

        return li.append(wrapper).appendTo(ul);
    },
    _renderButtonItem: function(item) {
        var buttonItem = $("<span>", {
            "class": "ui-selectmenu-text"
        });
        this._setText(buttonItem, item.label);
        buttonItem.html('<i class="' + item.element.attr('data-icon') + '"></i> ' + buttonItem.html());

        buttonItem.css("background-color", item.value);

        return buttonItem;
    }
});

/*!
 * \brief Pseudo class for manage icon select menu.
 */
function LmsUiIconSelectMenu( id, options ) {
    // select id
    this.select_id = id;
    this.options = options;

    // select is ready to refresh list?
    // 0 - no
    // 1 - yes
    this.ready = 0;
}

LmsUiIconSelectMenu.prototype.init = function() {
    $( this.select_id )
        .iconselectmenu($.extend(this.options, { classes: { 'ui-selectmenu-button': 'lms-ui-button-selectmenu' }}))
        .iconselectmenu( 'menuWidget' )
        .css("max-height","50vh");

    // rewrite jQuery UI styles
    $( this.select_id+"-button" ).addClass('lms-ui-address-select ' + $(this.select_id).attr('class'));

    // mark select as ready to use refresh
    this.ready = 1;
}

/*!
 * \brief Clear list and set new items.
 *
 * \param address_list
 */
LmsUiIconSelectMenu.prototype.setAddressList = function( address_list, preselection ) {
    // clear addresses list
    this._clearList();

    if (typeof(preselection) === 'undefined') {
        preselection = true;
    }

    // insert new addresses
    this.appendAddressList( address_list, preselection );
}

/*!
 * \brief Append address list to current select.
 *
 * \param address_list
 */
LmsUiIconSelectMenu.prototype.appendAddressList = function( address_list, preselection ) {
    if (typeof(preselection) === 'undefined') {
        preselection = true;
    }

    // append addresses
    this._appendAddressList( address_list, preselection );

    // refresh list
    this.refresh();
}

/*!
 * \brief Pseudo private method for append address list to current select.
 */
LmsUiIconSelectMenu.prototype._appendAddressList = function( address_list, preselection ) {
    var icon;
    var select_id = this.select_id; // can't use 'this' inside of each

	var addresses = [];
	$.each( address_list, function(key, value) {
		addresses.push(value);
	});
	addresses.sort(function(a, b) {
		var a_city = a.location_city_name.toLowerCase();
		var b_city = b.location_city_name.toLowerCase();
		if (a_city > b_city) {
			return 1;
		} else if (a_city < b_city) {
			return -1;
		}
		var a_street = a.location_street_name;
		var b_street = b.location_street_name;
		if (a_street && !b_street) {
			return -1;
		} else if (b_street && !a_street) {
			return 1;
		} else if (!a_street && !b_street) {
			return 0;
		}
		a_street = a_street.toLowerCase();
		b_street = b_street.toLowerCase();
		if (a_street > b_street) {
			return 1;
		} else if (a_street < b_street) {
			return -1;
		}
		return 0;
	});
    var html = '<option value="-1">---</option>';
	$.each( addresses, function() {
        switch ( this.location_address_type ) {
            case "0": icon = "lms-ui-icon-mail fa-fw";     break; // postal address
            case "1": icon = "lms-ui-icon-home fa-fw"; break; // billing address
            case "2": icon = "lms-ui-icon-customer-location fa-fw"; break; // location/recipient address
            case "3": icon = "lms-ui-icon-default-customer-location fa-fw"; break; // default location address
            case "4": icon = "lms-ui-icon-document fa-fw";    break; // invoice address

            default:
                icon = "";
        }

        html += '<option value="' + this.address_id  + '" data-icon="' + icon + '"' +
            ' data-territ="' + this.teryt + '"' +
            (preselection && this.hasOwnProperty('default_address') ? ' selected' : '') + '>' +
            this.location + '</option>';
    });
    $( select_id ).html(html);
}

/*!
 * \brief Clear select.
 */
LmsUiIconSelectMenu.prototype._clearList = function() {
    $( this.select_id ).html('<option value="-1">---</option>');

    this.refresh();
}

/*!
 * \brief Refresh select.
 *
 * \return boolean refresh status
 */
LmsUiIconSelectMenu.prototype.refresh = function() {
    // refresh select if ready
    if ( this.ready == 1 ) {
        $( this.select_id ).iconselectmenu( 'refresh' );
        return true;
    }

    return false;
}





