/*
 * DO NOT REMOVE THIS NOTICE
 *
 * PROJECT:   mygosuMenu
 * VERSION:   1.3.3 (hardly modified for LMS)
 * COPYRIGHT: (c) 2003,2004 Cezary Tomczak
 *            (c) 2016 Tomasz Chiliński
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

    this.init = function() {
        if (!document.getElementById(this.id)) {
            alert("Element '"+this.id+"' does not exist in this document. ClickShowHideMenu cannot be initialized");
            return;
        }
        this.parse(document.getElementById(this.id).childNodes, this.tree, this.id);
        this.load();
        if (window.attachEvent) {
            window.attachEvent("onunload", function(e) { self.save(); });
        } else if (window.addEventListener) {
            window.addEventListener("unload", function(e) { self.save(); }, false);
        }
    }

    this.parse = function(nodes, tree, id) {
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].nodeType != 1) {
                continue;
            }
            if (nodes[i].className) {
                if ("box1" == nodes[i].className.substr(0, 4)) {
                    nodes[i].id = id + "-" + tree.length;
                    tree[tree.length] = [];
                    nodes[i].onmouseover = function() { self.box1over(this.id); }
                    nodes[i].onmouseout = function() { self.box1out(this.id); }
                    nodes[i].onclick = function() { self.box1click(this.id); }
                }
                if ("section" == nodes[i].className) {
                    id = id + "-" + (tree.length - 1);
                    nodes[i].id = id + "-section";
                    tree = tree[tree.length - 1];
                }
                if ("box2" == nodes[i].className.substr(0, 4)) {
                    nodes[i].id = id + "-" + tree.length;
                    tree[tree.length] = [];
                    nodes[i].onmouseover = function() { self.box2over(this.id); }
                    nodes[i].onmouseout = function() { self.box2out(this.id); }
                }
            }
            if (this.highlightActive && nodes[i].tagName && nodes[i].tagName == "A") {
                if (document.location.href == nodes[i].href) {
                    nodes[i].className = (nodes[i].className ? ' active' : 'active')
                }
            }
            if (nodes[i].childNodes) {
                this.parse(nodes[i].childNodes, tree, id);
            }
        }
    }

    this.box1over = function(id) {
        if (!this.box1Hover) return;
        if (!document.getElementById(id)) return;
		var sections = document.getElementsByClassName('section');
		for (var i = 0; i < sections.length; i++) {
			var section = document.getElementById(sections[i].id.replace('-section', ''));
			if (section.id == id)
				section.className = (sections[i].style.display == 'block' ? "box1-open-hover" : "box1-hover");
		}
    }

    this.box1out = function(id) {
        if (!this.box1Hover) return;
        if (!document.getElementById(id)) return;
		var sections = document.getElementsByClassName('section');
		for (var i = 0; i < sections.length; i++) {
			var section = document.getElementById(sections[i].id.replace('-section', ''));
			if (section.id == id)
				section.className = (sections[i].style.display == 'block' ? "box1-open" : "box1");
		}
    }

    this.box1click = function(id) {
		if ((elem = document.getElementById(id)) === null)
			return;
		var section = document.getElementById(id + "-section");
		if (this.openedSections.indexOf(id) > -1) {
			this.hide(id);
			if (this.box1Hover)
				elem.className = 'box1-hover';
		} else {
			this.show(id);
			if (this.box1Hover) {
				elem = document.getElementById(id);
				elem.className = 'box1-open-hover';
			}
		}
    }

	this.box2over = function(id) {
		if (!this.box2Hover) return;
		$('#' + id).addClass('box2-hover');
	}

	this.box2out = function(id) {
		if (!this.box2Hover) return;
		$('#' + id).removeClass('box2-hover');
	}

    this.show = function(id) {
		if ((section = document.getElementById(id + "-section")) !== null &&
			section.childNodes.length > 1) {
			section.style.display = "block";
			this.appendOpenedSection(id);
		}
    }

    this.hide = function(id) {
		if ((section = document.getElementById(id + "-section")) !== null &&
			section.childNodes.length > 1) {
			section.style.display = "";
			this.removeOpenedSection(id);
		}
    }

    this.save = function() {
		var sections = document.getElementsByClassName('section');
		var openedSections = [];
		for (var i = 0; i < sections.length; i++)
			if (sections[i].style.display == 'block')
				openedSections.push(sections[i].id.replace('-section', ''));
		if (openedSections.length)
			this.cookie.set(this.id, openedSections.join(';'));
		else
			this.cookie.del(this.id);
    }

    this.load = function() {
		var openedSections = this.cookie.get(this.id);
		if (openedSections) {
			openedSections = openedSections.split(';');
			for (var i = 0; i < openedSections.length; i++) {
				this.show(openedSections[i]);
				document.getElementById(openedSections[i]).className = "box1-open";
			}
		}
    }

	this.appendOpenedSection = function(id) {
		var index;
		if ((index = this.openedSections.indexOf(id)) > -1)
			this.openedSections.splice(index, 1);
		this.openedSections.push(id);
		if (this.maxOpened && this.openedSections.length > this.maxOpened)
			this.hide(this.openedSections[0]);
	}

	this.removeOpenedSection = function(id) {
		if ((index = this.openedSections.indexOf(id)) > -1) {
			this.openedSections.splice(index, 1);
			if (this.box1Hover && (elem = document.getElementById(id)) !== null)
				elem.className = 'box1';
		}
	}

    function Cookie() {
        this.get = function(name) {
            var cookies = document.cookie.split(";");
            for (var i = 0; i < cookies.length; i++) {
                var a = cookies[i].split("=");
                if (a.length == 2) {
                    a[0] = a[0].trim();
                    a[1] = a[1].trim();
                    if (a[0] == name) {
                        return unescape(a[1]);
                    }
                }
            }
            return "";
        }
        this.set = function(name, value) {
            document.cookie = name + "=" + escape(value);
        }
        this.del = function(name) {
            document.cookie = name + "=; expires=Thu, 01-Jan-70 00:00:01 GMT";
        }
    }

    this.tree = [];
    this.cookie = new Cookie();
}

