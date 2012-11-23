Leaflet.markercluster
=====================

(all changes without author notice are by [@danzel](https://github.com/danzel))

## Master

### Improvements

 * Work better with custom projections (by [@andersarstrand](https://github.com/andersarstrand)) [#74](https://github.com/danzel/Leaflet.markercluster/issues/74)
 * Add custom getBounds that works (Reported by [@2803media](https://github.com/2803media))

### Bugfixes

 * Fix singleMarkerMode when you aren't on the map (by [@duncanparkes](https://github.com/duncanparkes)) [#77](https://github.com/danzel/Leaflet.markercluster/issues/77)
 * Fix clearLayers when you aren't on the map (by [@duncanparkes](https://github.com/duncanparkes)) [#79](https://github.com/danzel/Leaflet.markercluster/issues/79)

## 0.2 (2012-10-11)

### Improvements

 * Add addLayers/removeLayers bulk add and remove functions that perform better than the individual methods
 * Allow customising the polygon generated for showing the area a cluster covers (by [@yohanboniface](https://github.com/yohanboniface)) [#68](https://github.com/danzel/Leaflet.markercluster/issues/68)
 * Add zoomToShowLayer method to zoom down to a marker then call a callback once it is visible
 * Add animateAddingMarkers to allow disabling animations caused when adding/removing markers
 * Add hasLayer
 * Pass the L.MarkerCluster to iconCreateFunction to give more flexibility deciding the icon
 * Make addLayers support geojson layers
 * Allow disabling clustering at a given zoom level
 * Allow styling markers that are added like they were clusters of size 1

 
### Bugfixes

 * Support when leaflet is configured to use canvas rather than SVG
 * Fix some potential crashes in zoom handlers
 * Tidy up when we are removed from the map

## 0.1 (2012-08-16)

Initial Release!