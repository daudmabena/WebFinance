<?php
/*
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
?>
<?php
//
// This file is part of « Backoffice NBI »
//
// Copyright (c) 2004-2006 NBI SARL
// Author : Nicolas Bouthors <nbouthors@nbi.fr>
//
// You can use and redistribute this file under the term of the GNU LGPL v2.0
//
?>
<?php

// $Id: backoffice.php 531 2007-06-13 12:32:31Z thierry $

ini_set('session.gc_maxlifetime',3600);
session_start();
require($GLOBALS['_SERVER']['DOCUMENT_ROOT']."/inc/dbconnect.php");
require($GLOBALS['_SERVER']['DOCUMENT_ROOT']."/inc/User.php");
require($GLOBALS['_SERVER']['DOCUMENT_ROOT']."/inc/Facture.php");
require($GLOBALS['_SERVER']['DOCUMENT_ROOT']."/inc/Client.php");
$language='fr_FR';
foreach(array(LC_MESSAGES, LC_TIME, LC_MONETARY, LC_CTYPE) as $locale)
  setlocale($locale, $language.".UTF-8")
    or die("locale $locale language failed $language");

function parselogline($str) {
  if (preg_match("/(user|fa|client):([0-9]+)/", $str)) {
    while (preg_match("/(user|fa|client):([0-9]+)/", $str, $matches)) {
      switch ($matches[1]) {
        case "fa":
          $result = mysql_query("SELECT num_facture FROM webfinance_invoices WHERE id_facture=".$matches[2]);
          list($num_facture) = mysql_fetch_array($result);
          mysql_free_result($result);
          if (empty($num_facture)) {
            $str = preg_replace("/".$matches[0]."/", '<i>facture supprimée</i>', $str);
          } else {
            $str = preg_replace("/".$matches[0]."/", '<a href="/prospection/edit_facture.php?id_facture='.$matches[2].'">'.$num_facture.'</a> <a href="/prospection/getfacture.php?id='.$matches[2].'"><img src="/imgs/icons/pdf.png" valign="bottom"></a>', $str);
          }
          break;
        case "user":
          $result = mysql_query("SELECT login FROM webfinance_users  WHERE id_user=".$matches[2]);
          list($login) = mysql_fetch_array($result);
          mysql_free_result($result);
          $str = preg_replace("/".$matches[0]."/", '<a href="/admin/fiche_user.php?id='.$matches[2].'">'.$login.'</a>', $str);
          break;
        case "client":
          $result = mysql_query("SELECT nom FROM webfinance_clients WHERE id_client=".$matches[2]);
          list($client) = mysql_fetch_array($result);
          mysql_free_result($result);
          $str = preg_replace("/".$matches[0]."/", '<a href="/prospection/fiche_prospect.php?id='.$matches[2].'">'.$client.'</a>', $str);
          break;
      }
    }
  }
  return $str;
}

function randomPass() {
  $passwd = "";

  $passwd .= chr(96+rand(1,26));
  $passwd .= chr(96+rand(1,26));
  $passwd .= rand(0,9);
  $passwd .= rand(0,9);
  $passwd .= chr(96+rand(1,26));
  $passwd .= chr(96+rand(1,26));
  $passwd .= rand(0,9);
  $passwd .= rand(0,9);

  print $passwd;
}

// Logs a message ala syslog
function logmessage($msg, $id_client = 'NULL', $id_facture = 'NULL') 
{
    $id = (empty($_SESSION['id_user']))?-1:$_SESSION['id_user'];
    $msg = preg_replace("/'/", "\\'", $msg );
    $msg = preg_replace('/"/', "\\'", $msg );
    
    $query = 
        sprintf("INSERT INTO webfinance_userlog " .
                " (log,date,id_user,id_client,id_facture) VALUES('%s', now(), %s, %s, %s) ", $msg, $id, $id_client,$id_facture);
    mysql_query($query) or wf_mysqldie();
}


header("Content-Type: text/html; charset=utf-8");

// This array starts empty here and is filled by pages
$_SESSION['preload_images'] = array();
$extra_css = array();
$extra_js = array();


?>
