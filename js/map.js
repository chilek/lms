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

OpenLayers.Control.LmsModifyFeature = OpenLayers.Class(OpenLayers.Control.ModifyFeature, {
	dragStart: function(feature) {
		var isPoint = feature.geometry.CLASS_NAME === 'OpenLayers.Geometry.Point';
		if (!this.standalone &&
			((!feature._sketch && isPoint) || !feature._sketch)) {
			if (this.toggle && this.feature === feature) {
				// mark feature for unselection
				this._unselect = feature;
			}
			this.selectFeature(feature);
		}
		if (this.feature &&
			(feature._sketch || isPoint && feature === this.feature)) {
			var map = feature.layer.map;
			// feature is a drag or virtual handle or point
			var points = feature.geometry.parent.components;
			var selectedPointIndex = -1;
			points.forEach(function(value, index) {
				if (value.x === feature.geometry.x && value.y === feature.geometry.y) {
					selectedPointIndex = index;
				}
				//var pointLonLat = new OpenLayers.LonLat(value.x, value.y);
				//console.log(pointLonLat.transform(map.getProjectionObject(), lmsProjection), value);
			});
			if (feature.style == null && (!selectedPointIndex || selectedPointIndex === points.length -1)) {
				return;
			}
			this.vertex = feature;
			this.handlers.drag.stopDown = true;
		}
	}
});

var map = null;
//var layerSwitcher = null;
var maprequest = null;
var mappopup = null;
var lastonline_limit;
var lmsProjection = new OpenLayers.Projection("EPSG:4326");
var devicesLbl;
var nodesLbl;

function removeInvisiblePopups() {
	// OpenLayers doesn't destroy closed popups, so
	// we search for them here and destroy if there are ...
	for (var i in map.popups)
		if (!map.popups[i].visible()) {
			alert('Invisible popup ' + map.popups[i].id);
			map.removePopup(map.popups[i]);
		}
}

function set_lastonline_limit(sec) {
	lastonline_limit = sec;
}

