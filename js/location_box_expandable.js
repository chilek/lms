/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
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

/*!
 * \brief File used by function.location_box_expandable.php smarty plugin.
 */
$(function() {
    var customer_location_icon = 'lms-ui-icon-customer-location fa-fw';
    var default_customer_location_icon = 'lms-ui-icon-default-customer-location fa-fw';

    var counter = -1;

    /*!
     * \brief Show/hide single address box.
     */
    $('body').on('click', '.address-full', function() {
    	$('.address-full').not(this).next().hide().attr('data-state', 'closed');

        $( '#' + $(this).attr( 'data-target' ) ).toggle();

        if ( $(this).attr('data-state') == 'closed' ) {
            $(this).attr('data-state', 'opened');
        } else {
            $(this).attr('data-state', 'closed');
        }
    });

    /*!
     * \brief Add new address box to table.
     */
    $('.lms-ui-location-add-button').click( function() {
        var _buttonrow = $(this).closest('tr');

        if ( counter == -1 ) {
            counter = $('body').find('.location-box-expandable').length + 1;
        } else {
            ++counter;
        }

        $.ajax({
            url: "?m=customeraddresses&action=getlocationboxhtml&prefix=" + $(this).attr('data-prefix') +
                "[addresses][" + counter + "]&default_type=1&delete_button=1&billing_address_button=1&post_address_button=1&show=1",
        }).done( function(data) {
            insertRow( _buttonrow, data );
        });
    });

	var timer = null;

	function updateLocationString(box) {
        var address_type = box.find('[data-address="address_type"]').val();
        var location_name = box.find('[data-address="location-name"]').val();
        var teryt = box.find('[data-address="teryt-checkbox"]').prop('checked');
        var city   = box.find('[data-address="city"]').val();
        var street = box.find('[data-address="street"]').val();
        var house  = box.find('[data-address="house"]').val();
        var flat   = box.find('[data-address="flat"]').val();
        var zip    = box.find('[data-address="zip"]').val();
        var postoffice = box.find('[data-address="postoffice"]').val();

        var location = location_str({
            city: city,
            street: street,
            house: house,
            flat: flat,
            zip: zip,
            postoffice: postoffice,
            teryt: teryt
        });
        location = (address_type == 1 || !location_name.length ? '' : location_name + ', ') + (location.length > 0 ? location : '...');

        box.find('[data-address="location"]').val( location );
        box.find('.address-full').text( location );
    }

    /*!
     * \brief Update address string name on box input change.
     */
    $('body').on('input', '.location-box-expandable input', function(){

        var box = getLocationBox(this);

        updateLocationString(box);

        var teryt = box.find('[data-address="teryt-checkbox"]').prop('checked');
		var city   = box.find('[data-address="city"]').val();
		var cityid = teryt ? box.find('[data-address="city-hidden"]').val() : null;
        var street = box.find('[data-address="street"]').val();
		var streetid = teryt ? box.find('[data-address="street-hidden"]').val() : null;
		var house  = box.find('[data-address="house"]').val();
        var zip    = box.find('[data-address="zip"]').val();
		var country = box.find('[data-address="country"] option:selected').text();
		var countryid = box.find('[data-address="country"]').val();

        var elem = this;

        if (timer) {
            clearTimeout(timer);
        }
		if (city.length && house.length && !$(this).is('[data-address="zip"]') && !zip.length) {
			timer = window.setTimeout(function () {
				var search = {
					"city": city,
					"cityid": cityid,
					"street": street,
					"streetid": streetid,
					"house": house,
					"country": country,
					"countryid": countryid
				}
				if (lmsSettings.zipCodeBackend == 'pna') {
					pna_get_zip_code(search, function (zip) {
						if (zip.length) {
							box.find('[data-address="zip"]').val(zip);
							$(elem).trigger('input');
						} else {
							osm_get_zip_code(search, function (zip) {
								box.find('[data-address="zip"]').val(zip);
								$(elem).trigger('input');
							});
						}
					});
				} else {
					osm_get_zip_code(search, function (zip) {
						box.find('[data-address="zip"]').val(zip);
						$(elem).trigger('input');
					});
				}
			}, 500);
		}
    });

	$('.zip-code-button').click(function() {
		var box = $(this).closest('.location-box-expandable');
        var teryt = box.find('[data-address="teryt-checkbox"]').prop('checked');
		var city   = box.find('[data-address="city"]').val();
		var cityid = teryt ? box.find('[data-address="city-hidden"]').val() : null;
		var street = box.find('[data-address="street"]').val();
		var streetid = teryt ? box.find('[data-address="street-hidden"]').val() : null;
		var house  = box.find('[data-address="house"]').val();
		var zipelem = box.find('[data-address="zip"]');
		var country = box.find('[data-address="country"] option:selected').text();
		var countryid = box.find('[data-address="country"]').val();

		if (city.length && house.length) {
			var search = {
				"city": city,
				"cityid": cityid,
				"street": street,
				"streetid": streetid,
				"house": house,
				"country": country,
				"countryid": countryid
			}
		    if (lmsSettings.zipCodeBackend == 'pna') {
				pna_get_zip_code(search, function (zip) {
					if (zip.length) {
						zipelem.val(zip).trigger('input');
					} else {
						osm_get_zip_code(search, function (zip) {
							zipelem.val(zip).trigger('input');
						});
					}
				});
			} else {
				osm_get_zip_code(search, function (zip) {
					zipelem.val(zip).trigger('input');
				});
			}
		}
		return false;
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
        var uid = lms_uniqid();

        // replace old id with current generated
        data = String(data).replace( prev_id, id );

        // insert data
        var row_content = '';

        row_content += '<tr>';
        row_content += '<td class="valign-top">';
        row_content += '<i class="' + customer_location_icon + ' location-box-image" title="' +
			$t('location/recipient address') + '" id="' + uid + '"></i>';
        row_content += '</td>';
        row_content += '<td>' + data + '</td></tr>';

        $(container).before( row_content );

        $( '#'+uid ).tooltip().removeAttr('id');
    }

    /*!
     * \brief Remove address box.
     */
	$('body').on('click', '.delete-location-box', function () {
		confirmDialog($t('Are you sure that you want to remove address?'), this).done(function () {
			var location_box = getLocationBox(this);
			var node_use_counter = parseInt(location_box.attr('data-node-use-counter'));
            var netdev_use_counter = parseInt(location_box.attr('data-netdev-use-counter'));
            var netnode_use_counter = parseInt(location_box.attr('data-netnode-use-counter'));
			if (node_use_counter || netdev_use_counter || netnode_use_counter) {
			    var msg = $t('Address is used by the following resources:');
			    msg += '<br><br><ul>';
			    if (node_use_counter) {
			        msg += '<li>' + $t('assigned to <strong>$a</strong> nodes', node_use_counter) + '</li>';
                }
                if (netdev_use_counter) {
                    msg += '<li>' + $t('assigned to <strong>$a</strong> network devices', netdev_use_counter) + '</li>';
                }
                if (netnode_use_counter) {
                    msg += '<li>' + $t('assigned to <strong>$a</strong> network nodes', netnode_use_counter) + '</li>';
                }
                msg += '</ul><br>';
                msg += $t('Do you confirm?');
                confirmDialog(msg, this).done(function () {
                    location_box.closest('tr').remove();
                });
            } else {
                location_box.closest('tr').remove();
            }
		});
	});

    /*!
     * \brief Clear address box inputs.
     */
    $('body').on('click', '.clear-location-box', function() {
        var box = getLocationBox(this);

        function clearLocationBox() {
            // find all inputs and clear values
            $( box.find('input') ).each(function( index ) {
                switch ( $(this).attr('type') ) {
                    case 'checkbox':
                        $(this).prop('checked', false);
                        break;

                    case 'text':
                    case 'hidden':
                        if (!$(this).is('[data-address="address_type"]')) {
                            $(this).val('')
                                .removeAttr('readonly');
                        }
                        break;
                }
            });

            // clear state of location image if it was default location so far
            var address_type = box.find('input[data-address="address_type"]');
            if (address_type.val() == 3) {
                $('.location-box-image', box.closest('tr'))
                    .attr('class', customer_location_icon + ' location-box-image')
                    .tooltip().tooltip('destroy')
                    .attr('title', $t('location/recipient address'))
                    .tooltip();
                address_type.val(2)
                    .closest('tr')
                    .find('.address-full')
                    .tooltip().tooltip('destroy')
                    .attr('title', $t('location/recipient address'))
                    .tooltip();
            }

            // clear location address text
            box.find('.address-full').text('...');

            // choose first option for each select inside location box
            $( box.find('select') ).each(function() {
                $(this).val( $(this).find('option:first').val() );
            });
        }

        var node_use_counter = parseInt(box.attr('data-node-use-counter'));
        var netdev_use_counter = parseInt(box.attr('data-netdev-use-counter'));
        var netnode_use_counter = parseInt(box.attr('data-netnode-use-counter'));
        if (node_use_counter || netdev_use_counter || netnode_use_counter) {
            var msg = $t('Address is used by the following resources:');
            msg += '<br><br><ul>';
            if (node_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> nodes', node_use_counter) + '</li>';
            }
            if (netdev_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> network devices', netdev_use_counter) + '</li>';
            }
            if (netnode_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> network nodes', netnode_use_counter) + '</li>';
            }
            msg += '</ul><br>';
            msg += $t('Do you confirm?');
            confirmDialog(msg, this).done(function () {
                clearLocationBox();
            });
        } else {
            clearLocationBox();
        }
    });

    /*!
     * \brief Use group of checkboxes as radio button by class.
     */
    $('body').on('click', '.lms-ui-address-box-def-address', function() {
        var state = this.checked;
        var box = getLocationBox(this);

        // mark old default location address as normal location address
        // open definitions.php for more
        // 3 = DEFAULT_LOCATION_ADDRESS
        // 2 = LOCATION_ADDRESS
        $( $("input[data-address='address_type']") ).each(function() {
            if ( $(this).val() == 3 ) {
                $(this).val(2)                                                // update address type
                       .closest('tr')
                       .find('.address-full')
                       .tooltip().tooltip('destroy')                          // can't destroy or update not initialized tooltip
                       .attr('title', $t('location/recipient address')) // update title
                       .tooltip();                                            // init tooltip
            }
        });

        // set all image source as default
        $( $('.location-box-image') ).each(function() {
            $(this).attr('class', customer_location_icon + ' location-box-image')                     // change icon source
                   .tooltip().tooltip('destroy')                              // can't destroy or update not initialized tooltip
                   .attr('title', $t('location/recipient address'))     // update title
                   .tooltip();                                                // init tooltip
        });

        // unmark all checkboxes
        $( $('.lms-ui-address-box-def-address') ).each(function() {
            $(this).prop('checked', false);                                   // uncheck all default address checkboxes
        });

        // toggle current clicked checkbox
        if ( state == true ) {
            $(this).prop('checked', true);                                    // check default address checkbox

            box.closest('tr')
               .find('.address-full')
               .tooltip().tooltip('destroy')                                  // can't destroy or update not initialized tooltip
               .attr('title', $t('default location address'))           // update title
               .tooltip();                                                    // init tooltip

            box.closest('tr')
               .find('.location-box-image')
               .attr('class', default_customer_location_icon + ' location-box-image')                      // change icon source
               .tooltip().tooltip('destroy')                                  // can't destroy or update not initialized tooltip
               .attr('title', $t('default location address'))           // update icon title
               .tooltip();                                                    // init tooltip

            box.find("input[data-address='address_type']").val(3);            // update address type
                                                                              // 3 = DEFAULT_LOCATION_ADDRESS
        }
    });

    function copyAddress(from, to) {
        from.find('[data-address]').each(function(index, elem) {
            var property = $(elem).attr('data-address');
            if (['location-name', 'location', 'state', 'state-hidden', 'city', 'city-hidden', 'street', 'street-hidden', 'house', 'flat', 'zip', 'postoffice', 'country'].indexOf(property) != -1) {
                to.find('[data-address="' + property + '"]').val($(elem).val());
            }
            to.find('[data-address="teryt-checkbox"]').prop('checked',
                from.find('[data-address="teryt-checkbox"]').prop('checked')).trigger('change');
        });

        updateLocationString(to);
    }

    $('body').on('click', '.copy-address', function() {
        var from = getLocationBox($('[data-address="address_type"][value="' + $(this).attr('data-type') + '"]'));
        var to = getLocationBox(this);

        var node_use_counter = parseInt(to.attr('data-node-use-counter'));
        var netdev_use_counter = parseInt(to.attr('data-netdev-use-counter'));
        var netnode_use_counter = parseInt(to.attr('data-netnode-use-counter'));
        if (node_use_counter || netdev_use_counter || netnode_use_counter) {
            var msg = $t('Address is used by the following resources:');
            msg += '<br><br><ul>';
            if (node_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> nodes', node_use_counter) + '</li>';
            }
            if (netdev_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> network devices', netdev_use_counter) + '</li>';
            }
            if (netnode_use_counter) {
                msg += '<li>' + $t('assigned to <strong>$a</strong> network nodes', netnode_use_counter) + '</li>';
            }
            msg += '</ul><br>';
            msg += $t('Do you confirm?');
            confirmDialog(msg, this).done(function () {
                copyAddress(from, to);
            });
        } else {
            copyAddress(from, to);
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
