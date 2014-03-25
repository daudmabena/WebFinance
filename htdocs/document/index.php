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

require_once("../inc/main.php");
require_once('WebfinancePreferences.php');

$prefs = new WebfinancePreferences;

$User = new User();
if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

$roles = 'manager,accounting,employee';
require_once("../top.php");

?>

<script type="text/javascript" language="javascript"
  src="/js/ask_confirmation.js"></script>

<h1><?=_('Documents');?></h1>

<br />

<h3>Upload</h3>
<form method="POST" action="/document/upload.php" enctype="multipart/form-data">
  <input type="file" name="file" />
  <input type="hidden" name="provider_id" value="<?=$_GET[id]?>" />
  <input type="submit" name="upload" value="Upload" title="Supported extensions: PDF, ZIP"/>
</form>

<br/>
<br/>

<h3>Documents</h3>

  <a href="./?status_filter=missing_information">À saisir</a>,
  <a href="./?accounting_filter=todo">à comptabiliser</a>,
  <a href="./">tous</a> <br/>

<form>

<input type="hidden" name="id" value="<?=$_GET[id]?>" />
<input type="hidden" name="onglet" value="documents" />

<table border="1" cellspacing="0" cellpadding="5">

<tr>

  <th>
<select name="type_filter" onchange="this.form.submit();">
  <option value="none">Type</option>
  <option value="invoice" <?=($_GET['type_filter']=='invoice'?'selected':'')?>>Invoice</option>
  <option value="document" <?=($_GET['type_filter']=='document'?'selected':'')?>>Document</option>
  <option value="unknown" <?=($_GET['type_filter']=='unknown'?'selected':'')?>>Unknown</option>
</select>
 </th>

 <th>

<select name="status_filter" onchange="this.form.submit();">
  <option value="none">Status</option>
  <option value="paid" <?=($_GET['status_filter']=='paid'?'selected':'')?>>Paid</option>
  <option value="unpaid" <?=($_GET['status_filter']=='unpaid'?'selected':'')?>>Unpaid</option>
  <option value="unknown" <?=($_GET['status_filter']=='unknown'?'selected':'')?>>Unknown</option>
  <option value="missing_information" <?=($_GET['status_filter']=='missing_information'?'selected':'')?>>Missing info</option>
</select>

 </th>

 <th>

<select name="date_filter" onchange="this.form.submit();">>
  <option value="none">Date</option>

	<?
for($y=2020; $y>=2009; $y--) {
  for($m=12; $m>=1; $m--) {
    $m=sprintf("%02d", $m);
    $selected='';

    if("$y-$m" == $_GET['date_filter'])
      $selected='selected';

    echo "<option value=\"$y-$m\" $selected> $y-$m </option>\n";
  }
}
?>

</select>

</th>

 <th>

<select name="provider_id_filter" onchange="this.form.submit();">>
  <option value="all">Sender</option>

<?
$q = '
SELECT id_client, nom
FROM webfinance_clients
ORDER BY nom';

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

while($row = mysql_fetch_assoc($result))
{
  $selected='';

  if($row['id_client'] == $_GET['provider_id_filter'])
    $selected='selected';

  echo "<option value=\"$row[id_client]\" $selected>$row[nom]</option>\n";
}
?>

</select>

</th>
 <th>Total</th>
 <th>VAT</th>
 <th>Note</th>

 <th>

<select name="accounting_filter" onchange="this.form.submit();">
  <option value="none">Accounting</option>
  <option value="todo" <?=($_GET['accounting_filter']=='todo'?'selected':'')?>>Todo</option>
  <option value="done" <?=($_GET['accounting_filter']=='done'?'selected':'')?>>Done</option>
  <option value="canceled" <?=($_GET['accounting_filter']=='canceled'?'selected':'')?>>Canceled</option>
</select>

 </th>

 <th>Uploader</th>
</tr>


<?
  $where='WHERE 1=1';

# Status filter
switch($_GET['status_filter'])
{
  # Show invoices with missing information
  case 'missing_information':
    $where .= " AND
 (
    d.provider_id IS NULL
    OR d.date IS NULL
    OR d.type = 'unknown'
   OR
   (
     d.type = 'invoice' AND
     (
       d.vat IS NULL
       OR d.total_amount IS NULL
     )
   )
 )";
    break;

  case 'unknown':
  case 'paid':
  case 'unpaid':
    $where .= " AND d.paid = '$_GET[status_filter]'";
    break;
}

# Type filter
switch($_GET['type_filter'])
{
  case 'unknown':
  case 'invoice':
  case 'document':
    $where .= " AND d.type = '$_GET[type_filter]'";
    break;
}