function netdevmap_updater() {
	var i, j, data;

	if (maprequest.status == 200) {
		try {
			data = JSON.parse(maprequest.responseText);
		} catch (e) {
			alertDialog($t('Network device map refresh error!'));
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

function netdevmap_refresh(live) {
	maprequest = OpenLayers.Request.issue({
		url: '?m=netdevmaprefresh' + (live ? '&live=1' : ''),
		callback: netdevmap_updater
	});
	if (!live)
		setTimeout(function() {
				netdevmap_refresh();
			}, lastonline_limit * 1000);
}

function close_popup(id) {
	map.removePopup(id)
}

function ping_host(id, ip, type) {
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

function ping_any_host(id) {
	var ip = document.forms[id + '_ipform'].ip.value;
	if (!ip.match(/^([0-9]{1,3}\.){3}[0-9]{1,3}$/))
		return false;

	var type = document.forms[id + '_ipform'].type.value;

	ping_host(id, ip, type);

	return false;
}

function findFeaturesIntersection(selectFeature, feature, featureLonLat) {
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

function toleranceMapUnitsByScale(map, basePx, scaleRef, exponent) {
	var scale = map.getScale();       // np. 1:5000 → 5000
	var k = Math.pow(scale / scaleRef, exponent || 0.5); // 0.5 = √, łagodna zmiana
	var px = basePx * k;
	return px * map.getResolution();
}

function projectPointOnSegment(P, A, B) {
	var vx = B.x - A.x, vy = B.y - A.y;
	var wx = P.x - A.x, wy = P.y - A.y;
	var segLen2 = vx*vx + vy*vy;

	if (segLen2 === 0) {
		// Zdegenerowany odcinek: A==B. Projekcja to A.
		var projA = new OpenLayers.Geometry.Point(A.x, A.y);
		return {
			tRaw: 0,
			t: 0,
			point: projA,                            // nowy obiekt, nie referencja!
			distance: projA.distanceTo(P)
		};
	}

	// Surowe t na prostej AB
	var tRaw = (wx*vx + wy*vy) / segLen2;

	// t na odcinku (klamracja)
	var t = (tRaw < 0) ? 0 : (tRaw > 1 ? 1 : tRaw);

	// Punkt projekcji (dokładnie na odcinku AB)
	var proj = new OpenLayers.Geometry.Point(
		A.x + t * vx,
		A.y + t * vy
	);

	return {
		tRaw: tRaw,
		t: t,
		point: proj,
		distance: proj.distanceTo(P)
	};
}

/**
 * Znajdź segment w geometrii, który "zawiera" kliknięty punkt w zadanej tolerancji.
 *
 * @param {OpenLayers.Geometry.LineString|OpenLayers.Geometry.MultiLineString} geometry
 * @param {OpenLayers.Geometry.Point|{x:number,y:number}|OpenLayers.LonLat} clickCoord  // w tym samym układzie co geometry
 * @param {Object} opts
 *   - tolerancePx {number}        // opcjonalnie: tolerancja w pikselach (wymaga map)
 *   - toleranceMapUnits {number}  // opcjonalnie: tolerancja w jednostkach mapy/geometrii
 *   - map {OpenLayers.Map}        // opcjonalnie: potrzebna gdy używasz tolerancePx
 *
 * @returns {null|{lineIndex:number, segIndex:number, projection:OpenLayers.Geometry.Point, distance:number}}
 */
function findSegmentContainingPoint(map, geometry, clickPoint) {
	// Rozbij MultiLineString na tablicę LineStringów
	var lines = (geometry.CLASS_NAME === "OpenLayers.Geometry.MultiLineString") ?
		geometry.components :
		(geometry.CLASS_NAME === "OpenLayers.Geometry.LineString") ?
			[geometry] :
			[];

	var best = null;

	var tolerancePx = toleranceMapUnitsByScale(map, /*basePx*/ 50, /*scaleRef*/ 1692, /*exp*/ 0.5);

	for (var li = 0; li < lines.length; li++) {
		var line = lines[li];
		var pts = line.components;
		for (var si = 0; si < pts.length - 1; si++) {
			var A = pts[si], B = pts[si + 1];
			var pr = projectPointOnSegment(clickPoint, A, B);

			// Warunek "zawiera": rzut NA ODCINKU (t w [0,1]) i odległość ≤ tolerancja.
			if (pr.t >= 0 && pr.t <= 1 && pr.distance <= tolerancePx) {
				if (!best || pr.distance < best.distance) {
					best = {
						lineIndex: li,
						segIndex: si,
						insertIndex: si + 1,          // wstaw zaraz po A (między A a B)
						t: pr.t,
						projection: pr.point,         // <<— poprawne współrzędne nowego punktu
						distance: pr.distance
					};
				}
			}
		}
	}

	return best; // null, gdy brak trafienia
}

function createMap(deviceArray, devlinkArray, nodeArray, nodelinkArray, rangeArray, selection, startLon, startLat) {
	var i, j;

	var devLinkForeignEntityToggleButton = null;
	var customerOwnedToggleButton = null;
	var mapFilters = {
		devLinkForeignEntity: false,
		customerOwned: false
	};

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

	var styleContext = 	{
		context: {
			strokeColor: function(feature) {
				var data = feature.data;
				if (data.hasOwnProperty('technology')) {
					if (data.technology in linkstyles) {
						return linkstyles[data.technology].strokeColor;
					} else {
						return linkstyles[data.type.length ? data.type : 0].strokeColor;
					}
				}
			},
			strokeWidth: function(feature) {
				var data = feature.data;
				if (data.hasOwnProperty('speed')) {
					return data.speed.length ? linkweights[data.speed] : 1;
				}
			},
			display: function(feature) {
				if (feature.renderIntent === 'vertex' || !mapFilters.devLinkForeignEntity && !mapFilters.customerOwned) {
					return '';
				}

				var data = feature.data;
				return mapFilters.devLinkForeignEntity && data.foreignentity.length || mapFilters.customerOwned && data.customers.length ? 'none' : '';
			}
		}
	}

	var linkStyleDefault = new OpenLayers.Style(
		OpenLayers.Util.applyDefaults(
			{
				strokeColor: "${strokeColor}",
				strokeWidth: "${strokeWidth}",
				pointRadius: 9,
				fillColor: "#0000FF",
				fillOpacity: 0.9,
				display: "${display}"
			},
			OpenLayers.Feature.Vector.style.default
		),
		styleContext
	);

	var linkStyleSelect = new OpenLayers.Style(
		OpenLayers.Util.applyDefaults(
			{
				strokeColor: "${strokeColor}",
				strokeWidth: "${strokeWidth}",
				pointRadius: 9,
				fillColor: "#000080",
				fillOpacity: 0.9,
				display: "${display}"
			},
			OpenLayers.Feature.Vector.style.select
		),
		styleContext
	);

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
			fill: false,
			label: "${label}",
			labelAlign: "cc",
			labelXOffset: 0,
			labelYOffset: 0,
			labelSelect: true,
			labelOutlineWidth: 0,
			fontSize: "1.3em",
			fontOpacity: 1,
			fontFamily: '"' + lmsSettings.fontAwesomeName + '"',
			fontWeight: 900,
			fontStrokeColor: "${fontStrokeColor}",
			fontStrokeWidth: "${fontStrokeWidth}",
			display: "${display}"
		}, {
			context: {
				label: function(feature) {
					switch (feature.attributes.state) {
						case 0: return "\uf2db";
						case 1: return "\uf2db";
						case 2: return "\uf2db";
						default: return "\uf2db";
					}
				},
				fontStrokeColor: function(feature) {
					switch (feature.attributes.state) {
						case 0:
							return "red";
						case 1:
							return "limegreen";
						default:
							return false;
					}
				},
				fontStrokeWidth: function(feature) {
					switch (feature.attributes.rangetype) {
						case 0:
							return 1;
						case 1:
							return 1;
						default:
							return 0;
					}
				},
				display: function(feature) {
					if (!mapFilters.customerOwned) {
						return '';
					}

					var data = feature.data;
					return mapFilters.customerOwned && data.ownerid.length ? 'none' : '';
				}
			}
		});

	var nodestyle = new OpenLayers.Style(
		{
			fill: false,
			label: "${label}",
			labelAlign: "cc",
			labelXOffset: 0,
			labelYOffset: 0,
			labelSelect: true,
			labelOutlineWidth: 0,
			fontSize: "1.2em",
			fontOpacity: 1,
			fontFamily: '"' + lmsSettings.fontAwesomeName + '"',
			fontWeight: 900,
			fontStrokeColor: "${fontStrokeColor}",
			fontStrokeWidth: "${fontStrokeWidth}"
		}, {
			context: {
				label: function(feature) {
					switch (feature.attributes.state) {
						case 0: return "\uf108";
						case 1: return "\uf108";
						case 2: return "\uf108";
						default: return "\uf108";
					}
				},
				fontStrokeColor: function(feature) {
					switch (feature.attributes.state) {
						case 0:
							return "red";
						case 1:
							return "limegreen";
						default:
							return false;
					}
				},
				fontStrokeWidth: function(feature) {
					switch (feature.attributes.rangetype) {
						case 0:
							return 1;
						case 1:
							return 1;
						default:
							return 0;
					}
				}
			}
		});

	var rangeStyle = new OpenLayers.Style(
		{
			fill: false,
			label: "${label}",
			labelAlign: "cc",
			labelXOffset: 0,
			labelYOffset: 0,
			labelSelect: true,
			labelOutlineWidth: 0,
			fontSize: "1.2em",
			fontOpacity: 1,
			fontFamily: '"' + lmsSettings.fontAwesomeName + '"',
			fontWeight: 900,
		}, {
			context: {
				label: function(feature) {
					switch (feature.attributes.rangetype) {
						case "1":
							return "\uf058";
						case "2":
							return "\uf059";
					}
				}
			}
		}
	);

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

	function addPoint(index, point) {
		points.push(
			new OpenLayers.Geometry.Point(point.lon, point.lat)
				.transform(lmsProjection, map.getProjectionObject())
		);
	}

	var devlinks = [];
	if (devlinkArray) {
		for (i in devlinkArray) {
			points = [];

			for (var pointIndex in devlinkArray[i].points) {
				addPoint(pointIndex, devlinkArray[i].points[pointIndex])
			}

			devlinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				devlinkArray[i]
			));
		}
	}

	var devlinkLbl = OpenLayers.Lang.translate("Device Links");
	var devlinklayer = new OpenLayers.Layer.Vector(devlinkLbl, {
		styleMap: new OpenLayers.StyleMap({
			"default": linkStyleDefault,
			"select": linkStyleSelect,
			"vertex": new OpenLayers.Style({
				pointRadius: 9,
				fillColor: "#0000FF",
				fillOpacity: 0.9
			})
		})
	});
	devlinklayer.addFeatures(devlinks);

	var nodes = [];
	if (nodeArray) {
		for (i in nodeArray) {
			lonLat = new OpenLayers.LonLat(nodeArray[i].lon, nodeArray[i].lat)
				.transform(lmsProjection, map.getProjectionObject());
			area.extend(lonLat);
			nodes.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), nodeArray[i]));
		}
	}

	nodesLbl = OpenLayers.Lang.translate("Nodes");
	var nodelayer = new OpenLayers.Layer.Vector(nodesLbl, {
		styleMap: new OpenLayers.StyleMap(nodestyle)
	});
	nodelayer.addFeatures(nodes);

	var nodelinks = [];
	if (nodelinkArray) {
		for (i in nodelinkArray) {
			points = new Array(
				new OpenLayers.Geometry.Point(nodelinkArray[i].nodelon, nodelinkArray[i].nodelat)
					.transform(lmsProjection, map.getProjectionObject()),
				new OpenLayers.Geometry.Point(nodelinkArray[i].netdevlon, nodelinkArray[i].netdevlat)
					.transform(lmsProjection, map.getProjectionObject())
			);
			if (nodelinkArray[i].technology in linkstyles) {
				linkstyle = linkstyles[nodelinkArray[i].technology];
			} else {
				linkstyle = linkstyles[nodelinkArray[i].type.length ? nodelinkArray[i].type : 0];
			}
			linkstyle.strokeWidth = nodelinkArray[i].speed.length ? linkweights[nodelinkArray[i].speed] : 1;
			nodelinks.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.LineString(points),
				nodelinkArray[i], linkstyle));
		}
	}

	var nodelinkLbl = OpenLayers.Lang.translate("Node Links");
	var nodelinklayer = new OpenLayers.Layer.Vector(nodelinkLbl);
	nodelinklayer.addFeatures(nodelinks);

	var ranges = [];
	if (rangeArray) {
		for (i in rangeArray) {
			lonLat = new OpenLayers.LonLat(rangeArray[i].lon, rangeArray[i].lat)
				.transform(lmsProjection, map.getProjectionObject());
			area.extend(lonLat);
			ranges.push(new OpenLayers.Feature.Vector(
				new OpenLayers.Geometry.Point(
					lonLat.lon,
					lonLat.lat
				), rangeArray[i]));
		}
	}

	var rangesLbl = OpenLayers.Lang.translate("Ranges");
	var rangeLayer = new OpenLayers.Layer.Vector(rangesLbl, {
		styleMap: new OpenLayers.StyleMap(rangeStyle)
	});
	rangeLayer.addFeatures(ranges);

	map.addLayer(devicelayer);
	map.addLayer(devlinklayer);
	map.addLayer(nodelayer);
	map.addLayer(nodelinklayer);
	map.addLayer(rangeLayer);
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

	if (lms.permissions.fullAccess || lms.permissions.networkMapEdit) {
		devlinklayer.events.on({
			"featuremodified": function (ev) {
				//		"afterfeaturemodified": function(ev) {
				var feature = ev.feature;
				var netlink = feature.data;
				if (!netlink.hasOwnProperty('netlinkid')) {
					return;
				}
				var map = this.map;
				var lonLats = [];
				var points = feature.geometry.components;
				points.forEach(function (point, index) {
					var lonLat = new OpenLayers.LonLat(point.x, point.y);
					lonLat.transform(map.getProjectionObject(), lmsProjection);
					lonLats.push(lonLat);
				})

				OpenLayers.Request.issue({
					url: "?m=netlinkpoints&api=1",
					params: {
						netlinkid: netlink.netlinkid,
						srcdevid: netlink.src,
						dstdevid: netlink.dst,
						points: JSON.stringify(lonLats)
					},
					callback: function (response) {
						if (response.status == 200) {
							try {
								data = JSON.parse(response.responseText);
							} catch (e) {
								alertDialog($t('Network link update failed!'));
							}
						} else {
							alertDialog($t('Network link update failed!'));
						}
					}
				});
			}
		});
	}

	var highlightlayers = [ devicelayer, devlinklayer, nodelayer, nodelinklayer, rangeLayer ];

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
				if (mappopup == null) {
					var map = this.map;
					var feature = e.feature;
					var featureLonLat, mapLonLat;
					if (feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point" && feature.style == null) {
						featureLonLat = new OpenLayers.LonLat(feature.data.lon, feature.data.lat);
						mapLonLat = featureLonLat.clone();
						featureLonLat.transform(lmsProjection, map.getProjectionObject());
					} else {
						featureLonLat = map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
						mapLonLat = featureLonLat.clone();
						mapLonLat.transform(map.getProjectionObject(), lmsProjection).transform(lmsProjection, map.getProjectionObject());
					}
					var features = findFeaturesIntersection(this, feature, featureLonLat);
					if (features.length) {
						var content = '<div class="lms-ui-map-popup-contents">';
						var first = true;
						var popupRequired = false;
						for (i in features) {
							if (features[i].geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
								if (features[i].data.hasOwnProperty('existing')) {
									if (!first) {
										content += '<br>';
									} else {
										first = false;
									}
									content += '<strong><span class="netrange-location">' + features[i].data.location + '</span><br>' +
										features[i].data.typename + '<br>' +
										features[i].data.technologyname + '<br>' +
										features[i].data.speedname + '<br>' +
										features[i].data.rangetypename + '<br>' +
										(features[i].data.existingname.length ? features[i].data.existingname  + '<br>' : '') +
										features[i].data.servicesname +
										'</strong>';
									popupRequired = true;
								} else if (features[i].data.hasOwnProperty('ipaddr')) {
									content += '<div class="lms-ui-map-popup-name">' + features[i].data.name + '</div>' +
										(features[i].data.ipaddr.length ?
											'<div class="lms-ui-map-popup-address">' + features[i].data.ipaddr.replace(/,/g,
												'</div><div class="lms-ui-map-popup-address">') + '</div>'
											: '');
									popupRequired = true;
								}
							} else {
								content += '<span class="bold">' + features[i].data.typename + '<br>' +
									(features[i].data.technologyname.length ? '<span class="bold">' + features[i].data.technologyname + '<br>' : '') +
									features[i].data.speedname + '</span>';
								popupRequired = true;
							}
						}
						content += '</div>';
						if (popupRequired) {
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

							// workaround popup positioning error manually moving popup to correct position
							var point = this.handlers.feature.evt.xy;
							var popupSize = {
								height: mappopup.div.scrollHeight,
								width: mappopup.div.scrollWidth,
							};
							var mapSize = {
								height: map.div.scrollHeight,
								width: map.div.scrollWidth
							}
							mappopup.moveTo(
								new OpenLayers.Pixel(
									point.x + popupSize.width >= mapSize.width ? point.x - popupSize.width : point.x,
									point.y + popupSize.height >= mapSize.height ? point.y - popupSize.height : point.y
								)
							);
						}
					}
				}
				OpenLayers.Event.stop(e);
			},
			"featureunhighlighted": function(e) {
				if (mappopup) {
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

	var selectlayer, modifyfeature = null;

	if (selection)
	{
		selectlayer = new OpenLayers.Control.SelectFeature(highlightlayers, {
			clickout: true, toggle: false,
			multiple: true, hover: false,
			toggleKey: "ctrlKey", // ctrl key removes from selection
			multipleKey: "shiftKey", // shift key adds to selection
			onSelect: function(feature) {
				if (feature.data.hasOwnProperty('existing')) {
					return;
				}
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
				} else {
					featureLonLat = map.getLonLatFromViewPortPx(this.handlers.feature.evt.xy);
				}

				var features = findFeaturesIntersection(this, feature, featureLonLat);

				if (lmsSettings.mapCreateNewPointAfterLinkEditStart &&
					features.length === 1 &&
					features[0].data.hasOwnProperty('netlinkid')) {
					var lineFeature = features[0];

					if (lineFeature.geometry.CLASS_NAME === "OpenLayers.Geometry.LineString") {
						var g = lineFeature.geometry;
						var pt = new OpenLayers.Geometry.Point(featureLonLat.lon, featureLonLat.lat);

						var hit = findSegmentContainingPoint(map, g, pt, 30.0);

						if (hit && hit.t >= 0.15 && hit.t <= 0.85) {
							var newPoint = new OpenLayers.Geometry.Point(hit.projection.x, hit.projection.y);

							g.addComponent(newPoint, hit.segIndex + 1);
							lineFeature.layer.drawFeature(feature);
						}

						modifyfeature.selectFeature(lineFeature);
					}
				}

				if (features.length > 1 || (features.length == 1 && features[0].geometry.CLASS_NAME == "OpenLayers.Geometry.Point")) {
					var featurepopup = new OpenLayers.Popup(null, featureLonLat, new OpenLayers.Size(10, 10));
					featurepopup.setOpacity(0.9);
					//featurepopup.closeOnMove = true;
					//featurepopup.keepInMap = true;
					//featurepopup.panMapIfOutOfView = true;
					var content = '<div class="lms-ui-map-popup-titlebar"><div class="lms-ui-map-popup-title">Info</div>' +
						'<div id="' + featurepopup.id + '_popupCloseBox" class="olPopupCloseBox lms-ui-map-popup-closebox">&nbsp;</div></div>' +
						'<div class="lms-ui-map-info-popup-contents">';
					var data, state;
					for (i in features) {
						data = features[i].data;
						if (!data.hasOwnProperty('type')) {
							continue;
						}
						content += '<div class="lms-ui-map-info-popup-entry">';
						if (data.type == 'netdevinfo') {
							content += '<div class="lms-ui-map-info-popup-name">' + data.name + '</div>';
							if (data.ipaddr.length) {
								var ips = data.ipaddr.split(',');
								var nodeids = data.nodeid.split(',');
								for (j in nodeids) {
									content += '<div class="lms-ui-map-info-popup-address"><a href="#" onclick="ping_host(\'' +
										featurepopup.id + '\', \'' + ips[j] + '\')"><i class="lms-ui-icon-routed fa-fw"></i>&nbsp;' +
										ips[j] + '</a></div>';
								}
							}
							content += '<div class="lms-ui-map-info-popup-details">' +
								'<i class="lms-ui-icon-location fa-fw"></i><a href="?m=' + data.type + '&id=' + data.id + '">&nbsp;Info</a></div>';
							if (data.location) {
								content += '<div class="lms-ui-map-info-popup-details"><i class="lms-ui-icon-location fa-fw"></i>&nbsp;' + data.location + '</div>';
							}
						} else {
							state = data.state;
							content += '<div class="lms-ui-map-info-popup-name">' +
								'<i class="lms-ui-icon-node' + (state == 2 ? 'off' : (state == 1 ? 'on' : 'unk')) +
								' fa-fw"></i>&nbsp;' + data.name + '</div>';
							content += '<div class="lms-ui-map-info-popup-address"><i class="lms-ui-icon-routed"></i>&nbsp;<a href="#" onclick="ping_host(\'' +
								featurepopup.id + '\', \'' + data.ipaddr + '\')">' +
								data.ipaddr + '</a></div>';
							content += '<div class="lms-ui-map-info-popup-details">' +
								'<i class="lms-ui-icon-location fa-fw"></i><a href="?m=' + data.type + '&id=' + data.id + '">&nbsp;Info</a></div>';
							if (data.location) {
								content += '<div class="lms-ui-map-info-popup-details"><i class="lms-ui-icon-location fa-fw"></i>&nbsp;' + data.location + '</div>';
							}
							if (data.linktype.length) {
								content += '<div class="lms-ui-map-info-popup-details"><i class="lms-ui-icon-' +
									data.linktypeicon + ' fa-fw"></i><div class="lms-ui-map-info-popup-details-list"><div>' + data.linktypename +
									'</div>' + (data.linktechnologyname.length ? '<div>' + data.linktechnologyname + '</div>' : '') +
									'</div></div>';
							}
							if (data.url) {
								var urls = data.url.split(',');
								var comments = data.comment.split(',');
								content += '<div class="lms-ui-map-info-popup-details"><i class="lms-ui-icon-url fa-fw"></i>' +
									'<div class="lms-ui-map-info-popup-details-list">';
								for (j in urls) {
									content += '<a href="' + urls[j] + '"' +
										(urls[j].match(/^(https?|ftp):/) ? ' target="_blank"' : '') + '>' +
										(comments[j].length ? comments[j] : urls[j]) + '</a>';
								}
								content += '</div></div>';
							}
						}
						content += '</div>';
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
					featurepopup.feature = feature;
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

		if (lms.permissions.fullAccess || lms.permissions.networkMapEdit) {
			modifyfeature = new OpenLayers.Control.LmsModifyFeature(
				devlinklayer,
				{
					standalone: lmsSettings.mapCreateNewPointAfterLinkEditStart ? true : false,
					vertexRenderIntent: "vertex"
				}
			);

			modifyfeature.virtualStyle = OpenLayers.Util.applyDefaults(
				{
					strokeColor: "",
					strokeWidth: "",
					pointRadius: 9,
					fillColor: "#0000FF",
					fillOpacity: 0.5
				},
				OpenLayers.Feature.Vector.style.default
			);

			map.addControl(modifyfeature);
			modifyfeature.activate();
		}

		var checkbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCheckButton",
			title: $t("Check a host"),
			command: 'check'});

		var centerbutton = new OpenLayers.Control.Button({
			displayClass: "lmsCenterButton",
			title: $t("Center map around network elements"),
			command: 'center'});

		var refreshbutton = new OpenLayers.Control.Button({
			displayClass: "lmsRefreshButton",
			title: $t("Refresh network state"),
			command: 'refresh'});

		devLinkForeignEntityToggleButton = new OpenLayers.Control.Button({
			displayClass: "lmsDevLinkForeignEntityToggleButton",
			title: $t("Toggle foreign entity network link visibility"),
			command: 'devLinkForeignEntityToggle',
		});

		customerOwnedToggleButton = new OpenLayers.Control.Button({
			displayClass: "lmsCustomerOwnedToggleButton",
			title: $t("Toggle customer owned infrastructure element visibility"),
			command: 'customerOwnedToggle',
		});

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
					case 'devLinkForeignEntityToggle':
						mapFilters.devLinkForeignEntity = !mapFilters.devLinkForeignEntity;
						devlinklayer.redraw();
						if (mapFilters.devLinkForeignEntity) {
							control.deactivate();
						} else {
							control.activate();
						}

						setStorageItem('mapFilters', JSON.stringify(mapFilters), 'local');

						break;
					case 'customerOwnedToggle':
						mapFilters.customerOwned = !mapFilters.customerOwned;
						devlinklayer.redraw();
						devicelayer.redraw();
						if (mapFilters.customerOwned) {
							control.deactivate();
						} else {
							control.activate();
						}

						setStorageItem('mapFilters', JSON.stringify(mapFilters), 'local');

						break;
				}
			}
		});
		panel.addControls([checkbutton, centerbutton, refreshbutton, devLinkForeignEntityToggleButton, customerOwnedToggleButton]);
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
		map.setBaseLayer(osm);

	var mapLayers = getCookie('mapLayers');
	if (mapLayers != null) {
		var visibleLayers = mapLayers.split(';');
		for (i = 0; i < visibleLayers.length, i < layerSwitcher.dataLayers.length; i++) {
			for (j = 0; j < layerSwitcher.dataLayers.length, j < visibleLayers.length, layerSwitcher.layerStates[j].id != layerSwitcher.dataLayers[i].layer.id; j++);
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

	var storageMapFilters = getStorageItem('mapFilters', 'local');
	if (typeof(storageMapFilters) === 'string') {
		try {
			storageMapFilters = JSON.parse(storageMapFilters);
		} catch (error) {
			storageMapFilters = {};
		}
		$.extend(mapFilters, storageMapFilters);
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

	if (devLinkForeignEntityToggleButton) {
		if (mapFilters.devLinkForeignEntity) {
			devLinkForeignEntityToggleButton.deactivate();
		} else {
			devLinkForeignEntityToggleButton.activate();
		}
	}

	if (customerOwnedToggleButton) {
		if (mapFilters.customerOwned) {
			customerOwnedToggleButton.deactivate();
		} else {
			customerOwnedToggleButton.activate();
		}
	}

	// closes popups after mouse double click on them
	document.getElementById('map').addEventListener(
		'dblclick',
		function(e) {
			var closestElement = e.target;
			while (closestElement && 'className' in closestElement &&
				(typeof(closestElement.className) != 'string' || !closestElement.className.match(/^olPopup$/))) {
				closestElement = closestElement.parentElement;
			}
			if (closestElement !== null) {
				for (var i in map.popups) {
					if (map.popups[i].visible() && map.popups[i].id == closestElement.id) {
						selectlayer.unselect(map.popups[i].feature);
						map.removePopup(map.popups[i]);
					}
				}
			}
		},
		{
			capture: true
		}
	);

	//map.events.register('mousemove', map, function(e) {
	//	removeInvisiblePopups();
	//});

	return map;
}
