<?php 
// 
// This file is part of « Webfinance »
//
// Copyright (c) 2004-2006 NBI SARL
// Author : Nicolas Bouthors <nbouthors@nbi.fr>
// 
// You can use and redistribute this file under the term of the GNU GPL v2.0
//
// $Id$

require("../inc/main.php");
$roles = 'any';
$title = _('Graphics');
array_push($extra_js, '/js/onglets.js');
require("../top.php");

$tab = new TabStrip();
global $User;
$User->getInfos();

?>
<script type="text/javascript">

function updateCashFlow(chk) {
  i = document.getElementById('cashflow_img');
  if (!i) return;
  url = i.src;
  url = url.replace(/&movingaverage=[0-9]+/, '');
  if (chk.checked) {
    url = url+'&movingaverage=1';
  } else {
    url = url+'&movingaverage=0';
  }
  i.src = url;
}

</script>
<form>
<?php

if ($User->isAuthorized('accounting,manager')) {
  $cashflow = <<<EOF
<input type="checkbox" name="moving_average" onchange="updateCashFlow(this)"> Display moving average
<img id="cashflow_img" alt="cashflow" src="cashflow.php?width=850&height=500&movingaverage=0" width="850" height="500" />
EOF;
  $tab->addTab(_('Cashflow'), $cashflow, 'cashflow');
}

if ($User->isAuthorized('accounting,employee,manager')) {
  $clientincome = <<<EOF
<img id="clients_income" alt="clients_income" src="clients_income.php?width=850&height=400" width="850" height="400" />
EOF;
  $tab->addTab(_('Client income'), $clientincome, 'client_income');
}

if (isset($_GET['tab']))
  $tab->setFocusedTab($_GET['tab']);
$tab->realise();

print "</form>";
$Revision = '$Revision$';
require("../bottom.php");
?>
