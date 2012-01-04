// $Id$

function CheckAll(form, elem, excl)
{
    var i, len, n, e, f,
        form = document.forms[form] ? document.forms[form] : document.getElementById(form),
        inputs = form.getElementsByTagName('INPUT');

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
