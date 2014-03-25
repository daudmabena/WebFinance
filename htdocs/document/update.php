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
    $_POST['accounting'],
    $_POST['type']))
  die('Missing parameter');

if(empty($_POST['provider_id']))
  $_POST['provider_id'] = 'NULL';

if($_POST['vat'] == '')
  $_POST['vat'] = 'NULL';

if(empty($_POST['total_amount']))
  $_POST['total_amount'] = 'NULL';

# Replace French comas by international points
$_POST['total_amount'] = str_replace(',', '.', $_POST['total_amount']);
$_POST['vat']          = str_replace(',', '.', $_POST['vat']);

# SQL escape
foreach(array('paid',
    'date',
    'provider_id',
    'vat',
    'total_amount',
    'currency',
    'note',
    'md5',
    'accounting',
    'type',
  ) as $key)
  $_POST[$key] = mysql_real_escape_string($_POST[$key]);

if(empty($_POST['date']))
  $_POST['date'] = 'NULL';
else
  $_POST['date'] = "'$_POST[date]'";  

# Open ticket if needed
$ticket_id = 'NULL';
if(!empty($_POST['open_ticket']))
{
  require_once('WebfinancePreferences.php');
  require_once('WebfinanceCompany.php');

  $prefs = new WebfinancePreferences;

  $wsdl     = $prefs->prefs['mantis_api_url'] . '?wsdl';
  $username = $prefs->prefs['mantis_login'];
  $password = $prefs->prefs['mantis_password'];

  $company = new WebfinanceCompany($_POST['provider_id']);
  $company_info = $company->GetInfo();

  $method = 'http://';
  if (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off')
    $method = 'https://';

  $url = $method . $_SERVER['HTTP_HOST'] . '/document/edit.php?md5=' . $_POST['md5'];

  $issue = array(
    'summary'     => "Document $note from $company_info[name]",
    'description' => "New document $note from $company_info[name]\n\n$url",
    'project'     => array(
      'id' => 381,
    ),
  );

  try
  {
    $mantis = new SoapClient($wsdl);
    $ticket_id = $mantis->mc_issue_add($username, $password, $issue);
  }
  catch(SoapFault $fault)
  {
    echo $fault;
    exit;
  }
}

$q = "
UPDATE document
SET
 paid         = '$_POST[paid]',
 date         = $_POST[date],
 provider_id  = $_POST[provider_id],
 vat          = $_POST[vat],
 total_amount = $_POST[total_amount],
 currency     = '$_POST[currency]',
 note         = '$_POST[note]',
 accounting   = '$_POST[accounting]',
 type         = '$_POST[type]',
 ticket_id    = $ticket_id
WHERE md5     = '$_POST[md5]'";

$result = mysql_query($q)
  or die(mysql_error() . ' ' . $q);

$redirect_url = './?';
if(!empty($_POST['provider_id_filter']))
  $redirect_url = "/prospection/fiche_prospect.php?id=$_POST[provider_id_filter]&onglet=documents&";

if(!empty($_POST['status_filter']))
  $redirect_url .= "&status_filter=$_POST[status_filter]";

if(!empty($_POST['accounting_filter']))
  $redirect_url .= "&accounting_filter=$_POST[accounting_filter]";

header("Location: $redirect_url");
exit;

?>
