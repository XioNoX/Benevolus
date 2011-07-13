<?php

class Profils {

	static function envoi_photo() {

		$file = $_FILES['file'];

		$dossier = 'uploads/photos/';
		$fichier = uniqid();
		$taille_maxi = 5000000;
		$taille = filesize($file['tmp_name']);
		$extensions = array('.png', '.gif', '.jpg', '.jpeg');
		$extension = strrchr($_FILES['file']['name'], '.');
		$fichier = time(). "-" .$fichier . $extension;

		//		echo $dossier . $fichier;


		//Début des vérifications de sécurité...
		if(!in_array($extension, $extensions)) //Si l'extension n'est pas dans le tableau
		{
			$message = 'Seules les images au format png, jpg, jpeg, et gif sont acceptées';
		}
		if($taille>$taille_maxi)
		{
			$message = 'Le poids de l\'image est trop important (5Mo maximum autorisé)';
		}
		if(!isset($message)) //S'il n'y a pas d'erreur, on upload
		{
			//		     On formate le nom du fichier ici...
			if(move_uploaded_file($file['tmp_name'], $dossier . $fichier)) //Si la fonction renvoie TRUE, c'est que ça a fonctionné...
			{
				Outils::redimmensionnerImage($dossier . $fichier, $dossier . $fichier);
				$message = 'Upload effectué avec succès !';
			}
			else //Sinon (la fonction renvoie FALSE).
			{
				$message = 'Echec de l\'upload !';
			}
		}
		$json = array();
		$json['name'] = $fichier;
		$json['message'] = $message;

		header("Content-type: application/json");
		echo json_encode($json);
	}

	static function inscription() {
		outils::activerJquery();
		F3::call('outils::menu');
		$uuid = F3::get('PARAMS.id');
		$invitation=new Axon('invitations');
		$invitation->load("uuid='$uuid'");
			
		if ($invitation->dry())
		F3::set('erreur','Cette invitation est mauvaise ou a déjà été utilisée');
		else
		{
			$invitation->copyTo('REQUEST');
			F3::set('REQUEST.date_naissance', outils::date_sql_fr($invitation->date_naissance));
		}
		F3::set('exempleMDP',outils::genererMDP());

		F3::set('inscription','1');

		F3::set('pagetitle','Inscription');
		F3::set('template','form_individu');
		F3::call('outils::generer');
	}


	static function oublie_pass(){
		$login = F3::get('REQUEST.login');

		if(!is_numeric($login)){	//Téléphone
			//Vérifier que le numéro existe
			//Envoyer un SMS
		}
		elseif(data::validEmail($login)){



		}
		else{
			F3::clear('error');
			F3::set('pagetitle','Mot de passe oublié ?');
		}
		F3::set('template','form_oublie_pass');
		F3::call('outils::generer_no_auth');

	}

