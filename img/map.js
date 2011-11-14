var map = null;
var maprequest = null;
var mappopup = null;
var featurepopup = null;
var pingpopup = null;
var lastonline_limit;

function removeInvisiblePopups()
{
	// OpenLayers doesn't destroy closed popups, so
	// we search for them here and destroy if there are ...
	for (var i in map.popups)
		if (!map.popups[i].visible())
			map.removePopup(map.popups[i]);
}

function set_lastonline_limit(sec)
{
	lastonline_limit = sec;
}

function netdevmap_updater()
{
	if (maprequest.status = 200)
	{
		data = eval('(' + maprequest.responseText + ')');
		devices = data.devices;
		nodes = data.nodes;

		devicelayer = map.getLayersByName('Devices')[0];
		for (i in devices)
		{
			var features = devicelayer.getFeaturesByAttribute('id', parseInt(devices[i].id));
			if (features.length && features[0].attributes.state != devices[i].state)
			{
				var newfeature = new OpenLayers.Feature.Vector(
					features[0].geometry.clone(),
					devices[i]);
				devicelayer.removeFeatures([features[0]]);
				devicelayer.addFeatures([newfeature]);
			}
		}

		nodelayer = map.getLayersByName('Nodes')[0];
		for (i in nodes)
		{
			var features = nodelayer.getFeaturesByAttribute('id', parseInt(nodes[i].id));
			if (features.length && features[0].attributes.state != nodes[i].state)
			{
				var newfeature = new OpenLayers.Feature.Vector(
					features[0].geometry.clone(),
					nodes[i]);
				nodelayer.removeFeatures([features[0]]);
				nodelayer.addFeatures([newfeature]);
			}
		}

	}
}

function netdevmap_refresh()
{
	maprequest = OpenLayers.Request.issue({
		url: '?m=netdevmaprefresh',
		callback: netdevmap_updater
	});
	setTimeout("netdevmap_refresh()", lastonline_limit * 1000);
}

function ping_from_map(id)
{
	featurepopup.setContentHTML(
		'<iframe id="autoiframe' + id + '" width=100 height=10 frameborder=0 scrolling=no src="/lms/?m=ping&id=' + id + '"></iframe>'
	);
	autoiframe_setsize('autoiframe' + id, 400, 300);
	featurepopup.updateSize();
}

function ping_from_popup()
{
	var ip = document.forms['ipform'].ip.value;
	if (!ip.match(/^([0-9]{1,3}\.){3}[0-9]{1,3}$/))
		return false;
	pingpopup.setContentHTML(
		'<iframe id="autoiframe' + ip.replace('.', '_') + '" width=100 height=10 frameborder=0 scrolling=no src="/lms/?m=ping&ip=' + ip + '"></iframe>'
	);

	removeInvisiblePopups();

	autoiframe_setsize('autoiframe' + ip.replace('.', '_'), 400, 300);
	pingpopup.updateSize();
	return false;
}

