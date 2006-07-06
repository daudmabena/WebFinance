<?php
//
// This file is part of « Webfinance »
//
// Copyright (c) 2004-2006 NBI SARL
// Author : Nicolas Bouthors <nbouthors@nbi.fr>
//
// You can use and redistribute this file under the term of the GNU GPL v2.0
//
//$Id$

class Facture extends WFO {
  function Facture() {
  }

  function _markForRebuild($id) {
    $this->SQL("UPDATE webfinance_invoices SET date_generated=NULL,pdf_file='' WHERE id_facture=$id");
  }

  function addLigne($id_facture, $desc, $pu_ht, $qtt) {
    $desc = preg_replace("/\'/", "\\'", $desc);
    $result = $this->SQL("INSERT INTO webfinance_invoice_rows (date_creation, id_facture, description, pu_ht, qtt) VALUES(now(), $id_facture, '$desc', '$pu_ht', $qtt)");
    $this->_markForRebuild($id_facture);
  }

  function getTotal($id_facture) {
    $result = $this->SQL("SELECT sum(qtt*pu_ht) FROM webfinance_invoice_rows WHERE id_facture=$id_facture");
    list($total) = mysql_fetch_array($result);
    mysql_free_result($result);

    return $total;
  }

  function exists($id_facture=null){
    if ($id_facture == "") { return 0; }
    $result = $this->SQL("SELECT count(*) FROM webfinance_invoices WHERE id_facture=$id_facture");
    list($exists) = mysql_fetch_array($result);
    return $exists;
  }