	static function inscription_post() { //TODO : Verifier que ce n'est pas quelqu'un anonymisé
		// Reset message d'erreur
		F3::clear('message');

		// Validation du formulaire
		F3::call('profils::verif_nom|profils::verif_prenom|profils::verif_date_naissance|profils::verif_telephone_fixe|profils::verif_telephone_portable|profils::verif_email|profils::verif_adresse1|profils::verif_adresse2|profils::verif_ville_id'); //Verifications de la saisie
		$uuid = F3::get('PARAMS.id');
		//Premiere vérification, toutes les informations sont OK mis à part le mot de passe
		if(!F3::exists('message')) {

			$invitation=new Axon('invitations');
			$invitation->load("uuid='$uuid'");

			if ($invitation->dry())
			{
				F3::set('message','Cette invitation est mauvaise ou a déjà été utilisée');
			}
			else
			{
				$individu=new Axon('individus');

				/*if ((F3::get('REQUEST.mdp') == F3::get('REQUEST.mdp_bis')) && F3::get('REQUEST.mdp') != "")
					//Toutes les verifications sont ok
					F3::set('REQUEST.mot_de_passe', sha1(F3::get('salt').F3::get('REQUEST.mdp')));
					else
					F3::set('message','Les deux mot de passe ne correspondent pas ou sont absent');
					*/

				//Pas d'erreur au niveau des mots de passe, on enregistre
				if (!F3::exists('message')) {

					//Conversion date pour insertion dans la base de données
					F3::set('REQUEST.date_naissance',
					outils::date_fr_sql( F3::get('REQUEST.date_naissance') ));

					// Pas d'erreur, inscription
					$individu->copyFrom('REQUEST');
					$individu->autorisation_parentale='0';
					$individu->actif='1';
					$individu->acces_web='0';
					$individu->statut_id='1';
					$individu->mot_de_passe='';
					$individu->save();

					$individu_id = $individu->_id;

					$historique_organisme = new Axon('historique_organismes');
					$historique_organisme->responsable = '0';
					F3::call('outils::dernier_festival');
					$historique_organisme->festival_id = F3::get('SESSION.festival_id');
					$historique_organisme->organisme_id = $invitation->organisme_id;
					$historique_organisme->individu_id = $individu_id;
					$historique_organisme->present = 0;
					$historique_organisme->save();

					$organisme_id = $invitation->organisme_id;
					$invitation->erase();

					if(F3::get('SESSION.id') != "")
					{
						historique::logger("Inscription de l'individu numéro ". $individu_id);
						F3::reroute('/organismes/editer/' . $organisme_id);
					}
					else
					{
						F3::set('SESSION.email',$individu->email);
						F3::set('SESSION.id',$individu_id);
						F3::set('SESSION.acces_web',$individu->acces_web);
						F3::set('SESSION.prenom',$individu->prenom);
						historique::logger("Inscription de l'individu numéro ". $individu_id);
						F3::reroute('/');
					}
				}
			}

		}
		F3::call('profils::inscription'); // Ré-Affichage du formulaire

	}

	static function inscription_succes() {
		F3::call('outils::menu');

		F3::set('succes','Inscription bien prise en compte.');
		F3::set('pagetitle','Inscription effectuée');
		F3::set('template','message_information');

		F3::call('outils::generer');
	}

	// Page d'accueil des individus
	static function afficher() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		$festival_id = F3::get('SESSION.festival_id');
		$id = F3::get('PARAMS.id');
		if(is_numeric($id) && outils::est_operateur()) //TODO à modifier quand on aura fait les préférences
		$individu_id = $id;
		else
		$individu_id = F3::get('SESSION.id');