function createMap(deviceArray, devlinkArray, nodeArray, nodelinkArray, selection)
{
	var linkstyles = [
		{ strokeColor: '#00ff00', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#0000ff', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#ff0000', strokeOpacity: 0.5, strokeWidth: 2 }
	];

	var map = new OpenLayers.Map("map");
	var gsat = new OpenLayers.Layer.Google("Google Satellite",
		{type: google.maps.MapTypeId.SATELLITE, numZoomLevels: 22, visibility: false});
	var gphy = new OpenLayers.Layer.Google("Google Physical",
		{type: google.maps.MapTypeId.TERRAIN, visibility: false});
	var gmap = new OpenLayers.Layer.Google("Google Streets", // the default
		{numZoomLevels: 20, visibility: false});
	var ghyb = new OpenLayers.Layer.Google("Google Hybrid",
		{type: google.maps.MapTypeId.HYBRID, numZoomLevels: 22, visibility: true});
	var osm = new OpenLayers.Layer.OSM();

	map.addLayers([gsat, gphy, gmap, ghyb, osm]);
	map.setBaseLayer(gmap);

	var devicestyle = new OpenLayers.Style(
		{
			graphicWidth: 16,
			graphicHeight: 16,
			graphicXOffset: -8,
			graphicYOffset: -8
		},
		{
			rules: [
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 0
					}),
					symbolizer: {
						externalGraphic: "img/netdev_unk.png"
					}
				}),
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 1
					}),
					symbolizer: {
						externalGraphic: "img/netdev_on.png"
					}
				}),
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 2
					}),
					symbolizer: {
						externalGraphic: "img/netdev_off.png"
					}
				}),
				new OpenLayers.Rule({
					elseFilter: true,
					symbolizer: {
						externalGraphic: "img/netdev.png"
					}
				})
			]
		}
	);

	var nodestyle = new OpenLayers.Style(
		{
			graphicWidth: 16,
			graphicHeight: 16,
			graphicXOffset: -8,
			graphicYOffset: -8
		},
		{
			rules: [
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 0
					}),
					symbolizer: {
						externalGraphic: "img/node_unk.png"
					}
				}),
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 1
					}),
					symbolizer: {
						externalGraphic: "img/node_on.png"
					}
				}),
				new OpenLayers.Rule({
					filter: new OpenLayers.Filter.Comparison({
						type: OpenLayers.Filter.Comparison.EQUAL_TO,
						property: "state",
						value: 2
					}),
					symbolizer: {
						externalGraphic: "img/node_off.png"
					}
				}),
				new OpenLayers.Rule({
					elseFilter: true,
					symbolizer: {
						externalGraphic: "img/node.png"
					}
				})
			]
		}
	);

	var area = new OpenLayers.Bounds();
	var devices = [];
	if (deviceArray)
		for (i in deviceArray)
		{
			var lonLat = new OpenLayers.LonLat(deviceArray[i].lon, deviceArray[i].lat)
				.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
			area.extend(lonLat);
			devices.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), deviceArray[i]));
		}

	var devicelayer = new OpenLayers.Layer.Vector("Devices", {
		styleMap: new OpenLayers.StyleMap(devicestyle)
	});
	devicelayer.addFeatures(devices);
	map.addLayer(devicelayer);

	var devlinks = [];
	if (devlinkArray)
		for (i in devlinkArray)
		{
			var points = new Array(
				new OpenLayers.Geometry.Point(devlinkArray[i].srclon, devlinkArray[i].srclat)
					.transform(
						new OpenLayers.Projection("EPSG:4326"),
						map.getProjectionObject()
					),
				new OpenLayers.Geometry.Point(devlinkArray[i].dstlon, devlinkArray[i].dstlat)
					.transform(
						new OpenLayers.Projection("EPSG:4326"),
						map.getProjectionObject()
					)
			);
			devlinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				null, linkstyles[devlinkArray[i].type]));
		}

	var devlinklayer = new OpenLayers.Layer.Vector("Device Links");
	devlinklayer.addFeatures(devlinks);
	map.addLayer(devlinklayer);

	var nodes = [];
	if (nodeArray)
		for (i in nodeArray)
		{
			var lonLat = new OpenLayers.LonLat(nodeArray[i].lon, nodeArray[i].lat)
				.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
			area.extend(lonLat);
			nodes.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), nodeArray[i]));
		}

	var nodelayer = new OpenLayers.Layer.Vector("Nodes", {
		styleMap: new OpenLayers.StyleMap(nodestyle)
	});
	nodelayer.addFeatures(nodes);
	map.addLayer(nodelayer);

	var nodelinks = [];
	if (nodelinkArray)
		for (i in nodelinkArray)
		{
			var points = new Array(
				new OpenLayers.Geometry.Point(nodelinkArray[i].nodelon, nodelinkArray[i].nodelat)
					.transform(
						new OpenLayers.Projection("EPSG:4326"),
						map.getProjectionObject()
					),
				new OpenLayers.Geometry.Point(nodelinkArray[i].netdevlon, nodelinkArray[i].netdevlat)
					.transform(
						new OpenLayers.Projection("EPSG:4326"),
						map.getProjectionObject()
					)
				);
			nodelinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				null, linkstyles[nodelinkArray[i].type]));
		}

	var nodelinklayer = new OpenLayers.Layer.Vector("Node Links");
	nodelinklayer.addFeatures(nodelinks);
	map.addLayer(nodelinklayer);

	var highlightlayer = new OpenLayers.Control.SelectFeature([devicelayer, nodelayer], {
		hover: true,
		highlightOnly: true,
		clickout: false,
		toggle: false,
		multiple: false,
		eventListeners: {
			"featurehighlighted": function(e) {
				if (mappopup == null)
				{
					mappopup = new OpenLayers.Popup.Anchored(null,
						new OpenLayers.LonLat(e.feature.data.lon, e.feature.data.lat)
							.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()),
						new OpenLayers.Size(600, 400),
						'<B>' + e.feature.data.name + '</B>' + (e.feature.data.ipaddr.length ? '<BR>' + e.feature.data.ipaddr.replace(/,/g, "<BR>") : ''));
					mappopup.setOpacity(0.8);
					mappopup.closeOnMove = true;
					map.addPopup(mappopup);
					mappopup.updateSize();
				}
				OpenLayers.Event.stop(e);
			},
			"featureunhighlighted": function(e) {
				if (mappopup)
				{
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
				if (mappopup)
				{
					map.removePopup(mappopup);
					mappopup = null;
				}
				selectedFeature = feature;
				featurepopup = new OpenLayers.Popup(null,
					new OpenLayers.LonLat(feature.data.lon, feature.data.lat)
						.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()),
					new OpenLayers.Size(600, 400), 
					null, null, false,
					function(e) {
						selectlayer.unselect(selectedFeature);
					});
				featurepopup.keepInMap = true;
				featurepopup.panMapIfOutOfView = true;
				var content = '<b>' + feature.data.name + '</b><br>';
				if (feature.data.type == 'netdevinfo')
				{
					if (feature.data.ipaddr.length) {
						var ips = feature.data.ipaddr.split(',');
						var nodeids = feature.data.nodeid.split(',');
						for (i in nodeids)
							content += '<a href="javascript:ping_from_map(' + nodeids[i] + ')" nowrap><img src="img/ip.gif" alt="">&nbsp;'
								+ ips[i] + '</a><br>';
					}
				} else
					content += '<a href="javascript:ping_from_map(' + feature.data.id + ')" nowrap><img src="img/ip.gif" alt="">&nbsp;'
						+ feature.data.ipaddr + '</a><br>';
				content += '<a href="?m=' + feature.data.type + '&id=' + feature.data.id + '" nowrap><img src="img/info1.gif" alt="">&nbsp;Info</a>&nbsp;';
				featurepopup.setContentHTML(content);
				featurepopup.setOpacity(0.8);
				map.addPopup(featurepopup);
				featurepopup.updateSize();
				cursize = featurepopup.size;
				featurepopup.setSize(new OpenLayers.Size(cursize.w + 20, cursize + 20));
				feature.popup = featurepopup;
			},
			onUnselect: function(feature) {
				map.removePopup(feature.popup);
				feature.popup = null;
			}
		});
		map.addControl(selectlayer);
		selectlayer.activate();
	}

	var pingbutton = new OpenLayers.Control.Button({
		displayClass: "olPingButton", 
		title: "Ping a host ...",
		command: 'ping'});

