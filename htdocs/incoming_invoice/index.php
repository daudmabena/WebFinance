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

include("../inc/main.php");

$User = new User();
if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

$roles = 'manager,employee';
include("../top.php");
include("nav.php");

?>

<script type="text/javascript" language="javascript"
  src="/js/ask_confirmation.js"></script>

<h1><?=_('Incoming invoices');?></h1>

<br />

<h3>Upload</h3>
<form method="POST" action="upload.php" enctype="multipart/form-data">
  <input type="file" name="file" />
  <input type="submit" name="upload" value="Upload"/>
</form>

<br/>
<br/>

<h3>Invoices</h3>

<form>
<table width="100%" border="1" cellspacing="0" cellpadding="5">

<tr>
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
  <option value="all">Providers</option>

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
</tr>


<?
  $where='WHERE 1=1';

# Status filter
switch($_GET['status_filter'])
{
  # Show invoices with missing information
  case 'missing_information':
    $where .= ' AND
  (ii.provider_id IS NULL
    OR ii.vat IS NULL
    OR ii.total_amount IS NULL
    OR ii.date IS NULL
  )';
    break;

  case 'unknown':
  case 'paid':
  case 'unpaid':
    $where .= " AND ii.paid = '$_GET[status_filter]'";
    break;
}

# Date filter
if(isset($_GET['date_filter']) and $_GET['date_filter'] != 'none')
{
  $_GET['date_filter'] = mysql_real_escape_string($_GET['date_filter']);
  $where .= " AND ii.date BETWEEN '$_GET[date_filter]-01' AND '$_GET[date_filter]-31'";
}

# Provider filter
if(isset($_GET['provider_id_filter']) and $_GET['provider_id_filter'] != 'all')
{
  $_GET['provider_id_filter'] = mysql_real_escape_string($_GET['provider_id_filter']);
  $where .= " AND ii.provider_id = $_GET[provider_id_filter]";
}

$q = "
SELECT ii.id, ii.provider_id, ii.vat, ii.total_amount, ii.currency, ii.date, ii.paid, ii.note, c.nom
FROM incoming_invoice ii
LEFT OUTER JOIN webfinance_clients c ON ii.provider_id = c.id_client
$where
ORDER BY ii.date DESC";

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

while($row = mysql_fetch_assoc($result))
{

  $status2icon = array(
    'paid'    => 'paid.png',
    'unpaid'  => 'not_paid.png',
    'unknown' => 'warning.png',
  );

  $status_icon = $status2icon[$row['paid']];
?>

<tr>
 <td>
   <img src="/imgs/icons/<?=$status_icon?>" title="Paid status">
   <a href="edit.php?id=<?=$row[id]?>"><img src="/imgs/icons/edit.png" border="0"></a>
 </td>
 <td> <?=(empty($row['date'])?'<img src="/imgs/icons/warning.png">':"$row[date]")?> </td>
 <td> <?=(empty($row['provider_id'])?'<img src="/imgs/icons/warning.png">':"<a href=\"/prospection/fiche_prospect.php?id=$row[provider_id]\">$row[nom]</a>")?> </td>
 <td> <?=(empty($row['total_amount'])?'<img src="/imgs/icons/warning.png">':"$row[total_amount]$row[currency]")?> </td>
 <td> <?=(empty($row['vat'])?'<img src="/imgs/icons/warning.png">':"$row[vat]$row[currency]")?> </td>
 <td> <?=$row['note']?> </td>
</tr>

<?
}
?>

</table>
</form>

<?
include("../bottom.php");

?>
