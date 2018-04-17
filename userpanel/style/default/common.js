// $Id$

function CheckAll(form, elem, excl)
{
    var i, len, n, e, f,
        inputs = form.getElementsByTagName('INPUT');

    form = document.forms[form] ? document.forms[form] : document.getElementById(form);

    for (i=0, len=inputs.length; i<len; i++) {
        e = inputs[i];

        if (e.type != 'checkbox' || e == elem)
            continue;

        if (excl && excl.length) {
            f = 0;
            for (n=0; n<excl.length; n++)
                if (e.name == excl[n])
                    f = 1;
            if (f)
                continue;
        }

        e.checked = elem.checked;
    }
}

function showOrHide(elementslist)
{
	var elements_array = elementslist.split(" ");
	var part_num = 0;
	while (part_num < elements_array.length)
	{
		var elementid = elements_array[part_num];
		if(document.getElementById(elementid).style.display != 'none')
		{
			document.getElementById(elementid).style.display = 'none';
			setCookie(elementid, '0');
		}
		else
		{
			document.getElementById(elementid).style.display = '';
			setCookie(elementid, '1');
		}
		part_num += 1;
	}
}
