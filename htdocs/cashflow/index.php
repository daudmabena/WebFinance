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

// $Id$

require("../inc/main.php");
$title = _('Cashflow');
$roles="manager,accounting";
require("../top.php");
require("nav.php");

$User = new User();

$user = $User->getInfos($_SESSION['id_user']);

// Number of transaction to show on one page
if(is_numeric($User->prefs->transactions_per_page) and $User->prefs->transactions_per_page>0 )
  $transactions_per_page = $User->prefs->transactions_per_page;
else
  $transactions_per_page = 50;

?>
<script type="text/javascript">

function ask_confirmation(txt) {
  resultat = confirm(txt);
  if(resultat=="1"){
      return true;
  } else {
      return false;
  }
}

function check(){
  if(!document.form.chk.checked){
    alert("Choose a transaction");
    return false;
  }else{
    return true;
  }
}

var specific_shown = null;

function updateCheck(id) {
  checkbox = document.getElementById('chk_'+id);
  f_action = document.getElementById('action_form');
  sel = f_action.selected_transactions.value;
  var regexp = ','+id+','; // This produces an INTENDED double coma !
  sel = sel.replace(regexp, '')
  if (checkbox.checked) {
    sel += ','+id+',';
  }
  f_action.selected_transactions.value = sel;
}
function changeAction(sel) {
  if (specific_shown)
    specific_shown.style.display = 'none';
  specific_options = document.getElementById( 'action_' + sel.options[sel.selectedIndex].value );
  if (specific_options) {
    specific_options.style.display = 'block';
    specific_shown = specific_options;
  }
}

function submitAction(f) {

  f.selected_transactions.value = '';
  check_form = document.getElementById('checkboxes');
  for (i=0 ; i<check_form.elements.length ; i++) {
    el = check_form.elements[i];

    if (m = el.id.match(/chk_(.*)/)) { // is a checkbox
      if (el.checked) { // is checked
        f.selected_transactions.value = f.selected_transactions.value + m[1]+',';
      }
    }
  }
  f.selected_transactions.value = f.selected_transactions.value.replace(/,$/, '');

  if (f.selected_transactions.value == '') {
    alert('<?= _('You must select at least one transaction to apply an action !!') ?>');
    return false;
  }

  f.submit();
}

function checkAll(c) {
  for (i=0 ; i<c.form.elements.length ; i++) {
    el = c.form.elements[i];

    if (m = el.id.match(/chk_(.*)/)) {
      // if needed, m[1] is id_transaction
      el.checked = c.checked;
    }
  }
}
</script>

<?


// Find the categories names and colors
$categories = array();
$result = mysql_query("SELECT id,name,color FROM webfinance_categories ORDER BY id");
while ($cat = mysql_fetch_assoc($result)) {
  array_push($categories, $cat);
}
mysql_free_result($result);


// Setup the default filter if none is given
extract($_GET);

if ((!count($filter['shown_cat'])) || ($filter['shown_cat']['check_all'] == "on")) {
  $result = mysql_query("SELECT id FROM webfinance_categories");
    $filter['shown_cat'][1] = "on";
  while (list($id) = mysql_fetch_array($result)) {
    $filter['shown_cat'][$id] = "on";
  }

  mysql_free_result($result);

  unset($filter['shown_cat']['check_all'] );
}

// Calculate balance for each transaction
if ($filter['id_account'] != 0) { $w = "WHERE id_account=".$filter['id_account']; }
$req=mysql_query("SELECT id, amount FROM webfinance_transactions $w ORDER BY date") or wf_mysqldie();
$balance_yesterday=0;
$balance_lines=array();
while ($row=mysql_fetch_assoc($req)) {
  $balance_lines[$row['id']]=$balance_yesterday+$row['amount'];
  $balance_yesterday+=$row['amount'];
}

// ---------------------------------------------------------------------------------------------------------------------
// Check filter data coherence :
if (!preg_match("!^[0-9.-]+$!", $filter['amount'])) { $filter['amount'] = ""; }  // Search by amount must be numeric

if (preg_match("!^([0-9.,]+)-([0-9.,]+)$!", $filter['amount'], $foo)) {
  if ($foo[1] > $foo[2]) {
    $filter['amount'] = $foo[2]."-".$foo[1]; // Special blondes check, invert amount range
  }
}

// If no date range is specified use "current month"
$days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
if ((strftime("%Y") % 4 == 0) && (strftime("%Y") % 100 != 0)) { // Leap year
  $days_in_month[1] = 29;
}

