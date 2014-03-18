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

require("../inc/main.php");

$User = new User();
if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

if(!isset($_FILES['file']['name']))
  die('Too few argument');

WebfinanceDocument::ValidateFileName($_FILES['file']['name']);
CybPHP_Validate::ValidateInt($_SESSION['id_user']);

if($_FILES['file']['error'] !== 0)
  die('Unknown upload error from PHP');

# Calculate MD5
$md5 = md5_file($_FILES['file']['tmp_name']);

$upload_file = rtrim('../../incoming_invoice/' . chunk_split($md5, 4, '/'), '/');

# Create directory
system("mkdir -p " . dirname($upload_file), $rc);
if ($rc != 0)
  die('mkdir failed');

// Check if destination file already exists
if(file_exists($upload_file))
  die("File $upload_file already exists");

mysql_query("INSERT INTO incoming_invoice
SET md5='$md5', note='".$_FILES['file']['name']."'")
  or die(mysql_error());

// Move the uploaded file to the final destination
if (!move_uploaded_file($_FILES['file']['tmp_name'], $upload_file))
  die("Failed uploading $upload_file");

header('Location: ./edit.php?md5=' . $md5);
exit;
?>
