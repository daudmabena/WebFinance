#!/usr/bin/php -q
<?
   //run this script every day with unix cron
   //php /path/to/webfinance/htdocs/inc/scheduler.php
require("dbconnect.php");
require("Facture.php");

//scheduled invoices and transactions
$result = mysql_query("SELECT id_facture, ".
		      "period, ".
		      "UNIX_TIMESTAMP(last_run) as last_run_ts ".
		      "FROM webfinance_invoices ".
		      "WHERE period<>'none' AND UNIX_TIMESTAMP(last_run)<".mktime(0,0,0,date("m"),1,date("Y"))  )
  or die(mysql_error());

while( list($id_invoice,$period,$last_run) = mysql_fetch_array($result)){

 $Invoice = new Facture();

  if($period=="end of month"){

    $id_new_invoice=$Invoice->duplicate($id_invoice);
    mysql_query("UPDATE webfinance_invoices SET type_doc='devis', date_facture='".date("Y-m-d",mktime(0,0,0,date("m")+1,0,date("Y")))."' WHERE id_facture=$id_new_invoice ")
      or die(mysql_error());
    $Invoice->updateTransaction($id_new_invoice);
    mysql_query("UPDATE webfinance_invoices SET last_run=NOW() WHERE id_facture=$id_invoice ") or die(mysql_error());

  }else if($period=="end of term" AND date("m")%3==0  ){

    $id_new_invoice=$Invoice->duplicate($id_invoice);
    mysql_query("UPDATE webfinance_invoices SET type_doc='devis', date_facture='".date("Y-m-d",mktime(0,0,0,date("m")+1,0,date("Y")))."' WHERE id_facture=$id_new_invoice ")
		or die(mysql_error());
    $Invoice->updateTransaction($id_new_invoice);
    mysql_query("UPDATE webfinance_invoices SET last_run=NOW() WHERE id_facture=$id_invoice ") or die(mysql_error());


  }else if($period=="end of year" AND date("m")==12 ){

    $id_new_invoice=$Invoice->duplicate($id_invoice);
    mysql_query("UPDATE webfinance_invoices SET type_doc='devis', date_facture='".date("Y-m-d",mktime(0,0,0,date("m")+1,0,date("Y")))."' WHERE id_facture=$id_new_invoice ")
      or die(mysql_error());
    $Invoice->updateTransaction($id_new_invoice);
    mysql_query("UPDATE webfinance_invoices SET last_run=NOW() WHERE id_facture=$id_invoice ") or die(mysql_error());

  }

 }
mysql_free_result($result);
//mysql_close() will not close links established by mysql_pconnect()
mysql_close();


?>