# Accounting filter
switch($_GET['accounting_filter'])
{
  case 'todo':
  case 'done':
  case 'canceled':
    $where .= " AND d.type = 'invoice' AND d.accounting = '$_GET[accounting_filter]'";
    break;
}

# Date filter
if(isset($_GET['date_filter']) and $_GET['date_filter'] != 'none')
{
  $_GET['date_filter'] = mysql_real_escape_string($_GET['date_filter']);
  $where .= " AND d.date BETWEEN '$_GET[date_filter]-01' AND '$_GET[date_filter]-31'";
}

# Provider filter
if(isset($_GET['provider_id_filter']) and $_GET['provider_id_filter'] != 'all')
{
  $_GET['provider_id_filter'] = mysql_real_escape_string($_GET['provider_id_filter']);
  $where .= " AND d.provider_id = $_GET[provider_id_filter]";
}

$q = "
SELECT
  d.md5,
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
  c.nom,
  u.first_name,
  u.last_name
FROM document d
JOIN webfinance_users u ON u.id_user = d.id_user
LEFT OUTER JOIN webfinance_clients c ON d.provider_id = c.id_client
$where
ORDER BY d.date DESC";

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

$i = 0;
while($row = mysql_fetch_assoc($result))
{

  $status2icon = array(
    'paid'    => 'paid.png',
    'unpaid'  => 'not_paid.png',
    'unknown' => 'warning.png',
  );

  $status_icon = $status2icon[$row['paid']];

  $accounting2icon = array(
    'todo'     => 'warning.png',
    'done'     => 'ok.gif',
  );

  $accounting_icon = '';
  if(isset($accounting2icon[$row['accounting']]))
    $accounting_icon = $accounting2icon[$row['accounting']];

  $class = ($i++ % 2 ?'row_even':'row_odd');

?>

<tr class="<?=$class?>">
 <td> <?
   if($row['type']=='unknown')
     echo '<img src="/imgs/icons/warning.png" title="Unknown type">';
  else
    echo $row['type'];
  ?>
 </td>

 <td>
   <a href="/document/edit.php?md5=<?=$row[md5]?>&provider_id_filter=<?=$_GET[provider_id_filter]?>&status_filter=<?=$_GET[status_filter]?>&accounting_filter=<?=$_GET[accounting_filter]?>"><img src="/imgs/icons/edit.png" border="0" title="Edit invoice"></a>

   <?
     if($row['type']=='invoice')
       echo "<img src=\"/imgs/icons/$status_icon\" title=\"Paid status: $row[paid]\">";

  if(!empty($row['ticket_id']))
  {
    $ticket_url = $prefs->prefs['mantis_home_url'] . 'view.php?id=' . $row['ticket_id'];

    echo "<a href=\"$ticket_url\"><img src=\"/imgs/icons/notes.gif\" border=\"0\" title=\"Show ticket\"></a>";
  }
   ?>

 </td>

 <td>
   <?=(empty($row['date'])?'<img src="/imgs/icons/warning.png" title="No date specified">':"$row[date]")?>
 </td>

 <td>
   <?=(empty($row['provider_id'])?'<img src="/imgs/icons/warning.png" title="No provider specified">':"<a href=\"/prospection/fiche_prospect.php?id=$row[provider_id]\">$row[nom]</a>")?>
 </td>

 <td align="right">
   <?
   if($row['type']=='invoice')
     if(empty($row['total_amount']))
       echo '<img src="/imgs/icons/warning.png" title="No total amount specified"/>';
     else
       switch($row['currency'])
       {
         case '$':
           echo "$row[currency]$row[total_amount]";
           break;

         case '€':
           echo "$row[total_amount]&nbsp;$row[currency]";
           break;
       }
  ?>
 </td>

 <td align="right">
   <?
   if($row['type']=='invoice')
     if (empty($row['vat']))
       echo '<img src="/imgs/icons/warning.png" title="No VAT specified"/>';
     else
       switch($row['currency'])
       {
         case '$':
           echo "$row[currency]$row[vat]";
           break;

         case '€':
           echo "$row[vat]&nbsp;$row[currency]";
           break;
       }
  ?>
 </td>

 <td>
   <?=$row['note']?>
 </td>

 <td>
   <?
   if($row['type']=='invoice')

     if (empty($accounting_icon))
       echo $row['accounting'];
     else
       echo "<img src=\"/imgs/icons/$accounting_icon\" title=\"Accounting status: $row[accounting]\"/>";
  ?>
 </td>

 <td>
   <?=$row['first_name']?>&nbsp;<?=$row['last_name']?>
 </td>
</tr>

<?
}
?>

</table>
</form>