if (($filter['start_date'] != "") && ($filter['end_date'] == "")) {
  // If start_date is given and NOT end_date then we show transaction between
  // start_date and current date.
  $filter['end_date'] = strftime("%d/%m/%Y");
}
if (($filter['start_date'] == "") && ($filter['end_date'] != "")) {
  // If end_date is given and NOT start_date then we show transaction from the
  // start of the company to end_date
  $result = mysql_query("SELECT value FROM webfinance_pref WHERE type_pref='societe' AND owner=-1");
  list($value) = mysql_fetch_array($result);
  mysql_free_result($result);
  $company = unserialize(base64_decode($value));

  $filter['start_date'] = $company->date_creation;
}
if ($filter['start_date'] == "") { $filter['start_date'] = strftime("01/%m/%Y"); }
if ($filter['end_date'] == "") { $filter['end_date'] = strftime($days_in_month[strftime("%m")-1]."/%m/%Y"); }

preg_match( "!([0-9]{2})/([0-9]{2})/([0-9]{4})!", $filter['start_date'],$foo);
$ts_start_date = mktime( 0,0,0, $foo['2'], $foo['1'], $foo['3']);
preg_match( "!([0-9]{2})/([0-9]{2})/([0-9]{4})!", $filter['end_date'],$foo);
$ts_end_date = mktime( 0,0,0, $foo['2'], $foo['1'], $foo['3']);

// end date must be after begin date. If not reverse them
if ($ts_start_date > $ts_end_date) {
  // Reverse : switch timestamps and formated dates
  $foo = $filter['start_date'];
  $filter['start_date'] = $filter['end_date'];
  $filter['end_date'] = $foo;

  $foo = $ts_start_date;
  $ts_start_date = $ts_end_date;
  $ts_end_date = $foo;
}

// End check filter data coherence
// ---------------------------------------------------------------------------------------------------------------------

$old_query_string = $GLOBALS['_SERVER']['QUERY_STRING']; // FIXME : Better than pass the big $filter around by get we sould store it in the session.
$GLOBALS['_SERVER']['QUERY_STRING'] = preg_replace("/sort=\w*\\&*+/", "", $GLOBALS['_SERVER']['QUERY_STRING']);

// print "-".$GLOBALS['_SERVER']['QUERY_STRING']."--";

?>

