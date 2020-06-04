var daily = $('#def-daily').val();
var monthly = $('#def-monthly').val();

$("#payments-aggregate").on('change', function () {
    var period = $(this).val();
    if (period !== daily && period !== monthly) {
        $('#payments-calculation').show();
    } else {
        $('#payments-calculation').hide();
    }
});

$(function () {
    $('#payments-calculation').hide();
});
