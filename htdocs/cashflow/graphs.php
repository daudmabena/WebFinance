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
require("../inc/main.php");

//Navigation bar function
function navigation($account,$start_date_ex,$end_date_ex){

  $last_date_start=date("Y-m-d" , mktime(0, 0, 0, $start_date_ex[1]-1, 1 , $start_date_ex[0]) );
  $next_date_start=date("Y-m-d" , mktime(0, 0, 0, $start_date_ex[1]+1, 1 , $start_date_ex[0]) );

  $last_date_end=date("Y-m-d" , mktime(0, 0, 0, $start_date_ex[1]-1, date("t",mktime(0, 0, 0, $start_date_ex[1]-1 , 1, $start_date_ex[0] )) , $start_date_ex[0]) );
  $next_date_end=date("Y-m-d" , mktime(0, 0, 0, $start_date_ex[1]+1, date("t",mktime(0, 0, 0, $start_date_ex[1]+1 , 1, $start_date_ex[0] )) , $start_date_ex[0]) );
  ?>
    <a href="?start_date=<?=$last_date_start?>&end_date=<?=$last_date_end?>&account=<?=$account?>">&lt;&lt;</a>
       <big style='font-weight: bold;'>
        <?=strftime("%d %B %Y",mktime(0,0,0,$start_date_ex[1],$start_date_ex[2],$start_date_ex[0]))?>
       </big>
       to
       <big style='font-weight: bold;'>
        <?=strftime("%d %B %Y",mktime(0,0,0,$end_date_ex[1],$end_date_ex[2],$end_date_ex[0]))?>
       </big>
       <a href="?start_date=<?=$next_date_start?>&end_date=<?=$next_date_end?>&account=<?=$account?>">&gt;&gt;</a>
  <?
}


$title = _("Financial graphics");
$roles = 'manager,accounting';
require("../top.php");
require("nav.php");

//start date
$start_date=date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y")) );
$start_date_ex=array(date("Y"),date("m"),1);

//end date
$end_date=date("Y-m-d", mktime(0, 0, 0, date("m"), date('t',mktime()), date("Y")));
$end_date_ex=array(date("Y"),date("m"),date("t",mktime()));

//default account = all
$account="";

if(isset($_GET['start_date']) AND isset($_GET['end_date']) ){
  if(empty($_GET['start_date'])){
    $_GET['start_date']=$start_date;
  }else{
    $tmp=explode("-",$_GET['start_date']);
    if(checkdate($tmp[1],$tmp[2],$tmp[0])){
      $start_date=$_GET['start_date'];
      $start_date_ex=$tmp;
    }
  }
  if(empty($_GET['end_date'])){
    $_GET['end_date']=$end_date;
  }else{
    $tmp=explode("-",$_GET['end_date']);
    if(checkdate($tmp[1],$tmp[2],$tmp[0])){
      $end_date=$_GET['end_date'];
      $end_date_ex=$tmp;
    }
  }
 }

if(isset($_GET['account']) AND !empty($_GET['account']))
  $account=$_GET['account'];

?>

 <?
 $result_accounts=mysql_query("SELECT id_pref,value FROM webfinance_pref WHERE owner=-1 AND type_pref='rib'") or wf_mysqldie();
 $nb_accounts=mysql_num_rows($result_accounts);
 ?>
  <form id="main_form" method="get" onchange="this.submit();" >
   <table border="0">
    <tr>
     <td>Account</td>
     <td>
      <select name="account">
       <option value="0"><?= _('-- All accounts --') ?></option>
   <?
   while ($acc=mysql_fetch_assoc($result_accounts)) {
     $cpt = unserialize(base64_decode($acc['value']));
     printf('<option value="%d"%s>%s #%s</option>',
	    $acc['id_pref'], ($acc['id_pref']==$account)?" selected":"",
	    $cpt->banque, $cpt->compte );
   }
   ?>
      </select>
     </td>
     <td>Date</td>
     <td><input type="text" name="start_date" value="<?=$start_date?>" size="9" maxlength="10"/></td>
     <td>to</td>
     <td><input type="text" name="end_date" value="<?=$end_date?>" size="9" maxlength="10"/></td>
    </tr>
   </table>
  </form>

  <!-- Affichage des graphes -->
  <div>
	<?
   	 navigation($account,$start_date_ex,$end_date_ex);
	?>
  </div>
 <img src="../graphs/cashflow.php?account=&end_date=&width=600&height=300&movingaverage=0&start_date=<?=$start_date?>&end_date=<?=$end_date?>" />
 <img src="../graphs/income_outgo_all.php?width=600&height=300&type=expense&start_date=<?=$start_date?>&end_date=<?=$end_date?>&account=<?=$account?>" />

          <br/>
 <img src="plots.php?width=400&height=400&type=category&start_date=<?=$start_date?>&end_date=<?=$end_date?>&account=<?=$account?>&sign=positive"/>
 <img src="plots.php?width=400&height=400&type=category&start_date=<?=$start_date?>&end_date=<?=$end_date?>&account=<?=$account?>&sign=negative"/>

				  <!--<img src="plots_all_history.php?type=category&sign=positive&plot=piecharts"/> -->
				  <!--<img src="plots_all_history.php?type=category&sign=negative&plot=piecharts"/> -->

				  <!--<img src="plots.php?account=<?=$account?>"/>-->
				  <!--<img src="plots.php?type=amount&account=<?=$account?>"/>-->

				  <!-- <img src="plots_all_history.php?type=category&sign=positive&plot=bars"/> -->
				  <!-- <img src="plots_all_history.php?type=category&sign=negative&plot=bars"/> -->

<table border="1" width="100%">
   <tr>
   <td colspan='2' align="center">
	<?
   	 navigation($account,$start_date_ex,$end_date_ex);
	?>
    </td>
    </tr>
  </table>

 <hr/>
<?
$Revision = '$Revision: 531 $';
require("../bottom.php");
 ?>
