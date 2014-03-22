<?php
/*
 Copyright (C) 2014 NBI SARL, ISVTEC SARL

   This file is part of Webfinance.

   Webfinance is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

    Webfinance is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Webfinance; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require("../inc/main.php");

$User = new User();
if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

# Load extra Javacript
array_push($extra_js, "/js/ask_confirmation.js");

# Load JQuery UI Javascript
array_push($extra_js, '/javascript/jquery-ui/jquery-ui.js');

# Load Datepicker CSS
array_push($extra_css, '/javascript/jquery-ui/css/smoothness/jquery-ui.css');

# Load PDF Reader in JavaScript
array_push($extra_js, '/javascript/pdf/pdf.js');

$roles = 'manager,employee';
require("../top.php");

CybPHP_Validate::ValidateMD5($_GET['md5']);

$_GET['md5'] = mysql_real_escape_string($_GET['md5']);

$q = "
SELECT
  ii.provider_id,
  ii.vat,
  ii.total_amount,
  ii.currency,
  ii.date,
  ii.paid,
  ii.note,
  c.nom
FROM incoming_invoice ii
LEFT OUTER JOIN webfinance_clients c ON ii.provider_id = c.id_client
WHERE ii.md5 = '$_GET[md5]'";

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

if(mysql_num_rows($result) != 1)
  die('Invalid MD5');

$row = mysql_fetch_assoc($result);

?>

<script>
 $(function() {
     $( "#datepicker" ).datepicker({ dateFormat: "yy-mm-dd" });
   });
</script>

<!-- PDF viewer -->
<script type="text/javascript">
   PDFJS.workerSrc = '/javascript/pdf/pdf.js';

'use strict';

function RenderPDFPage(pageNumber) {

//
// Fetch the PDF document from the URL using promices
//
PDFJS.getDocument('download.php?md5=<?=$_GET[md5]?>').then(function(pdf) {
  // Using promise to fetch the page
  pdf.getPage(pageNumber).then(function(page) {
    var scale = 1.2;
    var viewport = page.getViewport(scale);

    //
    // Prepare canvas using PDF page dimensions
    //
    var canvas = document.getElementById('the-canvas');
    var context = canvas.getContext('2d');
    canvas.height = viewport.height;
    canvas.width = viewport.width;

    //
    // Render PDF page into canvas context
    //
    var renderContext = {
      canvasContext: context,
      viewport: viewport
    };
    page.render(renderContext);
  });
});
}

CurrentPDFPage=1;
RenderPDFPage(CurrentPDFPage);
</script>

<a href="./"><h1><?=_('Incoming invoices');?></h1></a>

<br />

<table border="0">
<tr>
<td valign="top">

<table border="1" cellspacing="0" cellpadding="5">

<form action="update.php" method="POST">

  <a href="download.php?md5=<?=$_GET[md5]?>"><img src="/imgs/icons/pdf.png" border="0"></a>
  &nbsp;
  <a href="delete.php?md5=<?=$_GET[md5]?>" onclick="return ask_confirmation('Are you sure?')"><img src="/imgs/icons/delete.png" border="0"></a>

 <input type="hidden" name="md5" value="<?=$_GET[md5]?>" />

  <tr>
   <td> Paid </td>
   <td>

 <input type="radio" name="paid" value="unknown" <?=($row['paid']=='unknown'?'checked':'')?>>unknown</input>
 <input type="radio" name="paid" value="paid" <?=($row['paid']=='paid'?'checked':'')?>>paid</input>
 <input type="radio" name="paid" value="unpaid" <?=($row['paid']=='unpaid'?'checked':'')?>>unpaid</input>

</td>
  </tr>

  <tr>
   <td> Provider </td>
   <td>
    <select name="provider_id">
     <option value=""></option>
 <?
$q = '
SELECT id_client, nom
FROM webfinance_clients
ORDER BY nom';

$result_provider = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

while($row_provider = mysql_fetch_assoc($result_provider))
{
  $selected='';

  if($row_provider['id_client'] == $row['provider_id'])
    $selected='selected';

  echo "<option value=\"$row_provider[id_client]\" $selected>$row_provider[nom]</option>\n";
}
?>

   </select>
   &nbsp;
   <a href="/prospection/fiche_prospect.php?action=_new" onclick="return ask_confirmation('Are you sure you want to create a new provider?')" target="_blank">Add provider</a>

   </td>
  </tr>

  <tr>
   <td> Date </td>
   <td> <input name="date" size="10" value="<?=$row['date']?>" type="text" id="datepicker"/> </td>
  </tr>

  <tr>
   <td> Total amount </td>
   <td> <input name="total_amount" size="8" value="<?=$row[total_amount]?>" type="text"/>
     <select name="currency" onchange="document.getElementById('currency').innerHTML=value;">
      <option value="€" <?=($row['currency']=='€'?'selected':'')?>>€</option>
      <option value="$" <?=($row['currency']=='$'?'selected':'')?>>$</option>
     </select>
 </td>
  </tr>

  <tr>
   <td> VAT </td>
   <td>
  <input name="vat" size="5" value="<?=$row[vat]?>" type="text"/> <div id="currency" style="display:inline"><?=$row['currency']?></div>
   </td>
  </tr>

  <tr>
   <td> Note </td>
   <td> <input name="note" size="80" value="<?=$row[note]?>" type="text"/> </td>
  </tr>

<tr>
  <td> </td>
  <td>
    <input type="submit" name="action" value="Save and auto-advance" title="Save this invoice and go to the next invoice that needs information"/>
    <br/>
    <input type="submit" name="action" value="Save"/>
  </td>
</tr>

</table>


</form>

</td>
<td>
<p align="left" style="display:inline"><a href="#" onclick="RenderPDFPage(--CurrentPDFPage);"><<</a></p> / <p align="right" style="display:inline"><a href="#" onclick="RenderPDFPage(++CurrentPDFPage);">>></a></p>
<br/>
<canvas id="the-canvas" style="border:1px solid black;"></canvas>

</td>
</tr>
</table>

<?
require("../bottom.php");

?>
