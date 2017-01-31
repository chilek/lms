/*!
 * \brief File used by function.location_box_expandable.php smarty plugin.
 */
$(function() {
    var counter = -1;

    /*!
     * \brief Show/hide single address box.
     */
    $('body').on('click', '.toggle-address', function() {
        $( '#' + $(this).attr( 'data-target' ) ).slideToggle(200);

        if ( $(this).attr('data-state') == 'closed' ) {
            $(this).attr('data-state', 'opened')
                   .text('–');
        } else {
            $(this).attr('data-state', 'closed')
                   .text('+');
        }
    });

    /*!
     * \brief Add new address box to table.
     */
    $('.locbox-addnew').click( function() {
        var box = $(this).closest(".customer-location-addresses-box").find(".addresses-container");

        if ( counter == -1 ) {
            counter = $(this).closest(".customer-location-addresses-box").find(".addresses-container > div").length + 2;
        } else {
            ++counter;
        }

        $.ajax({
            url: "?m=customeraddresses&action=getlocationboxhtml&prefix=" + $(this).attr('data-prefix') + "[addresses][" + counter + "]&default_type=1&clear_button=1",
        }).done( function(data) {
            insertRow( box, data );
        });
    });

    /*!
     * \brief Function insert row content into table.
     * Before insert will be generated new id for
     * handle events using inside.
     *
     * \param container jQuery object contains location div
     * \param data      html code with row content
     */
    function insertRow( container, data ) {
        var prev_id = $(data).attr('id');
        var id = lms_uniqid();

        // replace old id with current generated
        data = String(data).replace( prev_id, id );

        // insert data
        container.append(data);
    }

    /*!
     * \brief Remove address box.
     */
    $('body').on('click', '.delete-location-box', function() {
        getLocationBox(this).remove();
    });

    /*!
     * \brief Clear address box inputs.
     */
    $('body').on('click', '.clear-location-box', function() {
        var box = getLocationBox(this);

        // find all inputs and clear values
        $( box.find('input') ).each(function( index ) {
            switch ( $(this).attr('type') ) {
                case 'checkbox':
                    $(this).prop('checked', false);
                break;

                case 'text':
                case 'hidden':
                    $(this).val('');
                    $(this).removeAttr('readonly');
                break;
            }
        });

        // choose first option for each select inside location box
        $( box.find('select') ).each(function() {
            $(this).val( $(this).find('option:first').val() );
        });
    });

    /*!
     * \brief Use group of checkboxes as radio button by class.
     */
    $('body').on('click', '.lmsui-address-box-def-address', function() {
        var state = this.checked;

        // unmark all checkboxes
        $( $('.lmsui-address-box-def-address') ).each(function() {
            $(this).prop('checked', false);
        });

        // toggle current clicked checkbox
        if ( state == true ) {
            $(this).prop('checked', true);
        }
    });

    /*!
     * \brief Get closest location box.
     *
     * \param  object inside box
     * \return object
     */
    function getLocationBox( _this ) {
        return $( _this ).closest(".location-box-expandable");
    }
});
