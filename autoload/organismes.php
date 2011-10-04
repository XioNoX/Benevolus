<?php
class Organismes {


	static function lister() {
		outils::activerJqgrid();
		F3::set('type','organisme');
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::set('pagetitle','Liste des organismes');
		F3::set('lien_ajouter','<a href=/organismes/ajouter>Ajouter un organisme</a> - <a href=/organismes/imprimer>Imprimer la liste des organismes</a>');
		F3::set('jquery_url_list','/ajax/organismes');
		F3::set('jquery_url_edit','/organismes/editer/');
		F3::set('jquery_url_edit2','/organismes/editer');
		F3::set('jquery_largeur','975');
		F3::set('jquery_col_names',"['id','Libelle','Ville','Nb membres','Type']");

		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
      {name:'libelle', index:'libelle', width:90}, 
      {name:'nom', index:'nom', width:90}, 
      {name:'nb_membres', index:'nb_membres', width:90}, 
      {name:'type_organ', index:'type_struct', width:80} 
    ]");

		F3::set('template','liste_generique2');
		F3::call('outils::generer');
	}

	static function membres_organisme($organisme_id, $festival_id){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql('SELECT individus.id, nom, prenom FROM individus, historique_organismes WHERE individus.id = historique_organismes.individu_id AND festival_id = :festival_id AND organisme_id = :organisme_id;',array(':festival_id'=>array($festival_id,PDO::PARAM_INT),':organisme_id'=>array($organisme_id,PDO::PARAM_INT)));
		return F3::get('DB')->result;
	}

	static function organisme_individu($individu_id, $festival_id){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql('SELECT historique_organismes.organisme_id FROM historique_organismes WHERE historique_organismes.individu_id = :individu_id AND historique_organismes.festival_id = :festival_id;',array(':festival_id'=>array($festival_id,PDO::PARAM_INT),':individu_id'=>array($individu_id,PDO::PARAM_INT)));
		return F3::get('DB')->result[0]['organisme_id'];
	}
	
	static function responsable_organisme($organisme_id, $festival_id){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql('SELECT individu_id FROM historique_organismes WHERE organisme_id = :organisme_id AND historique_organismes.festival_id = :festival_id AND responsable=1;',array(':organisme_id'=>array($organisme_id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT)));
		return F3::get('DB')->result[0]['individu_id'];
	}

	static function comptage($organisme_id, $type, $festival_id =""){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		switch ($type) {
			case "travailles":
				DB::sql("SELECT COUNT(DISTINCT affectations.individu_id) as count FROM `vacations`, `festivals_jours` , `affectations`, `historique_organismes` WHERE affectations.heure_debut!=''  AND affectations.pas_travaille=0 AND festivals_jours.festival_id = $festival_id AND `vacations`.festival_jour_id = festivals_jours.id AND `vacations`.id = affectations.vacation_id AND `affectations`.individu_id = `historique_organismes`.individu_id AND `historique_organismes`.festival_id = $festival_id AND `historique_organismes`.organisme_id = $organisme_id;");
				break;
			case 'absents':
				DB::sql("SELECT COUNT(id) as count FROM `historique_organismes` WHERE `festival_id` = $festival_id AND `organisme_id` = $organisme_id AND `present`= 0");
				break;
		}
		return F3::get('DB')->result[0]['count'];
	}
	
	static function don_a_faire($heures){
		DB::sql('SELECT taux_horaire FROM festivals WHERE festivals.id = :festival_id;',array(':festival_id'=>array(F3::get('SESSION.festival_id'),PDO::PARAM_INT)));
		$taux = floatval(preg_replace("/,/",".",F3::get('DB')->result[0]['taux_horaire']));
		return $heures*$taux;
	}
	
	static function heures_travaillees($organisme_id,$sortie_type){
		$festival_id = F3::get('SESSION.festival_id');
		$nb_heures = 0;
		$nb_minutes = 0;
		$nb_secondes = 0;
		DB::sql("SELECT `affectations`.* FROM `vacations`, `festivals_jours` , `affectations`, `historique_organismes` WHERE festivals_jours.festival_id = $festival_id AND `vacations`.festival_jour_id = festivals_jours.id AND `vacations`.id = affectations.vacation_id AND `affectations`.individu_id = `historique_organismes`.individu_id AND `historique_organismes`.festival_id = $festival_id AND `historique_organismes`.organisme_id = $organisme_id;");
		foreach (F3::get('DB')->result as $row) {
			if ($row['pas_travaille'] != 1){ // Si la personne a bien travailler
				if(($row['heure_debut'] == $row['heure_fin']) && ($row['heure_debut'] == "00:00:00")){
					//cas des T0 hdébut == hfin
					$nb_heures =  $nb_heures + 8; //FIXME definir dans les options
					//echo($row['individu_id']);
				}

				$str_debut = strtotime($row['heure_debut']);
				$str_fin = strtotime($row['heure_fin']);
				$str_24h = strtotime("24:00:00");
				$str_00h = strtotime("00:00:00");


				if($str_debut < $str_fin){ //exemple  14h30 => 19h30
					$diff    =    $str_fin - $str_debut;
					if( $hours=intval((floor($diff/3600))) )
					$diff = $diff % 3600;
					if( $minutes=intval((floor($diff/60))) )
					$diff = $diff % 60;
					$diff = intval( $diff );
					$nb_heures =  $nb_heures + $hours;
					$nb_minutes = $nb_minutes + $minutes;
					$nb_secondes = $nb_secondes + $diff; //secondes
						
				}
				else if($str_debut > $str_fin){ //exemple 19h30 => 2h30
					$diff = ($str_24h - $str_debut) + ($str_fin - $str_00h);
						
					//echo intval($diff/3600);
					if( $hours=intval((floor($diff/3600))) )
					$diff = $diff % 3600;
					if( $minutes=intval((floor($diff/60))) )
					$diff = $diff % 60;
					$diff = intval($diff);
					$nb_heures =  $nb_heures + $hours;
					$nb_minutes = $nb_minutes + $minutes;
					$nb_secondes = $nb_secondes + $diff; //secondes
				}
			}
		}

		if( $nb_minutes = $nb_minutes + intval((floor($nb_secondes/60))) )
		$nb_secondes = $nb_secondes % 60;

		if( $nb_heures = $nb_heures + intval((floor($nb_minutes/60))) )
		$nb_minutes = $nb_minutes % 60;

		if($sortie_type == 1){
			$nb_heures = $nb_heures + $nb_minutes/60;

			return $nb_heures;
		}
		else
		return $nb_heures . 'h' . $nb_minutes . 'min';
	}

	static function recommandations() {
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		F3::call('vacations::affecter');
	}

	static function recommandations_post() {
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		F3::call('vacations::affecter_post');
	}

	static function editer_tableau_post() {
		F3::set('type','organisme');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				F3::set('PARAMS.id',$id);
				$organismes=new Axon('organismes');
				$organismes->load("id=$id");
				if(outils::est_responsable())
				{
					DB::sql("DELETE FROM organismes WHERE id=$id");
					historique::logger("Suppréssion de l'organisme ". $organismes->libelle);
				}



			}
		}
	}

	static function mon_organisme() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');

		$historique_organisme=new Axon('historique_organismes');
		$historique_organisme->load("festival_id='$festival_id' AND individu_id='$individu_id'");

		if(!$historique_organisme->dry())
		{
			F3::set('organisme','1');

			$organisme=new Axon('organismes');
			$organisme->load("id=$historique_organisme->organisme_id");
			$organisme->copyTo('REQUEST');

			//Savoir si la personne est responsable de cet organisme
			F3::set('REQUEST.responsable', $historique_organisme->responsable);

			//Recherche ville de cet organisme
			$ville=new Axon('villes');
			$ville->load("id=$organisme->ville_id");
			F3::set('REQUEST.cp', $ville->cp);
			F3::set('REQUEST.ville', $ville->nom);

			//Recherche responsable de cet organisme
			$historique_organisme_resp=new Axon('historique_organismes');
			$historique_organisme_resp->load("festival_id='$festival_id' AND responsable='1' AND organisme_id='$historique_organisme->organisme_id'");

			$responsable=new Axon('individus');
			$responsable->load("id='$historique_organisme_resp->individu_id'");
			F3::set('REQUEST.responsable_nom', $responsable->prenom . " " . $responsable->nom );
			F3::set('REQUEST.responsable_id', $responsable->id );
			$type=new Axon('organismes_types');
			$type->load("id='$organisme->organisme_type_id'");
			F3::set('type',$type->libelle);



			F3::set('effmax',$organisme->maximum);

			F3::set('heures_travaillees',organismes::heures_travaillees($historique_organisme->organisme_id,0));
				
				
			//Liste des membres
			DB::sql('SELECT individus.id, nom, prenom FROM individus, historique_organismes WHERE individus.id = historique_organismes.individu_id AND festival_id = :festival_id AND organisme_id = :organisme_id ORDER BY individus.nom;',array(':festival_id'=>array(F3::get('SESSION.festival_id'),PDO::PARAM_INT),':organisme_id'=>array($historique_organisme->organisme_id,PDO::PARAM_INT)));

			if($organisme->organisme_type_id != 2) //XXX gestion du type d'organisme individu
			{
				F3::set('REQUEST.membres', F3::get('DB')->result);
			}
			F3::set('REQUEST.nb_membres', count(F3::get('DB')->result));

			F3::set('pagetitle','Mon organisme - ' . $organisme->libelle);
		}
		else
		{
			F3::set('pagetitle','Mon organisme');
		}

		F3::set('template','organisme');
		F3::call('outils::generer');
	}

	static function ajouter() {
		outils::activerJquery();
		F3::set('type','organisme');
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$organismes_types=new Axon('organismes_types');
		F3::set('types',$organismes_types->afind());
		F3::set('pagetitle','Ajouter un organisme');
		F3::set('template','form_organismes');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::set('type','organisme');
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('organismes::verif_libelle|organismes::verif_adresse1|organismes::verif_adresse2|organismes::verif_ville_id|organismes::verif_organisme_type_id|organismes::verif_maximum|organismes::verif_responsable_id');
		if (!F3::exists('message')) {
			// Pas d'erreur, enregistrement de la organisme
			$organismes=new Axon('organismes');
			$organismes->copyFrom('REQUEST');
			$organismes->save();
			
			$organisme_id = $organismes->_id;
			// Enregistrement du responsable
			$hist_organismes=new Axon('historique_organismes');
			$hist_organismes->id='';
			$hist_organismes->responsable=1;
			$hist_organismes->festival_id= F3::get('SESSION.festival_id');
			$hist_organismes->organisme_id= $organisme_id;
			$hist_organismes->individu_id= F3::get('REQUEST.responsable_id');
			$hist_organismes->present=0;
			$hist_organismes->save();
			historique::logger("Création de l'organisme numéro $organisme_id");
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/organismes');
		}
		// Ré-Affichage du formulaire
		F3::call('organismes::ajouter');
	}

	static function editer() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		
		$festival_id = F3::get('SESSION.festival_id');
		$organisme_id = F3::get('PARAMS.id');
		if(!is_numeric($organisme_id)) $organisme_id = 0;

		$organismes=new Axon('organismes');
		$organismes->load("id=$organisme_id");
		if (!$organismes->dry()) {
			$organismes->copyTo('REQUEST');

			$ville_id=$organismes->ville_id;
			$villes=new Axon('villes');
			$villes->load("id=$ville_id");
			F3::set('REQUEST.ville',$villes->cp . ' - ' .$villes->nom);

			$organismes_types=new Axon('organismes_types');
			F3::set('types',$organismes_types->afind());
			DB::sql("SELECT individu_id FROM historique_organismes WHERE responsable = 1 AND festival_id = $festival_id AND organisme_id = $organisme_id;");
			$result = F3::get('DB')->result;
			if(!isset($result[0]))$responsable_id=0;
			else
			$responsable_id = $result[0]['individu_id']; //A modifier si plusieurs responsables
			F3::set('REQUEST.responsable_id',$responsable_id);

			if ($responsable_id != '') {
				$individus=new Axon('individus');
				$individus->load("id=$responsable_id");
				F3::set('REQUEST.responsable', $individus->prenom . ' ' . $individus->nom . ' - ' .outils::date_sql_fr($individus->date_naissance));
				F3::set('infosResponsable', '<a href=tel:' . $individus->telephone_fixe . '>'. $individus->telephone_fixe .'</a> - <a href=tel:' . $individus->telephone_portable . '>'. $individus->telephone_portable .'</a> - <a href=mailto:' . $individus->email . '>' . $individus->email. '</a>');
			}
			
			F3::set('nb_travailles',organismes::comptage($organisme_id, "travailles"));
			F3::set('nb_absents',organismes::comptage($organisme_id, "absents"));
			
			$membres = DB::sql("SELECT individu_id, nom, prenom, date_naissance FROM historique_organismes, individus WHERE individus.id=historique_organismes.individu_id AND responsable = 0 AND festival_id = $festival_id AND organisme_id = $organisme_id ORDER BY individus.nom;");
			F3::set('nb_membres', count($membres));
			$listeMembres = "<ol>";
			
			F3::set('redirect', '/organismes/editer/'.$organisme_id.'', true);
			
			foreach($membres as $cle=>$valeur)
			{
				if (Disponibilites::verif_dispos($valeur["individu_id"], ''))
				$listeMembres .= "<li><a href='/profils/editer/".$valeur['individu_id']."'>". strtoupper($valeur['nom']) . " ". $valeur['prenom'] ."</a> - <a href='/disponibilites/".$valeur['individu_id']."/gerer'><font color='green'>Disponibilitées</font></a> - <a href='/vacations/imprimer/".$valeur['individu_id']."'>Convocation</a> - <a id=".$valeur['individu_id']." class='supprimerMembre' href='#'>X</a></li>";
				else
				$listeMembres .= "<li><a href='/profils/editer/".$valeur['individu_id']."'>". strtoupper($valeur['nom']) . " ".$valeur['prenom']." </a> - <a href='/disponibilites/".$valeur['individu_id']."/gerer'><font color='red'>Disponibilitées</font></a> - <a href='/vacations/imprimer/".$valeur['individu_id']."'>Convocation</a> - <a id=".$valeur['individu_id']." class='supprimerMembre' href='#'>X</a></li>";

			}
			$listeMembres .= "</ol>";
				
			F3::set('listeMembres', $listeMembres);

			DB::sql("SELECT invitations.id, invitations.uuid, invitations.nom, invitations.prenom, invitations.date_naissance, invitations.email, invitations.date_invitation, invitations.valide FROM `invitations` WHERE organisme_id = $organisme_id ORDER BY invitations.id, invitations.valide;");
			F3::set('listeAttente',F3::get('DB')->result);


			F3::set('pagetitle','Editer un organisme');
			F3::set('editer','editer');
			F3::set('template','form_organismes');
			F3::call('outils::generer');
		}
		else
		F3::http404();
	}

	static function editer_post() {
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		F3::clear('message'); //Efface le message d'erreur au cas ou si présent
		F3::call('organismes::verif_libelle|organismes::verif_adresse1|organismes::verif_adresse2|organismes::verif_ville_id|organismes::verif_organisme_type_id|organismes::verif_maximum|organismes::verif_responsable_id'); //Verifications de la saisie
		$organisme_id = F3::get('PARAMS.id');
		//Si pas d'erreur dans la saisie, début du traitement
		if (!F3::exists('message')) {
			$organismes=new Axon('organismes'); //Chargement de la table organismes
			$organismes->load("id=$organisme_id"); //chargement de la bonne organisme
			$organismes->copyFrom('REQUEST'); //Changement des champs
			$organismes->save(); //Enregistrement

			$responsable_id = F3::get('REQUEST.responsable_id'); //Récupération du responsable_id
			if(!is_numeric($responsable_id)) $responsable_id = 0;
			$festival_id = F3::get('SESSION.festival_id'); //Récupération du festival_id
			$organisme_id = F3::get('PARAMS.id');
			if(!is_numeric($organisme_id)) $organisme_id = 0;
			DB::sql("SELECT COUNT(id) AS count FROM historique_organismes WHERE individu_id = $responsable_id AND festival_id = $festival_id AND organisme_id = $organisme_id;"); //Recherche si il est déjà membre
			$result = F3::get('DB')->result;
			$result = $result[0]['count'];

			//Changement statut responsable > membre
			DB::sql("UPDATE historique_organismes SET responsable = 0 WHERE festival_id = $festival_id AND organisme_id = $organisme_id;");

			$hist_organismes=new Axon('historique_organismes'); //Chargement de la table historique organismes

			//Si pas déjà membre de la organisme
			if($result == 0){
				//On le rajoute
				$hist_organismes->id='';
				$hist_organismes->responsable = 1;
				$hist_organismes->festival_id = $festival_id;
				$hist_organismes->organisme_id = $organisme_id;
				$hist_organismes->individu_id = $responsable_id;
				$hist_organismes->present = 0;
				$hist_organismes->save();
			}
			else
			{
				//On change son statut de membre a responsable
				DB::sql("UPDATE historique_organismes SET responsable = 1 WHERE festival_id = $festival_id AND organisme_id = $organisme_id AND individu_id = $responsable_id;");
			}
			historique::logger("Édition de l'organisme numéro $organisme_id");
			F3::reroute('/organismes');
		}
		F3::call('organismes::editer');
	}
	static function ajax_action(){
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		$festival_id = F3::get('SESSION.festival_id');
		$organisme_id = F3::get('PARAMS.id');
		$action = F3::get('REQUEST.action');
		switch ($action){
			case 'supprimer_affectations' :
				echo organismes::supprimer_affectations($organisme_id,$festival_id);
				break;		
		}
	
	}
	
	static function supprimer_affectations($organisme_id, $festival_id=""){
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql("DELETE FROM `affectations` WHERE individu_id IN (SELECT individu_id FROM `historique_organismes` WHERE responsable =0 AND organisme_id = $organisme_id AND festival_id = $festival_id) ;");
		return "OK";
	}
	
	

	static function suppr_membre(){
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		$festival_id = F3::get('SESSION.festival_id');
		$organisme_id = F3::get('PARAMS.id');
		if(!is_numeric($organisme_id)) $organisme_id = 0;
		$individu_id = F3::get('REQUEST.id');
		if(!is_numeric($individu_id)) $individu_id = 0;
		$historique=new Axon('historique_organismes');
		$historique->load("individu_id='$individu_id' AND organisme_id='$organisme_id' AND festival_id='$festival_id'");
		historique::logger("Suppréssion du membre numéro " . $individu_id . " de l'organisme ". $organisme_id . " pour le festival " . $festival_id);
		$historique->erase();
		echo "OK";


	}


	static function suppr_invitation() {
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');

		if (!F3::exists('message')) {
			$organisme_id = F3::get('PARAMS.id');

			if(!is_numeric($organisme_id)) $organisme_id = 0;
			$invitation_id = F3::get('REQUEST.id');
			if(!is_numeric($invitation_id)) $invitation_id = 0;
			$invitation=new Axon('invitations');
			$invitation->load("id='$invitation_id'");
			historique::logger("Suppréssion de l'invitation de ". $invitation->prenom . " " . $invitation->nom . " dans l'organisme ". $invitation->organisme_id);
			$invitation->erase();
			echo "OK";

		}
		else echo "Erreur";

	}

	static function organisme_rempli($organisme_id){
		$festival_id = F3::get('SESSION.festival_id');
		$organisme=new Axon('organismes'); //Chargement de la table organismes
		$organisme->load("id=$organisme_id");
		$maximum = $organisme->maximum;
		DB::sql("SELECT (SELECT COUNT(id) FROM `historique_organismes` WHERE `festival_id` = $festival_id AND `organisme_id` = $organisme_id) + (SELECT count(id) FROM `invitations` WHERE organisme_id = $organisme_id) as count;");
		$result = F3::get('DB')->result;
		$nb_personnes = $result[0]['count'];
		if($nb_personnes >= $maximum) return true;
		return false;
	}

	static function ajouter_membre() {
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		F3::call('profils::verif_nom');
		F3::call('profils::verif_prenom');
		F3::call('profils::verif_date_naissance');

		$organisme_id = F3::get('PARAMS.id');
		$festival_id = F3::get('SESSION.festival_id'); //Récupération du festival_id

		$invitation = F3::get('REQUEST.invitation');
		$individu_id = F3::get('REQUEST.id');

		if($invitation == '0'){
			DB::sql("SELECT count(individus.id) as count FROM individus, historique_organismes WHERE individus.id = :individu_id AND individus.statut_id = 3 AND individus.id = historique_organismes.individu_id AND organisme_id = :organisme_id ;",array(':organisme_id'=>array($organisme_id,PDO::PARAM_INT),':individu_id'=>array($individu_id,PDO::PARAM_INT)));
			if ((F3::get('DB')->result[0]['count'] == 1)&& !organismes::organisme_rempli($organisme_id)){
				DB::sql('SELECT count(id) as count FROM historique_organismes WHERE historique_organismes.individu_id = :individu_id AND festival_id = :festival_id ;',array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':festival_id'=>array(F3::get('SESSION.festival_id'),PDO::PARAM_INT)));
				if(F3::get('DB')->result[0]['count'] == 0){
					$historique_organismes=new Axon('historique_organismes');
					$historique_organismes->responsable = 0;
					$historique_organismes->organisme_id =  $organisme_id;
					$historique_organismes->individu_id = $individu_id;
					$historique_organismes->festival_id = $festival_id;
					$hist_organismes->present = 0;
					$historique_organismes->save();
				}
				else
				DB::sql('UPDATE `historique_organismes` SET organisme_id= :organisme_id, responsable=0 WHERE individu_id = :individu_id AND festival_id = :festival_id ;', array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT),':organisme_id'=>array($organisme_id,PDO::PARAM_INT)));
				historique::logger("Ajout de l'individu $individu_id  dans l'organisme $organisme_id");
				exit("OK");
			}
			exit("Cette personne n'est pas valide ou l'organisme est complet.");
		}

		if(organismes::organisme_rempli($organisme_id))  F3::set('message',"Nombre maximum d'inscrits (ou en attente) atteint (responsable inclus)");

		if (!F3::exists('message')) {
			$emailInvite = F3::get('REQUEST.email');
			if (!filter_var($emailInvite, FILTER_VALIDATE_EMAIL) && $emailInvite != "") exit("Email incorrect");

			$parrain_id = F3::get('SESSION.id');

			$nom_individu = F3::get('REQUEST.nom');
			$prenom_individu = F3::get('REQUEST.prenom');
			$ddn_individu = outils::date_fr_sql(F3::get('REQUEST.date_naissance'));

			DB::sql('SELECT COUNT(id) AS count FROM `invitations` WHERE  prenom = :prenom_individu AND date_naissance = :ddn_individu AND nom = :nom_individu AND organisme_id = :organisme_id ;',array(':nom_individu'=>array($nom_individu,PDO::PARAM_STR),':prenom_individu'=>array($prenom_individu,PDO::PARAM_STR),':ddn_individu'=>array($ddn_individu,PDO::PARAM_STR),':organisme_id'=>array($organisme_id,PDO::PARAM_INT)));  //Recherche si il est déjà en cours d'invitation
			$result = F3::get('DB')->result;

			//Si pas déjà présent, on le rajoute
			if($result[0]['count'] == 0){
				$uuid = uniqid();
				$invitations=new Axon('invitations'); //Chargement de la table historique organismes
				$invitations->id='';
				$invitations->uuid = $uuid;
				$invitations->nom = $nom_individu;
				$invitations->prenom = $prenom_individu;
				$invitations->date_naissance = $ddn_individu;
				$invitations->valide = 0;
				$invitations->email = $emailInvite;
				$invitations->organisme_id = $organisme_id;
				$invitations->parrain_id = $parrain_id;
				$invitations->date_invitation = date('Y-m-d H:i:s');
				$invitations->save();

				historique::logger("Nouvelle invitation de $prenom_individu $nom_individu dans l'organisme numéro $organisme_id");
				echo "OK";

			}
			else echo "Cette personne est déjà inscrite ou en cours d'invitation.";
		}
		else echo F3::get('message');
	}

	static function tous_membres() {
		$organisme_id = F3::get('PARAMS.id');
		F3::set('type','organisme');
		F3::call('outils::verif_responsable');
		$recherche = mysql_escape_string(F3::get('REQUEST.term'));

		DB::sql("SELECT DISTINCT individus.id, individus.nom, individus.prenom, individus.date_naissance FROM individus, historique_organismes WHERE individus.statut_id = 3 AND individus.id = historique_organismes.individu_id AND organisme_id = :organisme_id AND CONCAT(individus.prenom, ' ', individus.nom) LIKE :recherche ;",array(':organisme_id'=>array($organisme_id,PDO::PARAM_INT),':recherche'=> '%' . $recherche. '%')); //FIXME statut OK


		$data = array();

		foreach (F3::get('DB')->result as $row) {
			$json = array();
			$json['id'] = $row['id'];
			$json['label'] = $row['prenom'] . " " . $row['nom'] . " - " . outils::date_sql_fr($row['date_naissance']);
			$json['value'] = $row['prenom'] . " " . $row['nom'] . " - " . outils::date_sql_fr($row['date_naissance']);
			$data[] = $json;
		}
		header("Content-type: application/json");
		echo json_encode($data);

	}

	static function imprimer_liste() {
		F3::call('outils::verif_admin');

		require_once 'lib/pdf.php';

		$festival_id = F3::get('SESSION.festival_id');

		//Instanciation de la classe dérivée	
		$pdf=new PDF();

		$pdf->header = true;
		$pdf->footer = true;

		$pdf->SetMargins(15, 15);
		$pdf->titre = "Organismes..................................";

		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Arial','',12);

		$organismes = new Axon('organismes');

		foreach($organismes->afind('1=1','libelle') as $cle=>$valeur)
		{
			$pdf->Cell(0,5,utf8_decode($valeur["libelle"]),0,1);
		}

		$pdf->Ln(10);

		$pdf->Output("Organismes.pdf", 'D');
	}

	static function imprimer_liste_membres() {
		F3::call('outils::verif_individu');
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		$organisme_id = F3::get('PARAMS.id');
		$historique_organisme=new Axon('historique_organismes');
		$historique_organisme->load("festival_id='$festival_id' AND individu_id='$individu_id' AND responsable='1' AND organisme_id='$organisme_id'");

		if(!$historique_organisme->dry() || outils::est_admin())
		{
			require_once 'lib/pdf.php';

			if(is_numeric($organisme_id))
			{
				$organisme = DB::sql("SELECT o.libelle, o.adresse1, o.adresse2, o.ville_id, ot.libelle AS organisme_type FROM organismes AS o, historique_organismes AS ho, organismes_types AS ot WHERE ho.festival_id=$festival_id AND ho.organisme_id=o.id AND o.id=$organisme_id AND o.organisme_type_id=ot.id");
				if (count($organisme)>0)
				{
					$festival = new Axon('festivals');
					$festival->load("id=$festival_id");

					$pdf=new PDF('L');

					$pdf->header = true;
					$pdf->footer = true;

					$pdf->SetMargins(15, 15);
					$pdf->titre = "Organisme " . $organisme[0]['libelle'] . " " . $festival->annee;
					$pdf->AliasNbPages();
					$pdf->AddPage();

					$responsable = DB::sql("SELECT i.photo, i.nom, i.prenom, i.adresse1, i.adresse2, i.ville_id FROM individus AS i, historique_organismes AS ho WHERE ho.responsable='1' AND ho.organisme_id=$organisme_id AND ho.festival_id=$festival_id AND i.id=ho.individu_id");
					if (count($responsable)>0)
					{
						$pdf->SetFont('Arial','B',14);
						$pdf->Cell(25);
						$pdf->SetY(40);
						$pdf->Cell(0,6,"Responsable :",0,1, 'L');
							
						$pdf->SetFont('Arial','',14);
							
						if ($responsable[0]['photo'] != NULL)

						$pdf->Image("uploads/photos/". $responsable[0]['photo'] ,15,49,null,30);

						$pdf->Cell(25);
						$pdf->Cell(0,6,$responsable[0]['nom'] . " " . $responsable[0]['prenom'] ,0,1, 'L');
						if ($responsable[0]['adresse1'] != NULL)
						{
							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['adresse1'],0,1, 'L');
							//$x_line = $pdf->GetStringWidth($responsable[0]['adresse1']);
							$x_line = $pdf->GetStringWidth($responsable[0]['adresse1']);
						}
						if ($responsable[0]['adresse2'] != NULL)
						{
							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['adresse2'],0,1, 'L');
						}

						if (($responsable[0]['ville_id'] != NULL) && ($responsable[0]['ville_id'] != 0) )
						{
							$responsable_ville_id = $responsable[0]['ville_id'];

							$ville_responsable = new Axon('villes');
							$ville_responsable->load("id=$responsable_ville_id");
							$pdf->Cell(25);
							$pdf->Cell(0,6,$ville_responsable->cp . " " . $ville_responsable->nom,0,1, 'L');
						}

					}
					
					$pdf->Line(140,40,140,68);

					//Décalage à droite
					$pdf->SetY(40);
					$pdf->Cell(150);

					$pdf->SetFont('Arial','B',14);
					$pdf->Cell(0,6,$organisme[0]['organisme_type'] . " :",0,1, 'L');
					$pdf->SetFont('Arial','',14);

					$pdf->Cell(150);
					$pdf->Cell(0,6,trim($organisme[0]['libelle']) . " (ID:" . $organisme_id . ")" ,0,1, 'L');
					if ($organisme[0]['adresse1'] != NULL)
					{
						$pdf->Cell(150);
						$pdf->Cell(0,6,$organisme[0]['adresse1'],0,1, 'L');
					}

					if ($organisme[0]['adresse2'] != NULL)
					{
						$pdf->Cell(150);
						$pdf->Cell(0,6,$organisme[0]['adresse2'],0,1, 'L');
					}

					if (($organisme[0]['ville_id'] != NULL) && ($organisme[0]['ville_id'] != 0) )
					{
						$organisme_ville_id = $organisme[0]['ville_id'];
							
						$ville_organisme = new Axon('villes');
						$ville_organisme->load("id=$organisme_ville_id");
						$pdf->Cell(150);
						$pdf->Cell(0,6,$ville_organisme->cp . " " . $ville_organisme->nom,0,1, 'L');
					}

					$pdf->Ln(4);

					if (F3::get('PARAMS.option')=="")
					{
						//Titres des colonnes
						$header=array('#','ID','Nom','Prenom','CP','Commune','Tel. fixe','Tel. port.');
						//Largeur des colonnes
						$w=array(8,16,35,35,16,60,30,30);
						//Titre des colonnes SQL
						$header2=array('rank','id','nom_individu','prenom','cp','nom_ville','telephone_fixe','telephone_portable');
						//DonnÃ©es SQL
						$data = DB::sql("SELECT @rownum := @rownum +1 AS rank, i.id, i.nom AS nom_individu, i.prenom, v.cp, v.nom  AS nom_ville, i.telephone_fixe, i.telephone_portable FROM (SELECT @rownum:=0) r, individus AS i, villes AS v, historique_organismes AS ho WHERE ho.festival_id=$festival_id AND i.id=ho.individu_id AND ho.organisme_id=$organisme_id AND i.ville_id=v.id ORDER BY i.nom");
					}
					else if (F3::get('PARAMS.option')=="adresse")
					{
						$pdf->SetFont('','',10);
						//Titres des colonnes
						$header=array('#','ID','Nom','Prenom','Adresse','Adresse2','CP','Commune','Tel. fixe','Tel. port.');
						//Largeur des colonnes
						$w=array(8,10,35,25,45,45,16,40,22,22);
						//Titre des colonnes SQL
						$header2=array('rank','id','nom_individu','prenom','adresse1','adresse2','cp','nom_ville','telephone_fixe','telephone_portable');
						//DonnÃ©es SQL
						$data = DB::sql("SELECT @rownum := @rownum +1 AS rank, i.id, i.nom AS nom_individu, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom  AS nom_ville, i.telephone_fixe, i.telephone_portable FROM (SELECT @rownum:=0) r, individus AS i, villes AS v, historique_organismes AS ho WHERE ho.festival_id=$festival_id AND i.id=ho.individu_id AND ho.organisme_id=$organisme_id AND i.ville_id=v.id ORDER BY i.nom");
					}
								
					//Affichage des donnÃ©es
					$pdf->FancyTable($header,$data,$header2,$w);
					

					$pdf->Output("Organisme-".$organisme_id."-".$festival->annee.".pdf", 'D');
					//$pdf->Output();

				}
				else
				Outils::http401();
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
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

	static function verif_adresse1() {
		F3::input('adresse1',
		function($value) {
			if (!F3::exists('message')) {
				if (strlen($value)>255)
				F3::set('message','Libelle trop long');
			}
		}
		);
	}

	static function verif_adresse2() {
		F3::input('adresse2',
		function($value) {
			if (!F3::exists('message')) {
				if (strlen($value)>255)
				F3::set('message','Libelle trop long');
			}
		}
		);
	}

	static function verif_ville_id() {
		F3::input('ville_id',
		function($value) {
			if (!F3::exists('message')) {
				if ( (preg_match('/^[0-9]+$/', $value) == 0) || $value == 0)
				F3::set('message','Ville incorrecte');
			}
		}
		);
	}

	static function verif_maximum() {
		F3::input('maximum',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]+$/', $value) == 0 )
				F3::set('message','Effectif maximum incorrect');
			}
		}
		);
	}

	static function verif_organisme_type_id() {
		F3::input('organisme_type_id',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]+$/', $value) == 0 )
				F3::set('message','Type de organisme incorrect');
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
	static function verif_individu_id() {
		F3::input('individu_id',
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
		$entite_id = F3::get('PARAMS.id');
		if(isset($entite_id)){
			if(!is_numeric($entite_id)) return false;
			DB::sql("SELECT COUNT(id) as count FROM historique_organismes WHERE individu_id = $individu_id AND responsable = 1 AND festival_id = $festival_id AND organisme_id = $entite_id;");
		}
		else
		DB::sql("SELECT COUNT(id) as count FROM historique_organismes WHERE individu_id = $individu_id AND responsable = 1 AND festival_id = $festival_id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];
		if($result[0]['count'] == 1) return true;
		return false;
	}

}

?>
