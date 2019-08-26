/*
 * DO NOT REMOVE THIS NOTICE
 *
 * PROJECT:   mygosuMenu
 * VERSION:   1.3.3 (hardly modified for LMS)
 * COPYRIGHT: (c) 2003,2004 Cezary Tomczak
 *            (c) 2016-2018 Tomasz Chiliński
 * LINK:      http://gosu.pl/dhtml/mygosumenu.html
 * LICENSE:   BSD (revised)
 */

function ClickShowHideMenu(params) {
	var self = this;

	if (typeof(params) == 'string') {
		this.id = params;
		this.maxOpened = 1;
	} else {
		if (params.id == undefined) {
			alert("Id of element was not specified. ClickShowHideMenu cannot be initialized");
			return;
		}
		this.id = params.id;
		this.maxOpened = parseInt(params.maxOpened);
		if (isNaN(this.maxOpened))
			this.maxOpened = 1;
	}

	this.box1Hover = true;
	this.box2Hover = true;
	this.highlightActive = false;
	this.openedSections = [];

	this.init = function () {
		if (!document.getElementById(self.id)) {
			alert("Element '" + self.id + "' does not exist in this document. ClickShowHideMenu cannot be initialized");
			return;
		}

		var nodes = $('#' + self.id).children();
		this.parse(nodes, this.tree, self.id);
		this.initEventHandlers(nodes)

		this.load();
		if (window.attachEvent) {
			window.attachEvent("onunload", function (e) {
				self.save();
			});
		} else if (window.addEventListener) {
			window.addEventListener("unload", function (e) {
				self.save();
			}, false);
		}
	}

	this.initEventHandlers = function (nodes) {
		var box1 = $('[class^="box1"]', nodes);
		var box2 = $('[class^="box2"]', nodes);

		box1.click(function (e) {
			if (this.nodeType != 1) {
				return false;
			}
			self.box1click(this.id);
		});
		if (self.box1Hover) {
			box1.mouseover(function () {
				if (this.nodeType == 1) {
					self.box1over(this.id);
				}
			}).mouseout(function () {
				if (this.nodeType == 1) {
					self.box1out(this.id);
				}
			});
		}

		if (self.box2Hover) {
			box2.mouseover(function () {
				if (this.nodeType == 1) {
					$('#' + this.id).addClass('box2-hover');
				}
			}).mouseout(function () {
				if (this.nodeType == 1) {
					$('#' + this.id).removeClass('box2-hover');
				}
			});
		}
	}

	this.parse = function (nodes, tree, id) {
		for (var i = 0; i < nodes.length; i++) {
			if (nodes[i].nodeType != 1) {
				continue;
			}
			if (nodes[i].className) {
				if ("box1" == nodes[i].className.substr(0, 4)) {
					nodes[i].id = id + "-" + tree.length;
					tree[tree.length] = [];
				}
				if ("section" == nodes[i].className) {
					id = id + "-" + (tree.length - 1);
					nodes[i].id = id + "-section";
					tree = tree[tree.length - 1];
				}
				if ("box2" == nodes[i].className.substr(0, 4)) {
					nodes[i].id = id + "-" + tree.length;
					tree[tree.length] = [];
				}
			}
			if (self.highlightActive && nodes[i].tagName && nodes[i].tagName == "A") {
				if (document.location.href == nodes[i].href) {
					nodes[i].className = (nodes[i].className ? ' active' : 'active')
				}
			}
			var children = $(nodes[i]).children();
			if (children.length) {
				this.parse(children, tree, id);
			}
		}
	}

	this.box1over = function (id) {
		if (!document.getElementById(id)) return;
		var sections = document.getElementsByClassName('section');
		for (var i = 0; i < sections.length; i++) {
			var section = document.getElementById(sections[i].id.replace('-section', ''));
			if (section.id == id)
				section.className = (sections[i].style.display == 'block' ? "box1-open-hover" : "box1-hover");
		}
	}

	this.box1out = function (id) {
		if (!document.getElementById(id)) return;
		var sections = document.getElementsByClassName('section');
		for (var i = 0; i < sections.length; i++) {
			var section = document.getElementById(sections[i].id.replace('-section', ''));
			if (section.id == id)
				section.className = (sections[i].style.display == 'block' ? "box1-open" : "box1");
		}
	}

	this.box1click = function (id) {
		if ((elem = document.getElementById(id)) === null)
			return;
		if (self.openedSections.indexOf(id) > -1) {
			this.hide(id);
			if (self.box1Hover)
				elem.className = 'box1-hover';
		} else {
			this.show(id);
			if (self.box1Hover) {
				elem = document.getElementById(id);
				elem.className = 'box1-open-hover';
			}
		}
	}

	this.show = function (id) {
		var section;
		if ((section = document.getElementById(id + "-section")) !== null &&
			section.childNodes.length > 1) {
			section.style.display = "block";
			this.appendOpenedSection(id);
		}
	}

	this.hide = function (id) {
		if ((section = document.getElementById(id + "-section")) !== null &&
			section.childNodes.length > 1) {
			section.style.display = "";
			this.removeOpenedSection(id);
		}
	}

	this.save = function () {
		var sections = document.getElementsByClassName('section');
		var openedSections = [];
		for (var i = 0; i < sections.length; i++)
			if (sections[i].style.display == 'block')
				openedSections.push(sections[i].id.replace('-section', ''));
		if (openedSections.length)
			this.cookie.set(self.id, openedSections.join(';'));
		else
			this.cookie.del(self.id);
	}

	this.load = function () {
		var openedSections = this.cookie.get(self.id);
		if (openedSections) {
			openedSections = openedSections.split(';');
			for (var i = 0; i < openedSections.length; i++) {
				this.show(openedSections[i]);
				var elem = document.getElementById(openedSections[i]);
				if (elem) {
					elem.className = "box1-open";
				}
			}
		}
	}

	this.appendOpenedSection = function (id) {
		var index;
		if ((index = this.openedSections.indexOf(id)) > -1)
			this.openedSections.splice(index, 1);
		this.openedSections.push(id);
		if (this.maxOpened && this.openedSections.length > this.maxOpened)
			this.hide(this.openedSections[0]);
	}

	this.removeOpenedSection = function (id) {
		if ((index = this.openedSections.indexOf(id)) > -1) {
			this.openedSections.splice(index, 1);
			if (this.box1Hover && (elem = document.getElementById(id)) !== null)
				elem.className = 'box1';
		}
	}

	function Cookie() {
		this.get = function (name) {
			var cookies = document.cookie.split(";");
			for (var i = 0; i < cookies.length; i++) {
				var a = cookies[i].split("=");
				if (a.length == 2) {
					a[0] = a[0].trim();
					a[1] = a[1].trim();
					if (a[0] == name) {
						return decodeURIComponent(a[1]);
					}
				}
			}
			return "";
		}
		this.set = function (name, value) {
			document.cookie = name + "=" + encodeURIComponent(value);
		}
		this.del = function (name) {
			document.cookie = name + "=; expires=Thu, 01-Jan-70 00:00:01 GMT";
		}
	}

	this.tree = [];
	this.cookie = new Cookie();
}

