// $Id$

function savecolumnstate() {
    var colchecked = [];
    $.each($("input[class='columns']:checked"), function() {
        colchecked.push(this.id)
    });

    var jsonString = JSON.stringify(colchecked);

    $.ajax({
        url: '?m=vlanlist',
        type: "POST",
        data: { visiblecolumns : jsonString },
    });
}
