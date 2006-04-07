<?php 
// 
// This file is part of « Webfinance »
//
// Copyright (c) 2004-2006 NBI SARL
// Author : Nicolas Bouthors <nbouthors@nbi.fr>
// 
// You can use and redistribute this file under the term of the GNU GPL v2.0
//
?>
<?php

include("inc/main.php");

if (($GLOBALS['HTTP_SERVER_VARS']['REDIRECT_STATUS'] == "404") && (preg_match("/.html$/", $GLOBALS['HTTP_SERVER_VARS']['REDIRECT_URL']))) {
  $new_loc = preg_replace("/(\w+)\.html/", "index.php?file=\\1.html", $GLOBALS['HTTP_SERVER_VARS']['REDIRECT_URL']);
  header("Location: $new_loc");
} elseif (($GLOBALS['HTTP_SERVER_VARS']['REDIRECT_STATUS'] == "404") && (preg_match("!/imgs/boutons/([^\.]+).png$!", $GLOBALS['HTTP_SERVER_VARS']['REDIRECT_URL'], $matches))) {
  // G�n�ration des images dynamiquement.
  header("Location: /cgi-bin/button.cgi?data=".urlencode($matches[1]));
  die();
} else {
  header("Location: /not_found.php");
}

?>
