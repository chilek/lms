var map = null;
//var layerSwitcher = null;
var maprequest = null;
var mappopup = null;
var lastonline_limit;
var lmsProjection = new OpenLayers.Projection("EPSG:4326");
var devicesLbl;
var nodesLbl;

function removeInvisiblePopups()
{
	// OpenLayers doesn't destroy closed popups, so
	// we search for them here and destroy if there are ...
	for (var i in map.popups)
		if (!map.popups[i].visible()) {
			alert('Invisible popup ' + map.popups[i].id);
			map.removePopup(map.popups[i]);
		}
}

function set_lastonline_limit(sec)
{
	lastonline_limit = sec;
}

function netdevmap_updater()
{
	if (maprequest.status == 200) {
		try {
			var data = JSON.parse(maprequest.responseText);
		} catch (e) {
			alert('Network device map refresh error!');
			map.getControlsBy('displayClass', 'lmsRefreshButton')[0].deactivate();
			return 0;
		}
		//var data = eval('(' + maprequest.responseText + ')');
		var devices = data.devices;
		var nodes = data.nodes;

		var devicelayer = map.getLayersByName(devicesLbl)[0];
		for (i in devices)
		{
			var features = devicelayer.getFeaturesByAttribute('id', parseInt(devices[i].id));
			if (features.length && features[0].attributes.state != devices[i].state)
			{
				devices[i].id = parseInt(devices[i].id);
				var newfeature = new OpenLayers.Feature.Vector(
					features[0].geometry.clone(),
					devices[i]);
				devicelayer.removeFeatures([features[0]]);
				devicelayer.addFeatures([newfeature]);
			}
		}

		var nodelayer = map.getLayersByName(nodesLbl)[0];
		for (i in nodes)
		{
			var features = nodelayer.getFeaturesByAttribute('id', parseInt(nodes[i].id));
			if (features.length && features[0].attributes.state != nodes[i].state)
			{
				nodes[i].id = parseInt(nodes[i].id);
				var newfeature = new OpenLayers.Feature.Vector(
					features[0].geometry.clone(),
					nodes[i]);
				nodelayer.removeFeatures([features[0]]);
				nodelayer.addFeatures([newfeature]);
			}
		}
	}
	map.getControlsBy('displayClass', 'lmsRefreshButton')[0].deactivate();
}

function netdevmap_refresh(live)
{
	maprequest = OpenLayers.Request.issue({
		url: '?m=netdevmaprefresh' + (live ? '&live=1' : ''),
		callback: netdevmap_updater
	});
	if (!live)
		setTimeout("netdevmap_refresh()", lastonline_limit * 1000);
}

function close_popup(id)
{
	map.removePopup(id)
}

function ping_host(id, ip, type)
{
	//removeInvisiblePopups();

	for (var i = 0; i < map.popups.length, map.popups[i].id != id; i++);
	var popupid = id;
	if (type == null)
		type = 1;
	var pingContentsRequest = OpenLayers.Request.issue({
		url: '?m=ping&p=titlebar&popupid=' + id + '&ip=' + ip + '&type=' + type,
		async: false
	});
	if (pingContentsRequest.status == 200) {
		map.popups[i].setContentHTML(pingContentsRequest.responseText);
		autoiframe_setsize('autoiframe_' + popupid, 450, 300);
		map.popups[i].updateSize();
	}
}

function ping_any_host(id)
{
	var ip = document.forms[id + '_ipform'].ip.value;
	if (!ip.match(/^([0-9]{1,3}\.){3}[0-9]{1,3}$/))
		return false;

	ping_host(id, ip);

	return false;
}

function findFeaturesIntersection(selectFeature, feature)
{
	var featureLonLat = new OpenLayers.LonLat(feature.data.lon, feature.data.lat);
	featureLonLat.transform(lmsProjection, map.getProjectionObject());
	var featurePixel = map.getPixelFromLonLat(featureLonLat);
	var features = [];
	for (var i in selectFeature.layers) {
		var layer = selectFeature.layers[i];
		for (var j in layer.features) {
			var currentFeature = layer.features[j];
			if (currentFeature.getVisibility() && currentFeature.onScreen()) {
				var currentLonLat = new OpenLayers.LonLat(
					currentFeature.geometry.x, currentFeature.geometry.y);
				var currentPixel = map.getPixelFromLonLat(currentLonLat);
				if (Math.abs(currentPixel.x - featurePixel.x) < 12 &&
					Math.abs(currentPixel.y - featurePixel.y) < 12)
					features.push(currentFeature);
			}
		}
	}
	return features;
}

