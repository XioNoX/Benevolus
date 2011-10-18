<?php
class Statistiques {
	static function accueil() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::call('outils::activerJsCharts');
		F3::set('pagetitle','Statistiques');
		F3::set('template','statistiques');
		F3::call('outils::generer');
	}
	
	static function ajax_stats($action) {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		
		
		$data = array();
		switch ($action) {
			case "statuts": //total=personnes dans les asso de ce festival, divisions par statuts
				
				DB::sql("SELECT * FROM statuts;");
				foreach (F3::get('DB')->result as $row) {
					$statut_id = $row['id'];
					$libelle = $row['libelle'];
					DB::sql("SELECT count(historique_organismes.id) AS total FROM historique_organismes, individus WHERE historique_organismes.festival_id=$festival_id AND individus.statut_id=$statut_id AND historique_organismes.individu_id=individus.id;");
					$comp_statuts = F3::get('DB')->result[0]['total'];
					

					$data['unit'] = $statut_id;
					$data['value'] = $comp_statuts;
					$data_tmp[] = $data;
				}
				
				
				
				break;
				
			case 'travail':
				DB::sql("SELECT COUNT(id) as count FROM `historique_organismes` WHERE `festival_id` = $festival_id AND `organisme_id` = $organisme_id AND `present`= 0");
				break;
		}
		
		$final = array();
		$JSChart = array();
		$datasets = array();	
		$datasets['type'] = "pie";
		$datasets['data'] = $data_tmp;
		$JSChart['datasets'] = $datasets;
		$final['JSChart'] = $JSChart;
		
		
		header("Content-type: application/json");
		print outils::jsonRemoveUnicodeSequences($final) . "\n";
	}
	
	
	
	
	static function dons() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::set('pagetitle','Dons');
		$festival_id = F3::get('SESSION.festival_id');

		// Récupérer la liste des organismes du festival courant
		$organismes = DB::sql('SELECT DISTINCT organismes.id, organismes.libelle FROM organismes, historique_organismes WHERE historique_organismes.organisme_id = organismes.id AND historique_organismes.festival_id = :festival_id  ORDER BY organismes.libelle;',array(':festival_id'=>array(F3::get('SESSION.festival_id'),PDO::PARAM_INT)));
		$heures_travaillees_total = 0;
		$dons_total = 0;
		$nbMembres_total = 0;

		$tabOrganismes='<table><tr><td>Libelle</td><td># Bénévoles inscrits</td><td>Heures travaillées</td><td>Don</td><td>PDF</td></tr>';

		foreach($organismes as $cle=>$valeur)
		{

			$heures_travaillees = organismes::heures_travaillees($valeur['id'],1);
			$heures_travaillees_total = $heures_travaillees_total+$heures_travaillees;

			$don = organismes::don_a_faire($heures_travaillees);
			$dons_total = $dons_total + $don;

			$nbMembres = count(organismes::membres_organisme($valeur['id'], $festival_id));
			$nbMembres_total = $nbMembres_total+$nbMembres;

			$tabOrganismes .= "<tr><td><a href='/organismes/editer/".$valeur['id']."'>". $valeur['libelle'] . "</a></td><td>" . $nbMembres . "</td><td>". $heures_travaillees . "h</td><td>" . $don . "€<td><a href='/statistiques/dons/".$valeur['id']."'>PDF</a></td></tr>";
		}
		$tabOrganismes .= "<tr><td>Total</td><td>" . $nbMembres_total . "</td><td>". $heures_travaillees_total . "h</td><td>" . $dons_total . "€<td><a href=#>pdf</a></td></tr>";


		$tabOrganismes .= "</table>";

		F3::set('tabOrganismes', $tabOrganismes);

		F3::set('nbOrganismes', count($organismes));
		// Les passer dans la moulinette
		// Faire les sommes
		// Rajouter les liens


		F3::set('template','dons');
		F3::call('outils::generer');
	}

	static function imprimer_bilan_organisme() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$organisme_id = F3::get('PARAMS.id');
		if(is_numeric($organisme_id))
		{
			$organisme = new Axon('organismes');
			$organisme->load("id=$organisme_id");

			if(!$organisme->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				$festival_id = F3::get('SESSION.festival_id');
				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");

				//Instanciation de la classe dérivée
				$pdf=new PDF();

				$pdf->header = true;
				$pdf->footer = true;
					
				$pdf->SetMargins(5, 5);
				$pdf->titre = "Bilan des benevoles " . $festival->annee;
				$pdf->AliasNbPages();
				$pdf->AddPage();

				$pdf->SetY(65);
				$pdf->SetFont('Arial','B',14);
				$pdf->Cell(0,6,"Association :",0,1, 'L');

				$pdf->SetY(65);
				$pdf->Cell(120);
				$pdf->Cell(0,6, $organisme->libelle ,0,1, 'L');

				$o_responsable = DB::sql("SELECT i.prenom, i.nom, i.adresse1, i.adresse2, v.cp, v.nom AS ville FROM individus AS i, historique_organismes AS ho, villes AS v WHERE ho.individu_id=i.id AND ho.responsable=1 AND ho.organisme_id=$organisme_id AND v.id=i.ville_id");
				if (count($o_responsable)>0)
				{
					$pdf->SetY(80);
					$pdf->SetFont('Arial','',14);
					$pdf->Cell(120);
					$pdf->Cell(0,6,$o_responsable[0]["nom"] . " " . $o_responsable[0]["prenom"],0,1, 'L');
					
					$pdf->Cell(120);
					$pdf->Cell(0,6,$o_responsable[0]["adresse1"],0,1, 'L');
					$pdf->Cell(120);
					$pdf->Cell(0,6,$o_responsable[0]["adresse2"],0,1, 'L');
					$pdf->Cell(120);
					$pdf->Cell(0,6,$o_responsable[0]["cp"] . " " . $o_responsable[0]["ville"],0,1, 'L');
				
					$pdf->SetY(120);
					
					$header=array('Statut','Nbre personnes','Nombre d\'heures', 'Somme(euros)');
					$width=array(80, 40, 40, 40);
					$pdf->rowTable($header, $width);
					
					$pdf->SetFont('Arial','',11);
					
					$ok="OK \nListe des bénévoles inscrits et qui ont effectué leurs tranches horaires.\nNous tenons à remercier chaleureusement ces bénévoles";
					$y = $pdf->GetY();
					$pdf->MultiCell($width[0],6,$ok,1);
					$pdf->setY($y);
					$pdf->Cell($width[0]);
					$pdf->MultiCell($width[1],6,"\n\n" . organismes::comptage($organisme_id, "ok", "") . "\n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1]);
					$pdf->MultiCell($width[1],6,"\n\n" . $heures_travaillees = organismes::heures_travaillees($organisme_id,1) . "h\n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1] + $width[2]);
					$pdf->MultiCell($width[3],6,utf8_decode("\n\n" . $don = organismes::don_a_faire($heures_travaillees) . "\n\n\n"),1);
					
					$pas_retire="Pas retiré de bracelet";
					$y = $pdf->GetY();
					$pdf->Cell($width[0],6,$pas_retire,1);
					$pdf->setY($y);
					$pdf->Cell($width[0]);
					$pdf->Cell($width[1],6,organismes::comptage($organisme_id, "pas-retire", ""),1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1]);
					$pdf->Cell($width[2],6,"0",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1] + $width[2]);
					$pdf->Cell($width[3],6,"0",1);
					$pdf->Ln();
					
					
					$pas_travaille="Pas travaillé\nCes personnes n'ont pas signé leurs feuilles de présence\nCes personnes n'ont peut-être pas travaillé !!!";
					$y = $pdf->GetY();
					$pdf->MultiCell($width[0],6,$pas_travaille,1);
					$pdf->setY($y);
					$pdf->Cell($width[0]);
					$pdf->MultiCell($width[1],6,"\n\n". organismes::comptage($organisme_id, "pas-travaille", "") . "\n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1]);
					$pdf->MultiCell($width[2],6,"\n\n 0 \n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1] + $width[2]);
					$pdf->MultiCell($width[3],6,"\n\n 0 \n\n\n",1);
					
					$ne_pas_reprendre="Ne pas reprendre";
					$y = $pdf->GetY();
					$pdf->Cell($width[0],6,$ne_pas_reprendre,1);
					$pdf->setY($y);
					$pdf->Cell($width[0]);
					$pdf->Cell($width[1],6,organismes::comptage($organisme_id, "pas-reprendre", ""),1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1]);
					$pdf->Cell($width[2],6,"0",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1] + $width[2]);
					$pdf->Cell($width[3],6,"0",1);
					$pdf->Ln();
					
					$ww="W/W\nCes personnes n'ont pas signé la totalité de leurs feuilles de présence.\nCes personnes ont travaillé qu'une partie de leurs affectations";
					$y = $pdf->GetY();
					$pdf->MultiCell($width[0],6,$ww,1);
					$pdf->setY($y);
					$pdf->Cell($width[0]);
					$pdf->MultiCell($width[1],6,"\n\n" . organismes::comptage($organisme_id, "ww", "") . "\n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1]);
					$pdf->MultiCell($width[1],6,"\n\n0\n\n\n",1);
					$pdf->setY($y);
					$pdf->Cell($width[0] + $width[1] + $width[2]);
					$pdf->MultiCell($width[3],6,"\n\n0\n\n\n",1);
					
					$pdf->Ln();
					
					$pdf->Cell($width[0],6,"Total association :",1);
					$pdf->Cell($width[1],6,organismes::comptage($organisme_id, "ok", ""),1);
					$pdf->Cell($width[1],6,"\n\n" . $heures_travaillees . "\n\n\n",1);
					$pdf->Cell($width[3],6,"\n\n" . $don . "\n\n\n",1);
					

					$pdf->Output("Bilan-".$organisme_id."-".$festival->annee.".pdf", 'D');
				}
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}
}

?>