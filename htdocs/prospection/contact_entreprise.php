<?php
/*
 Copyright (C) 2004-2006 NBI SARL, ISVTEC SARL

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
<div style="overflow: auto; height: 300px;">
<table width="100%" border="0" cellspacing="0" cellpadding="2">
  <?php
  global $Client;
  // Liste les personnes contacts pour ce client
  $result = mysql_query("SELECT id_personne,nom,prenom,fonction,mobile,tel,note,email FROM webfinance_personne WHERE client=".$_GET['id']." ORDER BY nom") or wf_mysqldie();
  $count = 1;
  while ($contact = mysql_fetch_object($result)) {
    $contact->note = preg_replace("!\r\n!", "<br/>", $contact->note );
    $class = ($count%2 == 0)?"odd":"even";
    if ($contact->email != "") $mail = sprintf('<a href="mailto:%s"><img class="icon" src="/imgs/icons/mail.gif" alt="%s" /></a>', $contact->email, $contact->email ); else $mail = "";
    if ($contact->tel != "") $tel = sprintf('<img style="vertical-align: middle;" src="/imgs/icons/tel.gif" alt="Tel" />&nbsp;%s<br/>', $contact->tel); else $tel = "";
    if ($contact->mobile != "") $mobile = sprintf('<img style="vertical-align: middle;" src="/imgs/icons/gsm.gif" alt="GSM" />&nbsp;%s<br/>', $contact->mobile); else $mobile = "";
    if ($contact->note != "") $note = sprintf('<img style="vertical-align: middle;" src="/imgs/icons/notes.gif" onMouseOut="UnTip();" onmouseover="Tip(\'%s\')"/>', addslashes($contact->note)); else $note = "";
    $c_mobile	= urlencode(format_phone($mobile));
	$c_tel		= urlencode(format_phone($tel));
	$c_id 		= $_GET['id'];
	
	print <<<EOF
      <tr onmouseover="this.className='row_over';" onmouseout="this.className='row_$class';" class="row_$class" valign="top">
        <td width="16">$mail</td>
        <td onclick="inpagePopup(event, this, 240, 220, 'edit_contact.php?id_personne=$contact->id_personne');"><b>$contact->prenom $contact->nom</b></td>
        <td onclick="inpagePopup(event, this, 240, 220, 'edit_contact.php?id_personne=$contact->id_personne');">$contact->fonction</td>
        <td onclick="ctc('$c_mobile',$c_id)" class="show_hide">$mobile</td>
        <td onclick="ctc('$c_tel',$c_id)" class="show_hide">$tel</td>
        <td>$note</td>
      </tr>
EOF;
    $count++;
  }
  mysql_free_result($result);
  ?>
</table>
</div>
