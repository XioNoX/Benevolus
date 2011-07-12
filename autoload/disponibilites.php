<?php
class Disponibilites {

	static function gerer() {
		F3::call('outils::menu');
		outils::activerJquery();
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');

		if(is_numeric(F3::get('PARAMS.id')) && is_numeric($festival_id) && is_numeric($individu_id))
		{
			$id = F3::get('PARAMS.id');
			$responsable = DB::sql("SELECT h2.individu_id FROM `historique_organismes` as h1, `historique_organismes` as h2 WHERE h1.individu_id = $id AND h1.festival_id = $festival_id AND h1.organisme_id = h2.organisme_id AND h2.responsable=1 AND h1.festival_id=h2.festival_id;");
			if (( count($responsable) && ($responsable[0]["individu_id"] == $individu_id)) || outils::est_operateur())
			{
				$individu_id = $id;
				$individu=new Axon('individus');
				$individu->load("id=$individu_id");
				
				if (!$individu->dry())
					F3::set('pagetitle','Gérer les disponibilités de ' . $individu->prenom . ' ' . $individu->nom);
			}
			else
			outils::http401();
		}
		else
		{
			F3::call('outils::verif_individu');
			$individu_id = F3::get('SESSION.id');
			F3::set('pagetitle','Gérer mes disponibilités');
		}

		F3::call('outils::recuperation_festivals_jours');
		$contenu='<table style="width:500px">';

		$festivals_jours = F3::get('festivals_jours');
		foreach($festivals_jours as $valeur)
		{
			$festival_jour_id = $valeur["id"];
			$disponibilite=new Axon('disponibilites');
			$disponibilite->load("festival_jour_id='$festival_jour_id' AND individu_id='$individu_id'");

			$contenu .= "<tr><td>Le " . strftime("%A %d/%m/%Y",strtotime($valeur["jour"])) . "</td>";
			if (!$disponibilite->dry())
			{
				$contenu .= '<td><input type="checkbox" name="disponible_'.$festival_jour_id.'" id="disponibilite_'.$festival_jour_id.'" checked onclick="actdesact('.$festival_jour_id.')" /></td>';
				$contenu .= '<td>de <input type="text" size="4" class="heure_debut" name="heure_debut_'.$festival_jour_id.'" id="heure_debut_'.$festival_jour_id.'" value='.outils::date_sql_timepicker($disponibilite->heure_debut).'  /></td>';
				$contenu .= '<td>à <input type="text" size="4" class="heure_fin" name="heure_fin_'.$festival_jour_id.'" id="heure_fin_'.$festival_jour_id.'" value='.outils::date_sql_timepicker($disponibilite->heure_fin).'  /></td>';
			}
			else
			{
				$contenu .= '<td><input type="checkbox" name="disponible_'.$festival_jour_id.'" id="disponibilite_'.$festival_jour_id.'" onclick="actdesact('.$festival_jour_id.')"  /></td>';
				$contenu .= '<td>de <input type="text" size="4" class="heure_debut" name="heure_debut_'.$festival_jour_id.'" id="heure_debut_'.$festival_jour_id.'" disabled /></td>';
				$contenu .= '<td>à <input type="text" size="4" class="heure_fin" name="heure_fin_'.$festival_jour_id.'" id="heure_fin_'.$festival_jour_id.'" disabled /></td>';
			}
			$contenu .= '</tr>';
		}
		$contenu .= '</table>';

		F3::set('contenu',$contenu);
		F3::set('template','form_disponibilites');
		F3::call('outils::generer');
	}

	static function gerer_post() {
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');

		if(is_numeric(F3::get('PARAMS.id')) && is_numeric($festival_id) && is_numeric($individu_id)){
			$id = F3::get('PARAMS.id');
			DB::sql("SELECT h2.individu_id FROM `historique_organismes` as h1, `historique_organismes` as h2 WHERE h1.individu_id = $id AND h1.festival_id = $festival_id AND h1.organisme_id = h2.organisme_id AND h2.responsable=1 AND h1.festival_id=h2.festival_id;");
			if ((F3::get('DB')->result[0]['responsable_id'] == $individu_id) || outils::est_operateur()) {
				$individu_id = $id;
				$role = "responsable";

			}
			else
			outils::http401();
		}
		else
		{
			$individu_id = F3::get('SESSION.id');
			F3::call('outils::verif_individu');
			$role = "individu";

		}
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		//F3::call('vacations::verif_libelle|vacations::verif_responsable_id'); //TODO: factoriser toutes ces verif dans outils // TODO: Verif autres champs

		if (!F3::exists('message')) {
			// Pas d'erreur, enregistrement de la vacation

			F3::call('outils::recuperation_festivals_jours');

			$festivals_jours = F3::get('festivals_jours');
			//$individu_id = F3::get('SESSION.id');

			foreach($festivals_jours as $cle=>$valeur)
			{
				$festival_jour_id = $valeur["id"];
				if(!is_null(F3::get('REQUEST.disponible_'.$festival_jour_id.'')))
				{
					F3::clear('message');
					//F3::call('disponibilites::verif_heure_debut|disponibilites::verif_heure_fin');
					if (!F3::exists('message'))
					{
						$disponibilite=new Axon('disponibilites');
						$disponibilite->load("festival_jour_id='$festival_jour_id' AND individu_id='$individu_id'");

						$disponibilite->festival_jour_id=$valeur["id"];
						$disponibilite->individu_id=$individu_id;

						$date = F3::get('REQUEST.heure_debut_'.$valeur["id"].'');

						$disponibilite->heure_debut = outils::date_timepicker_sql(F3::get('REQUEST.heure_debut_'.$valeur["id"].''));
						$disponibilite->heure_fin = outils::date_timepicker_sql(F3::get('REQUEST.heure_fin_'.$valeur["id"].''));
						$disponibilite->save();
					}
				}
			}
		}

		if(($role == "responsable") && ($individu_id != F3::get('SESSION.id')) ){
			$organisme_id = organismes::organisme_individu($individu_id, $festival_id);
			F3::reroute("/organismes/editer/$organisme_id");
		}else
		{// Ré-Affichage du formulaire
			F3::set('succes','Disponibilités enregistrées');
			F3::call('disponibilites::gerer');
		}
	}

	static function verif_dispos($individu_id, $festival_id) {
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		if ($individu_id == "") $individu_id = F3::get('SESSION.id');
		DB::sql("SELECT count(disponibilites.id) as count FROM `disponibilites`, `festivals_jours` WHERE festival_jour_id=festivals_jours.id AND festivals_jours.festival_id = $festival_id AND disponibilites.individu_id = $individu_id");
		$result = F3::get('DB')->result;
		$nombre_dispos = $result[0]['count'];
		if ($nombre_dispos == 0){
			F3::set('message_dispos','Veuillez indiquer vos disponibilités : <a href=/disponibilites/gerer title="Disponibilités">Gérer mes disponibilités</a>');
			return false;
		}
		return true;
	}



}
?>
