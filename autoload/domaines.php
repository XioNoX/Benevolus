<?php
class Domaines {


	static function lister() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
    outils::activerJqgrid();
		F3::set('pagetitle','Liste de tous les domaines');
		F3::set('lien_ajouter','<a href=/domaines/ajouter>Ajouter un domaine</a>');
		F3::set('jquery_url_list','/ajax/domaines');
		F3::set('jquery_url_edit','/domaines/editer/');
		F3::set('jquery_url_edit2','/domaines/editer');
		F3::set('jquery_largeur','100%');
		F3::set('jquery_col_names',"['id','Libelle']");

		F3::set('jquery_col_model',"[
	      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
	      {name:'libelle', index:'libelle', width:200}
	    ]");
		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function lister_festival() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
    outils::activerJqgrid();
		F3::set('pagetitle','Liste des domaines du festival courant');
		F3::set('lien_ajouter','<a href=/domaines/ajouter>Ajouter un domaine</a> - <a href=/domaines/tous>Tous les domaines</a>');
		F3::set('jquery_url_list','/ajax/domaines_festival');
		F3::set('jquery_url_edit','/domaines/editer/');
		F3::set('jquery_url_edit2','/domaines/editer_festival');
		F3::set('jquery_largeur','100%');
		F3::set('jquery_col_names',"['id','Libelle']");

		F3::set('jquery_col_model',"[
	      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
	      {name:'libelle', index:'libelle', width:200}
	    ]");
		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function affecter() {
    outils::activerJqgrid();
		F3::set('type','domaine');
		F3::call('outils::verif_responsable');
		F3::call('vacations::affecter');
	}
	static function affecter_post() {
		F3::set('type','domaine');
		F3::call('outils::verif_responsable');
		F3::call('vacations::affecter_post');
	}

