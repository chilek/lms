OpenLayers.Renderer.LmsSVG = OpenLayers.Class(OpenLayers.Renderer.SVG, {
	/**
	* Method: drawText
	* This method is only called by the renderer itself.
	*
	* Parameters:
	* featureId - {String}
	* style -
	* location - {<OpenLayers.Geometry.Point>}
	*/
	drawText: function(featureId, style, location) {
		var drawOutline = (!!style.labelOutlineWidth);
		// First draw text in halo color and size and overlay the
		// normal text afterwards
		if (drawOutline) {
			var outlineStyle = OpenLayers.Util.extend({}, style);
			outlineStyle.fontColor = outlineStyle.labelOutlineColor;
			outlineStyle.fontStrokeColor = outlineStyle.labelOutlineColor;
			outlineStyle.fontStrokeWidth = style.labelOutlineWidth;
			if (style.labelOutlineOpacity) {
				outlineStyle.fontOpacity = style.labelOutlineOpacity;
			}
			delete outlineStyle.labelOutlineWidth;
			this.drawText(featureId, outlineStyle, location);
		}

		 var resolution = this.getResolution();

		//add this for rotation----------------------------------------
		var layer = this.map.getLayer(this.container.id);
		var feature = layer.getFeatureById(featureId);
		location = (feature.attributes.centroid ? feature.attributes.centroid : location);
		/////////////////////////--------------------------------------

		var x = ((location.x - this.featureDx) / resolution + this.left);
		var y = (location.y / resolution - this.top);

		var suffix = (drawOutline)?this.LABEL_OUTLINE_SUFFIX:this.LABEL_ID_SUFFIX;
		var label = this.nodeFactory(featureId + suffix, "text");

		label.setAttributeNS(null, "x", x);
		label.setAttributeNS(null, "y", -y);

		//add this for rotation----------------------------------------
		var transform;
		if (style.angle || style.angle == 0) {
			if (style.angle <= 90 || style.angle >= 270) {
				transform = 'rotate(' + style.angle + ',' + x + "," + -y + ') translate(0,-10)';
				label.setAttributeNS(null, "transform", transform);
			} else {
				transform = 'rotate(-' + (540 - style.angle) + ',' + x + "," + -y + ') translate(0,-10)';
				label.setAttributeNS(null, "transform", transform);
			}
		}
		/////////////////////////--------------------------------------

		if (style.fontColor) {
			label.setAttributeNS(null, "fill", style.fontColor);
		}
		if (style.fontStrokeColor) {
			label.setAttributeNS(null, "stroke", style.fontStrokeColor);
		}
		if (style.fontStrokeWidth) {
			label.setAttributeNS(null, "stroke-width", style.fontStrokeWidth);
		}
		if (style.fontOpacity) {
			label.setAttributeNS(null, "opacity", style.fontOpacity);
		}
		if (style.fontFamily) {
			label.setAttributeNS(null, "font-family", style.fontFamily);
		}
		if (style.fontSize) {
			label.setAttributeNS(null, "font-size", style.fontSize);
		}
		if (style.fontWeight) {
			label.setAttributeNS(null, "font-weight", style.fontWeight);
		}
		if (style.fontStyle) {
			label.setAttributeNS(null, "font-style", style.fontStyle);
		}
		if (style.labelSelect === true) {
			label.setAttributeNS(null, "pointer-events", "visible");
			label._featureId = featureId;
		} else {
			label.setAttributeNS(null, "pointer-events", "none");
		}
		var align = style.labelAlign || OpenLayers.Renderer.defaultSymbolizer.labelAlign;
		label.setAttributeNS(null, "text-anchor",
		OpenLayers.Renderer.SVG.LABEL_ALIGN[align[0]] || "middle");

		if (OpenLayers.IS_GECKO === true) {
			label.setAttributeNS(null, "dominant-baseline",
			OpenLayers.Renderer.SVG.LABEL_ALIGN[align[1]] || "central");
		}

		var labelRows = style.label.split('\n');
		var numRows = labelRows.length;
		while (label.childNodes.length > numRows) {
			label.removeChild(label.lastChild);
		}
		for (var i = 0; i < numRows; i++) {
			var tspan = this.nodeFactory(featureId + suffix + "_tspan_" + i, "tspan");
			if (style.labelSelect === true) {
				tspan._featureId = featureId;
				tspan._geometry = location;
				tspan._geometryClass = location.CLASS_NAME;
			}
			if (OpenLayers.IS_GECKO === false) {
				tspan.setAttributeNS(null, "baseline-shift",
				OpenLayers.Renderer.SVG.LABEL_VSHIFT[align[1]] || "-35%");
			}
			tspan.setAttribute("x", x);
			if (i == 0) {
				var vfactor = OpenLayers.Renderer.SVG.LABEL_VFACTOR[align[1]];
				if (vfactor == null) {
					vfactor = -0.5;
				}
				tspan.setAttribute("dy", (vfactor*(numRows-1)) + "em");
			} else {
				tspan.setAttribute("dy", "1em");
			}
			tspan.textContent = (labelRows[i] === '') ? ' ' : labelRows[i];
			if (!tspan.parentNode) {
				label.appendChild(tspan);
			}
		}

		if (!label.parentNode) {
			this.textRoot.appendChild(label);
		}
	},

	CLASS_NAME: "OpenLayers.Renderer.LmsSVG"
});

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
	var i, j, data;

	if (maprequest.status == 200) {
		try {
			data = JSON.parse(maprequest.responseText);
		} catch (e) {
			alert('Network device map refresh error!');
			map.getControlsBy('displayClass', 'lmsRefreshButton')[0].deactivate();
			return 0;
		}

		var newfeature;
		var features;

		//var data = eval('(' + maprequest.responseText + ')');
		var devices = data.devices;
		var nodes = data.nodes;

		var devicelayer = map.getLayersByName(devicesLbl)[0];
		for (i in devices)
		{
			features = devicelayer.getFeaturesByAttribute('id', parseInt(devices[i].id));
			if (features.length && features[0].attributes.state != devices[i].state)
			{
				devices[i].id = parseInt(devices[i].id);
				newfeature = new OpenLayers.Feature.Vector(
					features[0].geometry.clone(),
					devices[i]);
				devicelayer.removeFeatures([features[0]]);
				devicelayer.addFeatures([newfeature]);
			}
		}

		var nodelayer = map.getLayersByName(nodesLbl)[0];
		for (i in nodes)
		{
			features = nodelayer.getFeaturesByAttribute('id', parseInt(nodes[i].id));
			if (features.length && features[0].attributes.state != nodes[i].state)
			{
				nodes[i].id = parseInt(nodes[i].id);
				newfeature = new OpenLayers.Feature.Vector(
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
		setTimeout(function() {
				netdevmap_refresh();
			}, lastonline_limit * 1000);
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

	var type = document.forms[id + '_ipform'].type.value;

	ping_host(id, ip, type);

	return false;
}

function findFeaturesIntersection(selectFeature, feature, featureLonLat)
{
	var featurePixel = map.getPixelFromLonLat(featureLonLat);
	var features = [];
	for (var i in selectFeature.layers) {
		var layer = selectFeature.layers[i];
		for (var j in layer.features) {
			var currentFeature = layer.features[j];
			// position feature is not needed - we detect it by non-null style and point geometry
			if (currentFeature.style != null && currentFeature.geometry.CLASS_NAME == 'OpenLayers.Geometry.Point')
				continue;
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
	// position feature is not needed - we detect it by non-null style and geometry other than line
	if (!features.length && (feature.style == null || feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString'))
		features.push(feature);
	return features;
}

function createMap(deviceArray, devlinkArray, nodeArray, nodelinkArray, selection, startLon, startLat)
{
	var i, j;

	var linkstyles = [];
	linkstyles[0] = { strokeColor: '#00ff00', strokeOpacity: 0.5 }; // wired link type
	linkstyles[1] = { strokeColor: '#0000ff', strokeOpacity: 0.5 }; // wireless link type
	linkstyles[2] = { strokeColor: '#ff0000', strokeOpacity: 0.5 }; // fiber link type
	// wire
	linkstyles[6] = { strokeColor: '#80ff00', strokeOpacity: 0.5 }; // 10 Mb/s Ethernet
	linkstyles[7] = { strokeColor: '#00ff00', strokeOpacity: 0.5 }; // 100 Mb/s Fast Ethernet
	linkstyles[8] = { strokeColor: '#008000', strokeOpacity: 0.5 }; // 1 Gigabit Ethernet
	// wireless
	linkstyles[100] = { strokeColor: '#0000ff', strokeOpacity: 0.5 }; // WiFi - 2,4 GHz
	linkstyles[101] = { strokeColor: '#0080ff', strokeOpacity: 0.5 }; // WiFi - 5 GHz
	// fiber
	linkstyles[204] = { strokeColor: '#ff0000', strokeOpacity: 0.5 }; // 100 Mb/s Fast Ethernet
	linkstyles[205] = { strokeColor: '#ff8000', strokeOpacity: 0.5 }; // 1 Gigabit Ethernet

	var linkweights = [];
	linkweights[10000] = 1;
	linkweights[25000] = 1;
	linkweights[54000] = 2;
	linkweights[100000] = 2;
	linkweights[200000] = 3;
	linkweights[300000] = 3;
	linkweights[1000000] = 4;
	linkweights[10000000] = 6;

	var rsareastyle2 = {
		fillOpacity: 0.2,
		graphicOpacity: 1,
		fillColor: '#0000aa',
		strokeColor: '#0000bb'
	}

	var rsareastyle5 = {
		fillOpacity: 0.2,
		graphicOpacity: 1,
		fillColor: '#0080aa',
		strokeColor: '#0080bb'
	}

	var rsdirectionstyle2 = new OpenLayers.Style(
		{
			graphicOpacity: 1,
			strokeColor: '#0000bb',
			strokeDashstyle: "dashdot",
			fontFamily: 'arial, monospace',
			//fontWeight: 'bold',
			fontColor: '#0000bb',
			labelAlign: 'middle',
			angle: "${angle}",
			label: "${label}"
		}, {
			context: {
				angle: function(feature) {
					var attr = feature.attributes;
					if (attr === undefined || feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString' ||
						(map.getZoom() / map.getNumZoomLevels()) < 0.70)
						return '';
					else
						return attr.azimuth - 90;
				},
				label: function(feature) {
					var attr = feature.attributes;
					if (attr === undefined || feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString' ||
						(map.getZoom() / map.getNumZoomLevels()) < 0.70)
						return '';
					else
						return attr.name + (attr.frequency != '' ?
							' / ' + attr.frequency + (attr.frequency2 != '' ? ' / ' + attr.frequency2 : '') +
								(attr.bandwidth != '' ?
									' (' + attr.bandwidth + ')' : '')
							: '');
				}
			}
		}
	);

	var rsdirectionstyle5 = new OpenLayers.Style(
		{
			graphicOpacity: 1,
			strokeColor: '#0080bb',
			strokeDashstyle: "dashdot",
			fontFamily: 'arial, monospace',
			//fontWeight: 'bold',
			fontColor: '#0080bb',
			labelAlign: 'middle',
			angle: "${angle}",
			label: "${label}"
		}, {
			context: {
				angle: function(feature) {
					var attr = feature.attributes;
					if (attr === undefined || feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString' ||
						(map.getZoom() / map.getNumZoomLevels()) < 0.70)
						return '';
					else
						return attr.azimuth + attr.width / 2 - 90;
				},
				label: function(feature) {
					var attr = feature.attributes;
					if (attr === undefined || feature.geometry.CLASS_NAME == 'OpenLayers.Geometry.LineString' ||
						(map.getZoom() / map.getNumZoomLevels()) < 0.70)
						return '';
					else
						return attr.name + (attr.frequency != '' ?
							' / ' + attr.frequency + (attr.frequency2 != '' ? ' / ' + attr.frequency2 : '') +
								(attr.bandwidth != '' ?
									' (' + attr.bandwidth + ')' : '')
							: '');
				}
			}
		}
	);

	var rsarealayer2 = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate("Radio sectors 2.4GHz - areas"), {
		style: rsareastyle2,
	});

	var rsarealayer5 = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate("Radio sectors 5GHz - areas"), {
		style: rsareastyle5,
	});

	var rsdirectionlayer2 = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate("Radio sectors 2.4GHz - directions"), {
		styleMap: new OpenLayers.StyleMap(rsdirectionstyle2),
		renderers: ['LmsSVG', 'VML', 'Canvas']
	});

	var rsdirectionlayer5 = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate("Radio sectors 5GHz - directions"), {
		styleMap: new OpenLayers.StyleMap(rsdirectionstyle5),
		renderers: ['LmsSVG', 'VML', 'Canvas']
	});

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

	var lonLat;

	var area = new OpenLayers.Bounds();
	var devices = [];
	var areas2 = [];
	var areas5 = [];
	var directions2 = [];
	var directions5 = [];
	if (deviceArray)
		for (i in deviceArray)
		{
			var normalLonLat = new OpenLayers.LonLat(deviceArray[i].lon, deviceArray[i].lat);
			lonLat = normalLonLat.clone().transform(lmsProjection, map.getProjectionObject());
			area.extend(lonLat);
			devices.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), deviceArray[i]));
			if (!deviceArray[i].radiosectors.length)
				continue;
			for (j in deviceArray[i].radiosectors) {
				var radiosector = deviceArray[i].radiosectors[j];
				var rsPointList = [];
				var rsPoint, rsLonLat;
				//if (radiosector.width < 360) {
					rsPoint = new OpenLayers.Geometry.Point(lonLat.lon, lonLat.lat);
					rsPointList.push(rsPoint);
				//}

				var steps = radiosector.width / 10;
				var step = radiosector.width / steps;
				for (var k = 0, width = - radiosector.width / 2; k <= steps; k++, width += step) {
					rsLonLat = OpenLayers.Util.destinationVincenty(normalLonLat, radiosector.azimuth + width, radiosector.rsrange)
						.transform(lmsProjection, map.getProjectionObject());
					rsPoint = new OpenLayers.Geometry.Point(rsLonLat.lon, rsLonLat.lat);
					rsPointList.push(rsPoint);
				}

				rsPointList.push(rsPointList[0]);
				var rsRing = new OpenLayers.Geometry.LinearRing(rsPointList);
				var rsFeature = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Polygon([rsRing]));
				if (radiosector.technology == 100) {
					areas2.push(rsFeature);
				} else {
					areas5.push(rsFeature);
				}

				rsPointList = [];
				rsPoint = new OpenLayers.Geometry.Point(lonLat.lon, lonLat.lat);
				rsPointList.push(rsPoint);
				rsLonLat = OpenLayers.Util.destinationVincenty(normalLonLat, radiosector.azimuth, radiosector.rsrange)
						.transform(lmsProjection, map.getProjectionObject());
				rsPoint = new OpenLayers.Geometry.Point(rsLonLat.lon, rsLonLat.lat);
				rsPointList.push(rsPoint);
				var rsCurve = new OpenLayers.Geometry.LineString(rsPointList);
				rsFeature = new OpenLayers.Feature.Vector(rsCurve, radiosector);
				if (radiosector.technology == 100) {
					directions2.push(rsFeature);
				} else {
					directions5.push(rsFeature);
				}

				rsFeature = new OpenLayers.Feature.Vector(
					rsCurve.getCentroid(true), radiosector);
				if (radiosector.technology == 100) {
					directions2.push(rsFeature);
					directions2.push(rsFeature);
				} else {
					directions5.push(rsFeature);
					directions5.push(rsFeature);
				}
			}
		}
	rsarealayer2.addFeatures(areas2);
	rsarealayer5.addFeatures(areas5);
	rsdirectionlayer2.addFeatures(directions2);
	rsdirectionlayer5.addFeatures(directions5);

	devicesLbl = OpenLayers.Lang.translate("Devices");
	var devicelayer = new OpenLayers.Layer.Vector(devicesLbl, {
		styleMap: new OpenLayers.StyleMap(devicestyle)
	});
	devicelayer.addFeatures(devices);

	var points;

	var devlinks = [];
	if (devlinkArray)
		for (i in devlinkArray)
		{
			points = new Array(
				new OpenLayers.Geometry.Point(devlinkArray[i].srclon, devlinkArray[i].srclat)
					.transform(lmsProjection, map.getProjectionObject()),
				new OpenLayers.Geometry.Point(devlinkArray[i].dstlon, devlinkArray[i].dstlat)
					.transform(lmsProjection, map.getProjectionObject())
			);
			if (devlinkArray[i].technology in linkstyles)
				linkstyle = linkstyles[devlinkArray[i].technology];
			else
				linkstyle = linkstyles[devlinkArray[i].type];
			linkstyle.strokeWidth = linkweights[devlinkArray[i].speed];
			devlinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				devlinkArray[i], linkstyle));
		}

	var devlinkLbl = OpenLayers.Lang.translate("Device Links");
	var devlinklayer = new OpenLayers.Layer.Vector(devlinkLbl);
	devlinklayer.addFeatures(devlinks);

	var nodes = [];
	if (nodeArray)
		for (i in nodeArray)
		{
			lonLat = new OpenLayers.LonLat(nodeArray[i].lon, nodeArray[i].lat)
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
			points = new Array(
				new OpenLayers.Geometry.Point(nodelinkArray[i].nodelon, nodelinkArray[i].nodelat)
					.transform(lmsProjection, map.getProjectionObject()),
				new OpenLayers.Geometry.Point(nodelinkArray[i].netdevlon, nodelinkArray[i].netdevlat)
					.transform(lmsProjection, map.getProjectionObject())
				);
			if (nodelinkArray[i].technology in linkstyles)
				linkstyle = linkstyles[nodelinkArray[i].technology];
			else
				linkstyle = linkstyles[nodelinkArray[i].type];
			linkstyle.strokeWidth = linkweights[nodelinkArray[i].speed];
			nodelinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				nodelinkArray[i], linkstyle));
		}

	var nodelinkLbl = OpenLayers.Lang.translate("Node Links");
	var nodelinklayer = new OpenLayers.Layer.Vector(nodelinkLbl);
	nodelinklayer.addFeatures(nodelinks);

	map.addLayer(devicelayer);
	map.addLayer(devlinklayer);
	map.addLayer(nodelayer);
	map.addLayer(nodelinklayer);
	// add layer if exist any 2.4GHz radiosector
	if (areas2.length) {
		map.addLayer(rsarealayer2);
		map.addLayer(rsdirectionlayer2);
	}
	// add layer if exist any 5GHz radiosector
	if (areas5.length) {
		map.addLayer(rsarealayer5);
		map.addLayer(rsdirectionlayer5);
	}

	var highlightlayers = [ devicelayer, devlinklayer, nodelayer, nodelinklayer ];

	if (startLon != null && startLat != null) {
		var positionLayer = new OpenLayers.Layer.Vector(OpenLayers.Lang.translate("Position"));

		var startLonLat = new OpenLayers.LonLat(startLon, startLat).transform(lmsProjection, map.getProjectionObject());
		positionLayer.addFeatures([
			new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(startLonLat.lon, startLonLat.lat),
				null, {
					graphicWidth: 32,
					graphicHeight: 32,
					graphicXOffset: -4,
					graphicYOffset: -28,
					externalGraphic: "img/location.png"
				}
			)
		]);

		map.addLayer(positionLayer);
		highlightlayers.push(positionLayer);
	}

	var highlightlayer = new OpenLayers.Control.SelectFeature(highlightlayers, {
		hover: true,
		highlightOnly: true,
		clickout: false,
		toggle: false,
		multiple: false,
		eventListeners: {
			"featurehighlighted": function(e) {
				if (mappopup == null)
				{
					var map = this.map;
					var feature = e.feature;
					var featureLonLat, mapLonLat;
					if (feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point" && feature.style == null) {
						featureLonLat = new OpenLayers.LonLat(feature.data.lon, feature.data.lat);
						featureLonLat.transform(lmsProjection, map.getProjectionObject());
						mapLonLat = featureLonLat.clone();
					}
					else {
						featureLonLat = map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
						mapLonLat = featureLonLat.clone();
						mapLonLat.transform(map.getProjectionObject(), lmsProjection).transform(lmsProjection, map.getProjectionObject());
					}
					var features = findFeaturesIntersection(this, feature, featureLonLat);
					if (features.length) {
						var content = '<div class="lmsMapPopupContents">';
						for (i in features) {
							if (features[i].geometry.CLASS_NAME == "OpenLayers.Geometry.Point")
								content += '<div class="lmsMapPopupName">' + features[i].data.name + '</div>' +
									(features[i].data.ipaddr.length ? 
										'<div class="lmsMapPopupAddress">' + features[i].data.ipaddr.replace(/,/g,
											'</div><div class="lmsMapPopupAddress">') + '</div>'
										: '');
							else
								content += '<span class="bold">' + features[i].data.typename + '<br>' +
									(features[i].data.technologyname.length ? '<span class="bold">' + features[i].data.technologyname + '<br>' : '') +
									features[i].data.speedname + '</span>';
						}
						content += '</div>';
						mappopup = new OpenLayers.Popup.Anchored(null, mapLonLat, new OpenLayers.Size(10, 10), content);
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

	var selectlayer;

	if (selection)
	{
		selectlayer = new OpenLayers.Control.SelectFeature(highlightlayers, {
			clickout: true, toggle: false,
			multiple: true, hover: false,
			toggleKey: "ctrlKey", // ctrl key removes from selection
			multipleKey: "shiftKey", // shift key adds to selection
			onSelect: function(feature) {
				var i, j;
				var map = feature.layer.map;
				if (mappopup)
				{
					map.removePopup(mappopup);
					mappopup = null;
				}
				selectedFeature = feature;
				var featureLonLat;
				// position feature is not needed - we detect it by nullified style
				if (feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point" && feature.style == null) {
					featureLonLat = new OpenLayers.LonLat(feature.data.lon, feature.data.lat);
					featureLonLat.transform(lmsProjection, map.getProjectionObject());
				}
				else 
					featureLonLat = map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
				var features = findFeaturesIntersection(this, feature, featureLonLat);
				if (features.length > 1 || (features.length == 1 && features[0].geometry.CLASS_NAME == "OpenLayers.Geometry.Point")) {
					var featurepopup = new OpenLayers.Popup(null, featureLonLat, new OpenLayers.Size(10, 10));
					featurepopup.setOpacity(0.9);
					//featurepopup.closeOnMove = true;
					//featurepopup.keepInMap = true;
					//featurepopup.panMapIfOutOfView = true;
					var content = '<div class="lmsPopupTitleBar"><div class="lmsPopupTitle">Info</div>' +
						'<div id="' + featurepopup.id + '_popupCloseBox" class="olPopupCloseBox lmsPopupCloseBox">&nbsp;</div></div>' +
						'<div class="lmsInfoPopupContents">';
					for (i in features) {
						content += '<div class="lmsInfoPopupName">' + features[i].data.name + '</div>';
						if (features[i].data.type == 'netdevinfo') {
							if (features[i].data.ipaddr.length) {
								var ips = features[i].data.ipaddr.split(',');
								var nodeids = features[i].data.nodeid.split(',');
								for (j in nodeids)
									content += '<div class="lmsInfoPopupAddress"><a href="#" onclick="ping_host(\'' +
										featurepopup.id + '\', \'' + ips[j] + '\')"><img src="img/ip.gif" alt="">&nbsp;' +
										ips[j] + '</a></div>';
							}
						} else
							content += '<div class="lmsInfoPopupAddress"><a href="#" onclick="ping_host(\'' +
								featurepopup.id + '\', \'' + features[i].data.ipaddr + '\')"><img src="img/ip.gif" alt="">&nbsp;' +
								features[i].data.ipaddr + '</a></div>';
						content += '<div class="lmsInfoPopupDetails"><a href="?m=' + features[i].data.type + '&id=' + features[i].data.id + '">' +
							'<img src="img/info1.gif" alt="">&nbsp;Info</a></div>';
						if (features[i].data.url) {
							var urls = features[i].data.url.split(',');
							var comments = features[i].data.comment.split(',');
							for (j in urls) {
								content += '<div class="lmsInfoPopupDetails"><a href="' + urls[j] + '"' +
									(urls[j].match(/^(https?|ftp):/) ? ' target="_blank"' : '') + '>' +
									'<img src="img/network.gif" alt=""> ' +
									(comments[j].length ? comments[j] : urls[j]) + '</a></div>';
							}
						}
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
				}
			},
			onUnselect: function(feature) {
				//map.removePopup(feature.popup);
				//feature.popup = null;
			}
		});
		map.addControl(selectlayer);
		selectlayer.activate();

		var checkbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCheckButton", 
			title: checkhostcaption,
			command: 'check'});

		var centerbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCenterButton", 
			title: centermapcaption,
			command: 'center'});

		var refreshbutton = new OpenLayers.Control.Button({
			displayClass: "lmsRefreshButton", 
			title: refreshmapcaption,
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
						for (i = 0; i < map.layers.length; i++) {
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
	} else {
		selectlayer = new OpenLayers.Control.SelectFeature(highlightlayers, {
			clickout: true, toggle: false,
			multiple: true, hover: false,
			toggleKey: "ctrlKey", // ctrl key removes from selection
			multipleKey: "shiftKey", // shift key adds to selection
			onSelect: function(feature) {
				if (feature.data.hasOwnProperty('lon')) {
					map.events.triggerEvent('feature_click', {
						xy: new OpenLayers.LonLat(feature.data.lon, feature.data.lat),
						feature: true
					});
				} else {
					map.events.triggerEvent('feature_click', {
						xy: map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy)
							.transform(map.getProjectionObject(), lmsProjection),
						feature: true
					});
				}
			}
		});
		map.addControl(selectlayer);
		selectlayer.activate();
	}

	map.addControl(new OpenLayers.Control.ScaleLine());
	//map.addControl(new OpenLayers.Control.NavToolbar());
	/* in MSIE LayerSwitcher display rounded corners is broken */
	var layerSwitcher;
	if (navigator.appName == "Microsoft Internet Explorer") {
		layerSwitcher = new OpenLayers.Control.LayerSwitcher({ roundedCorner: false });
	} else {
		layerSwitcher = new OpenLayers.Control.LayerSwitcher({ roundedCornerColor: '#CEBD9B' });
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
		for (i = 0; i < visibleLayers.length, i < layerSwitcher.dataLayers.length; i++) {
			for (j = 0; j < layerSwitcher.dataLayers.length, layerSwitcher.layerStates[j].id != layerSwitcher.dataLayers[i].layer.id; j++);
			layerSwitcher.dataLayers[i].layer.setVisibility((visibleLayers[i] == '1' ? true : false));
		}
	}

	var loadedSettings = false;
	var mapSettings = getCookie('mapSettings');
	var lon = null, lat = null, zoom = null;
	if (mapSettings != null) {
		var mapData = mapSettings.split(';');
		if (mapData.length == 3) {
			lon = parseFloat(mapData[0]);
			lat = parseFloat(mapData[1]);
			zoom = parseInt(mapData[2]);
			loadedSettings = true;
		}
	}

	if (startLon != null && startLat != null)
		if (zoom)
			map.setCenter(new OpenLayers.LonLat(startLon, startLat)
					.transform(lmsProjection, map.getProjectionObject()), zoom);
		else {
			map.setCenter(new OpenLayers.LonLat(startLon, startLat)
					.transform(lmsProjection, map.getProjectionObject()));
			if (deviceArray || nodeArray)
				map.zoomToExtent(area);
			else
				map.zoomToMaxExtent();
		}
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
