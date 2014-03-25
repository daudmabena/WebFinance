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

$roles = 'manager,employee,accounting';
require("../top.php");

CybPHP_Validate::ValidateMD5($_GET['md5']);

$_GET['md5'] = mysql_real_escape_string($_GET['md5']);

$q = "
SELECT
  d.provider_id,
  d.vat,
  d.total_amount,
  d.currency,
  d.date,
  d.paid,
  d.note,
  d.accounting,
  d.type,
  d.ticket_id,
  c.nom
FROM document d
LEFT OUTER JOIN webfinance_clients c ON d.provider_id = c.id_client
WHERE d.md5 = '$_GET[md5]'";

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
          document.getElementById('page_number').innerHTML='page ' + pageNumber;

        });
    });

}

CurrentPDFPage=1;
RenderPDFPage(CurrentPDFPage);
</script>

<a href="./"><h1><?=_('Documents');?></h1></a>

<br />

<table border="0">
<tr>
<td valign="top">

<table border="1" cellspacing="0" cellpadding="5">

<form action="update.php" method="POST">

  <a href="download.php?md5=<?=$_GET[md5]?>"><img src="/imgs/icons/pdf.png" border="0"></a>
<?
  # Show 'delete' button to managers
  if(in_array('manager', explode(',', $User->userData->role)))
    echo "&nbsp; <a href=\"delete.php?md5=$_GET[md5]\" onclick=\"return ask_confirmation('Are you sure?')\"><img src=\"/imgs/icons/delete.png\" border=\"0\"></a>";

  if(!empty($row['ticket_id']))
  {
    require_once('WebfinancePreferences.php');
    $prefs = new WebfinancePreferences;

    $ticket_url = $prefs->prefs['mantis_home_url'] . 'view.php?id=' . $row['ticket_id'];

    echo "&nbsp;<a href=\"$ticket_url\"><img src=\"/imgs/icons/notes.gif\" border=\"0\" title=\"Show ticket\"></a>";
  }
?>

 <input type="hidden" name="md5" value="<?=$_GET[md5]?>" />
 <input type="hidden" name="provider_id_filter" value="<?=$_GET[provider_id_filter]?>" />

  <tr>
   <td> Type </td>

   <td>

 <input id="type-unknown" type="radio" name="type" value="unknown" <?=($row['type']=='unknown'?'checked':'')?>>unknown</input>
 <input id="type-invoice" type="radio" name="type" value="invoice" <?=($row['type']=='invoice'?'checked':'')?>>invoice</input>
 <input id="type-document" type="radio" name="type" value="document" <?=($row['type']=='document'?'checked':'')?>>document</input>

<script>
jQuery(function(){

      jQuery('#type-unknown').click(function(){
          jQuery('.invoice-only').hide();
        });

      jQuery('#type-document').click(function(){
          jQuery('.invoice-only').hide();
        });

      jQuery('#type-invoice').click(function(){
          jQuery('.invoice-only').show();
        });
    });
</script>

</td>

  </tr>

  <tr>
   <td> Sender </td>
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

  <tr class="invoice-only">
   <td> Paid </td>
   <td>

 <input type="radio" name="paid" value="unknown" <?=($row['paid']=='unknown'?'checked':'')?>>unknown</input>
 <input type="radio" name="paid" value="paid" <?=($row['paid']=='paid'?'checked':'')?>>paid</input>
 <input type="radio" name="paid" value="unpaid" <?=($row['paid']=='unpaid'?'checked':'')?>>unpaid</input>

</td>
  </tr>

  <tr class="invoice-only">
   <td> Total amount </td>
   <td> <input name="total_amount" size="8" value="<?=$row[total_amount]?>" type="text"/>
     <select name="currency" onchange="document.getElementById('currency').innerHTML=value;">
      <option value="€" <?=($row['currency']=='€'?'selected':'')?>>€</option>
      <option value="$" <?=($row['currency']=='$'?'selected':'')?>>$</option>
     </select>
 </td>
  </tr>

  <tr class="invoice-only">
   <td> VAT </td>
   <td>
  <input name="vat" size="5" value="<?=$row[vat]?>" type="text"/> <div id="currency" style="display:inline"><?=$row['currency']?></div>
   </td>
  </tr>

  <tr>
   <td> Note </td>
   <td> <input name="note" size="50" value="<?=$row[note]?>" type="text"/> </td>
  </tr>

  <tr class="invoice-only">
   <td> Accounting </td>
   <td>

 <input type="radio" name="accounting" value="todo" <?=($row['accounting']=='todo'?'checked':'')?>>todo</input>
 <input type="radio" name="accounting" value="done" <?=($row['accounting']=='done'?'checked':'')?>>done</input>
 <input type="radio" name="accounting" value="canceled" <?=($row['accounting']=='canceled'?'checked':'')?>>canceled</input>

</td>
  </tr>

<tr>
  <td> </td>
  <td>
  <?
    if(empty($row['ticket_id']))
    {
      # Show 'open ticket' checkbox to managers
      if(in_array('manager', explode(',', $User->userData->role)))
        echo '<input type="checkbox" name="open_ticket" value="1"> Open ticket <br/>';
      else
        echo '<input type="hidden" name="open_ticket" value="0"/>';
    }
  ?>
    <input type="submit" name="action" value="Save"/>
  </td>
</tr>

</table>


</form>

</td>
<td>
<p align="left" style="display:inline"><a href="#" onclick="RenderPDFPage(--CurrentPDFPage);"><<</a></p>
<div id="page_number" style="display:inline">Loading...</div>
<p align="right" style="display:inline"><a href="#" onclick="RenderPDFPage(++CurrentPDFPage);">>></a></p>
<br/>
<canvas id="the-canvas" style="border:1px solid black;"></canvas>

</td>
</tr>
</table>

<script>
  type='<?=$row['type']?>';

if(type == 'invoice')
  jQuery('.invoice-only').show();
else
  jQuery('.invoice-only').hide();
</script>

<?
require("../bottom.php");

?>
