const isNumeric = (value) => !isNaN(value) && isFinite(value);

function modalwindow(module, width, height, reload) {
	if (isNumeric(width))
		width = width + 'px';

	if (isNumeric(height))
		height = height + 'px';

	var src = location.protocol + '//' + document.domain + '/?m=' + module + '&popup=1';
	$.modal('<iframe src="' + src + '" style="border:0; width: ' + width + '; height: ' + height + ';">', {
		opacity: 60,
		containerCss:{
			padding: 3
		},
		onClose: function (dialog) {
			$.modal.close();
			if (reload) {
				location.reload(true);
			}
		}
	});
}

function modalclose() {
	parent.$.modal.close();
}

function pad(id) {
	$('#pid').val(id);
}

function gtuad(gtu) {
	$('#gtuid').val(gtu);

	if (gtu > 0) {
                $('#gtucs option').attr('selected',false);
                $('#gtucs option[value='+gtu+']').attr('selected','selected');
                $('#gtu_help').attr('title', $('#gch' + gtu).text()).attr('data-tooltip', $('#gch' + gtu).text())
        	$('#taxcategoryl option[value='+gtu+']').attr('selected','selected');
	} else {
                $('#gtucs option').attr('selected',false);
                $('#gtucs option[value=""]').attr('selected','selected');
                $('#gtu_help').attr('title', $('#gch' + gtu).text()).attr('data-tooltip', 'Choose wisely, you must, young padawan')
        }
}

function stckrnpadd(id, gtu=false) {
	pad(id);

	if (gtu > 0) {
		$('#gtucs option').attr('selected',false);
		$('#gtucs option[value='+gtu+']').attr('selected','selected');
		$('#gtu_help').attr('title', $('#gch' + gtu).text()).attr('data-tooltip', $('#gch' + gtu).text())
	} else {
		$('#gtucs option').attr('selected',false);
		$('#gtucs option[value=""]').attr('selected','selected');
		$('#gtu_help').attr('title', $('#gch' + gtu).text()).attr('data-tooltip', 'Choose wisely, you must, young padawan')
	}
}

function pinv(id, net, gross, quant = false, gtu=false) {
	pad(id);

	if (gtu > 0) {
		gtuad(gtu);
	}

	if (gross > 0) {
		$('input[name="valuenetto"]').val('');
		$('input[name="valuebrutto"]').val(gross);
	} else {
		$('input[name="valuenetto"]').val(net);
		$('input[name="valuebrutto"]').val('');
	}
	
	if (quant > 0) {
		$('input[name="count"]').change(function() {
			var count = $('input[name="count"]').val();
			if (count > quant) {
				var a = confirm("There are only " + quant + " on stock. Are you sure?");
				if (a != true)
					$('input[name="count"]').val(quant);
			}
		});
	}
}

$(document).ready(function() {
	var i = 0;
	var count = 0;

	if ($('#pcount').val())
		count = $('#pcount').val();

	$('#pcount').change(function() {
		var serials = new Array();
		count = $('#pcount').val();
		
		//var finame = $('#pserial').data('finame');
		//finame = f;
	//	if (!finame)
			finame = 'receivenote[product][serial][]';
	
		$('#pserial input[name="'+finame+'"]').each(function() {
			//alert($(this).val());
			serials.push($(this).val());
		});
		console.log(serials);

		$('#pserial').text('');

		for (i = 1; i <= count; i++) {
			$("#pserial").append('<INPUT type="text" name="' + finame + '" value = "' + (serials.length ? serials.shift() : '') + '" SIZE="40" data-ct="serialnumber"><br />');
		}
	});

	$('#packaging_check').change(function() {
		if ($(this).is(':checked')) {
			$('#packaging_count').attr('disabled', false);
			$('#packaging_unit').attr('disabled', false);
		} else {
			$('#packaging_count').val('');
			$('#packaging_count').attr('disabled', true);
			$('#packaging_unit').attr('disabled', true);
		}
	});

});
