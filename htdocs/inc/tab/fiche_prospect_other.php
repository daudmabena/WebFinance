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
<?php 
// 
// This file is part of « Webfinance »
//
// Copyright (c) 2004-2006 NBI SARL
// Author : Nicolas Bouthors <nbouthors@nbi.fr>
// 
// You can use and redistribute this file under the term of the GNU GPL v2.0
//

global $Client;
?>
  <b>Social :</b><br/>
  <input type="text" class="tva" name="vat_number" value="<?= $Client->vat_number ?>" class="vat_number" /><br />
  <input type="text" class="siren" name="siren" value="<?= $Client->siren ?>" class="siren" />&nbsp;<?= $Client->link_societe ?><br />
  <b>id_mantis:</b> <input type="text" class="id_mantis" name="id_mantis" value="<?= $Client->id_mantis ?>" class="siren" /><br />


 <b><?= _('Login and password:') ?></b><br/>
  <input type="text" name="login" value="<?= $Client->login ?>" class="person" /><br/>
  <input type="text" name="password" value="<?= $Client->password ?>" class="keyring" />
<?php
   if(!empty($Client->email)){
     printf('<a href="javascript:confirmSendInfo(%d,\'%s\');"><img src="../imgs/icons/mail-send.png" title="%s" /></a>',$Client->id,_('Send info to client?'),_('Send information'));
   }
  ?>
<br/>

<br/>
  <b><?= _('IBAN:') ?></b><br/>
<table border="0">
    <tr><td><?= _('Bank name:') ?></td><td><input type="text" size="10" maxsize="24" name="rib_banque" value="<?= addslashes($Client->rib_banque) ?>" style="color: #666;" /></td></tr>
    <tr><td><?= _('IBAN:') ?></td><td><input type="text" size="30" maxlength="50" name="iban" value="<?= addslashes($Client->iban) ?>" style="color: #666;" /></td></tr>
	<tr><td><?= _('BIC:') ?></td><td><input type="text" size="30" maxlength="50" name="bic" value="<?= addslashes($Client->bic) ?>" style="color: #666;" /></td></tr>
</table>
