<?php
class Statistiques {
	static function accueil() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::set('pagetitle','Statistiques');
		F3::set('template','statistiques');
		F3::call('outils::generer');
	}
	
	static function ajax_stats($id) {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		switch ($type) {
			case "statuts":
				DB::sql("SELECT COUNT(DISTINCT affectations.individu_id) as count FROM `vacations`, `festivals_jours` , `affectations`, `historique_organismes` WHERE affectations.heure_debut!=''  AND affectations.pas_travaille=0 AND festivals_jours.festival_id = $festival_id AND `vacations`.festival_jour_id = festivals_jours.id AND `vacations`.id = affectations.vacation_id AND `affectations`.individu_id = `historique_organismes`.individu_id AND `historique_organismes`.festival_id = $festival_id AND `historique_organismes`.organisme_id = $organisme_id;");
				break;
			case 'travail':
				DB::sql("SELECT COUNT(id) as count FROM `historique_organismes` WHERE `festival_id` = $festival_id AND `organisme_id` = $organisme_id AND `present`= 0");
				break;
		}
		foreach (F3::get('DB')->result as $row) {
			$json = array();
			$json['id'] = $row['id'];
			$json['label'] = $row['cp'] . " - " . $row['nom'];
			$json['value'] = $row['cp'] . " - " . $row['nom'];
			$data[] = $json;
		}
		header("Content-type: application/json");
		echo json_encode($data);
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
			
			$tabOrganismes .= "<tr><td><a href='/organismes/editer/".$valeur['id']."'>". $valeur['libelle'] . "</a></td><td>" . $nbMembres . "</td><td>". $heures_travaillees . "h</td><td>" . $don . "€<td><a href=#>pdf</a></td></tr>";
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
	
	
}

?>