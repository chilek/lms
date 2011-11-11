
var popup = null;

function createMap(deviceArray, devlinkArray, nodeArray, nodelinkArray, selection)
{
	var linkstyles = [
		{ strokeColor: '#00ff00', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#0000ff', strokeOpacity: 0.5, strokeWidth: 2 },
		{ strokeColor: '#ff0000', strokeOpacity: 0.5, strokeWidth: 2 }
	];

	var map = new OpenLayers.Map("map");
	var osmap = new OpenLayers.Layer.OSM();
	var gmap = new OpenLayers.Layer.Google("Google Maps");
	map.addLayer(gmap);
	map.addLayer(osmap);

//	var renderer = OpenLayers.Layer.Vector.prototype.renderers;

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
				popup = new OpenLayers.Popup(null,
					new OpenLayers.LonLat(e.feature.data.lon, e.feature.data.lat)
						.transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()),
					new OpenLayers.Size(150, 50), 
					'<B>' + e.feature.data.name + '</B><BR>' + (e.feature.data.ipaddr).split(",")[0]);
				popup.setOpacity(0.8);
				popup.closeOnMove = true;
				map.addPopup(popup);
				OpenLayers.Event.stop(e);
			},
			"featureunhighlighted": function(e) {
				if (popup != null)
				{
					map.removePopup(popup);
					popup.destroy();
					popup = null;
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
			multiple: false, hover: false,
			toggleKey: "ctrlKey", // ctrl key removes from selection
			multipleKey: "shiftKey", // shift key adds to selection
			onSelect: function(e) {
				document.location = "?m=" + e.data.type + "&id=" + e.data.id;
				OpenLayers.Event.stop(e);
			}
		});
		map.addControl(selectlayer);
		selectlayer.activate();
	}

	map.addControl(new OpenLayers.Control.ScaleLine());
	map.addControl(new OpenLayers.Control.LayerSwitcher());
	map.addControl(new OpenLayers.Control.MousePosition({ displayProjection: new OpenLayers.Projection("EPSG:4326") }));

	if (deviceArray || nodeArray)
		map.zoomToExtent(area);
	else
		map.zoomToMaxExtent();

	return map;
}
