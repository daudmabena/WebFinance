<?php
/*
 Copyright (C) 2004-2012 NBI SARL, ISVTEC SARL

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

$User = new User();

if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

CybPHP_Validate::ValidateMD5($_GET['md5']);

$file = rtrim('../../document/' . chunk_split($_GET['md5'], 4, '/'), '/');

$fp = fopen($file, 'r')
  or die("Unable to open $file");

header("Content-Type: application/pdf");
header("Content-Length: " . filesize($file));
header("Content-Disposition: attachment; filename=$_GET[md5].pdf");

// Tell the browser to keep our document in cache for 1 year
// seconds, minutes, hours, days
$expires = 60*60*24*365;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

fpassthru($fp);
fclose($fp);
exit;

?>