function createMap(deviceArray, devlinkArray, nodeArray, nodelinkArray, selection, startLon, startLat)
{
	var linkstyles = [
		{ strokeColor: '#00ff00', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#0000ff', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#ff0000', strokeOpacity: 0.5, strokeWidth: 2 }
	];

	var map = new OpenLayers.Map("map", {
		controls: [new OpenLayers.Control.KeyboardDefaults(),
			(selection ? new OpenLayers.Control.PanZoomBar() : new OpenLayers.Control.ZoomPanel()),
			new OpenLayers.Control.Navigation(),
			new OpenLayers.Control.Permalink("Permalink")] });
	if (!selection)
		map.addControl(new OpenLayers.Control.PanPanel());

	var gsat = new OpenLayers.Layer.Google("Google Satellite",
		{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 20, visibility: false});
	var gphy = new OpenLayers.Layer.Google("Google Physical",
		{type: google.maps.MapTypeId.TERRAIN, visibility: false});
	var gmap = new OpenLayers.Layer.Google("Google Streets", // the default
		{numZoomLevels: 20, visibility: false});
	var ghyb = new OpenLayers.Layer.Google("Google Hybrid",
		{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 20, visibility: true});
	var osm = new OpenLayers.Layer.OSM();

	map.addLayers([gsat, gphy, gmap, ghyb, osm]);
	var devicestyle = new OpenLayers.Style(
		{
			graphicWidth: 16,
			graphicHeight: 16,
			graphicXOffset: -8,
			graphicYOffset: -8,
			externalGraphic: "${img}"
		}, {
			context: {
				img: function(feature) {
					//alert(map.zoom);
					switch (feature.attributes.state) {
						case 0: return "img/netdev_unk.png";
						case 1: return "img/netdev_on.png";
						case 2: return "img/netdev_off.png";
						default: return "img/netdev.png";
					}
				}
			}
		});

	var nodestyle = new OpenLayers.Style(
		{
			graphicWidth: 16,
			graphicHeight: 16,
			graphicXOffset: -8,
			graphicYOffset: -8,
			externalGraphic: "${img}"
		}, {
			context: {
				img: function(feature) {
					switch (feature.attributes.state) {
						case 0: return "img/node_unk.png";
						case 1: return "img/node_on.png";
						case 2: return "img/node_off.png";
						default: return "img/node.png";
					}
				}
			}
		});

	var area = new OpenLayers.Bounds();
	var devices = [];
	if (deviceArray)
		for (i in deviceArray)
		{
			var lonLat = new OpenLayers.LonLat(deviceArray[i].lon, deviceArray[i].lat)
				.transform(lmsProjection, map.getProjectionObject());
			area.extend(lonLat);
			devices.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), deviceArray[i]));
		}

	devicesLbl = OpenLayers.Lang.translate("Devices");
	var devicelayer = new OpenLayers.Layer.Vector(devicesLbl, {
		styleMap: new OpenLayers.StyleMap(devicestyle)
	});
	devicelayer.addFeatures(devices);

	var devlinks = [];
	if (devlinkArray)
		for (i in devlinkArray)
		{
			var points = new Array(
				new OpenLayers.Geometry.Point(devlinkArray[i].srclon, devlinkArray[i].srclat)
					.transform(lmsProjection, map.getProjectionObject()),
				new OpenLayers.Geometry.Point(devlinkArray[i].dstlon, devlinkArray[i].dstlat)
					.transform(lmsProjection, map.getProjectionObject())
			);
			devlinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				null, linkstyles[devlinkArray[i].type]));
		}

	var devlinkLbl = OpenLayers.Lang.translate("Device Links");
	var devlinklayer = new OpenLayers.Layer.Vector(devlinkLbl);
	devlinklayer.addFeatures(devlinks);

	var nodes = [];
	if (nodeArray)
		for (i in nodeArray)
		{
			var lonLat = new OpenLayers.LonLat(nodeArray[i].lon, nodeArray[i].lat)
				.transform(lmsProjection, map.getProjectionObject());
			area.extend(lonLat);
			nodes.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), nodeArray[i]));
		}

	nodesLbl = OpenLayers.Lang.translate("Nodes");
	var nodelayer = new OpenLayers.Layer.Vector(nodesLbl, {
		styleMap: new OpenLayers.StyleMap(nodestyle)
	});
	nodelayer.addFeatures(nodes);

	var nodelinks = [];
	if (nodelinkArray)
		for (i in nodelinkArray)
		{
			var points = new Array(
				new OpenLayers.Geometry.Point(nodelinkArray[i].nodelon, nodelinkArray[i].nodelat)
					.transform(lmsProjection, map.getProjectionObject()),
				new OpenLayers.Geometry.Point(nodelinkArray[i].netdevlon, nodelinkArray[i].netdevlat)
					.transform(lmsProjection, map.getProjectionObject())
				);
			nodelinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				null, linkstyles[nodelinkArray[i].type]));
		}

	var nodelinkLbl = OpenLayers.Lang.translate("Node Links");
	var nodelinklayer = new OpenLayers.Layer.Vector(nodelinkLbl);
	nodelinklayer.addFeatures(nodelinks);

	map.addLayer(devicelayer);
	map.addLayer(devlinklayer);
	map.addLayer(nodelayer);
	map.addLayer(nodelinklayer);

	var highlightlayer = new OpenLayers.Control.SelectFeature([devicelayer, nodelayer], {
		hover: true,
		highlightOnly: true,
		clickout: false,
		toggle: false,
		multiple: false,
		eventListeners: {
			"featurehighlighted": function(e) {
				var map = this.map;
				var feature = e.feature;
				if (mappopup == null)
				{
					var features = findFeaturesIntersection(this, feature);
					var content = '<div class="lmsMapPopupContents">';
					for (var i in features) {
						content += '<div class="lmsMapPopupName">' + features[i].data.name + '</div>'
							+ (features[i].data.ipaddr.length ? 
								'<div class="lmsMapPopupAddress">' + features[i].data.ipaddr.replace(/,/g, 
									'</div><div class="lmsMapPopupAddress">') + '</div>'
								: '');
					}
					content += '</div>';
					mappopup = new OpenLayers.Popup.Anchored(null,
						new OpenLayers.LonLat(feature.data.lon, feature.data.lat)
							.transform(lmsProjection, map.getProjectionObject()),
						new OpenLayers.Size(10, 10), content);
					mappopup.setOpacity(0.8);
					mappopup.closeOnMove = true;
					map.addPopup(mappopup);
					mappopup.div.style.overflow = 'visible';
					mappopup.div.style.width = 'auto';
					mappopup.div.style.height = 'auto';
					mappopup.groupDiv.style.overflow = 'visible';
					mappopup.groupDiv.style.width = 'auto';
					mappopup.groupDiv.style.height = 'auto';
					mappopup.contentDiv.style.width = 'auto';
					mappopup.contentDiv.style.heigh = 'auto';
					//mappopup.updateSize();
				}
				OpenLayers.Event.stop(e);
			},
			"featureunhighlighted": function(e) {
				if (mappopup)
				{
					var map = this.map;
					map.removePopup(mappopup);
					mappopup = null;
				}
				OpenLayers.Event.stop(e);
			}
		}
	});
	map.addControl(highlightlayer);
	highlightlayer.activate();

	if (selection)
	{
		var selectlayer = new OpenLayers.Control.SelectFeature([devicelayer, nodelayer], {
			clickout: true, toggle: false,
			multiple: true, hover: false,
			toggleKey: "ctrlKey", // ctrl key removes from selection
			multipleKey: "shiftKey", // shift key adds to selection
			onSelect: function(feature) {
				//alert(feature);
				var map = feature.layer.map;
				if (mappopup)
				{
					map.removePopup(mappopup);
					mappopup = null;
				}
				selectedFeature = feature;
				var featurepopup = new OpenLayers.Popup(null,
					new OpenLayers.LonLat(feature.data.lon, feature.data.lat)
						.transform(lmsProjection, map.getProjectionObject()),
					new OpenLayers.Size(10, 10));
				featurepopup.setOpacity(0.9);
				//featurepopup.closeOnMove = true;
				//featurepopup.keepInMap = true;
				//featurepopup.panMapIfOutOfView = true;
				var content = '<div class="lmsPopupTitleBar"><div class="lmsPopupTitle">Info</div>'
					+ '<div id="' + featurepopup.id + '_popupCloseBox" class="olPopupCloseBox lmsPopupCloseBox">&nbsp;</div></div>'
					+ '<div class="lmsInfoPopupContents">';
				var features = findFeaturesIntersection(this, feature);
				for (var i in features) {
					content += '<div class="lmsInfoPopupName">' + features[i].data.name + '</div>';
					if (features[i].data.type == 'netdevinfo') {
						if (features[i].data.ipaddr.length) {
							var ips = features[i].data.ipaddr.split(',');
							var nodeids = features[i].data.nodeid.split(',');
							for (var j in nodeids)
								content += '<div class="lmsInfoPopupAddress"><a href="#" onclick="ping_host(\''
								+ featurepopup.id + '\', \'' + ips[j] + '\')"><img src="img/ip.gif" alt="">&nbsp;'
								+ ips[j] + '</a></div>';
						}
					} else
						content += '<div class="lmsInfoPopupAddress"><a href="#" onclick="ping_host(\''
							+ featurepopup.id + '\', \'' + features[i].data.ipaddr + '\')"><img src="img/ip.gif" alt="">&nbsp;'
							+ features[i].data.ipaddr + '</a></div>';
					content += '<div class="lmsInfoPopupDetails"><a href="?m=' + features[i].data.type + '&id=' + features[i].data.id + '">'
						+ '<img src="img/info1.gif" alt="">&nbsp;Info</a></div>';
				}
				content += '</div>';
				featurepopup.setContentHTML(content);

				map.addPopup(featurepopup);

				var dragpopup = new OpenLayers.Control.DragPopup(featurepopup, { feature: feature });
				map.addControl(dragpopup);

				featurepopup.div.style.overflow = 'visible';
				featurepopup.div.style.width = 'auto';
				featurepopup.div.style.height = 'auto';
				featurepopup.groupDiv.style.overflow = 'visible';
				featurepopup.groupDiv.style.width = 'auto';
				featurepopup.groupDiv.style.height = 'auto';
				featurepopup.contentDiv.style.width = 'auto';
				featurepopup.contentDiv.style.heigh = 'auto';
				//featurepopup.updateSize();
				feature.popup = featurepopup;
			},
			onUnselect: function(feature) {
				//map.removePopup(feature.popup);
				//feature.popup = null;
			}
		});
		map.addControl(selectlayer);
		selectlayer.activate();

		var checkbuttonLbl = OpenLayers.Lang.translate("Check a host ...");
		var checkbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCheckButton", 
			title: checkbuttonLbl,
			command: 'check'});

		var centerbuttonLbl = OpenLayers.Lang.translate("Center map around network elements ...");
		var centerbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCenterButton", 
			title: centerbuttonLbl,
			command: 'center'});

		var refreshbuttonLbl = OpenLayers.Lang.translate("Refesh network state ...");
		var refreshbutton = new OpenLayers.Control.Button({
			displayClass: "lmsRefreshButton", 
			title: refreshbuttonLbl,
			command: 'refresh'});

		var panel = new OpenLayers.Control.Panel({
			type: OpenLayers.Control.TYPE_BUTTON,
			title: "Toolbar",
			activateControl: function(control) {
				var map = control.map;
				switch (control.command) {
					case 'check':
						var pingpopup = new OpenLayers.Popup(null,
							map.getLonLatFromPixel(new OpenLayers.Pixel(61, 32)).clone(),
							new OpenLayers.Size(10, 10));
						pingpopup.setOpacity(0.9);
						//pingpopup.closeOnMove = true;
						pingpopup.keepInMap = true;
						pingpopup.panMapIfOutOfView = true;
						var pingPopupRequest = OpenLayers.Request.issue({
							url: '?m=ping&p=ipform&popupid=' + pingpopup.id,
							async: false
						});
						if (pingPopupRequest.status == 200)
							pingpopup.setContentHTML(pingPopupRequest.responseText);
						map.addPopup(pingpopup);

						var dragpopup = new OpenLayers.Control.DragPopup(pingpopup);
						map.addControl(dragpopup);

						pingpopup.div.style.overflow = 'visible';
						pingpopup.div.style.width = 'auto';
						pingpopup.div.style.height = 'auto';
						pingpopup.groupDiv.style.overflow = 'visible';
						pingpopup.groupDiv.style.width = 'auto';
						pingpopup.groupDiv.style.height = 'auto';
						pingpopup.contentDiv.style.width = 'auto';
						pingpopup.contentDiv.style.heigh = 'auto';
						//pingpopup.updateSize();
						document.forms[pingpopup.id + '_ipform'].ip.focus();
						break;
					case 'center':
						var area = new OpenLayers.Bounds();
						for (var i = 0; i < map.layers.length; i++) {
							if (!map.layers[i].isBaseLayer)
								for (var j = 0; j < map.layers[i].features.length; j++) {
									var feature = map.layers[i].features[j];
									area.extend(feature.geometry.bounds);
								}
						}
						if (area.left != null)
							map.zoomToExtent(area);
						else
							map.zoomToMaxExtent();
						break;
					case 'refresh':
						if (!control.active) {
							control.activate();
							netdevmap_refresh(true);
						}
						break;
				}
			}
		});
		panel.addControls([checkbutton, centerbutton, refreshbutton]);
		map.addControl(panel);
	}

	map.addControl(new OpenLayers.Control.ScaleLine());
	map.addControl(new OpenLayers.Control.NavToolbar());
	/* in MSIE LayerSwitcher display rounded corners is broken */
	if (navigator.appName == "Microsoft Internet Explorer") {
		var layerSwitcher = new OpenLayers.Control.LayerSwitcher({ roundedCorner: false });
	} else {
		var layerSwitcher = new OpenLayers.Control.LayerSwitcher({ roundedCornerColor: '#CEBD9B' });
	}
	map.addControl(layerSwitcher);
	map.addControl(new OpenLayers.Control.MousePosition({ displayProjection: lmsProjection }));

	// load saved map settings from cookies
	var mapBaseLayer = getCookie('mapBaseLayer');
	if (mapBaseLayer != null)
		map.setBaseLayer(map.layers[mapBaseLayer]);
	else
		map.setBaseLayer(gmap);

	var mapLayers = getCookie('mapLayers');
	if (mapLayers != null) {
		var visibleLayers = mapLayers.split(';');
		for (var i = 0; i < visibleLayers.length; i++) {
			for (var j = 0; j < layerSwitcher.dataLayers.length, layerSwitcher.layerStates[j].id != layerSwitcher.dataLayers[i].layer.id; j++);
			layerSwitcher.dataLayers[i].layer.setVisibility((visibleLayers[i] == '1' ? true : false));
		}
	}

	var loadedSettings = false;
	var mapSettings = getCookie('mapSettings');
	var lon = null, lat = null, zoom = null;
	if (mapSettings != null) {
		var mapData = mapSettings.split(';');
		if (mapData.length == 3) {
			var lon = parseFloat(mapData[0]);
			var lat = parseFloat(mapData[1]);
			var zoom = parseInt(mapData[2]);
			loadedSettings = true;
		}
	}

	if (startLon != null && startLat != null)
		if (zoom)
			map.setCenter(new OpenLayers.LonLat(startLon, startLat)
					.transform(lmsProjection, map.getProjectionObject()), zoom);
		else
			map.setCenter(new OpenLayers.LonLat(startLon, startLat)
					.transform(lmsProjection, map.getProjectionObject()));
	else
		if (loadedSettings)
			map.setCenter(new OpenLayers.LonLat(lon, lat), zoom);
		else
			if (deviceArray || nodeArray)
				map.zoomToExtent(area);
			else
				map.zoomToMaxExtent();

	// register events to save map settings to cookies
	map.events.register('changelayer', map, function(e) {
		if (!e.layer.isBaseLayer) {
			var visibleLayers = [];
			for (i = 0; i < layerSwitcher.dataLayers.length; i++) {
				for (var j = 0; j < layerSwitcher.layerStates.length, layerSwitcher.layerStates[j].id != layerSwitcher.dataLayers[i].layer.id; j++);
				visibleLayers.push(layerSwitcher.layerStates[j].visibility ? '1' : '0');
			}
			setCookie('mapLayers', visibleLayers.join(';'), true);
		}
	});
	map.events.register('changebaselayer', map, function(e) {
		for (var i = 0; i < layerSwitcher.baseLayers.length, layerSwitcher.baseLayers[i].layer.id != e.layer.id; i++);
		setCookie('mapBaseLayer', i, true);
	});
	map.events.register('moveend', map, function(e) {
		setCookie('mapSettings',  map.getCenter().lon + ';' + map.getCenter().lat + ';' + map.getZoom(), true);
	});

	//map.events.register('mousemove', map, function(e) {
	//	removeInvisiblePopups();
	//});

	return map;
}