	static function editer_tableau_post() {
		F3::set('type','domaine');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				F3::set('PARAMS.id',$id);
        $organismes=new Axon('domaines');
			  $organismes->load("id=$id");			 
				if(outils::est_admin())
        {
          DB::sql('DELETE FROM domaines WHERE id = :id ;',array(':id'=>array($id,PDO::PARAM_INT)));
          historique::logger("Suppréssion du domaine ". $organismes->libelle);
        } 
			}
		}
	}

	static function editer_tableau_festival_post() {
		F3::set('type','domaine');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$festival_id = F3::get('SESSION.festival_id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				F3::set('PARAMS.id',$id);
        $organismes=new Axon('domaines');
			  $organismes->load("id=$id");			 
				if(outils::est_admin())
        {
          DB::sql('DELETE FROM responsables_domaines WHERE domaine_id = :id AND festival_id = :festival_id ;',array(':id'=>array($id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT)));
          historique::logger("Suppréssion du domaine pour le festivla courant ". $organismes->libelle);
        } 
			}
		}
	}

	static function recuperation_domaines()
	{	
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
	
		$domaines = DB::sql("SELECT d.* FROM individus AS i, affectations AS a, vacations AS v, lieux AS l, domaines AS d, festivals_jours AS fj WHERE i.id=$individu_id AND a.individu_id=i.id AND v.id=a.vacation_id AND l.id=v.lieu_id AND d.id=l.domaine_id AND v.festival_jour_id=fj.id AND fj.festival_id=$festival_id UNION SELECT d.* FROM domaines AS d, responsables_domaines AS rd WHERE rd.individu_id=$individu_id AND rd.festival_id=$festival_id AND rd.domaine_id=d.id");
		
		if(count($domaines)>0)
		{
			F3::set('domaines', $domaines);			
		}
	}
	
	static function ajax_responsable() {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$id = F3::get('PARAMS.id');

      DB::sql("SELECT individu_id, nom, prenom, date_naissance FROM responsables_domaines, individus WHERE responsables_domaines.individu_id = individus.id AND festival_id = $festival_id AND domaine_id = $id;");
      $result = F3::get('DB')->result[0];

		echo $result['individu_id'] . ';'. $result['prenom'] . " " . $result['nom'] . " - " . $result['date_naissance'];
	}
	
	static function membres_domaine($domaine_id, $festival_id){	
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
  		$membres = DB::sql("SELECT DISTINCT i.* FROM individus AS i, affectations AS a, vacations as v, lieux AS l WHERE i.id=a.individu_id AND a.vacation_id=v.id AND v.lieu_id=l.id AND l.domaine_id=$domaine_id UNION SELECT i.* FROM individus AS i, responsables_lieux AS rl, lieux AS l WHERE i.id=rl.individu_id AND rl.lieu_id=l.id AND l.domaine_id=$domaine_id AND rl.festival_id=$festival_id UNION SELECT i.* FROM individus AS i, responsables_lieux AS rl, lieux AS l WHERE i.id=rl.individu_id AND rl.lieu_id=l.id AND l.domaine_id=$domaine_id AND rl.festival_id=$festival_id UNION SELECT i.* FROM individus AS i, responsables_domaines AS rd WHERE i.id=rd.individu_id AND rd.domaine_id=$domaine_id AND rd.festival_id=$festival_id");
  		return F3::get('DB')->result;
	}
	
	static function mes_domaines() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
				
		F3::set('mesdomaines', 1);
		F3::call('domaines::recuperation_domaines');

		F3::set('pagetitle','Mes domaines');
		F3::set('template','domaine');
		F3::call('outils::generer');
	}
	
	static function mon_domaine() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');

		F3::call('domaines::recuperation_domaines');
		
		$individu_id = F3::get('SESSION.id');
		$festival_id = F3::get('SESSION.festival_id');
		$domaine_id = F3::get('PARAMS.id');
		
		$domaine_individu = DB::sql("Select domaines.* FROM individus, affectations, vacations, lieux, domaines WHERE individus.id=$individu_id AND affectations.individu_id=individus.id AND vacations.id=affectations.vacation_id AND lieux.id=vacations.lieu_id AND domaines.id=lieux.domaine_id AND domaines.id=$domaine_id");
		
		//Vérification de l'appartenance au domaine
		if(count($domaine_individu)>0)
		{
			F3::set('domaine', $domaine_individu);
			
			//Récupération du responsable
			$responsable_domaine = DB::sql("SELECT i.id, nom, prenom FROM individus AS i, responsables_domaines AS rd WHERE i.id=rd.individu_id AND rd.domaine_id=$domaine_id AND rd.festival_id=$festival_id");
			
			if(count($responsable_domaine)>0)
			{
				F3::set('responsable', $responsable_domaine);
				
				if ( $responsable_domaine[0]['id'] == F3::get('SESSION.id') )
				{
					F3::set('estResponsable', 1);
				}
			}
			
			//Récupération des membres
			DB::sql("SELECT COUNT(DISTINCT individus.id) AS count FROM individus, affectations, vacations, lieux, domaines, festivals_jours WHERE affectations.individu_id=individus.id AND vacations.id=affectations.vacation_id AND lieux.id=vacations.lieu_id AND domaines.id=lieux.domaine_id AND domaines.id=$domaine_id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id");
			$nb_membres = F3::get('DB')->result;
			F3::set('nb_membres', $nb_membres[0]['count']);
			
			//Affichage de tous les membres dans la liste
//			$membres_domaine = DB::sql("SELECT DISTINCT individus.id, individus.nom, individus.prenom FROM individus, affectations, vacations, lieux, domaines, festivals_jours WHERE affectations.individu_id=individus.id AND vacations.id=affectations.vacation_id AND lieux.id=vacations.lieu_id AND domaines.id=lieux.domaine_id AND domaines.id=$domaine_id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id");
//			if(count($membres_domaine)>0)
//			{
//				F3::set('membres', $membres_domaine);
//			}
		}

		F3::set('pagetitle','Mon domaine');
		F3::set('template','domaine');
		F3::call('outils::generer');
	}

	static function ajouter() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
    	outils::activerJquery();
		F3::set('pagetitle','Ajouter un domaine');
		F3::set('template','form_domaines');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('domaines::verif_libelle|domaines::verif_responsable_id');
		if (!F3::exists('message')) {
			// Pas d'erreur, enregistrement de la organisme
			$domaines=new Axon('domaines');
			$domaines->copyFrom('REQUEST');
			$domaines->save();

			$domaine_id = $domaines->_id;
			// Enregistrement du responsable
			$resp_domaines=new Axon('responsables_domaines');
			$resp_domaines->id='';
			$resp_domaines->festival_id= F3::get('SESSION.festival_id');
			$resp_domaines->domaine_id= $domaine_id;
			$resp_domaines->individu_id= F3::get('REQUEST.responsable_id');
			$resp_domaines->save();
      historique::logger("Création du domaine numéro $domaine_id");
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/domaines');
		}
		// Ré-Affichage du formulaire
		F3::call('domaines::ajouter');
	}

	static function editer() {
		F3::set('type','domaine');
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');
    outils::activerJqgrid();
		$id = F3::get('PARAMS.id');
		if(!is_numeric($id)) $id = 0;
		$festival_id = F3::get('SESSION.festival_id');
		$domaines=new Axon('domaines');
		$domaines->load("id=$id");
		if (!$domaines->dry()) {
			$domaines->copyTo('REQUEST');

			DB::sql("SELECT individu_id FROM responsables_domaines WHERE festival_id = $festival_id AND domaine_id = $id;");
			$result = F3::get('DB')->result;
			if(!isset($result[0]))$responsable_id=0;
			else
			$responsable_id = $result[0]['individu_id']; //A modifier si plusieurs responsables
			F3::set('REQUEST.responsable_id',$responsable_id);
			if ($responsable_id != '') {
				$individus=new Axon('individus');
				$individus->load("id=$responsable_id");
				F3::set('REQUEST.responsable', $individus->prenom . ' ' . $individus->nom . ' - ' .$individus->date_naissance);
			}
			F3::set('pagetitle','Editer un domaine');
			F3::set('editer','editer');
			F3::set('type','domaines');
			F3::set('liste_vacations','liste_vacations');
			F3::set('template','form_domaines');
			F3::call('outils::generer');
		}
		else F3::http404();
	}

	static function editer_post() {
		F3::set('type','domaine');
		F3::call('outils::verif_responsable');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('domaines::verif_libelle|domaines::verif_responsable_id');
		if (!F3::exists('message')) {
			$festival_id = F3::get('SESSION.festival_id');
			$domaine_id = F3::get('PARAMS.id');
			if(!is_numeric($domaine_id)) $domaine_id = 0;
			// Pas d'erreur, enregistrement de la organisme
			$domaines=new Axon('domaines');
			$domaines->load("id=$domaine_id");
			$domaines->copyFrom('REQUEST');
			$domaines->save();

			//Suppression du responsable actuel
			DB::sql("DELETE FROM responsables_domaines WHERE festival_id = $festival_id AND domaine_id = $domaine_id;");
			// Enregistrement du responsable
			$resp_domaines=new Axon('responsables_domaines');
			$resp_domaines->id='';
			$resp_domaines->festival_id= $festival_id;
			$resp_domaines->domaine_id= $domaine_id;
			$resp_domaines->individu_id= F3::get('REQUEST.responsable_id');
			$resp_domaines->save();
			// Retour à la liste des festivals. Le nouveau festival doit être présent
      historique::logger("Édition du domaine numéro $domaine_id");
			F3::reroute('/domaines');
		}
		// Ré-Affichage du formulaire
		F3::call('domaines::editer');
	}

	/* Validation des formulaires */
	static function verif_responsable_id() {
		F3::input('responsable_id',
		function($value) {
			if (empty($value))
			F3::set('message','Responsable non renseigné');
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]+$/', $value) == 0 )
				F3::set('message','Responsable incorrect');
			}
		}
		);
	}

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


	static function est_responsable() {
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		if(!isset($individu_id) || !isset($festival_id)) return false;
		$entite_id = F3::get('PARAMS.id');
		if(isset($entite_id))
		{
			if(!is_numeric($entite_id)) return false;
			DB::sql("SELECT COUNT(id) as count FROM responsables_domaines WHERE individu_id = $individu_id AND festival_id = $festival_id AND domaine_id = $entite_id;");
		}
		else
		DB::sql("SELECT COUNT(id) as count FROM responsables_domaines WHERE individu_id = $individu_id AND festival_id = $festival_id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];
		if($result[0]['count'] == 1) return true;
		return false;
	}

}

?>
