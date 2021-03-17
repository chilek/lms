// $Id$

function savecolumnstate() {
    var colchecked = [];
    $.each($("input[class='columns']:checked"), function() {
        colchecked.push(this.id)
    });

    $.ajax({
        url: '?m=vlanlist',
        type: "POST",
        data: { visiblecolumns : colchecked },
        dataType: 'json',
    });
}
