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
//$Id$

class Facture {
  function Facture() {
  }

  function _markForRebuild($id) {
    mysql_query("UPDATE webfinance_invoices SET date_generated=NULL,pdf_file='' WHERE id_facture=$id");
  }

  function addLigne($id_facture, $desc, $pu_ht, $qtt) {
    $desc = preg_replace("/\'/", "\\'", $desc);
    $result = mysql_query("INSERT INTO webfinance_invoice_rows (date_creation, id_facture, description, pu_ht, qtt) VALUES(now(), $id_facture, '$desc', '$pu_ht', $qtt)") or wf_mysqldie();
    $this->_markForRebuild($id_facture);
  }

  function getTotal($id_facture) {
    $result = mysql_query("SELECT sum(qtt*pu_ht) FROM webfinance_invoice_rows WHERE id_facture=$id_facture") or wf_mysqldie();
    list($total) = mysql_fetch_array($result);
    mysql_free_result($result);

    return $total;
  }

  function getInfos($id_facture) {
    if (!is_numeric($id_facture)) {
      die("Facture:getInfos no id");
    }
    $result = mysql_query("SELECT c.id_client as id_client,c.nom as nom_client, c.addr1, c.addr2, c.addr3, c.cp, c.ville, c.vat_number,
                                  date_format(f.date_created,'%d/%m/%Y') as nice_date_created,
                                  date_format(f.date_paiement, '%d/%m/%Y') as nice_date_paiement,
                                  date_format(f.date_facture, '%d/%m/%Y') as nice_date_facture,
                                  unix_timestamp(f.date_facture) as timestamp_date_facture,
                                  unix_timestamp(f.date_paiement) as timestamp_date_paiement,
                                  date_format(f.date_facture, '%Y%m') as mois_facture,
                                  UPPER(LEFT(f.type_doc, 2)) AS code_type_doc,
                                  date_sent<now() as is_sent,
                                  f.type_paiement, f.is_paye, f.ref_contrat, f.extra_top, f.extra_bottom, f.num_facture, f.*
                           FROM webfinance_clients as c, webfinance_invoices as f
                           WHERE f.id_client=c.id_client
                           AND f.id_facture=$id_facture") or wf_mysqldie();
    $facture = mysql_fetch_object($result);

    $result = mysql_query("SELECT id_facture_ligne,prix_ht,qtt,description FROM webfinance_invoice_rows WHERE id_facture=$id_facture ORDER BY ordre");
    $facture->lignes = Array();
    $total = 0;
    $count = 0;
    while ($el = mysql_fetch_object($result)) {
      array_push($facture->lignes, $el);
      $total += $el->qtt * $el->prix_ht;
      $count++;
    }
    mysql_free_result($result);
    $facture->nb_lignes = $count;
    $facture->total_ht = $total;
    $facture->total_ttc = $total*1.196;
    $facture->nice_total_ht = sprintf("%.2f", $facture->total_ht);
    $facture->nice_total_ttc = sprintf("%.2f", $facture->total_ttc);
    $facture->immuable = $facture->is_paye || $facture->is_sent;

//     print "<pre>";
//     print_r($facture);

    $result = mysql_query("SELECT nom FROM webfinance_clients WHERE id_client=".$facture->id_client) or wf_mysqldie();
    list($facture->nom_client) = mysql_fetch_array($result);
    mysql_free_result($result);


    return $facture;
  }

  /** Marque chaque ligne d'une facture comme "pay�e"
   */
  function setPaid($id_facture) {
    // Marque toutes les lignes comme "pay�es"
    mysql_query("UPDATE webfinance_invoices SET date_paiement=now(),is_payee=1 WHERE id_facture=$id_facture") or wf_mysqldie();
  }


  /** Renvoie vrai si la facture est g�n�r�e au format PDF
    */
  function hasPdf($id) {
    $result = mysql_query("SELECT pdf_file FROM webfinance_invoices WHERE id_facture=$id");
    list($file) = mysql_fetch_array($result);
    mysql_free_result($result);

    if (file_exists($file))
      return true;
    else
      return false;
  }
}

?>
