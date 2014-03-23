/* 
 * $Id: onglets.js 173 2006-04-24 17:23:21Z nico $
 */

var onglet_shown = '';

function focusOnglet(id) {
  if ((onglet_shown != '') && (onglet_shown != id)) {
    shown = document.getElementById('tab_'+onglet_shown);
    shown.style.display = 'none';

    oldtab = document.getElementById('handle_'+onglet_shown);
    oldtab.className = '';
  }
  toshow = document.getElementById('tab_'+id);
  if (toshow) {
    toshow.style.display='block';
    tab = document.getElementById('handle_'+id);
    tab.className = 'focus';

    f = document.getElementById('main_form');
    if (f) {
      f.focused_onglet.value = id;
    }

    onglet_shown = id;
  }

    var pageurl = '/prospection/fiche_prospect.php?id=' + getQueryVariable('id') +
	'&onglet=' + id;

    if(pageurl != window.location)
	window.history.pushState({path: pageurl}, '', pageurl);
}

// Stolen from
// https://stackoverflow.com/questions/827368/using-the-get-parameter-of-a-url-in-javascript
function getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i=0;i<vars.length;i++) {
    var pair = vars[i].split("=");
    if (pair[0] == variable) {
      return pair[1];
    }
  }
  alert('Query Variable ' + variable + ' not found');
}

function mainFormChanged(f) {
  f.save_button.className = 'unsaved_button';
}