<table border="0" cellspacing="5" cellpadding="0" width="100%">
<tr style="vertical-align: top;">
  <td width="100%">
    <?php // Transaction listing ?>
    <form id="checkboxes" name="checkboxes"> <? // This form does not submit !! It's only here to allow apearance of checkboxes ?>
    <table border="0" cellspacing="0" width="750" cellpadding="3" class="framed">
      <tr style="text-align: center;" class="row_header">
        <td><input onmouseover="return escape('<?= _('Check and unchecks all transactions shown') ?>');" type="checkbox" onchange="checkAll(this);" /></td>
        <td></td>
        <td><a href="?sort=date&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Date') ?></a></td>
        <td><a href="?sort=category&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Category') ?></a>/<a href="?sort=color&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Color') ?></a></td>
        <td><a href="?sort=type&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Type') ?></a></td>
        <td><a href="?sort=desc&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Description') ?></a></td>
        <td><a href="?sort=amount&<?= $GLOBALS['_SERVER']['QUERY_STRING'] ?>"><?= _('Amount') ?></a></td>
        <td><?= _('Balance') ?></td>
      </tr>
     <?php

     // -------------------------------------------------------------------------------------------------------------
     // Begin where clause
     // Filter on type of transaction
     if (count($filter['shown_cat'])) {
       $where_clause .= " (";
       foreach ($filter['shown_cat'] as $catid=>$dummy) {
         $where_clause .= "id_category=$catid OR ";
       }
       $where_clause = preg_replace("/ OR $/", ")", $where_clause);
     }

     $limit_clause = sprintf(" LIMIT %d,%d", $filter['page']*$transactions_per_page, $transactions_per_page );

     // Filter on dates
     if (($ts_start_date != 0) && ($ts_end_date != 0)) {
       $where_clause .= " AND (unix_timestamp(date)>=$ts_start_date AND unix_timestamp(date)<=$ts_end_date) ";
     }

     // Filter on account
     if ($filter['id_account'] != 0) {
       $where_clause .= " AND id_account=".$filter['id_account'];
     }

     // Filter on text
     if ($filter['textsearch'] != "") {
       $where_clause .= " AND (text LIKE '%".$filter['textsearch']."%' OR comment LIKE '%".$filter['textsearch']."%')";
     }

     // Filter on amount
     if ($filter['amount'] != "") {
       $filter['amount'] = preg_replace("!,!", ".", $filter['amount']); // Decimal dot can be coma for european users

       if (preg_match("!([0-9.]+)-([0-9.]+)!", $filter['amount'], $matches)) {
         // Interval
         $where_clause .= " AND (abs(amount) >= ".$matches[1]." AND abs(amount) <= ".$matches[2].") ";
       } else {
         // One amount
         $where_clause .= " AND (abs(amount*1.10) >= ".$filter['amount']." AND abs(amount*0.9) <= ".$filter['amount'].") ";
       }
     }
     // End where clause
     // -------------------------------------------------------------------------------------------------------------

     // -------------------------------------------------------------------------------------------------------------
     // BEGIN order clause
     switch ($_GET['sort']) {
       case "category" : $order_clause = "c.name, t.date DESC"; break;
       case "color" : $order_clause = "HEX(MID(c.color, 1,2)),HEX(MID(c.color,3,2)),HEX(MID(c.color,5,2))"; break;
       case "amount" : $order_clause = "abs(t.amount) DESC"; break;
       case "type" : $order_clause = "t.type,t.date DESC "; break;
       case "desc" : $order_clause = "t.text,t.comment "; break;
       case "date" :
       default : $order_clause = "t.date DESC";
     }
     // END order clause
     // -------------------------------------------------------------------------------------------------------------


     $q = "SELECT t.id,t.amount,t.date,UNIX_TIMESTAMP(t.date) as ts_date,c.name,t.type,t.text,t.comment,c.color,t.id_category,t.file_name,t.id_account
           FROM webfinance_transactions AS t LEFT JOIN webfinance_categories AS c ON t.id_category=c.id
           HAVING $where_clause
           ORDER BY $order_clause";
     // Get number of total pages for this filter :
     $result = mysql_query($q) or wf_mysqldie();
     $nb_transactions = mysql_num_rows($result);
     mysql_free_result($result);

     $q .= $limit_clause;
     $result = mysql_query($q) or wf_mysqldie();

     $filter_base = sprintf("sort=%d&filter[start_date]=%s&filter[end_date]=%s&filter[textsearch]=%s&filter[amount]=%s",
                            $_GET['sort'], $filter[start_date], $filter[end_date], $filter[textsearch], $filter[amount] );
     $result = mysql_query($q) or wf_mysqldie();
     $total_shown = 0;
     $count = 1;
     $prev_date="";
     while ($tr = mysql_fetch_object($result)) {

       //s�parer les mois
       $current_month=ucfirst(strftime("%B %Y",$tr->ts_date));
       if(!empty($prev_date)){
	 if(date("m",$prev_date)!=date("m",$tr->ts_date))
	   echo "<tr><td colspan='9' align='center'><b>$current_month</b></td></tr>";
       }else
	 echo "<tr><td colspan='9' align='center'><b>$current_month</b></td></tr>";

       $prev_date=$tr->ts_date;


       $total_shown += $tr->amount;

       $fmt_date = strftime("%d/%m/%Y", $tr->ts_date); // Formated date (localized)
       $fmt_amount = number_format($tr->amount, 2, ',', ' '); // Formated amount
       $amount_color = ($tr->amount > 0)?"#e0ffe0":"#ffe0e0";

       $balance = $balance_lines[$tr->id];
       $balance_color = ($balance > 0)?"#e0ffe0":"#ffe0e0";
       $fmt_balance = number_format($balance, 2, ',', ' '); // Formated balance

       $help_edit = addslashes(_('Click to modify this transaction'));

       $class = ($count%2)?"row_odd":"row_even";

       //file
       $file="";
       if($tr->file_name != ""){
	 //put a icon here
	 $file="<a href='file.php?action=file&id=$tr->id'><small>$tr->file_name</small></a>";
       }

       print <<<EOF
<tr class="$class">
  <td>
	 <input type="checkbox" id="chk_$tr->id" name="chk[]" onchange="updateCheck($tr->id);" value="$tr->id"/>
  </td>
  <td>
	 <img src="/imgs/icons/edit.gif" onmouseover="return escape('$help_edit');" onclick="inpagePopup(event, this, 440, 350, 'fiche_transaction.php?id=$tr->id');" />
  </td>
  <td>$fmt_date</td>
  <td style="background: $tr->color; text-align: center;" nowrap><a href="?$filter_base&filter[shown_cat][$tr->id_category]='on'">$tr->name</a></td>
  <td style="text-align: center;">$tr->type</td>
	 <td width="100%" style="font-size: 9px;">$tr->text<br/><i>$tr->comment</i>&nbsp;$file</td>
  <td style="text-align: right; font-weight: bold; background: $amount_color" nowrap>$fmt_amount &euro;</td>
  <td style="text-align: right; background: $balance_color;" nowrap>$fmt_balance &euro;</td>
</tr>
EOF;
       $count++;
     }
     ?>
     <tr>
       <td colspan="6" style="text-align: right; font-weight: bold;"><?= _('Total amount of shown transactions') ?></td>
       <td nowrap style="text-align: right; font-weight: bold;"><?= number_format($total_shown, 2, ',', ' ') ?> &euro;</td>
       <td></td>
     </tr>
    </table>
    </form> <? // End of checkboxes form ?>
    <a href="" onClick="inpagePopup(event, this, 440, 350, 'fiche_transaction.php?id=-1');return false"><?= _('Add a transaction') ?></a>
  </td>

  <td><?php // Begin of right column ?>
    <?php // Filter ?>
    <form id="main_form" onchange="this.submit();" method="get">
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?>" />
    <table border="0" cellspacing="0" cellpadding="3" width="310" class="framed">
    <tr class="row_header">
      <td colspan="2" style="text-align: center"><?= _('Filter') ?></td>
    </tr>
    <tr>
      <td><b>Page</b></td>
      <td>
       <?php

       if($filter['page']>0)
	 printf('<a class="pager_link" href="?%s&filter[page]=%d"><<&nbsp;</a> ', $filter_base, $filter['page']-1 );

      for ($i=0 ; $i<$nb_transactions/$transactions_per_page ; $i++) {
        if ($filter['page'] == $i) {
          printf("%d ", $i+1);
        } else {
          printf('<a class="pager_link" href="?%s&filter[page]=%d">%d</a> ', $filter_base, $i, $i+1 );
        }
      }
	if($filter['page'] < floor($nb_transactions/$transactions_per_page) )
	  printf('<a class="pager_link" href="?%s&filter[page]=%d">&nbsp;>></a> ', $filter_base, $filter['page']+1 );

      ?>
      </td>
    <tr>
      <td><b><?= _('Account :') ?></b></td>
      <td><select name="filter[id_account]" style="width: 150px;">
        <option value="0"><?= _('-- All accounts --') ?></option>
      <?php
      $result = mysql_query("SELECT id_pref,value FROM webfinance_pref WHERE owner=-1 AND type_pref='rib'");
      while (list($id_cpt,$cpt) = mysql_fetch_array($result)) {
        $cpt = unserialize(base64_decode($cpt));
        printf(_('        <option value="%d"%s>%s #%s</option>')."\n", $id_cpt, ($filter['id_account']==$id_cpt)?" selected":"", $cpt->banque, $cpt->compte );
      }
      mysql_free_result($result);
      ?></select></td>
    </tr>
    <tr>
      <td nowrap><b><?= _('Amount') ?> <img class="help_icon" src="/imgs/icons/help.png" onmouseover="return escape('<?= _('Enter a number for 10% aproximated search, enter 100-200 to search transactions fromm 100&euro; to 200&euro; included') ?>');" /></b></td>
      <td><input style="text-align: center; width: 130px;" type="text" id="amount_criteria" name="filter[amount]" value="<?= $filter['amount'] ?>" /><img src="/imgs/icons/delete.gif" onmouseover="return escape('<?= _('Click to suppress this filter criteria') ?>');" onclick="fld = document.getElementById('amount_criteria'); fld.value = ''; fld.form.submit();" /></td>
    </tr>
    <tr>
      <td nowrap><b><?= _('Text contains :') ?></b></td>
      <td><input style="text-align: center; width: 130px;" id="text_criteria" type="text" name="filter[textsearch]" value="<?= $filter['textsearch'] ?>" /><img src="/imgs/icons/delete.gif" onmouseover="return escape('<?= _('Click to suppress this filter criteria') ?>');" onclick="fld = document.getElementById('text_criteria'); fld.value = ''; fld.form.submit();" /></td>
    </tr>
    <tr>
      <td nowrap><b><?= _('Start date :') ?></b></td>
      <td><?php makeDateField("filter[start_date]", $ts_start_date, 1, 'start_date_criteria', 'width: 114px'); ?><img src="/imgs/icons/delete.gif" onmouseover="return escape('<?= _('Click to suppress this filter criteria') ?>');" onclick="fld = document.getElementById('start_date_criteria'); fld.value = ''; fld.form.submit();" /></td>
    </tr>
    <tr>
      <td nowrap><b><?= _('End date :') ?></b></td>
      <td><?php makeDateField("filter[end_date]", $ts_end_date, 1, 'end_date_criteria', 'width: 114px'); ?><img src="/imgs/icons/delete.gif" onmouseover="return escape('<?= _('Click to suppress this filter criteria') ?>');" onclick="fld = document.getElementById('end_date_criteria'); fld.value = ''; fld.form.submit();" /></td>
    </tr>
    <tr>
      <td nowrap><b><?= _('Shown categories :') ?></b></td>
      <td><input type="checkbox" name="filter[shown_cat][check_all]" /><b><?= _('View all') ?></b></td>
    </tr>
    <tr>
      <?php
      $count = 0;
      $result = mysql_query("SELECT id,name,color FROM webfinance_categories ORDER BY name");
      while ($cat = mysql_fetch_object($result)) {
        printf('<td nowrap><input type="checkbox" name="filter[shown_cat][%d]" %s>&nbsp;%s</td>', $cat->id, ($filter['shown_cat'][$cat->id])?"checked":"", $cat->name );
        $count++;
        if ($count % 2 == 0) {
          print "</tr>\n<tr>\n";
        }
      }
      mysql_free_result($result);
      ?>
    </tr>
    </table>
    </form><br/>

  <?php //Actions on selected transactions ?>

  <form id="action_form" action="save_transaction.php" method="post">
  <input type="hidden" name="query" value="<?= $old_query_string ?>" />
  <input type="hidden" name="selected_transactions" value="" />

  <table border="0" cellspacing="0" cellpadding="2" width="310" class="framed">
  <tr class="row_header">
    <td style="text-align: center;" colspan="2"><?= _('Action on selected transactions') ?></td>
  </tr>
  <tr>
    <td style="width: 90px;"><?= _('Action') ?></td>
    <td>
      <select onchange="changeAction(this);" name="action[type]" style="width: 200px;">
        <option value="delete"><?= _('Delete the selected transactions') ?></option>
        <option value="change_account"><?= _('Move to account...') ?></option>
        <option value="change_category"><?= _('Change category...') ?></option>
      </select>
  </tr>
  <tr>
    <td colspan="2">
      <div id="action_change_account" style="display: none;">
        <div style="display: block; float: left; width: 90px;"><?= _('To account ') ?></div>&nbsp;<select name="action[id_account]" style="width: 150px;">
        <?php
        $result = mysql_query("SELECT id_pref,value FROM webfinance_pref WHERE owner=-1 AND type_pref='rib'");
        while (list($id_cpt,$cpt) = mysql_fetch_array($result)) {
          $cpt = unserialize(base64_decode($cpt));
          printf(_('        <option value="%d"%s>%s #%s</option>')."\n", $id_cpt, ($filter['id_account']==$id_cpt)?" selected":"", $cpt->banque, $cpt->compte );
        }
        mysql_free_result($result);
        ?></select>
      </div>
      <div id="action_change_category" style="display: none;">
        <div style="display: block; float: left; width: 90px;"><?= _('Category is') ?></div>&nbsp;<select name="action[id_category]">
        <option value="1"><?= _('-- Choose --') ?></option>
        <?php
        $result = mysql_query("SELECT id,name,color FROM webfinance_categories ORDER BY name");
        while ($cat = mysql_fetch_object($result)) {
          printf('<option value="%d">%s</option>', $cat->id, $cat->name );
        }
        mysql_free_result($result);
        ?>
        </select>
      </div>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="text-align: center"><input type="button" onclick="submitAction(this.form);" value="<?= _('Apply this action') ?>" /></td>
  </tr>
  </table>
  </form>



  </td>
</tr>
</table>

<?php
// print "<pre>";
// print_r($filter);
// print "</pre>";
$Revision = '$Revision$';
require("../bottom.php");
?>