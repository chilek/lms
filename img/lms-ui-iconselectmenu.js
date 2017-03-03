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
        var li = $( "<li>" ),
        wrapper = $( "<div>", { text: item.label } );

        if ( item.disabled ) {
            li.addClass( "ui-state-disabled" );
        }

        $( "<span>", {
            style: item.element.attr( "data-style" ),
            "class": "ui-icon " + item.element.attr( "data-class" )
        })
        .appendTo( wrapper );

        return li.append( wrapper ).appendTo( ul );
    }
});

/*
 * \brief Pseudo class for manage icon select menu.
 */
function LmsUiIconSelectMenu( id ) {
    // select id
    this.select_id = id;

    // select is ready to refresh list?
    // 0 - no
    // 1 - yes
    this.ready = 0;
}

LmsUiIconSelectMenu.prototype.init = function() {
    $( this.select_id )
        .iconselectmenu()
        .iconselectmenu( 'menuWidget' );

    // mark select as ready to use refresh
    this.ready = 1;
}

/*
 * \brief Clear list and set new items.
 *
 * \param address_list
 */
LmsUiIconSelectMenu.prototype.setAddressList = function( addresses_list ) {
    var icon;
    var select_id = this.select_id; // can't use this inside of each

    // clear addresses list
    $( this.select_id ).empty()
                       .append( $('<option>', { value: -1, text: "---", 'data-style': "background-image: url()" }) );

    // insert new addresses
    $.each( addresses_list, function(index) {

        switch ( this['location_address_type'] ) {
            case "0": // postal address
                icon = "img/post.gif";
            break;

            case "1": // billing address
                icon = "img/customer.gif";
            break;

            case "3": // default location address
                icon = "img/location.png";
            break;

            default:  // location address (no icon)
                icon = "";
        }

        $( select_id ).append( $('<option>', { value:this['address_id'], text:this['location'], 'data-style': "background-image: url("+icon+")" } ));
    });

    this.refresh();
}

/*
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





