typ_zgloszenia = function(type) {
    if (type == '1'){
        document.getElementById('zgloszenieawarii').style.display = '';
        document.getElementById('zgloszenieawarii2').style.display = '';
        document.getElementById('dane_kontaktowe').style.display = '';
        document.getElementById('zgloszenieawarii4').style.display = '';
	document.getElementById('informacjahandlowa').style.display = 'none';
	document.getElementById('sprawyfinansowe').style.display = 'none';
	document.getElementById('sprawyfinansowe3').style.display = 'none';
	document.getElementById('sprawyfinansowe4').style.display = 'none';
        document.getElementById('ponowny_kontakt_tak').style.display = 'none';
	document.getElementById('ponowny_kontakt_nie').style.display = 'none';
        document.getElementById('zakonczenie').style.display = 'none';
	document.getElementById('zapisanie').style.display = '';
	document.getElementById('zapisanie2').style.display = '';
    }
    else if (type == '2') {
	document.getElementById('zgloszenieawarii').style.display = 'none';
	document.getElementById('zgloszenieawarii2').style.display = 'none';
	document.getElementById('dane_kontaktowe').style.display = 'none';
	document.getElementById('zgloszenieawarii4').style.display = 'none';
	document.getElementById('informacjahandlowa').style.display = '';
	document.getElementById('sprawyfinansowe').style.display = 'none';
	document.getElementById('sprawyfinansowe3').style.display = 'none';
	document.getElementById('sprawyfinansowe4').style.display = 'none';
    }
    else if (type == '3') {
	document.getElementById('zgloszenieawarii').style.display = 'none';
	document.getElementById('zgloszenieawarii2').style.display = 'none';
	document.getElementById('dane_kontaktowe').style.display = 'none';
	document.getElementById('zgloszenieawarii4').style.display = 'none';
	document.getElementById('informacjahandlowa').style.display = 'none';
	document.getElementById('sprawyfinansowe').style.display = '';
	document.getElementById('sprawyfinansowe3').style.display = 'none';
        document.getElementById('ponowny_kontakt_tak').style.display = 'none';
	document.getElementById('ponowny_kontakt_nie').style.display = 'none';
        document.getElementById('zakonczenie').style.display = 'none';
	document.getElementById('zapisanie').style.display = '';
	document.getElementById('zapisanie2').style.display = '';
    }
}

ponowny_kontakt = function(type) {
    if (type == 'tak') {
        document.getElementById('dane_kontaktowe').style.display = '';
	document.getElementById('ponowny_kontakt_nie').style.display = 'none';
        document.getElementById('zakonczenie').style.display = 'none';
	document.getElementById('zapisanie').style.display = '';
	document.getElementById('zapisanie2').style.display = '';
    }
    else if (type == 'nie') {
        document.getElementById('dane_kontaktowe').style.display = 'none';
	document.getElementById('ponowny_kontakt_nie').style.display = '';
        document.getElementById('zakonczenie').style.display = '';
	document.getElementById('zapisanie').style.display = 'none';
	document.getElementById('zapisanie2').style.display = 'none';
    }
}

blokowanie_komunikatu = function(type) {
    if (type == 'tak') {
        document.getElementById('sprawyfinansowe3').style.display = '';
	document.getElementById('dane_kontaktowe').style.display = '';
	document.getElementById('sprawyfinansowe4').style.display = 'none';
        document.getElementById('zakonczenie').style.display = 'none';
	document.getElementById('zapisanie').style.display = '';
	document.getElementById('zapisanie2').style.display = '';
    }
    else if (type == 'nie') {
        document.getElementById('sprawyfinansowe3').style.display = 'none';
	document.getElementById('dane_kontaktowe').style.display = 'none';
	document.getElementById('sprawyfinansowe4').style.display = '';
        document.getElementById('zakonczenie').style.display = '';
	document.getElementById('zapisanie').style.display = 'none';
	document.getElementById('zapisanie2').style.display = 'none';
    }
}

function closeTHEwindow () {
    close();
}

function phoneContact() {
	if (document.getElementById("phonetype").checked) {
		document.getElementById("contact_phone").style.display = 'none';
		document.getElementsByName("contactphone")[0].value= '';
	} else {
		document.getElementById("contact_phone").style.display = 'block';
	}
}

function showPanel(value) {
	if (value == 'show') {
		document.getElementById("panel").style.display = 'block';
		document.getElementById("showbtn").style.display = 'none';
		document.getElementById("hidebtn").style.display = 'block';
	} else {
		document.getElementById("panel").style.display = 'none';
		document.getElementById("showbtn").style.display = 'block';
		document.getElementById("hidebtn").style.display = 'none';
	}
}