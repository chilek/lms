/*
 * LMS version 1.11-git
 *
 * (C) Copyright 2001-2016 LMS Developers
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

/*
 * \brief Pseudo class for fast create dialog boxes.
 */
function LmsUiDialog( id ) {
    var that = this;

    // dialog body id
    this.dialog_body_id = "#" + id;

    // dialog opener button id
    this.opener_id = undefined;

    // dialog handler
    this.handler = $( this.dialog_body_id ).dialog({
                       autoOpen: false,
                       modal: true,
                       resizable: false,
                          buttons: [],
                       show: {
                           effect: "fade",
                           duration: 150
                       },
                       hide: {
                           effect: "fade",
                           duration: 150
                       },
                       open: function(){
                           jQuery('.ui-widget-overlay').bind('click',function(){
                               $( "#" + id ).dialog('close');
                           })
                       },
                       close: function() {
                           that.formReset();
                       }
                   });
}

/*
 * \brief Bind button who open dialog box after click.
 *
 * \param id button id
 */
LmsUiDialog.prototype.setOpener = function( id ) {
    var dialog = this;

    $("#" + id).button().on("click", function() {
        // blur button after click
        $(this).blur();

        // show dialog
        dialog.open();
    });
}

/*
 * \brief Show dialog box.
 */
LmsUiDialog.prototype.open = function() {
    $( this.handler ).dialog( "open" );
}

/*
 * \brief Hide dialog box.
 */
LmsUiDialog.prototype.close = function() {
    $( this.handler ).dialog( "close" );
}

/*
 * \brief Restore default forms value inside dialog box.
 */
LmsUiDialog.prototype.formReset = function() {
    $( this.dialog_body_id + " form" ).each( function() { this.reset() } );
}

/*
 * \brief Disable all buttons in dialog box.
 * Protection against fast click in submit button when you send form via ajax.
 * Use this function before send request and unlock buttons after.
 */
LmsUiDialog.prototype.disableButtons = function() {
    var name = "div[aria-describedby=" + this.dialog_body_id.substring(1);

    $( name ).find( ".ui-button" ).attr( "disabled", true );
}

/*
 * \brief Enable all buttons in dialog box.
 */
LmsUiDialog.prototype.enableButtons = function() {
    var name = "div[aria-describedby=" + this.dialog_body_id.substring(1);

    $( name ).find( ".ui-button" ).attr( "disabled", false );
}

/*
 * \brief Set buttons of dialog window.
 *
 * \param buttons json array with buttons
 */
LmsUiDialog.prototype.setButtons = function( buttons ) {
    $.each(buttons, function(index, button) {
       button.class = 'lms-ui-button';
    });
    $(this.handler).dialog('option', 'buttons', buttons);
}


// ------------ VISUAL HELPER FUNCTIONS ------------

/*
 * \brief Set width of dialog window.
 *
 * \param x number of pixels
 */
LmsUiDialog.prototype.setDialogWidth = function( x ) {
    $(this.handler).dialog('option', 'width', x);
}

/*
 * \brief Set height of dialog window.
 *
 * \param y number of pixels
 */
LmsUiDialog.prototype.setDialogHeight = function( y ) {
    $(this.handler).dialog('option', 'height', y);
}
