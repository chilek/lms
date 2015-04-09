/*
 * DO NOT REMOVE THIS NOTICE
 *
 * PROJECT:   mygosuMenu
 * VERSION:   1.3.3
 * COPYRIGHT: (c) 2003,2004 Cezary Tomczak
 * LINK:      http://gosu.pl/dhtml/mygosumenu.html
 * LICENSE:   BSD (revised)
 */

function ClickShowHideMenu(id) {
    this.box1Hover = true;
    this.box2Hover = true;
    this.highlightActive = false;

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
                    eval('nodes[i].onmouseover = function() { self.box1over("'+nodes[i].id+'"); }');
                    eval('nodes[i].onmouseout = function() { self.box1out("'+nodes[i].id+'"); }');
                    eval('nodes[i].onclick = function() { self.box1click("'+nodes[i].id+'"); }');
                }
                if ("section" == nodes[i].className) {
                    id = id + "-" + (tree.length - 1);
                    nodes[i].id = id + "-section";
                    tree = tree[tree.length - 1];
                }
                if ("box2" == nodes[i].className.substr(0, 4)) {
                    nodes[i].id = id + "-" + tree.length;
                    tree[tree.length] = [];
                    eval('nodes[i].onmouseover = function() { self.box2over("'+nodes[i].id+'", "'+nodes[i].className+'"); }');
                    eval('nodes[i].onmouseout = function() { self.box2out("'+nodes[i].id+'", "'+nodes[i].className+'"); }');
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
        document.getElementById(id).className = (this.id_openbox == id ? "box1-open-hover" : "box1-hover");
    }

    this.box1out = function(id) {
        if (!this.box1Hover) return;
        if (!document.getElementById(id)) return;
        document.getElementById(id).className = (this.id_openbox == id ? "box1-open" : "box1");
    }

    this.box1click = function(id) {
        if (!document.getElementById(id)) {
            return;
        }
        var id_openbox = this.id_openbox;
        if (this.id_openbox) {
            if (!document.getElementById(id + "-section")) {
                return;
            }
            this.hide();
            if (id_openbox == id) {
                if (this.box1hover) {
                    document.getElementById(id_openbox).className = "box1-hover";
                } else {
                    document.getElementById(id_openbox).className = "box1";
                }
            } else {
                document.getElementById(id_openbox).className = "box1";
            }
        }
        if (id_openbox != id) {
            this.show(id);
            var className = document.getElementById(id).className;
            if ("box1-hover" == className) {
                document.getElementById(id).className = "box1-open-hover";
            }
            if ("box1" == className) {
                document.getElementById(id).className = "box1-open";
            }
        }
    }

    this.box2over = function(id, className) {
        if (!this.box2Hover) return;
        if (!document.getElementById(id)) return;
        document.getElementById(id).className = className + "-hover";
    }

    this.box2out = function(id, className) {
        if (!this.box2Hover) return;
        if (!document.getElementById(id)) return;
        document.getElementById(id).className = className;
    }

    this.show = function(id) {
        if (document.getElementById(id + "-section")) {
            document.getElementById(id + "-section").style.display = "block";
            this.id_openbox = id;
        }
    }

    this.hide = function() {
        document.getElementById(this.id_openbox + "-section").style.display = "none";
        this.id_openbox = "";
    }

    this.save = function() {
        if (this.id_openbox) {
            this.cookie.set(this.id, this.id_openbox);
        } else {
            this.cookie.del(this.id);
        }
    }

    this.load = function() {
        var id_openbox = this.cookie.get(this.id);
        if (id_openbox) {
            this.show(id_openbox);
            document.getElementById(id_openbox).className = "box1-open";
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

    var self = this;
    this.id = id;
    this.tree = [];
    this.cookie = new Cookie();
    this.id_openbox = "";
}

