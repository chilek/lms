/*
 * Funkcje pomocnicze interfejsu projektów inwestycyjnych
 */

function setNNProject() {
  var s = document.getElementById('NNproject');
  var n = document.getElementById('NNprojectname');
  if (s && s.value == '-1') {
    n.style.display = 'inline-block';
  } else {	  
    n.style.display = 'none';
  } 
 }

 function setNNOwner() {
  var s = document.getElementById('NNownership');
  var n = document.getElementById('NNcoowner');
  
  if (s && (s.value == '1' || s.value == '2')) {
    n.style.display = 'inline-block';
  } else {
    n.style.display = 'none';
  } 

 }
