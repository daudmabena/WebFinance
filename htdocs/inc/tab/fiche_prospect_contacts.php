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

global $Client, $User;
?>
  <div id="LoadPage" class="slidingDiv"> </div>

  <table border="0" width="100%"><tr valign="top"><td>
  <br/>
  <b><?= _('Contact name:') ?></b> <input type="text" name="addr1" value="<?= preg_replace('/"/', '\\"', $Client->addr1) ?>" style="color: #666; width: 200px" /><br/>
  <b><?= _('Address 1:') ?></b> <input type="text" name="addr2" value="<?= preg_replace('/"/', '\\"', $Client->addr2) ?>" style="color: #666; width: 200px" /><br/>
  <b><?= _('Address 2:') ?></b> <input type="text" name="addr3" value="<?= preg_replace('/"/', '\\"', $Client->addr3) ?>" style="color: #666; width: 200px" /><br/>
  <input type="text" name="cp" value="<?= preg_replace('/"/', '\\"', $Client->cp) ?>" style="text-align: center; color: #666; width: 48px" /><input type="text" name="ville" value="<?= $Client->ville ?>" style="color: #666; width: 148px" /><br/>
  <input type="text" name="pays" value="<?= preg_replace('/"/', '\\"', $Client->pays) ?>" style="color: #666; width: 80px; text-align: center;" />Lang: <select name="clt_language"><option value='fr_FR' <? if($Client->language == 'fr_FR') { ?>selected <? } ?>>French</option><option value='en_US' <? if($Client->language == 'en_US') { ?>selected <? } ?>>English</option></select><br/>
  <table border="0">
    <tr>
      <td>Type</td>
      <td><select style="font-size: 10px; width: 200px;" name="id_company_type"><?
  $result = mysql_query("SELECT id_company_type,nom FROM webfinance_company_types ORDER BY nom");
  while ($t = mysql_fetch_object($result)) {
    printf('<option value="%s" %s>%s</option>'."\n", $t->id_company_type, ($Client->id_company_type == $t->id_company_type)?"selected":"", ucfirst($t->nom));
  }
  ?>
  </select></td>
  </tr>

    <tr>
      <td><?= _('VAT:') ?></td>
      <td><input type="text" name="vat" value="<?=$Client->vat?>" style="color: #666; width: 100px; text-align: center;" />%</td>
   </tr>

    <tr>
      <td><?= _('RCS:') ?></td>
      <td>
	<input type="text" name="rcs" value="<?= preg_replace('/"/', '\\"', $Client->rcs) ?>" style="color: #666; width: 100px; text-align: center;" />

<? if(!empty($Client->rcs)) { ?>
<a href="http://www.societe.com/cgi-bin/recherche?rncs=<?=substr(preg_replace('/[^\d]/', '', $Client->rcs), 0, 9); ?>">
  <img src="http://www.societe.com/favicon.ico" width="13">
  </a>
<? } ?>

      </td>
    </tr>

    <tr>
      <td><?= _('Capital:') ?></td>
      <td><input type="text" name="capital" value="<?= preg_replace('/"/', '\\"', $Client->capital ) ?>" style="color: #666; width: 100px; text-align: center;" /></td>
   </tr>

    <tr>
      <td><?= _('Business entity:') ?></td>
      <td>
<select name="id_business_entity">
   <option value="0"></option>
<? foreach($Client->GetBusinessEntities()
     as $business_entity_id => $business_entity_name)
   {
     echo "<option value=\"$business_entity_id\"";

     if($business_entity_name === $Client->business_entity)
       echo 'selected';

     echo ">$business_entity_name</option>";
   }
?>

</select>
</td>
   </tr>

    <tr>
      <td style="white-space: nowrap;"><?= _('Contract signer:') ?></td>
      <td>
	<input name="contract_signer"
	       size="30"
	       value="<?=$Client->contract_signer?>"
	       type="text"
	       />
      </td>
    </tr>

    <tr>
      <td style="white-space: nowrap;"><?= _('Contract signer role:') ?></td>
      <td>
<select name="id_contract_signer_role">
   <option value="0"></option>
<? foreach($Client->GetContractSignerRoles()
     as $contract_signer_id => $contract_signer_role)
   {
     echo "<option value=\"$contract_signer_id\"";

     if($contract_signer_role === $Client->contract_signer_role)
       echo 'selected';

     echo ">$contract_signer_role</option>";
   }
?>

</select>
</td>
   </tr>

  </table>

 
  <b><?= _('Phone and URL:') ?></b><br/>
  <input type="text" name="tel" value="<?= addslashes(format_phone($Client->tel)) ?>" class="tel" /><? if($User->prefs->ctc_ovh_login != null AND !empty($Client->tel)) { ?> <a href="#" onclick="ctc('<?=urlencode(format_phone($Client->tel))?>',<?=$Client->id ?>)" class="show_hide">> Call</a><? } ?><br/>
  <input type="text" name="web" value="<?= addslashes($Client->web) ?>" class="web" /><br/>
<?php

  $mails = explode(',', $Client->email);

  foreach($mails as $mail)
   echo '<input type="text" name="email[]" value="'.$mail.'" class="email" /><br/>';

?>
  <input type="text" name="email[]" class="email" /><br/>
<input type="text" name="fax" value="<?= $Client->fax ?>" class="fax" />

<br/>
  <?= $Client->link_societe ?>
  </td><td width="100%">

  <b><?= _('Contacts :') ?></b><br/>
  <?include "contact_entreprise.php" ?>
  <div style="text-align: center;">
<?php
    if($User->hasRole("manager",$_SESSION['id_user']) || $User->hasRole("employee",$_SESSION['id_user']) ){
      printf("<a href=\"#\" onclick=\"inpagePopup(event, this, 240, 220, 'edit_contact.php?id=_new&id_client=%d');\">%s</a>" , $Client->id , _('Add a new contact'));
    }
?>
  </div>
  </td>

  </table>
