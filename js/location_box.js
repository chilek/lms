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

$(function() {
	var timer = null;

	$('body').on('input', '.location-box input', function() {
		var box = $(this).closest('.location-box');

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
				var backends = [...lmsSettings.zipCodeBackend];

				var deferred = $.Deferred();
				var backend = backends.shift();

				function get_zip_code(backend) {
					switch (backend) {
						case 'osm':
							osm_get_zip_code(search, function (zip) {
								if (zip.length) {
									box.find('[data-address="zip"]').val(zip);
									$(elem).trigger('input');
									deferred.resolve();
								} else {
									deferred.reject();
								}
							});
							backend = '';
							break;
						case 'pna':
							pna_get_zip_code(search, function (zip) {
								if (zip.length) {
									box.find('[data-address="zip"]').val(zip);
									$(elem).trigger('input');
									deferred.resolve();
								} else {
									deferred.reject();
								}
							});
							backend = '';
							break;
						case 'prg':
							prg_get_zip_code(search, function (zip) {
								if (zip.length) {
									box.find('[data-address="zip"]').val(zip);
									$(elem).trigger('input');
									deferred.resolve();
								} else {
									deferred.reject();
								}
							});
							backend = '';
							break;
					}
				}

				function failHandler() {
					if (backends.length) {
						backend = backends.shift();
						deferred = $.Deferred();
						deferred.fail(failHandler);
						get_zip_code(backend);
					}
				}

				deferred.fail(failHandler);

				get_zip_code(backend);
			}, 500);
		}
	});

	$('.zip-code-button').click(function() {
		var box = $(this).closest('.location-box');

		var teryt = box.find('[data-address="teryt-checkbox"]').prop('checked');
		var city   = box.find('[data-address="city"]').val();
		var cityid = teryt ? box.find('[data-address="city-hidden"]').val() : null;
		var street = box.find('[data-address="street"]').val();
		var streetid = teryt ? box.find('[data-address="street-hidden"]').val() : null;
		var house  = box.find('[data-address="house"]').val();
		var zipelem = box.find('[data-address="zip"]');
		var country = box.find('[data-address="country"] option:selected').text();
		var countryid = box.find('[data-address="country"]').val();

		var backends = [...lmsSettings.zipCodeBackend];

		var deferred = $.Deferred();
		var backend = backends.shift();

		function get_zip_code(backend) {
			switch (backend) {
				case 'osm':
					osm_get_zip_code(search, function (zip) {
						if (zip.length) {
							zipelem.val(zip).trigger('input');
							deferred.resolve();
						} else {
							deferred.reject();
						}
					});
					backend = '';
					break;
				case 'pna':
					pna_get_zip_code(search, function (zip) {
						if (zip.length) {
							zipelem.val(zip).trigger('input');
							deferred.resolve();
						} else {
							deferred.reject();
						}
					});
					backend = '';
					break;
				case 'prg':
					prg_get_zip_code(search, function (zip) {
						if (zip.length) {
							zipelem.val(zip).trigger('input');
							deferred.resolve();
						} else {
							deferred.reject();
						}
					});
					backend = '';
					break;
			}
		}

		function failHandler() {
			console.log(backend + ' failed!');
			if (backends.length) {
				backend = backends.shift();
				deferred = $.Deferred();
				deferred.fail(failHandler);
				get_zip_code(backend);
			}
		}

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

			deferred.fail(failHandler);

			get_zip_code(backend);
		}

		return false;
	});
});
