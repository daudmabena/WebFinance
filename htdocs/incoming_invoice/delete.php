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

$roles = 'manager,employee';

CybPHP_Validate::ValidateMD5($_GET['md5']);

$_GET['md5'] = mysql_real_escape_string($_GET['md5']);

$q = "DELETE FROM incoming_invoice
WHERE md5='$_GET[md5]'";

mysql_query($q)
  or die(mysql_error() . ' ' . $q);

if(mysql_affected_rows() != 1)
  die('Invalid MD5');

unlink(rtrim('../../incoming_invoice/' . chunk_split($_GET['md5'], 4, '/'), '/'));

header('Location: ./');
exit;

?>