		$individu=new Axon('individus');
		$individu->load("id=$individu_id");
		if (!$individu->dry()) {
			$individu->copyTo('REQUEST');

			$ville=new Axon('villes');
			$ville->load("id=$individu->ville_id");

			if (!$ville->dry())
			F3::set('ville',$ville->cp . ' ' . $ville->nom);

			F3::set('REQUEST.date_naissance',outils::date_sql_fr(F3::get('REQUEST.date_naissance')));
			
			//Acces & tickets
			F3::set('autorisation',acces::autorisation_individu($individu_id));
			F3::set('tickets',acces::tickets_individu($individu_id));
			F3::set('present',acces::present($individu_id,$festival_id));

			// Affectations :
			DB::sql("SELECT affectations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `affectations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND affectations.individu_id = $individu_id ORDER BY festivals_jours.jour;");
			if (count(F3::get('DB')->result) > 0)
			F3::set('assignations',F3::get('DB')->result);

			DB::sql("SELECT libelle FROM `historique_organismes`, `organismes` WHERE historique_organismes.festival_id = $festival_id AND organismes.id = historique_organismes.organisme_id AND historique_organismes.individu_id = $individu_id ;");
			F3::set('organisme',F3::get('DB')->result[0]['libelle']);


			F3::set('pagetitle',"Profil de $individu->prenom $individu->nom");
			F3::set('template','affichage_profil');
			F3::call('outils::generer');
		}
		else
		F3::reroute("/acces/entrees");
	}

	static function lister() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_annee = F3::get('festival_annee');
		F3::set('pagetitle',"Liste des bénévoles de $festival_annee");
		F3::set('description','Bénévoles étant actuellement assignés à une structure pour le festival courant.');
		F3::set('lien_ajouter','<a href=/profils/tous>Tous les bénévoles</a>');
		F3::set('jquery_url_list','/ajax/profils_festival');
		F3::set('jquery_url_edit','/profils/editer/');
		F3::set('jquery_url_edit2','/profils/editer');
		F3::set('jquery_largeur','970');
		F3::set('jquery_col_names',"['id', 'E-mail','Prénom', 'Nom', 'DDN', 'Fixe','Mobile','Organisme','Statut','Commentaire']");

		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:35, formatter: formateadorLink}, 
      {name:'email', index:'email', width:100 }, 
      {name:'prenom', index:'prenom', width:80}, 
      {name:'nom', index:'nom', width:80}, 
      {name:'date_naissance', index:'date_naissance', width:55, align:'right'}, 
      {name:'telephone_fixe', index:'telephone_fixe', width:60, align:'right'}, 
      {name:'telephone_portable', index:'telephone_portable', width:60, align:'right'},
      {name:'libelle', index:'libelle', width:60, align:'right'},
      {name:'statut', index:'statut', width:40, align:'right'},
      {name:'commentaire', index:'commentaire'} 
    ]");

		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function editer_tableau_post() {
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(outils::est_admin()) {DB::sql("DELETE FROM individus WHERE id=$id");} //Logger
		}
	}

	static function lister_tous() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$festival_annee = F3::get('festival_annee');
		F3::set('pagetitle','Liste de tous les bénévoles');
		F3::set('description','Tous les bénévoles présents dans le système.');
		F3::set('lien_ajouter',"<a href=/profils>Bénévoles de $festival_annee </a>");
		F3::set('jquery_url_list','/ajax/profils');
		F3::set('jquery_url_edit','/profils/editer/');
		F3::set('jquery_largeur','970');
		F3::set('jquery_col_names',"['id', 'E-mail','Prénom', 'Nom', 'DDN', 'Fixe','Mobile','Organisme','Statut','Commentaire']");

		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:35, formatter: formateadorLink}, 
      {name:'email', index:'email', width:100 }, 
      {name:'prenom', index:'prenom', width:80}, 
      {name:'nom', index:'nom', width:80}, 
      {name:'date_naissance', index:'date_naissance', width:55, align:'right'}, 
      {name:'telephone_fixe', index:'telephone_fixe', width:60, align:'right'}, 
      {name:'telephone_portable', index:'telephone_portable', width:60, align:'right'},
      {name:'libelle', index:'libelle', width:60, align:'right'},
      {name:'statut', index:'statut', width:40, align:'right'},
      {name:'commentaire', index:'commentaire'} 
    ]");

		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function anonymiser() {
		F3::call('outils::verif_individu');
		$profil_id = F3::get('PARAMS.id');
		$individu_id = F3::get('SESSION.id');
		$individu=new Axon('individus');
		if (outils::est_operateur() && $profil_id !="")
		$individu->load("id=$profil_id");
		else $individu->load("id=$individu_id");

		if (!$individu->dry()) {
			if($individu->nom == "Anonyme" && $individu->prenom == "Anonyme"){
				F3::set('erreur',"Le compte " . $individu->id . " a déjà été anonymisé.");
				F3::set('pagetitle','Anonymiser');
				F3::set('template','message_information');
				F3::call('outils::generer');
			}
			else
			{
				$individu->email = sha1($individu->email);
				$individu->mot_de_passe = sha1(date("r"));
				$individu->nom = "Anonyme";
				$individu->prenom =  "Anonyme";
				$individu->date_naissance =  "0000-00-00";
				$individu->telephone_fixe =  "0000000000";
				$individu->telephone_portable =  "0000000000";
				$individu->adresse1 = "Anonyme";
				$individu->adresse2 = "";
				$individu->identifier = "";
				$individu->ville_id = 0;
				$individu->actif = 0;
				$individu->save();

				if (outils::est_operateur() && F3::get('PARAMS.id')!="")
				{
					historique::logger("L'anonymisation du compte " . $individu->id . " a été réalisée.");
					F3::set('succes',"L'anonymisation du compte " . $individu->id . " a été réalisée.");
					F3::set('pagetitle','Anonymiser');
					F3::set('template','message_information');
					F3::call('outils::generer');
				}
				else
				{
					F3::set('succes',"Votre compte a été anonymisé et vous étès maintenant déconnecté.");
					F3::set('pagetitle','Anonymiser');
					F3::set('template','message_information');
					F3::call('outils::generer_no_auth');
					session_destroy();
				}
			}


		}

	}

	static function editer() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		$profil_id = F3::get('PARAMS.id');
		$individu_id = F3::get('SESSION.id');
		echo F3::get('redirect');

		// Recuperation des informations actuelles
		$individu=new Axon('individus');

		if ($profil_id !="")
		{
			//Organisme actuel
			$organisme_id = Organismes::organisme_individu($profil_id, "");
			$responsable_id = Organismes::responsable_organisme($organisme_id, "");

			if (outils::est_operateur())
			{
				$individu->load("id=$profil_id");
			}
			elseif( $responsable_id == F3::get('SESSION.id') )
			{
				$individu->load("id=$profil_id"); //XXX à vérifier
			}
		}
		else
		{
			//Si un opérateur édite son propre profil, on lui demande son ancien mot de passe
			if (outils::est_operateur())
			{
				F3::set('profil','1');
			}
			$individu->load("id=$individu_id");
		}

		if (!$individu->dry()) {
			// Populate REQUEST global with retrieved values
			$individu->copyTo('REQUEST');

			$statuts=new Axon('statuts');
			F3::set('statuts',$statuts->afind());

			$date_naissance = F3::get('REQUEST.date_naissance');
			list($year, $month, $day) = explode('-', $date_naissance);
			$ts_date_naissance = mktime(0, 0, 0, $month, $day, $year);
			F3::set('REQUEST.date_naissance', date("d/m/Y", $ts_date_naissance));
			F3::set('exempleMDP',outils::genererMDP());
			$ville_id=$individu->ville_id;
			$villes=new Axon('villes');
			$villes->load("id=$ville_id");
			F3::set('REQUEST.ville',$villes->cp . ' - ' .$villes->nom);

			F3::set('acces_web',
			array(
			array(
						"id"=>0,
						"libelle"=>"Individu"
						),
						array(
						"id"=>1,
						"libelle"=>"Opérateur"
						),
						array(
						"id"=>2,
						"libelle"=>"Administrateur"
						)
						)
						);

						F3::set('pagetitle','Editer un individu');
						F3::set('editer','editer');

						F3::set('template','form_individu');
						F3::call('outils::generer');
		}
		else
		F3::http404();
	}


	static function editer_post() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');

		// Reset message d'erreur
		F3::clear('message');
		$profil_id = F3::get('PARAMS.id');
		$individu_id = F3::get('SESSION.id');

		// Validation du formulaire
		F3::call('profils::verif_nom|profils::verif_prenom|profils::verif_date_naissance|profils::verif_telephone_fixe|profils::verif_telephone_portable|profils::verif_email|profils::verif_adresse1|profils::verif_adresse2|profils::verif_ville_id'); //Verifications de la saisie

		//Premiere vérification, toutes les informations sont OK mis à part le mot de passe
		if (!F3::exists('message')) {

			$individu=new Axon('individus');

			if ($profil_id !="")
			{
				//Organisme actuel
				$organisme_id = Organismes::organisme_individu($profil_id, "");
				$responsable_id = Organismes::responsable_organisme($organisme_id, "");

				if (outils::est_operateur() || $responsable_id == F3::get('SESSION.id'))
				{
					$individu->load("id=$profil_id");
						
					if(F3::get('REQUEST.mdp')!="")
					{
						$mdp = F3::get('REQUEST.mdp');

						if ((F3::get('REQUEST.mdp') == F3::get('REQUEST.mdp_bis')))
						{
							F3::call('profils::verif_mdp|profils::verif_mdp_bis');
							F3::set('REQUEST.mot_de_passe', sha1(F3::get('salt').F3::get('REQUEST.mdp')));
						}
						else
						{
							F3::set('message','Les deux mot de passe ne correspondent pas');
						}
					}
				}
			}
			//Si un individu veut éditer un autre membre
			//ou essaie de modifier des informations destinées à l'administateur
			//Tentative de hack
			elseif ( (!outils::est_operateur() ) && (F3::get('PARAMS.id')!="" || !is_null(F3::get('REQUEST.commentaire')) || !is_null(F3::get('REQUEST.actif')) || !is_null(F3::get('REQUEST.acces_web')) || !is_null(F3::get('REQUEST.statut_id'))))
			{
				//Outils::http401();
			}
			else
			{
				$individu->load("id=$individu_id");

				//Si ancien mot de passe renseigné et nouveau mot de passe renseigné
				if ( F3::get('REQUEST.ancien_mdp')!="" && F3::get('REQUEST.mdp')!="" && F3::get('REQUEST.mdp_bis')!="" )
				{
					if (sha1(F3::get('salt').F3::get('REQUEST.ancien_mdp')) == $individu->mot_de_passe) {
						F3::call('profils::verif_mdp|profils::verif_mdp_bis');
						if (F3::get('REQUEST.mdp') == F3::get('REQUEST.mdp_bis'))
						{
							//Toutes les verifications sont ok
							F3::set('REQUEST.mot_de_passe', sha1(F3::get('salt').F3::get('REQUEST.mdp')));
						}
						else
						{
							F3::set('message','Les deux mot de passe ne correspondent pas');
						}
					}
					else
					{
						F3::set('message','Mot de passe actuel incorrect');
					}
				}
				//Sinon si ancien mot de passe non renseigné ou nouveau mot de passe ou nouveau mot de passe bis non renseigné
				elseif ( F3::get('REQUEST.ancien_mdp')!="" || ( F3::get('REQUEST.mdp')!="" || F3::get('REQUEST.mdp_bis')!=""))
				{
					F3::set('message','Pour tout changement de mot de passe, veuillez indiquer votre mot de passe actuel, votre nouveau mot de passe, ainsi qu\'une confirmation de ce dernier');
				}
			}

			if (!$individu->dry())
			{
				//Conversion date pour insertion dans la base de données
				F3::set('REQUEST.date_naissance',
				outils::date_fr_sql( F3::get('REQUEST.date_naissance') ));

				//Conversion du résultat du champs "actif" pour insertion dans la base de données
				if ( F3::get('REQUEST.actif') == "on" )
				F3::set('REQUEST.actif',1);
				else
				F3::set('REQUEST.actif',0);

				try {
					if (F3::get('REQUEST.supprimer_photo') == "on" ){
						unlink( "/uploads/photos/" . $individu->photo);
						F3::set('REQUEST.photo',"");
					}
				} catch (Exception $e) {
					F3::set('message','Photo à supprimer introuvable');
				}




				//Pas d'erreur au niveau des mots de passe, on enregistre
				if (!F3::exists('message')) {
					// Pas d'erreur, mise à jour
					historique::logger("Modification du compte numero " . $individu->id);
					$individu->copyFrom('REQUEST'); //FIXME : s'assurer que photo est bien au bon format (et pas "../../index.php")
					$individu->save();
					F3::set('succes','Profil mis à jour avec succès');
				}
			}
		}
		// Display the blog form again
		F3::call('profils::editer');
	}


	static function historique() {
	}

	static function imprimer_courrier_haut() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$festival_id = F3::get('SESSION.festival_id');

		require_once 'lib/pdf.php';

		//Instanciation de la classe dérivée
		$pdf=new PDF();
		$pdf->SetMargins(15, 15);

		$festival = new Axon('festivals');
		$festival->load("id=$festival_id");

		//Récupération de tous les participants au festival xxxx
		$individus = DB::sql("SELECT individus.id AS individu_id, organismes.id AS organisme_id FROM `organismes`, `historique_organismes`, `individus` WHERE historique_organismes.individu_id = individus.id AND historique_organismes.festival_id = ($festival_id - 1) AND historique_organismes.organisme_id = organismes.id ORDER BY individus.id DESC LIMIT 10;");

		foreach($individus as $cle=>$valeur)
		{
			$individu_id = $valeur["individu_id"];

			$pdf->AddPage();
			$pdf->SetFont('Arial','',15);

			$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

			$pdf->SetY(55);

			//Organisme
			$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id= ($festival_id-1) AND ho.organisme_id=o.id");
			if (count($organisme)>0)
			{
				$o_infos = $organisme[0]['libelle'] . "\n" . "n° " . $organisme[0]['id'];

				$organisme_id = $organisme[0]['id'];
				$o_responsable = DB::sql("SELECT i.prenom, i.nom FROM individus AS i, historique_organismes AS ho WHERE ho.individu_id=i.id AND ho.responsable=1 AND ho.organisme_id=$organisme_id");
				if (count($o_responsable))
				$o_infos .= "\n" . "Contact: " . $o_responsable[0]['nom'] . " " . $o_responsable[0]['prenom'];

				$pdf->MultiCell(0, 6, $o_infos, 0, 'L');
			}

			$pdf->SetY(55);

			$pdf->AddFont('ocrb');
			$pdf->SetFont('ocrb');

			$i_adresse = $individu[0]["i_nom"] . " " . $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
			if ($individu[0]['adresse2'] != NULL)
			$i_adresse .= $individu[0]['adresse2'];
			$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

			$pdf->Cell(100);
			$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');
		}

		$pdf->Output("Affectations-".$individu_id."-".$festival->annee.".pdf", 'D');
	}

	static function verif_nom() {
		F3::input('nom',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Nom non renseigné');
				elseif (strlen($value)>255)
				F3::set('message','Nom trop long');
			}
		}
		);
	}

	static function verif_prenom() {
		F3::input('prenom',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Prénom non renseigné');
				elseif (strlen($value)>255)
				F3::set('message','Prénom trop long');
			}
		}
		);
	}

	static function verif_date_naissance() {
		F3::input('date_naissance',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $value) == 0 )
				F3::set('message','Date de naissance incorrecte');
			}
		}
		);
	}

	static function verif_telephone_fixe() {
		F3::input('telephone_fixe',
		function($value) {
			if (!F3::exists('message')) {
				if (!empty($value))
				{ F3::set('telephone_fixe_ok','1');
				if (preg_match('/^[0-9][1234589][0-9]{8}$/', $value) < 0 )
				{
					F3::set('message','Téléphone fixe incorrect (format : 0123456789)');
				}
				}
			}
		}
		);
	}

	static function verif_telephone_portable() { //TODO : Utiliser 1 unique fonction pour valider les 2 numéros
		F3::input('telephone_portable',
		function($value) {
			if (!F3::exists('message')) {
				if ((preg_match('/^0[67][0-9]{8}$/', $value) == 0) && ($value != ""))
				F3::set('message','Téléphone portable incorrect (format : 0612345678)');
				else if( F3::get('telephone_fixe_ok') != "1" && ($value == ""))
				F3::set('message','Veuillez indiquer un numéro de téléphone fix ou mobile');
			}
		}
		);
	}

	static function verif_email() {
		F3::input('email',
		function($value) {
			if (!F3::exists('message')) {
				if (!filter_var($value, FILTER_VALIDATE_EMAIL) && $value != "")
				F3::set('message','Email incorrect');
			}
		}
		);
	}

	static function verif_adresse1() {
		F3::input('adresse1',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Adresse 1 non renseignée');
				elseif (strlen($value)>255)
				F3::set('message','Adresse 1 trop longue');
			}
		}
		);
	}

	static function verif_adresse2() {
		F3::input('adresse2',
		function($value) {
			if (!F3::exists('message')) {
				if (strlen($value)>255)
				F3::set('message','Adresse 2 trop longue');
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

	// Validate password
	static function verif_ancien_mdp() {
		F3::input('ancien_mdp',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Password must be specified');
				elseif (strlen($value)>24)
				F3::set('message','Invalid password');
			}
		}
		);
	}

	static function verif_mdp() {
		F3::input('mdp',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Merci d\'entrer un mot de passe');
				elseif (strlen($value) < 7)
				F3::set('message','Votre mot de passe doit contenir au moins 7 caractères');
				elseif (strlen($value) > 25)
				F3::set('message','Votre mot de passe est trop long');
			}
		}
		);
	}

	static function verif_mdp_bis() {
		F3::input('mdp_bis',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Password must be specified');
				elseif (strlen($value)>24)
				F3::set('message','Invalid password');
			}
		}
		);
	}

	/**
	 * No param
	 */
	static function invitations_attente() {
		F3::call('outils::verif_admin');

		$json = array();

		$invitations = DB::sql('SELECT invitations.id, invitations.nom, invitations.prenom, invitations.date_naissance, organismes.libelle FROM invitations, organismes WHERE organismes.id = invitations.organisme_id AND invitations.valide = 0 ORDER BY invitations.id');
		if(count($invitations) > 0)
		{
			foreach($invitations as $row)
			{
				$nom = $row['nom'];
				$prenom = $row['prenom'];

				$doublons = DB::sql('SELECT individus.id, individus.nom, individus.prenom, individus.date_naissance, individus.adresse1, villes.nom as ville, individus.telephone_fixe, individus.telephone_portable, statuts.libelle AS statut FROM individus, statuts, villes WHERE individus.ville_id = villes.id AND statuts.id = individus.statut_id AND individus.nom SOUNDS LIKE :nom_individu AND individus.prenom SOUNDS LIKE :prenom_individu ;',array(':nom_individu'=>array($nom,PDO::PARAM_STR),':prenom_individu'=>array($prenom,PDO::PARAM_STR)));

				$json['id'] = $row['id'];
				$invitation_id = $row['id'];
				$json['nom'] = $row['nom'];
				$json['prenom'] = $row['prenom'];
				$json['date_naissance'] = outils::date_sql_fr($row['date_naissance']);
				$json['libelle'] = $row['libelle'];
				$json['doublon'] = 0;
				if (count($doublons) > 0)
				$json['doublons'] = 1;
				$data[] = $json;

				foreach($doublons as $row)
				{
					$organisme = DB::sql('SELECT organismes.libelle FROM organismes, historique_organismes WHERE historique_organismes.organisme_id = organismes.id AND historique_organismes.individu_id = :individu_id ORDER BY historique_organismes.festival_id DESC LIMIT 1;',array(':individu_id'=>array($row['id'],PDO::PARAM_STR)));
					$json['id'] = $row['id'];
					$json['nom'] = $row['nom'];
					$json['prenom'] = $row['prenom'];
					$json['date_naissance'] = outils::date_sql_fr($row['date_naissance']);
					if (count(F3::get('DB')->result) > 0) 
						$json['libelle'] = ''. F3::get('DB')->result[0]['libelle'];
					$json['adresse1'] = $row['adresse1'];
					$json['ville'] = $row['ville'];
					$json['telephone_fixe'] = $row['telephone_fixe'];
					$json['telephone_portable'] = $row['telephone_portable'];
					$json['statut'] = $row['statut'];
					$json['doublon'] = 1;
					$json['double'] = $invitation_id;
					$data[] = $json;
				}
			}
		}
		else $data[] = $json;
		//header("Content-type: application/json");
		echo json_encode($data);
	}

	/**
	 * params : id
	 */
	static function activer_invitation() {
		F3::call('outils::verif_admin');
		$invitation_id = F3::get('PARAMS.id');
		$invitation=new Axon('invitations');
		$invitation->load("id=$invitation_id");
		if (!$invitation->dry()){
			$invitation->valide = 1;
			$invitation->save();
			$sujet = "Invitation de " .$invitation->prenom . " " . $invitation->nom . " acceptée";
			$corps = "Bonjour,\n\nVeuillez maintenant vous rendre sur la page de gestion de votre organisme pour finir le procéssus.";
			messages::envoyer_message('', array($invitation->parrain_id), $sujet, $corps);
			historique::logger($sujet);
		}
		$row = F3::get('DB')->result;
		header("Content-type: application/json");
		echo (json_encode($row));
	}

	/**
	 * params : id
	 */
	static function supprimer_invitation() {
		F3::call('outils::verif_admin');
		$invitation_id = F3::get('PARAMS.id');
		$invitation=new Axon('invitations');
		$invitation->load("id=$invitation_id");
		if (!$invitation->dry()){

			$sujet = "Invitation de " .$invitation->prenom . " " . $invitation->nom . " refusée";
			$corps = "Bonjour,\n\nNous ne souhaitons pas intégrer cette personne comme bénévole.";
			messages::envoyer_message('', array($invitation->parrain_id), $sujet, $corps);
			historique::logger($sujet);
			$invitation->erase();
		}
		$row = F3::get('DB')->result;
		header("Content-type: application/json");
		echo (json_encode($row));
	}

	/**
	 * params : id
	 */
	static function fusionner_invitation() {
		F3::call('outils::verif_admin');
		$individu_id = F3::get('REQUEST.id');
		$double_id = F3::get('REQUEST.double_id');
		$festival_id = F3::get('SESSION.festival_id');

		// On déplace la personne existante dans le nouvel organisme et on la supprime des invitations
		$invitation=new Axon('invitations');
		$invitation->load("id=$double_id");
		if (!$invitation->dry()){
			$organisme_id = $invitation->organisme_id;

			DB::sql('SELECT count(id) as count FROM historique_organismes WHERE historique_organismes.individu_id = :individu_id AND festival_id = :festival_id ;',array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':festival_id'=>array(F3::get('SESSION.festival_id'),PDO::PARAM_INT)));
			$result = F3::get('DB')->result;
			$result = $result[0]['count'];
			if($result == 0){
				$historique_organismes=new Axon('historique_organismes');
				$historique_organismes->responsable = 0;
				$historique_organismes->organisme_id =  $organisme_id;
				$historique_organismes->individu_id = $individu_id;
				$historique_organismes->festival_id = $festival_id;
				$historique_organismes->present = 0;

				$historique_organismes->save();
			}
			else
			DB::sql('UPDATE `historique_organismes` SET organisme_id= :organisme_id, responsable=0 WHERE individu_id = :individu_id AND festival_id = :festival_id ;', array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT),':organisme_id'=>array($organisme_id,PDO::PARAM_INT)));


			$sujet = "Invitation de " .$invitation->prenom . " " . $invitation->nom . " acceptée";
			$corps = "Bonjour,\n\nCette personne vient d'être ajoutée à votre organisme.";
			messages::envoyer_message('', array($invitation->parrain_id), $sujet, $corps);
			historique::logger($sujet);
			$invitation->erase();
		}
		$row = F3::get('DB')->result;
		header("Content-type: application/json");
		echo (json_encode($row));
	}

}

?>
