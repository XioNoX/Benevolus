<?php
class Lieux {
	static function lister() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');
		F3::set('pagetitle','Liste de tous les lieux');
		F3::set('lien_ajouter','<a href=/lieux/ajouter>Ajouter un lieu</a>');
		F3::set('jquery_url_list','/ajax/lieux');
		F3::set('jquery_url_edit','/lieux/editer/');
		F3::set('jquery_url_edit2','/lieux/editer');
		F3::set('jquery_largeur','975');
		F3::set('jquery_col_names',"['id','Libelle']");

		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
      {name:'libelle', index:'libelle', width:90}, 
    ]");

		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function lister_festival() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');
		F3::set('pagetitle','Liste des lieux du festival courant');
		F3::set('lien_ajouter','<a href=/lieux/ajouter>Ajouter un lieu</a> - <a href=/lieux/tous>Tous les lieux</a>');
		F3::set('jquery_url_list','/ajax/lieux_festival');
		F3::set('jquery_url_edit','/lieux/editer/');
		F3::set('jquery_url_edit2','/lieux/editer_festival');
		F3::set('jquery_largeur','975');
		F3::set('jquery_col_names',"['id','Libelle']");

		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
      {name:'libelle', index:'libelle', width:90}, 
    ]");

		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}


	static function editer_tableau_post() {
		F3::set('type','lieu');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				$lieux=new Axon('lieux');
				$lieux->load("id=$id");
				if(outils::est_admin())
				{
					DB::sql("DELETE FROM lieux WHERE id=$id");
					historique::logger("Suppréssion du lieu ". $lieux->libelle);

				}
			}
		}
	}

	static function editer_tableau_festival_post() {
		F3::set('type','lieu');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				$lieux=new Axon('lieux');
				$lieux->load("id=$id");
				if(outils::est_admin())
				{
					DB::sql('DELETE FROM responsables_lieux WHERE lieu_id = :id AND festival_id = :festival_id ;',array(':id'=>array($id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT)));
					historique::logger("Suppréssion du lieu ". $lieux->libelle);

				}
			}
		}
	}

	static function recuperation_lieux()
	{
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');

		$lieux = DB::sql("SELECT DISTINCT l.id, l.libelle FROM individus AS i, affectations AS a, vacations AS v, lieux AS l, festivals_jours AS fj WHERE i.id=$individu_id AND a.individu_id=i.id AND v.id=a.vacation_id AND l.id=v.lieu_id AND v.festival_jour_id=fj.id AND fj.festival_id=$festival_id UNION SELECT l.id, l.libelle FROM responsables_lieux AS rl, lieux AS l WHERE rl.individu_id=$individu_id AND rl.festival_id=$festival_id AND rl.lieu_id=l.id");

		if(count($lieux)>0)
		{
			F3::set('lieux', $lieux);
		}
	}

	static function membres_lieu($lieu_id, $festival_id){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT DISTINCT i.* FROM individus AS i, affectations AS a, vacations as v WHERE i.id=a.individu_id AND a.vacation_id=v.id AND v.lieu_id=$lieu_id UNION SELECT i.* FROM individus AS i, responsables_lieux AS rl WHERE i.id=rl.individu_id AND rl.lieu_id=$lieu_id AND rl.festival_id=$festival_id");
		return F3::get('DB')->result;
	}

	static function ajax_responsable() {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$id = F3::get('PARAMS.id');

		DB::sql("SELECT individu_id, nom, prenom, date_naissance FROM responsables_lieux, individus WHERE responsables_lieux.individu_id = individus.id AND festival_id = $festival_id AND lieu_id = $id;");
		$result = F3::get('DB')->result[0];

		echo $result['individu_id'] . ';'. $result['prenom'] . " " . $result['nom'] . " - " . $result['date_naissance'];
	}

	static function mes_lieux() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');

		F3::set('meslieux', 1);
		F3::call('lieux::recuperation_lieux');

		F3::set('pagetitle','Mes lieux');
		F3::set('template','lieu');
		F3::call('outils::generer');
	}

	static function mon_lieu() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		F3::call('lieux::recuperation_lieux');
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		$lieu_id = F3::get('PARAMS.id');

		$lieu_individu = DB::sql("Select lieux.* FROM individus, affectations, vacations, lieux WHERE individus.id=$individu_id AND affectations.individu_id=individus.id AND vacations.id=affectations.vacation_id AND lieux.id=vacations.lieu_id AND lieux.id=$lieu_id UNION SELECT lieux.* FROM responsables_lieux, lieux WHERE responsables_lieux.lieu_id=$lieu_id AND responsables_lieux.lieu_id=lieux.id AND responsables_lieux.festival_id=$festival_id");
			
		//Vérification de l'appartenance au lieu
		if(count($lieu_individu)>0)
		{
			F3::set('lieu', $lieu_individu);

			//Récupération du responsable
			$responsable_lieu = DB::sql("SELECT i.id, nom, prenom FROM individus AS i, responsables_lieux AS rl WHERE i.id=rl.individu_id AND rl.lieu_id=$lieu_id AND rl.festival_id=$festival_id");

			if(count($responsable_lieu)>0)
			{
				F3::set('responsable', $responsable_lieu);

				if ( $responsable_lieu[0]['id'] == $individu_id )
				{
					F3::set('estResponsable', 1);
				}
			}

			//Affichage de tous les membres dans la liste
			$membres_lieu = DB::sql("SELECT DISTINCT individus.id, individus.nom, individus.prenom FROM individus, affectations, vacations, lieux, festivals_jours WHERE affectations.individu_id=individus.id AND vacations.id=affectations.vacation_id AND lieux.id=vacations.lieu_id AND lieux.id=$lieu_id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id");
			if(count($membres_lieu)>0)
			{
				F3::set('nb_membres', count($membres_lieu));
				F3::set('membres', $membres_lieu);
			}
		}

		F3::set('pagetitle','Mon lieu');
		F3::set('template','lieu');
		F3::call('outils::generer');
	}

	static function ajouter() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT domaines.id, libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ORDER BY libelle;");
		F3::set('domaines',F3::get('DB')->result);
		F3::set('pagetitle','Ajouter un lieu');
		F3::set('template','form_lieux');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('lieux::verif_libelle|lieux::verif_responsable_id');
		if (!F3::exists('message')) {
			$lieux=new Axon('lieux');
			$lieux->copyFrom('REQUEST');
			$lieux->save();

			$lieux_id = $lieux->_id;
			// Enregistrement du responsable
			$resp_lieux=new Axon('responsables_lieux');
			$resp_lieux->id='';
			$resp_lieux->festival_id= F3::get('SESSION.festival_id');
			$resp_lieux->lieu_id= $lieux_id;
			$resp_lieux->individu_id= F3::get('REQUEST.responsable_id');
			$resp_lieux->save();
			historique::logger("Création du lieu numéro $lieux_id");
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/lieux');
		}
		// Ré-Affichage du formulaire
		F3::call('lieux::ajouter');
	}

	static function editer() {
		outils::activerJqgrid();
		F3::set('type','lieu');
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');

		$festival_id = F3::get('SESSION.festival_id');
		$id = F3::get('PARAMS.id');

		$lieux=new Axon('lieux');
		$lieux->load("id=$id");
		if (!$lieux->dry() && is_numeric($id) ) {
			$lieux->copyTo('REQUEST');

			DB::sql("SELECT domaines.id, libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ORDER BY libelle;");
			F3::set('domaines',F3::get('DB')->result);

			DB::sql("SELECT individu_id FROM responsables_lieux WHERE festival_id = $festival_id AND lieu_id = $id;");
			$result = F3::get('DB')->result;
			if(!isset($result[0]))$responsable_id=0;
			else
			$responsable_id = $result[0]['individu_id']; //A modifier si plusieurs responsables
			F3::set('REQUEST.responsable_id',$responsable_id);
			if ($responsable_id != '') {
				$individus=new Axon('individus');
				$individus->load("id=$responsable_id");
				F3::set('REQUEST.responsable', $individus->prenom . ' ' . $individus->nom . ' - ' . outils::date_sql_fr($individus->date_naissance));
			}
				
			$jours = DB::sql("SELECT id, jour FROM festivals_jours WHERE festival_id = $festival_id");
			if (count($jours) > 0)
			F3::set('jours', $jours);

			F3::set('pagetitle','Editer un lieu');
			F3::set('editer','editer');
			F3::set('template','form_lieux');
			F3::call('outils::generer');
		}
		else
		F3::http404();
	}

	static function editer_post() {
		F3::set('type','lieu');
		F3::call('outils::verif_responsable');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('lieux::verif_libelle|lieux::verif_responsable_id');
		if (!F3::exists('message')) {
			$festival_id = F3::get('SESSION.festival_id');
			$lieu_id = F3::get('PARAMS.id');
			if(!is_numeric($lieu_id)) $lieu_id = 0;
			// Pas d'erreur, enregistrement de la organisme
			$lieux=new Axon('lieux');
			$lieux->load("id=$lieu_id");
			$lieux->copyFrom('REQUEST');
			$lieux->save();

			//Suppression du responsable actuel
			DB::sql("DELETE FROM responsables_lieux WHERE festival_id = $festival_id AND lieu_id = $lieu_id;");
			// Enregistrement du responsable
			$resp_lieux=new Axon('responsables_lieux');
			$resp_lieux->id='';
			$resp_lieux->festival_id= $festival_id;
			$resp_lieux->lieu_id= $lieu_id;
			$resp_lieux->individu_id= F3::get('REQUEST.responsable_id');
			$resp_lieux->save();
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			historique::logger("Édition du lieu numéro $lieu_id");
			F3::reroute('/lieux');
		}
		// Ré-Affichage du formulaire
		F3::call('domaines::editer');
	}

	static function suppr_vacation() {
	}

	static function ajouter_vacation() {
	}


	static function afficher() {

	}


	/* Validation des formulaires */

	static function verif_libelle() {
		F3::input('libelle',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Libelle non renseigné');
				elseif (strlen($value)>255)
				F3::set('message','Libelle trop long');
			}
		}
		);
	}


	static function verif_domaine_id() {
		F3::input('domaine_id',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]+$/', $value) == 0 )
				F3::set('message','Domaine incorrect');
			}
		}
		);
	}

	static function verif_lieu_id() {
		F3::input('lieu_id',
		function($value) {
			if (!F3::exists('message')) {
				if ( (preg_match('/^[0-9]+$/', $value) == 0) || $value == 0)
				F3::set('message','Lieu incorrect');
			}
		}
		);
	}
	static function verif_responsable_id() {
		F3::input('responsable_id',
		function($value) {
			if (!F3::exists('message')) {
				if ( (preg_match('/^[0-9]+$/', $value) == 0) || $value == 0)
				F3::set('message','Responsable incorrect');
			}
		}
		);
	}


	static function est_responsable() {
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		if(!isset($individu_id) || !isset($festival_id)) return false;
		$lieu_id = F3::get('PARAMS.id');
		if(!is_numeric($lieu_id)) return false;
		DB::sql("SELECT domaine_id FROM lieux WHERE id = $lieu_id;");
		$result = F3::get('DB')->result;
		$domaine_id = $result[0]['domaine_id'];
		DB::sql("SELECT COUNT(id) as count FROM responsables_domaines WHERE individu_id = $individu_id AND festival_id = $festival_id AND domaine_id = $domaine_id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];
		if($result[0]['count'] == 1) return true;
		return false;
	}





}

?>