  function getInfos($id_facture) {
    if (!is_numeric($id_facture)) {
      die("Facture:getInfos no id");
    }
    $result = $this->SQL("SELECT c.id_client as id_client,c.nom as nom_client, c.addr1, c.addr2, c.addr3, c.cp, c.ville, c.vat_number,
                                  date_format(f.date_created,'%d/%m/%Y') as nice_date_created,
                                  date_format(f.date_paiement, '%d/%m/%Y') as nice_date_paiement,
                                  date_format(f.date_sent, '%d/%m/%Y') as nice_date_sent,
                                  date_format(f.date_facture, '%d/%m/%Y') as nice_date_facture,
                                  unix_timestamp(f.date_facture) as timestamp_date_facture,
                                  unix_timestamp(f.date_paiement) as timestamp_date_paiement,
                                  unix_timestamp(f.date_sent) as timestamp_date_sent,
                                  date_format(f.date_facture, '%Y%m') as mois_facture,
                                  UPPER(LEFT(f.type_doc, 2)) AS code_type_doc,
                                  date_sent<now() as is_sent,
                                  f.type_paiement, f.is_paye, f.ref_contrat, f.extra_top, f.extra_bottom, f.num_facture, f.period, f.tax, f.*
                           FROM webfinance_clients as c, webfinance_invoices as f
                           WHERE f.id_client=c.id_client
                           AND f.id_facture=$id_facture");
    $facture = mysql_fetch_object($result);

    $result = $this->SQL("SELECT id_facture_ligne,prix_ht,qtt,description FROM webfinance_invoice_rows WHERE id_facture=$id_facture ORDER BY ordre");
    $facture->lignes = Array();
    $total = 0;
    $count = 0;
    while ($el = mysql_fetch_object($result)) {
      array_push($facture->lignes, $el);
      $total += $el->qtt * $el->prix_ht;
      $count++;
    }
    mysql_free_result($result);
    $facture->taxe = $facture->tax;
    $facture->nb_lignes = $count;
    $facture->total_ht = $total;
    //$facture->total_ttc = $total*1.196;
    $facture->total_ttc = $total+($total*$facture->taxe)/100;
    $facture->nice_total_ht = sprintf("%.2f", $facture->total_ht);
    $facture->nice_total_ttc = sprintf("%.2f", $facture->total_ttc);
    //$facture->immuable = $facture->is_paye || $facture->is_sent;
    //We can always modify invoice
    //Can be in preference #FIXME
    $facture->immuable = false;

//     print "<pre>";
//     print_r($facture);

    $result = $this->SQL("SELECT nom FROM webfinance_clients WHERE id_client=".$facture->id_client);
    list($facture->nom_client) = mysql_fetch_array($result);
    mysql_free_result($result);


    return $facture;
  }

  /** Marque chaque ligne d'une facture comme "pay�e"
   */
  function setPaid($id_facture) {
    // Marque toutes les lignes comme "pay�es"
    $this->SQL("UPDATE webfinance_invoices SET date_paiement=now(),is_payee=1 WHERE id_facture=$id_facture");
  }


  /** Renvoie vrai si la facture est g�n�r�e au format PDF
    */
  function hasPdf($id) {
    $result = $this->SQL("SELECT pdf_file FROM webfinance_invoices WHERE id_facture=$id");
    list($file) = mysql_fetch_array($result);
    mysql_free_result($result);

    if (file_exists($file))
      return true;
    else
      return false;
  }

  function getTransactions($id_invoice){
    $trs=array();
    $q = $this->SQL("SELECT id , text FROM webfinance_transactions WHERE id_invoice=$id_invoice");
    while(list($id_tr, $text) = mysql_fetch_array($q))
      $trs[$id_tr] = $text;
    return $trs;
  }

  function duplicate($id){

    if(is_numeric($id)){
      $result = $this->SQL("SELECT id_client FROM webfinance_invoices WHERE id_facture=$id");
      list($id_client) = mysql_fetch_array($result);
      mysql_free_result($result);

      $this->SQL("INSERT INTO webfinance_invoices (id_client,date_created,date_facture) VALUES ($id_client, now(), now())")
	or wf_mysqldie();
      $id_new_facture = mysql_insert_id();

      // On recopie les données de la facture
      $this->SQL("UPDATE webfinance_invoices as f1, webfinance_invoices as f2
               SET
                 f1.commentaire=f2.commentaire,
                 f1.type_paiement=f2.type_paiement,
                 f1.ref_contrat=f2.ref_contrat,
                 f1.extra_top=f2.extra_top,
                 f1.extra_bottom=f2.extra_bottom,
                 f1.id_type_presta=f2.id_type_presta,
                 f1.type_doc=f2.type_doc,
                 f1.id_compte=f2.id_compte
               WHERE f1.id_facture=$id_new_facture
                 AND f2.id_facture=$id");

      $this->SQL("INSERT INTO webfinance_invoice_rows (id_facture,description,qtt,ordre,prix_ht) SELECT $id_new_facture,description,qtt,ordre,prix_ht FROM webfinance_invoice_rows WHERE id_facture=$id")
	or wf_mysqldie();
      return $id_new_facture;
    }else{
      return 0;
    }
  }

  function updateTransaction($id_invoice, $type_prev=0){

    if (is_numeric($id_invoice)){
      $facture = $this->getInfos($id_invoice);

      if($facture->is_paye){
	$type="real";
	$date_transaction=date("Y-m-d", $facture->timestamp_date_paiement );
      }else if($type_prev==1){
	$type="asap";
	$date_transaction=date("Y-m-d", mktime(0,0,0,date("m"),date("d")+1, date("Y")) );
      }else if($type_prev>1){
	$type="prevision";
	$date_transaction=date("Y-m-d", ($facture->timestamp_date_facture)+(86400*$type_prev) );
      }else{
	$type="prevision";
	if($facture->timestamp_date_facture < $facture->timestamp_date_paiement )
	  $date_transaction=date("Y-m-d",$facture->timestamp_date_paiement);
	else
	  $date_transaction=date("Y-m-d",$facture->timestamp_date_facture);
      }


      $result=$this->SQL("SELECT id, id_category FROM webfinance_transactions WHERE id_invoice=$id_invoice" )
	or wf_mysqldie();
      $nb=mysql_num_rows($result);

      $text = "Num fact: $facture->num_facture, Ref contrat:  $facture->ref_contrat";
      $comment= "$facture->commentaire";
      $id_category=1;

      if($nb==1){
	list($id_tr, $id_category)=mysql_fetch_array($result);

	if($id_category<=1){

	  // Dans tous les cas on essaie de retrouver la catégorie de la transaction
	  // automagiquement.
	  $id_categorie = 1;
	  $result = $this->SQL("SELECT COUNT(*),id,name
                         FROM webfinance_categories
                         WHERE re IS NOT NULL
                         AND '".addslashes($comment." ".$text )."' RLIKE re
                         GROUP BY id");
	  list($nb_matches,$id, $name) = mysql_fetch_array($result);
	  if($nb_matches>0)
	    $id_category=$id;
	}

	//update
	$query = "UPDATE webfinance_transactions SET ".
	  "id_account=%d, ".
	  "id_category=%d, ".
	  "text='%s', ".
	  "amount='%s', ".
	  "type='$type', ".
	  "date='%s' ".
	  "WHERE id_invoice=%d";
	$q = sprintf($query, $facture->id_compte, $id_category, $text, preg_replace("!,!", ".", $facture->total_ttc),  $date_transaction, $id_invoice );
	$this->SQL($q);


      }else if($nb<1){
	//insert
	$query = "INSERT INTO webfinance_transactions SET ".
	  "id_account=%d, ".
	  "id_category=%d, ".
	  "text='%s', ".
	  "amount='%s', ".
	  "type='$type', ".
	  "date='%s', ".
	  "comment='%s', ".
	  "id_invoice=%d";
	$q = sprintf($query, $facture->id_compte, $id_category, $text, preg_replace('!,!', '.', $facture->total_ttc), $date_transaction , $comment, $id_invoice );
	$this->SQL($q);
	$id_tr=mysql_insert_id();
      }else{
	//multiple transactions
	$id_tr=0;
      }
      return $id_tr;

    }
  }

}

?>
