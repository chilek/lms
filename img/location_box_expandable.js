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
        var _buttonrow = $(this).closest('tr');

        if ( counter == -1 ) {
            counter = $('body').find('.location-box-expandable').length + 1;
        } else {
            ++counter;
        }

        $.ajax({
            url: "?m=customeraddresses&action=getlocationboxhtml&prefix=" + $(this).attr('data-prefix') + "[addresses][" + counter + "]&default_type=1&clear_button=1",
        }).done( function(data) {
            insertRow( _buttonrow, data );
        });
    });

    /*!
     * \brief Update address string name on box input change.
     */
    $('body').on('input', '.location-box-expandable input', function(){
        var box = getLocationBox(this);

        var city   = box.find('[data-address="city"]').val();
        var street = box.find('[data-address="street"]').val();
        var house  = box.find('[data-address="house"]').val();
        var flat   = box.find('[data-address="flat"]').val();

        var location = location_str( city, street, house, flat );

        if ( location.length > 0 ) {
            box.find('.address-full').text( location );
        } else {
            box.find('.address-full').text( '...' );
        }
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
        var row_content = '';

        row_content += '<tr>';
        row_content += '<td class="valign-top"><img src="" alt="" class="location-box-image"></td>';
        row_content += '<td>' + data + '</td>';
        row_content += '</tr>';

        $(container).before( row_content );
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

        // set all image source as empty
        $( $('.location-box-image') ).each(function() {
            $(this).attr('src', '');
        });

        // unmark all checkboxes
        $( $('.lmsui-address-box-def-address') ).each(function() {
            $(this).prop('checked', false);
        });

        // toggle current clicked checkbox
        if ( state == true ) {
            $(this).prop('checked', true);
            getLocationBox(this).closest('tr').find('.location-box-image').attr('src', 'img/location.png');
        }
    });

    /*!
     * \brief Get closest location box.
     *
     * \param  any object inside box
     * \return box object
     */
    function getLocationBox( _this ) {
        return $( _this ).closest(".location-box-expandable");
    }
});
