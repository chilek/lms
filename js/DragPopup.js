/*
 * Move a popup with a drag.
 *
 * @author Matt Walker
 * @class
 */
OpenLayers.Control.DragPopup = OpenLayers.Class(OpenLayers.Control, {

    down: false,

    popPnt: null,

    mapPnt: null,

    popup: null,

    docMouseUpProxy: null,

    /**
     * Constructor: OpenLayers.Control.DragPopup
     * Create a new control to drag a popup.
     *
     * Parameters:
     * @param {OpenLayers.Popup} popup
     * @param {Object} options
     */
    initialize: function(popup, options) {
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
        this.popup = popup;
        this.popup.events.register('mousedown', this, this.mouseDown);
        this.popup.events.register('mouseup', this, this.mouseUp);
        this.popup.events.register('mousemove', this, this.mouseMove);
        this.popup.events.register('click', this, this.click);
        // Define a function bound to this used to listen for
        // document mouseout events
        this.docMouseUpProxy = OpenLayers.Function.bind(this.mouseUp, this);
    },

    /**
     * Method: setMap
     * Set the map property for the control.
     *
     * Parameters:
     * map - {<openlayers.map>} The controls map.
     */
    setMap: function(map) {
        OpenLayers.Control.prototype.setMap.apply(this, [map]);
        this.map.events.register('mousemove', this, this.mouseMove);
    },

    mouseDown: function(evt) {
        //console.log('mouseDown');
        this.popPnt = this.popup.events.getMousePosition(evt);
        var divelems = this.popup.div.getElementsByTagName('div');
        for (var i = 0; i < divelems.length, divelems[i].className != 'lmsPopupTitleBar'; i++);
        if (i < divelems.length && (this.popPnt.x < divelems[i].offsetLeft || this.popPnt.x > divelems[i].offsetLeft + divelems[i].offsetWidth ||
                this.popPnt.y < divelems[i].offsetTop || this.popPnt.y > divelems[i].offsetTop + divelems[i].offsetHeight))
            return true;
        else
            this.down = true;
        this.moveToFront();
        OpenLayers.Event.observe(document, 'mouseup', this.docMouseUpProxy);
        OpenLayers.Event.stop(evt);
    },

    mouseUp: function(evt) {
        //console.log('mouseUp');
        this.down = false;
        OpenLayers.Event.stopObserving(document, 'mouseup', this.docMouseUpProxy);
        OpenLayers.Event.stop(evt);
    },

    click: function(evt) {
        var closeElem = document.getElementById(this.popup.div.id + '_' + 'popupCloseBox');
        if (closeElem != null)
        {
            var clickPnt = this.popup.events.getMousePosition(evt);
            if (clickPnt.x >= closeElem.offsetLeft && clickPnt.x <= closeElem.offsetLeft + closeElem.offsetWidth &&
                clickPnt.y >= closeElem.offsetTop && clickPnt.y <= closeElem.offsetTop + closeElem.offsetHeight) {
                if (this.feature) {
                    this.feature.popup = null;
                    for (var i = 0; i < this.map.controls.length, !(this.map.controls[i] instanceof OpenLayers.Control.SelectFeature); i++);
                    this.map.controls[i].unselect(this.feature);
                }
                this.map.removePopup(this.popup);
            }
        }
    },

    mouseOut: function(evt) {
        //console.log('map.mouseOut');
        this.down = false;
        OpenLayers.Event.stop(evt);
    },

    mouseMove: function(evt) {
        //console.log('mouseMove');
        if (this.down) {
            var mapPntPx = this.map.events.getMousePosition(evt);
            mapPntPx = mapPntPx.add((this.popPnt.x*-1), (this.popPnt.y*-1));
            this.popup.lonlat = this.map.getLonLatFromViewPortPx(mapPntPx);
            this.popup.updatePosition();
        }
        OpenLayers.Event.stop(evt);
    },

    destroy: function() {
        // Remove listeners
        this.popup.events.unregister('mousedown', this, this.mouseDown);
        this.popup.events.unregister('mouseup', this, this.mouseUp);
        this.popup.events.unregister('mousemove', this, this.mouseMove);
        this.map.events.unregister('mousemove', this, this.mouseMove);
        // Clear object references
        this.popup = null;
        this.popPnt = null;
        // allow our superclass to tidy up
        OpenLayers.Control.prototype.destroy.apply(this, []);
    },

    moveToFront: function() {
        var popups = this.map.popups;
        var maxZIndex = 0;
        for (var i in popups)
            if (popups[i].id != this.popup.id && popups[i].div.style.zIndex > maxZIndex)
                maxZIndex = popups[i].div.style.zIndex;
        if (parseInt(maxZIndex) > parseInt(this.popup.div.style.zIndex))
            this.popup.div.style.zIndex = parseInt(maxZIndex) + 1;
    },

    /** @final @type String */
    CLASS_NAME: "OpenLayers.Control.DragPopup"
});

