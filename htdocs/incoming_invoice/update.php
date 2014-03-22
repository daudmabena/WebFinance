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

if(!isset($_POST['md5'],
    $_POST['paid'],
    $_POST['date'],
    $_POST['provider_id'],
    $_POST['vat'],
    $_POST['total_amount'],
    $_POST['currency'],
    $_POST['action'],
    $_POST['note'],
    $_POST['accounting']))
  die('Missing parameter');

if(empty($_POST['provider_id']))
  $_POST['provider_id'] = 'NULL';

if($_POST['vat'] == '')
  $_POST['vat'] = 'NULL';

if(empty($_POST['total_amount']))
  $_POST['total_amount'] = 'NULL';

# SQL escape
foreach(array('paid', 'date', 'provider_id', 'vat', 'total_amount', 'currency', 'note', 'md5', 'accounting') as $key)
  $_POST[$key] = mysql_real_escape_string($_POST[$key]);

if(empty($_POST['date']))
  $_POST['date'] = 'NULL';
else
  $_POST['date'] = "'$_POST[date]'";  

$q = "
UPDATE incoming_invoice
SET
 paid         = '$_POST[paid]',
 date         = $_POST[date],
 provider_id  = $_POST[provider_id],
 vat          = $_POST[vat],
 total_amount = $_POST[total_amount],
 currency     = '$_POST[currency]',
 note         = '$_POST[note]',
 accounting   = '$_POST[accounting]'
WHERE md5     = '$_POST[md5]'";

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

switch($_POST['action'])
{
  case 'Save':
    header('Location: ./');
    exit;

  case 'Save and auto-advance':
    $q = "
SELECT md5
FROM incoming_invoice
WHERE paid = 'unknown'
  OR date IS NULL
  OR provider_id IS NULL
  OR vat IS NULL
  OR total_amount IS NULL
  OR accounting = 'todo'
LIMIT 1
";

    $result = mysql_query($q)
      or die(mysql_error() . ' ' . $q);

    if(mysql_num_rows($result) != 1)
    {
      header('Location: ./');
      exit;
    }

    $row = mysql_fetch_assoc($result);
    header("Location: edit.php?md5=$row[md5]");
    exit;
}

?>
