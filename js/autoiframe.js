// Autorem skryptu jest: SŁAWOMIR KOKŁOWSKI
// www.kurshtml.boo.pl
// Jeśli chcesz wykorzystać ten skrypt na swojej stronie, nie usuwaj tego komentarza!

function auto_iframe(margines)
{
   if (parent != self && document.body && (document.body.scrollHeight || document.body.offsetHeight))
   {
      if (isNaN(parseInt(margines))) margines = 0;

//      if (parent.document.getElementById) parent.document.getElementById('autoiframe').height = 1;
//      else if (parent.document.all) parent.document.all['autoiframe'].height = 1;
      var wysokosc = document.body.offsetHeight;
      if (wysokosc)
      {
        if (parent.document.getElementById)
        {
          parent.document.getElementById('autoiframe').height = wysokosc + margines;
          parent.document.getElementById('autoiframe').scrolling = 'no';
        }
        else if (parent.document.all)
        {
          parent.document.all.autoiframe.height = wysokosc + margines;
          parent.document.all.autoiframe.scrolling = 'no';
        }
      }
   }

  if (parent != self && document.body && (document.body.scrollWidth || document.body.offsetWidth))
  {
      if (isNaN(parseInt(margines))) margines = 0;

//      if (parent.document.getElementById) parent.document.getElementById('autoiframe').width = 1;
//      else if (parent.document.all) parent.document.all['autoiframe'].width = 1;
      var szerokosc = document.body.scrollWidth != undefined ? document.body.scrollWidth : document.body.offsetWidth;
      if (szerokosc)
      {
       if (parent.document.getElementById)
        {
          parent.document.getElementById('autoiframe').width = szerokosc + margines;
          parent.document.getElementById('autoiframe').scrolling = 'no';
        }
        else if (parent.document.all)
        {
          parent.document.all.autoiframe.width = szerokosc + margines;
          parent.document.all.autoiframe.scrolling = 'no';
        }
      }
   }
}

window.onload = auto_iframe;
