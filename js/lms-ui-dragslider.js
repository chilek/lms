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

$.widget("ui.dragslider", $.ui.slider, {

	options: $.extend({},$.ui.slider.prototype.options,{rangeDrag:false}),

	_create: function() {
		$.ui.slider.prototype._create.apply(this,arguments);
		this._rangeCapture = false;
	},

	_mouseInit: function() {
		this.ctrlPressed = false;
		var that = this;
		$.ui.slider.prototype._mouseInit.apply(this,arguments);
		$(document).on('keydown', function(e) {
			if (e.which == 17) {
				that.ctrlPressed = true;
			}
		}).on('keyup', function(e) {
			if (e.which == 17) {
				that.ctrlPressed = false;
			}
		});
	},

	_mouseCapture: function( event ) {
		var o = this.options;

		if ( o.disabled ) return false;

		if((this.ctrlPressed || event.target == this.range.get(0)) && o.rangeDrag == true && o.range == true) {
			this._rangeCapture = true;
			this._rangeStart = null;
		}
		else {
			this._rangeCapture = false;
		}

		$.ui.slider.prototype._mouseCapture.apply(this,arguments);

		if(this._rangeCapture == true) {
			this.handles.removeClass("ui-state-active").blur();
		}

		return true;
	},

	_mouseStop: function( event ) {
		this._rangeStart = null;
		return $.ui.slider.prototype._mouseStop.apply(this,arguments);
	},

	_slide: function( event, index, newVal ) {
		if(!this._rangeCapture) {
			return $.ui.slider.prototype._slide.apply(this,arguments);
		}

		if(this._rangeStart == null) {
			this._rangeStart = newVal;
		}

		var oldValLeft = this.options.values[0],
			oldValRight = this.options.values[1],
			slideDist = newVal - this._rangeStart,
			newValueLeft = oldValLeft + slideDist,
			newValueRight = oldValRight + slideDist,
			allowed;

		if ( this.options.values && this.options.values.length ) {
			if(newValueRight > this._valueMax() && slideDist > 0) {
				slideDist -= (newValueRight-this._valueMax());
				newValueLeft = oldValLeft + slideDist;
				newValueRight = oldValRight + slideDist;
			}

			if(newValueLeft < this._valueMin()) {
				slideDist += (this._valueMin()-newValueLeft);
				newValueLeft = oldValLeft + slideDist;
				newValueRight = oldValRight + slideDist;
			}

			if ( slideDist != 0 ) {
				newValues = this.values();
				newValues[ 0 ] = newValueLeft;
				newValues[ 1 ] = newValueRight;

				// A slide can be canceled by returning false from the slide callback
				allowed = this._trigger( "slide", event, {
					handle: this.handles[ index ],
					value: slideDist,
					values: newValues
				} );

				if ( allowed !== false ) {
					this.values( 0, newValueLeft, true );
					this.values( 1, newValueRight, true );
				}
				this._rangeStart = newVal;
			}
		}



	},

	_start: function( event, index ) {
		if (this._rangeCapture) {
			index = 3;
		} else {
			index++;
		}
		return this._trigger( "start", event, this._uiHash( index ) );
	},

	_stop: function( event, index ) {
		if (this._rangeCapture) {
			index = 3;
		} else {
			index++;
		}
		return this._trigger( "stop", event, this._uiHash( index ) );
	},

	/*
	//only for testing purpose
	value: function(input) {
		console.log("this is working!");
		$.ui.slider.prototype.value.apply(this,arguments);
	}
	*/
});