//	var ping2button = new OpenLayers.Control.Button({
//		displayClass: "olPing2Button", 
//		title: "Ping2 a host ...",
//		command: "ping2"});

	var panel = new OpenLayers.Control.Panel({
		type: OpenLayers.Control.TYPE_BUTTON,
		title: "Toolbar",
		activateControl: function(control) {
			if (control.command == 'ping') {
				pingpopup = new OpenLayers.Popup(null,
						map.getLonLatFromPixel(new OpenLayers.Pixel(60, 23)).clone(),
						new OpenLayers.Size(600, 400),
						'<form name="ipform" id="ipform" method="GET" action="?m=ping" onsubmit="return ping_from_popup();">'
						+ '<table><tr class="light"><td class="ftopu">Enter IP address:</td><tr class="light"><td class="fbottomu"><input type="text" name="ip"><input type="submit" class="hiddenbtn"></td></tr></table></form>',
						null,
						function() {
							alert('close')
						});
				pingpopup.setOpacity(0.8);
				pingpopup.closeOnMove = true;
				pingpopup.autoSize = true;
				//pingpopup.keepInMap = true;
				//pingpopup.panMapIfOutOfView = true;
				map.addPopup(pingpopup);
				pingpopup.updateSize();
				document.forms['ipform'].ip.focus();
			} else {
			}
		}
	});
//	panel.addControls([pingbutton, ping2button]);
	panel.addControls([pingbutton]);
	map.addControl(panel);

	map.addControl(new OpenLayers.Control.ScaleLine());
	map.addControl(new OpenLayers.Control.LayerSwitcher());
	map.addControl(new OpenLayers.Control.MousePosition({ displayProjection: new OpenLayers.Projection("EPSG:4326") }));

	if (deviceArray || nodeArray)
		map.zoomToExtent(area);
	else
		map.zoomToMaxExtent();

	map.events.register('mousemove', map, function(e) {
		removeInvisiblePopups();
	});

	return map;
}
