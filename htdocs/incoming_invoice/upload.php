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

function upload_file($file = '', $filename = '')
{
  // Calculate MD5
  $md5 = md5_file($file);

  $destination_file = rtrim('../../incoming_invoice/' . chunk_split($md5, 4, '/'), '/');

  // Create directory
  $cmd = "mkdir -p " . dirname($destination_file);
  system($cmd, $rc);
  if ($rc != 0)
    die("$cmd failed");

  // Check if destination file already exists
  if(file_exists($destination_file))
  {
    $md5 = md5_file($destination_file);
    echo "File <a href=\"edit.php?md5=$md5\">$filename</a> already exists, skipped<br/>\n";
    return FALSE;
  }

  $note = mysql_real_escape_string($filename);
  mysql_query("INSERT INTO incoming_invoice SET md5='$md5', note='$note'")
    or die(mysql_error());

  // Move the uploaded file to the final destination
  if (!rename($file, $destination_file))
    die("Failed renaming $file to $destination_file");

  echo "File <a href=\"edit.php?md5=$md5\">$filename</a> uploaded<br/>\n";
  return TRUE;
}

function upload_zip($zip_file = '')
{
  // Create temporary directory
  exec('mktemp -d', $output, $rc);
  if ($rc != 0)
    die('mktemp failed');

  $temp_dir = $output[0];
  if(!is_dir($temp_dir))
    die('Temporary directory not created');

  // Unzip
  system("unzip -q -d $temp_dir $zip_file", $rc);
  if ($rc != 0)
    die('unzip failed');

  unlink($zip_file);

  // Fetch directory listing
  $file_list = glob("$temp_dir/*");

  // Fetch file information
  $files = array();
  foreach($file_list as $file)
  {
    if(!preg_match('/\.pdf$/', $file))
      continue;

    upload_file($file, basename($file));
  }
}

require("../inc/main.php");

$User = new User();
if(!$User->isAuthorized("manager,accounting,employee")){
  $_SESSION['came_from'] = $_SERVER['REQUEST_URI'];
  header("Location: /login.php");
  exit;
}

if(!isset($_FILES['file']['name']))
  die('Too few argument. PHP upload limit reached?');

WebfinanceDocument::ValidateFileName($_FILES['file']['name']);
CybPHP_Validate::ValidateInt($_SESSION['id_user']);

if($_FILES['file']['error'] !== 0)
  die('Unknown upload error from PHP');

$file_extension = preg_replace('/.*\./', '', $_FILES['file']['name']);

switch($file_extension)
{
  case 'zip':
    upload_zip($_FILES['file']['tmp_name']);
    break;

  case 'pdf':
  case 'PDF':
    upload_file($_FILES['file']['tmp_name'], $_FILES['file']['name']);
    break;

  default:
    die("Unknown file extension $file_extension. Only PDF and ZIP are supported.");
    break;
}

?>

<a href="./">done</a>
