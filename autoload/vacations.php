<?php
class Vacations {
	static function lister() {
		outils::activerJqgrid();

		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::set('pagetitle','Liste des vacations');
		F3::set('lien_ajouter','<a href=/vacations/ajouter>Ajouter une vacation</a> - <a href=/vacations/organismes>Distribuer des vacations aux organismes</a> - <a href=/vacations/echanger>Echanger les vacations entre 2 individus</a>');
		F3::set('jquery_url_list','/ajax/vacations');
		F3::set('jquery_url_edit','/vacations/editer/');
		F3::set('jquery_url_edit2','/vacations/editer');
		F3::set('jquery_largeur','975');
		F3::set('jquery_col_names',"['id','Jour', 'Libelle', 'Lieux', 'Heure debut', 'Heure fin','Nb maxi', 'Occupe', 'Responsable']");

		//$reponse->rows[$i]['cell']=array($row['id'], outils::date_sql_fr($row['jour']), $row['libelle'], $row['lieu'], outils::date_sql_timepicker($row['heure_debut']), outils::date_sql_timepicker($row['heure_fin']), $row['nombre_maximum'],F3::get('DB')->result[0]['occupe'], $row['responsable']);

		//F3::set('jquery_col_names',"['id','Libelle', 'heure debut', 'heure fin', 'nombre minimum', 'nombre maximum','Occupé', 'Lieu', 'Jour', 'Responsable']");

		F3::set('jquery_col_model',"[{name:'id', index:'id', width:55, formatter: formateadorLink},
                                 {name:'jour', index:'jour', width:200},
                                 {name:'libelle', index:'libelle'},
                                 {name:'lieu', index:'lieu'},
                                 {name:'heure_debut', index:'heure_debut'},
                                 {name:'heure_fin', index:'heure_fin'},
                                 {name:'nombre_maximum', index:'nombre_maximum'},
                                 {name:'occupe', index:'occupe'},
                                 {name:'responsable', index:'responsable'}]");

		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

	static function affecter_post() {
		F3::call('outils::verif_responsable');
		$liste_individus = F3::get('REQUEST.individus');
		$liste_vacations = F3::get('REQUEST.vacations');

		$message = "";
		foreach( $liste_vacations as $id_vacations) {
			if(!is_numeric($id_vacations)) exit("Vacation pas numérique");
			DB::sql("SELECT nombre_maximum FROM vacations WHERE id=$id_vacations LIMIT 1");
			$result = F3::get('DB')->result;
			$nombre_maximum = $result[0]['nombre_maximum'];
			$compteur = 0;


			foreach( $liste_individus as $id_individus) {
				if(!is_numeric($id_individus)) exit("Individu pas numérique");

				DB::sql("SELECT COUNT(id) AS count FROM affectations WHERE vacation_id=$id_vacations;");
				$result = F3::get('DB')->result;
				$affectes = $result[0]['count'];
				if(($affectes >= $nombre_maximum) && outils::est_operateur())  // si vacation remplie
				$message .= "Vacation $id_vacations correctement surbookée\n";
				else if(($affectes >= $nombre_maximum) && !est_admin())
				exit("Vacation $id_vacations pleine");

				DB::sql("SELECT nom, prenom FROM individus WHERE individus.id = $id_individus");
				$result = F3::get('DB')->result[0];
				$nom_individu = $result['nom'] . ' ' . $result['prenom'];

				if (F3::get('type') == 'organisme')	{
					DB::sql("SELECT COUNT(id) AS count FROM recommandations WHERE vacation_id=$id_vacations and individu_id=$id_individus;");
					$result = F3::get('DB')->result;
					$count = $result[0]['count'];
					if($count == 0){ //verifier que la personne n'est pas deja dedans
						$compteur++;
						DB::sql("INSERT INTO recommandations VALUES('',$id_vacations,$id_individus);"); //XXX une personne peut faire plus que ce qu'elle à le droit (risque limité)
						$message .= "$compteur - $nom_individu a correctement été recommandée pour la vacation $id_vacations.\n";
						historique::logger("$nom_individu ($id_individus) a correctement été recommandée pour la vacation $id_vacations.");


					}
					else
					$message .= "$nom_individu est déjà recommandée pour la vacation $id_vacations.\n";

				}
				else
				{
					DB::sql("SELECT COUNT(id) AS count FROM affectations WHERE vacation_id=$id_vacations and individu_id=$id_individus;");
					$result = F3::get('DB')->result;
					$count = $result[0]['count'];
					if($count == 0){ //verifier que la personne n'est pas deja dedans
						$compteur++;
						DB::sql("INSERT INTO affectations VALUES('','','','',$id_vacations,$id_individus);"); //XXX une personne peut faire plus que ce qu'elle à le droit (risque limité)
						$message .= "$compteur - $nom_individu a correctement été assignée à la vacation $id_vacations.\n";
						historique::logger("$nom_individu a correctement été assignée à la vacation $id_vacations.");
					}
					else
					$message .= "La personne $id_individus est déjà assignée à la vacation $id_vacations.\n";
				}
			}
		}
		echo $message;

	}

	static function affecter() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		$id = F3::get('PARAMS.id');
		if(!is_numeric($id)) $id = 0;
		$festival_id = F3::get('SESSION.festival_id');

		if (F3::get('type') == 'organisme')	{
			$boucle = ":Tout";
			DB::sql("SELECT domaines.id, domaines.libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];
			}
			F3::set('domaines', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];
			}
			F3::set('lieux', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT jour FROM festivals_jours WHERE festival_id = $festival_id ORDER BY jour;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourait utiliser l'id du jour pour faire la recherche (idem au dessus)
				$boucle .= ";". outils::date_sql_fr($row['jour']) .":". outils::date_sql_fr($row['jour']);

			}
			F3::set('jours', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT libelle FROM `organismes` WHERE organismes.id = $id;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourait utiliser l'id de la organisme pour faire la recherche (idem au dessus)
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}
			F3::set('organismes', $boucle);

			DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE historique_organismes.festival_id = $festival_id AND historique_organismes.organisme_id = $id AND individu_id NOT IN (SELECT individu_id FROM `affectations`, `vacations`, `festivals_jours` WHERE affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id);");
			$count = F3::get('DB')->result[0];
			F3::set('nb_non_affectes', $count['count']);

			F3::set('pagetitle','Recommandations');
			F3::set('jqgrid_url_vacations', "/ajax/vacations_assigner/organisme/$id");
			F3::set('url_assigner_post', "/organismes/$id/recommander");
			F3::set('jqgrid_url_individus',"/ajax/profils_vacations/organisme/$id");



		}
		elseif (F3::get('type') == 'domaine')	{

			$boucle = ":Tout";
			DB::sql("SELECT libelle FROM domaines, responsables_domaines WHERE domaines.id=$id AND domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ORDER BY libelle;");

			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];
			}
			F3::set('domaines', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id AND lieux.domaine_id = $id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}

			F3::set('lieux', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT jour FROM festivals_jours WHERE festival_id = $festival_id ORDER BY jour;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id du jour pour faire la recherche (idem au dessus)
				$boucle .= ";". outils::date_sql_fr($row['jour']) .":". outils::date_sql_fr($row['jour']) ;

			}
			F3::set('jours', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT DISTINCT libelle FROM `organismes`, `historique_organismes` WHERE organismes.id = historique_organismes.organisme_id AND historique_organismes.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id de la organisme pour faire la recherche (idem au dessus)
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}
			F3::set('organismes', $boucle);


			DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE historique_organismes.festival_id = $festival_id and individu_id NOT IN (SELECT individu_id FROM `affectations`, `vacations`, `festivals_jours` WHERE affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id);");
			$count = F3::get('DB')->result[0];
			F3::set('nb_non_affectes', $count['count']);

			F3::set('pagetitle','Affectations');
			F3::set('url_assigner_post', "/domaines/$id/affecter");
			F3::set('jqgrid_url_vacations', "/ajax/vacations_assigner/domaine/$id");
			F3::set('jqgrid_url_individus','/ajax/profils_vacations');
		}
		else{
			F3::call('outils::verif_admin');

			$boucle = ":Tout";
			DB::sql("SELECT domaines.id, domaines.libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}
			F3::set('domaines', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) {
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}
			F3::set('lieux', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT jour FROM festivals_jours WHERE festival_id = $festival_id ORDER BY jour;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id du jour pour faire la recherche (idem au dessus)
				$boucle .= ";". outils::date_sql_fr($row['jour']) . ":" . outils::date_sql_fr($row['jour']);

			}
			F3::set('jours', $boucle);
			$boucle = ":Tout";
			DB::sql("SELECT DISTINCT libelle FROM `organismes`, `historique_organismes` WHERE organismes.id = historique_organismes.organisme_id AND historique_organismes.festival_id = $festival_id ORDER BY libelle;");
			foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id de la organisme pour faire la recherche (idem au dessus)
				$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
				$boucle .= ";".$row['libelle'].":".$row['libelle'];

			}
			F3::set('organismes', $boucle);

			DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE historique_organismes.festival_id = $festival_id and individu_id NOT IN (SELECT individu_id FROM `affectations`, `vacations`, `festivals_jours` WHERE affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id);");
			$count = F3::get('DB')->result[0];
			F3::set('nb_non_affectes', $count['count']);

			F3::set('pagetitle','Affectations');
			F3::set('jqgrid_url_individus','/ajax/profils_vacations');
			F3::set('url_assigner_post', '/vacations/affecter');
			F3::set('jqgrid_url_vacations', "/ajax/vacations_assigner");
		}

		//F3::call('vacations::recuperation_lieux');
		//F3::call('outils::recuperation_festivals_jours');

		F3::set('template','form_affectations');
		F3::call('outils::generer');
	}

	static function ajouter() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		F3::call('vacations::recuperation_lieux');
		F3::call('outils::recuperation_festivals_jours');
		F3::allow('date|strtotime');
		F3::set('pagetitle','Ajouter une vacation');
		F3::set('template','form_vacations');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('vacations::verif_libelle|vacations::verif_responsable_id'); //TODO: factoriser toutes ces verif dans outils // TODO: Verif autres champs
		if (!F3::exists('message')) {
			// Pas d'erreur, enregistrement de la vacation
			$vacation=new Axon('vacations');
			$vacation->copyFrom('REQUEST');
			$vacation->heure_debut = outils::date_timepicker_sql(F3::get('REQUEST.heure_debut'));
			$vacation->heure_fin = outils::date_timepicker_sql(F3::get('REQUEST.heure_fin'));
			$vacation->save();
			$vacation_id = $vacation->_id;
			historique::logger("Création de la vacation numéro $vacation_id");
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/vacations');
		}
		// Ré-Affichage du formulaire
		F3::call('vacations::ajouter');
	}

	static function editer() {
		outils::activerJquery();
		F3::call('outils::menu');
		F3::set('type','vacation');
		F3::call('outils::verif_responsable');
		$festival_id = F3::get('SESSION.festival_id');
		$vacation_id = F3::get('PARAMS.id');

		if(is_numeric($vacation_id))
		{
			$vacation=new Axon('vacations');
			$vacation->load("id=$vacation_id");

			if (!$vacation->dry()) {
				$vacation->copyTo('REQUEST');

				F3::set('REQUEST.heure_debut', outils::date_sql_timepicker(F3::get('REQUEST.heure_debut')));
				F3::set('REQUEST.heure_fin', outils::date_sql_timepicker(F3::get('REQUEST.heure_fin')));

				if ($vacation->responsable_id != 0) {
					$responsable_id = F3::get('REQUEST.responsable_id');
					$individu=new Axon('individus');
					$individu->load("id=$responsable_id");
					F3::set('REQUEST.responsable', $individu->prenom . ' ' . $individu->nom . ' - ' .$individu->date_naissance);
				}

				$membres = DB::sql("SELECT individus.id, individus.prenom, individus.nom, organismes.libelle FROM vacations, individus, affectations, organismes, historique_organismes WHERE vacations.id = $vacation_id AND historique_organismes.festival_id = $festival_id AND historique_organismes.individu_id = individus.id AND organismes.id = historique_organismes.organisme_id AND individus.id = affectations.individu_id AND affectations.vacation_id = vacations.id ORDER BY individus.nom;");
				if (count($membres) > 0)
				F3::set('membres', $membres);

				F3::call('vacations::recuperation_lieux');
				F3::call('outils::recuperation_festivals_jours');
				F3::set('editer','1');
				F3::set('pagetitle','Edition de la vacation ' . $vacation_id);
				F3::set('template','form_vacations');
				F3::call('outils::generer');
			}
			else F3::http404();
		}
		else F3::http404();
	}
	

    
	static function distribuer_organismes() {
		outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		F3::set('pagetitle','Distribuer des vacations aux organismes');
		$festival_id = F3::get('SESSION.festival_id');

		$boucle = ":Tout";
		DB::sql("SELECT libelle FROM domaines ORDER BY libelle;");
		foreach (F3::get('DB')->result as $row) {
			$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
			$boucle .= ";".$row['libelle'].":".$row['libelle'];
		}
		F3::set('domaines', $boucle);
		$boucle = ":Tout";
		DB::sql("SELECT libelle FROM lieux ORDER BY libelle;");
		foreach (F3::get('DB')->result as $row) {
			$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
			$boucle .= ";".$row['libelle'].":".$row['libelle'];

		}
		F3::set('lieux', $boucle);
		$boucle = ":Tout";
		DB::sql("SELECT jour FROM festivals_jours WHERE festival_id = $festival_id ORDER BY jour;");
		foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id du jour pour faire la recherche (idem au dessus)
			$boucle .= ";".$row['jour'].":".$row['jour'];

		}
		F3::set('jours', $boucle);

		F3::set('template','form_distribuer');
		F3::call('outils::generer');

	}

	static function distribuer_organismes_post() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');


		$liste_organismes = F3::get('REQUEST.organismes');
		$liste_vacations = F3::get('REQUEST.vacations');

		if(!preg_match('/^([0-9]+,?(\s+)?)+$/', $liste_organismes)  || !preg_match('/^([0-9]+,?(\s+)?)+$/', $liste_vacations)) exit("Séléction incorrecte");

		$tableau_organismes = explode(',', $liste_organismes);
		$tableau_vacations = explode(',', $liste_vacations);
		$message = "";
		foreach( $tableau_vacations as $id_vacation) {

			foreach( $tableau_organismes as $id_organisme) {
				DB::sql("SELECT COUNT(id) AS count FROM organismes_vacations WHERE vacation_id=$id_vacation;");
				$result = F3::get('DB')->result;
				$utilise = $result[0]['count'];
				if(($utilise > 0))  // si deja utilisé
				$message .= "Vacation $id_vacation déjà attribuée\n";
				else{
					DB::sql("INSERT INTO organismes_vacations VALUES('',$id_organisme,$id_vacation);");
					$message .= "L'organisme $id_organisme a correctement reçu la vacation $id_vacation.\n";
					historique::logger("L'organisme $id_organisme a correctement reçu la vacation $id_vacation.");
				}
			}
		}
		echo $message;

	}
	
    static function emargement_lieu() {
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');
    }
    
    static function emargement_organisme() {
		F3::call('outils::menu');
		F3::call('outils::verif_responsable');

    }
    
    static function emargement_vacation() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		
		F3::set('pagetitle','Saisie des heures travaillées');
		$festival_id = F3::get('SESSION.festival_id');
		$vacation_id = F3::get('PARAMS.id');
		//F3::call('vacations::verif_vacation_id'); //TODO XSS/SQL
		if (!F3::exists('message')) {
			$vacation=new Axon('vacations');
			$vacation->load("id=$vacation_id");
			
			
			F3::set('vacation',$vacation);	
			
			$contenu='<table style="width:500px">';
			 //TODO: SQLi
			$affectations = DB::sql("SELECT individus.id, individus.prenom, individus.nom FROM affectations, individus WHERE individus.id = affectations.individu_id AND affectations.vacation_id = $vacation_id ORDER BY individus.nom;");
			print_r($affectations);
						
			foreach($affectations as $affectation)
			{
				$individu_id = $affectation["id"];
				$contenu .= "<tr><td>" .$affectation["nom"] . ' ' . $affectation["prenom"] .  "</td>";
				
				$contenu .= '<td><input type="radio" name="'. $individu_id . '" value="1" checked><label for="individu">Tout OK</label></td>';
				$contenu .= '<td><input type="radio" name="'. $individu_id . '" value="2"><label for="individu">Modifier</label></td>';
				$contenu .= '<td>de <input type="text" size="4" class="heure_debut" name="heure_debut_'.$individu_id.'" id="heure_debut_'.$individu_id.'  /></td>';
				$contenu .= '<td>à <input type="text" size="4" class="heure_fin" name="heure_fin_'.$individu_id.'" id="heure_fin_'.$individu_id.'  /></td>';
				$contenu .= '<td><input type="radio" name="'. $individu_id . '" value="3"><label for="individu">Pas travaillé</label></td>';

				$contenu .= '</tr>';				
			}
			$contenu .= '</table>';

			F3::set('contenu',$contenu);
			F3::set('template','form_emargement');
			F3::call('outils::generer');	
			
			
			
		}
    }
    
    
	static function editer_post() {
		F3::set('type','vacation');
		F3::call('outils::verif_responsable');
		$vacation_id = F3::get('PARAMS.id');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('vacations::verif_vacation_id|vacations::verif_libelle|vacations::verif_responsable_id'); //TODO: factoriser toutes ces verif dans outils // TODO: Verif autres champs
		if (!F3::exists('message')) {

			// Pas d'erreur, enregistrement de la organisme
			$vacation=new Axon('vacations');
			$vacation->load("id=$vacation_id");
			$vacation->copyFrom('REQUEST');
			$vacation->heure_debut = outils::date_timepicker_sql(F3::get('REQUEST.heure_debut'));
			$vacation->heure_fin = outils::date_timepicker_sql(F3::get('REQUEST.heure_fin'));
			$vacation->save();

			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/vacations');
		}
		// Ré-Affichage du formulaire
		F3::call('vacations::editer');
	}

	static function editer_tableau_post() {
		F3::set('type','vacation');
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				F3::set('PARAMS.id',$id);
				$vacations=new Axon('vacations');
				$vacations->load("id=$id");
				if(outils::est_responsable())
				{
					DB::sql('DELETE FROM vacations WHERE id = :id ;',array(':id'=>array($id,PDO::PARAM_INT)));
					historique::logger("Suppréssion de la vacation ". $vacations->libelle . " sur le lieu " . $vacations->lieu_id);
				}
			}
		}
	}


	static function echanger_vacations() {
		outils::activerJqgrid();
		outils::activerFacyBox();

		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$festival_id = F3::get('SESSION.festival_id');
		$boucle = ":Tout";
		DB::sql("SELECT DISTINCT libelle FROM `organismes`, `historique_organismes` WHERE organismes.id = historique_organismes.organisme_id AND historique_organismes.festival_id = $festival_id ORDER BY libelle;");
		foreach (F3::get('DB')->result as $row) { //XXX On pourais utiliser l'id de la organisme pour faire la recherche (idem au dessus)
			$row['libelle'] = preg_replace("[^A-Za-z0-9-]", "", $row['libelle']);
			$boucle .= ";".$row['libelle'].":".$row['libelle'];
		}
		F3::set('organismes', $boucle);




		F3::set('pagetitle','Echange de vacations');
		F3::set('template','echanger_vacations');
		F3::call('outils::generer');

	}



	static function echanger_vacations_post() {
		// ID personne 1
		// ID personne 2
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');

		$individu1_id = F3::get('REQUEST.individu1_id');
		$individu2_id = F3::get('REQUEST.individu2_id');
			
		$affectations_individu1 = F3::get('REQUEST.affectations_individu1');
		$affectations_individu2 = F3::get('REQUEST.affectations_individu2');
			
		if(!is_numeric($individu1_id) || !is_numeric($individu2_id))
		exit("Individu 1 ou 2 manquant");
			
		if(($affectations_individu1 != "") || ($affectations_individu2 != "")){ //Si  vacations, faire l'échange
			$affectations_individu1 = explode(' ', $affectations_individu1);
			$affectations_individu2 = explode(' ', $affectations_individu2);


			foreach( $affectations_individu1 as $affectation_id) { 			// Pour chaques vacations de $vacations_individu1, on remplace individu1 par individu2
				if(is_numeric($affectation_id)){
					$affectation=new Axon('affectations');
					$affectation->load("id=$affectation_id");
					$affectation->individu_id = $individu2_id;
					$affectation->save();
				}
			}

			foreach( $affectations_individu2 as $affectation_id) { 			// Idem avec individu2
				if(is_numeric($affectation_id)){
					$affectation=new Axon('affectations');
					$affectation->load("id=$affectation_id");
					$affectation->individu_id = $individu1_id;
					$affectation->save();
				}
			}
			historique::logger("Echange de vacations entre $individu1_id et $individu2_id");

			echo "OK";

		}else{ //Sinon afficher le formulaire de selection de vacations

			$individu = new Axon('individus');
			$individu->load("id=$individu1_id");

			$sortie = '<fieldset><legend>'. $individu->prenom . ' '. $individu->nom . ' :</legend>';

			DB::sql("SELECT affectations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `affectations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND affectations.individu_id = $individu1_id;");
			foreach (F3::get('DB')->result as $row) {
				$sortie .= '<input type="checkbox" name="affectations_individu1" value=" '. $row['id'] .'" /> '. $row['jour'].$row['heure_debut'].$row['heure_fin'].$row['libelle']. "<br />";
			}

			$individu = new Axon('individus');
			$individu->load("id=$individu2_id");

			$sortie .= '</fieldset><fieldset><legend>'. $individu->prenom . ' '. $individu->nom . ' :</legend>';

			DB::sql("SELECT affectations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `affectations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND affectations.individu_id = $individu2_id;");
			foreach (F3::get('DB')->result as $row) {
				$sortie .= '<input type="checkbox" name="affectations_individu2" value=" '. $row['id'] .'" /> '. $row['jour'] . ' ' . $row['heure_debut'] . ' ' . $row['heure_fin'] . ' ' . $row['libelle']. "<br />";
			}


			$sortie .= '</fieldset><input type="submit" onclick="echanger()" name="echanger" value="Échanger" />';

			echo($sortie);
		}
	}

	static function supprimer_affectations_vacation() {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');

		$vacation_id = F3::get('PARAMS.id');

		if(is_numeric($vacation_id))
		{
			DB::sql("DELETE FROM affectations WHERE vacation_id=$vacation_id");
			F3::reroute("/vacations/editer/" . $vacation_id);
		}
		else
		Outils::http401();
	}

	static function imprimer_emargement_vacation() {
		F3::call('outils::verif_individu');

		ini_set('max_execution_time', '0');

		$vacation_id = F3::get('PARAMS.id');
		if(is_numeric($vacation_id))
		{
			$vacation = new Axon('vacations');
			$vacation->load("id=$vacation_id");

			if(!$vacation->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				$festival_id = F3::get('SESSION.festival_id');
				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");
					
				$festivals_jours = new Axon('festivals_jours');
				$festivals_jours->load("id=$vacation->festival_jour_id");

				$lieu = DB::sql("SELECT l.libelle FROM lieux AS l, vacations AS v WHERE v.id=$vacation_id AND v.lieu_id=l.id");

				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13('L');

				$pdf->header = true;
				$pdf->barcode = $vacation_id;
				$pdf->footer = true;
					
				$pdf->SetMargins(5, 5);
				$pdf->titre = "Emargement " . $lieu[0]['libelle'] . " " . $festival->annee;
				$pdf->AliasNbPages();
				$pdf->AddPage();
					
				$responsable = DB::sql("SELECT i.photo, i.nom AS nom_individu, i.prenom, i.adresse1, i.adresse2, vi.cp, vi.nom AS nom_ville FROM individus AS i, villes AS vi, vacations AS va WHERE va.id=$vacation_id AND va.responsable_id=i.id AND i.ville_id=vi.id");
				if (count($responsable)>0)
				{
					$pdf->SetFont('Arial','B',14);
					$pdf->Cell(25);
					$pdf->Cell(0,6,"Responsable :",0,1, 'L');

					$pdf->SetFont('Arial','',14);

					if ($responsable[0]['photo'] != NULL)
					$pdf->Image("uploads/photos/". $responsable[0]['photo'] ,15,49,null,30);

					$pdf->Cell(25);
					$pdf->Cell(0,6,$responsable[0]['nom_individu'] . " " . $responsable[0]['prenom'] ,0,1, 'L');

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

					$pdf->Cell(25);
					$pdf->Cell(0,6,$responsable[0]['cp'] . " " . $responsable[0]['nom_ville'],0,1, 'L');
				}

				$pdf->Line(140,40,140,68);
					
				//Données SQL
				$data = DB::sql("SELECT i.id AS id, i.nom AS nom_individu, i.prenom, o.libelle, '' AS debut, '' AS signature1, '' AS fin, '' AS signature2 FROM vacations AS va, individus AS i, affectations AS a, organismes AS o, historique_organismes AS ho WHERE va.id = $vacation_id AND ho.festival_id = $festival_id AND ho.individu_id = i.id AND o.id = ho.organisme_id AND i.id = a.individu_id AND a.vacation_id = va.id ORDER BY i.nom;");

				//Décalage à droite
				$pdf->SetY(40);

				$pdf->Cell(150);
				$pdf->Cell(0,6,$vacation->libelle . " (ID:" . $vacation_id . ")" ,0,1, 'L');
				$pdf->Cell(150);
				$pdf->Cell(0,6,"Date : " . outils::date_sql_fr($festivals_jours->jour),0,1, 'L');
				$pdf->Cell(150);
				$pdf->Cell(0,6,"Heure debut : " . outils::date_sql_timepicker($vacation->heure_debut) . " - Heure fin : " . outils::date_sql_timepicker($vacation->heure_fin),0,1, 'L');
				$pdf->Cell(150);
				$pdf->Cell(0,6,"# personnes : " . count($data),0,1, 'L');

				$pdf->SetY(70);

				//Titres des colonnes
				$header=array('ID','Nom','Prenom','Association','Debut','Signature','Fin','Signature');
				//Largeur des colonnes
				$w=array(16,40,40,90,20,30,20,30);
				//Titre des colonnes SQL
				$header2=array('id','nom_individu','prenom','libelle','debut','signature1','fin','signature2');
				//Grande taille pour signature
				$pdf->cellule_haute = true;
				//Affichage des données
				$pdf->FancyTable($header,$data,$header2,$w);

				$pdf->Output("Emargement-".$vacation_id."-".$festival->annee.".pdf", 'D');

			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	static function imprimer_emargement_lieu() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$lieu_id = F3::get('PARAMS.id');
		if(is_numeric($lieu_id))
		{
			$lieu = new Axon('lieux');
			$lieu->load("id=$lieu_id");

			if(!$lieu->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				$festival_id = F3::get('SESSION.festival_id');
				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");
					
				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13('L');

				$pdf->SetMargins(5, 5);
				$pdf->titre = "Emargement " . $lieu->libelle . " " . $festival->annee;

				//Lister les vacations du lieu
				$jour_id = F3::get("REQUEST.jour_id");
				if($jour_id != "")
				$vacations = DB::sql("SELECT * FROM vacations WHERE lieu_id=$lieu_id AND festival_jour_id=$jour_id");
				else
				$vacations = DB::sql("SELECT * FROM vacations WHERE lieu_id=$lieu_id");

				if( count($vacations) > 0)
				{
					foreach ($vacations as $vacation) {
						$vacation_id = $vacation["id"];

						$festival_jour_id = $vacation["festival_jour_id"];
						$festivals_jours = new Axon('festivals_jours');
						$festivals_jours->load("id=$festival_jour_id");

						$pdf->header = true;
						$pdf->barcode = $vacation_id;

						$pdf->AddPage();

						$responsable = DB::sql("SELECT i.photo, i.nom AS nom_individu, i.prenom, i.adresse1, i.adresse2, vi.cp, vi.nom AS nom_ville FROM individus AS i, villes AS vi, vacations AS va WHERE va.id=$vacation_id AND va.responsable_id=i.id AND i.ville_id=vi.id");
						if (count($responsable)>0)
						{
							$pdf->SetFont('Arial','B',14);
							$pdf->Cell(25);
							$pdf->Cell(0,6,"Responsable :",0,1, 'L');

							$pdf->SetFont('Arial','',14);

							if ($responsable[0]['photo'] != NULL)
							$pdf->Image("uploads/photos/". $responsable[0]['photo'] ,15,49,null,30);

							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['nom_individu'] . " " . $responsable[0]['prenom'] ,0,1, 'L');

							if ($responsable[0]['adresse1'] != NULL)
							{
								$pdf->Cell(25);
								$pdf->Cell(0,6,$responsable[0]['adresse1'],0,1, 'L');
							}
							if ($responsable[0]['adresse2'] != NULL)
							{
								$pdf->Cell(25);
								$pdf->Cell(0,6,$responsable[0]['adresse2'],0,1, 'L');
							}

							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['cp'] . " " . $responsable[0]['nom_ville'],0,1, 'L');
						}

						$pdf->Line(140,40,140,65);

						//Données SQL
						$data = DB::sql("SELECT i.id AS id, i.nom AS nom_individu, i.prenom, o.libelle, '' AS debut, '' AS signature1, '' AS fin, '' AS signature2 FROM vacations AS va, individus AS i, affectations AS a, organismes AS o, historique_organismes AS ho WHERE va.id = $vacation_id AND ho.festival_id = $festival_id AND ho.individu_id = i.id AND o.id = ho.organisme_id AND i.id = a.individu_id AND a.vacation_id = va.id ORDER BY i.nom;");

						//Décalage à droite
						$pdf->SetY(40);

						$pdf->Cell(150);
						$pdf->Cell(0,6,$vacation["libelle"] . " (ID:" . $vacation_id . ")" ,0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"Date : " . outils::date_sql_fr($festivals_jours->jour),0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"Heure debut : " . outils::date_sql_timepicker($vacation["heure_debut"]) . " - Heure fin : " . outils::date_sql_timepicker($vacation["heure_fin"]),0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"# personnes : " . count($data),0,1, 'L');

						$pdf->Ln(8);

						//Titres des colonnes
						$header=array('ID','Nom','Prenom','Association','Debut','Signature','Fin','Signature');
						//Largeur des colonnes
						$w=array(16,40,40,90,20,30,20,30);
						//Titre des colonnes SQL
						$header2=array('id','nom_individu','prenom','libelle','debut','signature1','fin','signature2');
						//Grande taille pour signature
						$pdf->cellule_haute = true;
						//Affichage des données
						$pdf->FancyTable($header,$data,$header2,$w);
					}
					$pdf->Output("Emargement-".$vacation_id."-".$festival->annee.".pdf", 'D');

				}
				else
				echo "Aucune vacation pour le lieu ou la période selectionnée";
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	static function imprimer_emargement_domaine() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$domaine_id = F3::get('PARAMS.id');
		if(is_numeric($domaine_id))
		{
			$domaine = new Axon('domaines');
			$domaine->load("id=$domaine_id");

			if(!$domaine->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				$festival_id = F3::get('SESSION.festival_id');
				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");

				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13('L');

				$pdf->SetMargins(5, 5);

				$lieux = DB::sql("SELECT * FROM lieux WHERE domaine_id=$domaine_id");

				foreach ($lieux as $lieu) {
					$pdf->titre = "Emargement " . $lieu['libelle'] . " " . $festival->annee;

					$lieu_id = $lieu["id"];
					//Lister les vacations du lieu
					$vacations = DB::sql("SELECT * FROM vacations WHERE lieu_id=$lieu_id");

					foreach ($vacations as $vacation) {
						$vacation_id = $vacation["id"];

						$festival_jour_id = $vacation["festival_jour_id"];
						$festivals_jours = new Axon('festivals_jours');
						$festivals_jours->load("id=$festival_jour_id");
							
						$pdf->header = true;
						$pdf->barcode = $vacation_id;

						$pdf->AddPage();

						$responsable = DB::sql("SELECT i.photo, i.nom AS nom_individu, i.prenom, i.adresse1, i.adresse2, vi.cp, vi.nom AS nom_ville FROM individus AS i, villes AS vi, vacations AS va WHERE va.id=$vacation_id AND va.responsable_id=i.id AND i.ville_id=vi.id");
						if (count($responsable)>0)
						{
							$pdf->SetFont('Arial','B',14);
							$pdf->Cell(25);
							$pdf->Cell(0,6,"Responsable :",0,1, 'L');

							$pdf->SetFont('Arial','',14);

							if ($responsable[0]['photo'] != NULL)
							$pdf->Image("uploads/photos/". $responsable[0]['photo'] ,15,49,null,30);

							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['nom_individu'] . " " . $responsable[0]['prenom'] ,0,1, 'L');

							if ($responsable[0]['adresse1'] != NULL)
							{
								$pdf->Cell(25);
								$pdf->Cell(0,6,$responsable[0]['adresse1'],0,1, 'L');
							}
							if ($responsable[0]['adresse2'] != NULL)
							{
								$pdf->Cell(25);
								$pdf->Cell(0,6,$responsable[0]['adresse2'],0,1, 'L');
							}

							$pdf->Cell(25);
							$pdf->Cell(0,6,$responsable[0]['cp'] . " " . $responsable[0]['nom_ville'],0,1, 'L');
						}

						$pdf->Line(140,40,140,65);
							
						//Données SQL
						$data = DB::sql("SELECT i.id AS id, i.nom AS nom_individu, i.prenom, o.libelle, '' AS debut, '' AS signature1, '' AS fin, '' AS signature2 FROM vacations AS va, individus AS i, affectations AS a, organismes AS o, historique_organismes AS ho WHERE va.id = $vacation_id AND ho.festival_id = $festival_id AND ho.individu_id = i.id AND o.id = ho.organisme_id AND i.id = a.individu_id AND a.vacation_id = va.id ORDER BY i.nom;");
							
						//Décalage à droite
						$pdf->SetY(40);

						$pdf->Cell(150);
						$pdf->Cell(0,6,$vacation["libelle"] . " (ID:" . $vacation_id . ")" ,0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"Date : " . outils::date_sql_fr($festivals_jours->jour),0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"Heure debut : " . outils::date_sql_timepicker($vacation["heure_debut"]) . " - Heure fin : " . outils::date_sql_timepicker($vacation["heure_fin"]),0,1, 'L');
						$pdf->Cell(150);
						$pdf->Cell(0,6,"# personnes : " . count($data),0,1, 'L');

						$pdf->Ln(4);

						//Titres des colonnes
						$header=array('ID','Nom','Prenom','Association','Debut','Signature','Fin','Signature');
						//Largeur des colonnes
						$w=array(16,40,40,90,20,30,20,30);
						//Titre des colonnes SQL
						$header2=array('id','nom_individu','prenom','libelle','debut','signature1','fin','signature2');
						//Grande taille pour signature
						$pdf->cellule_haute = true;
						//Affichage des données
						$pdf->FancyTable($header,$data,$header2,$w);
					}
				}

				$pdf->Output("Emargement-".$vacation_id."-".$festival->annee.".pdf", 'D');

			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	//Impression pour 2012
	/*static function imprimer() {
	F3::call('outils::verif_individu');

	$festival_id = F3::get('SESSION.festival_id');
	$individu_id = F3::get('SESSION.id');

	if(is_numeric(F3::get('PARAMS.id'))){
	$id = F3::get('PARAMS.id');
	DB::sql("SELECT h2.individu_id FROM `historique_organismes` as h1, `historique_organismes` as h2 WHERE h1.individu_id = $id AND h1.festival_id = $festival_id AND h1.organisme_id = h2.organisme_id AND h2.responsable=1 AND h1.festival_id=h2.festival_id;");
	if ((F3::get('DB')->result[0]['individu_id'] == $individu_id) || outils::est_operateur()){
	$individu_id = $id;
	}else
	outils::http401();
	}
	else
	{
	F3::call('outils::verif_individu');
	$individu_id = F3::get('SESSION.id');
	}

	require_once 'lib/pdf.php';
	require_once 'lib/barcode-ean13.php';

	//Instanciation de la classe dérivée
	$pdf=new PDF_EAN13();
	$pdf->SetMargins(15, 15);

	$pdf->header = true;
	$pdf->footer = true;

	$festival = new Axon('festivals');
	$festival->load("id=$festival_id");
	$pdf->titre = "Convocation " . $festival->annee . "..............................";

	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->SetFont('Arial','',15);
	$pdf->Ln(20);

	$individu = new Axon('individus');
	$individu->load("id=$individu_id");

	//$individu_id += 111111111111;
	$pdf->EAN13(110,70,$individu_id + 000000000000);

	//Nom & prenom de l'inidividu
	$pdf->Cell(0,6,$individu->nom . " " . $individu->prenom,0,1,'R');
	//Adresse1
	$pdf->Cell(0,6,$individu->adresse1,0,1, 'R');
	//Adresse2
	if ($individu->adresse2 != NULL)
	$pdf->Cell(0,6,$individu->adresse2,0,1, 'R');
	//Ville
	if (($individu->ville_id != NULL) && ($individu->ville_id != 0) )
	{
	$individu_ville_id = $individu->ville_id;
	$ville_individu = new Axon('villes');
	$ville_individu->load("id=$individu_ville_id");
	$pdf->Cell(0,6,$ville_individu->cp . " " . $ville_individu->nom,0,1, 'R');
	}

	$pdf->Ln(10);

	//Organisme
	$organisme = DB::sql("SELECT o.libelle, o.adresse1, o.adresse2, o.ville_id FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
	if (count($organisme)>0)
	{
	$pdf->Cell(0,6,"Organisme : ",0,1, 'L');
	$pdf->Cell(0,6,$organisme[0]['libelle'],0,1, 'L');
	if ($organisme[0]['adresse1'] != NULL)
	$pdf->Cell(0,6,$organisme[0]['adresse1'],0,1, 'L');

	if ($organisme[0]['adresse2'] != NULL)
	$pdf->Cell(0,6,$organisme[0]['adresse2'],0,1, 'L');

	if (($organisme[0]['ville_id'] != NULL) && ($organisme[0]['ville_id'] != 0) )
	{
	$organisme_ville_id = $organisme[0]['ville_id'];
	//echo $organisme_ville_id;
	$ville_organisme = new Axon('villes');
	$ville_organisme->load("id=$organisme_ville_id");
	$pdf->Cell(0,6,$ville_organisme->cp . " " . $ville_organisme->nom,0,1, 'L');
	}
	}

	$pdf->Ln(30);
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(0,5,"Vos affectations : ",0,1, 'L');
	$pdf->Ln(10);

	//Affectations
	$affectations = DB::sql("SELECT a.id, fj.jour, v.heure_debut, v.heure_fin, l.libelle, v.lieu_id FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id;");

	foreach($affectations as $cle=>$valeur)
	{
	$lieu_id = $valeur["lieu_id"];
	$responsable = DB::sql("SELECT i.prenom, i.nom FROM individus AS i, responsables_lieux AS rl WHERE rl.lieu_id=$lieu_id AND rl.festival_id=$festival_id AND i.id=rl.individu_id");

	$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
	if (count($responsable) > 0)
	$affectation .= ", demander " . $responsable[0]['prenom'] . " " . $responsable[0]['nom'];

	$pdf->Cell(0,5,$affectation,0,1);
	}

	//$pdf->Output();
	$pdf->Output("Affectations-".$individu_id."-".$festival->annee.".pdf", 'D');
	}*/

	static function imprimer_vacations_individu() {
		F3::call('outils::verif_individu');

		$festival_id = F3::get('SESSION.festival_id');

		$festival = new Axon('festivals');
		$festival->load("id=$festival_id");

		$individu_id = F3::get('SESSION.id');

		if(is_numeric(F3::get('PARAMS.id'))){
			$id = F3::get('PARAMS.id');
			DB::sql("SELECT h2.individu_id FROM `historique_organismes` as h1, `historique_organismes` as h2 WHERE h1.individu_id = $id AND h1.festival_id = $festival_id AND h1.organisme_id = h2.organisme_id AND h2.responsable=1 AND h1.festival_id=h2.festival_id;");
			if ((F3::get('DB')->result[0]['individu_id'] == $individu_id) || outils::est_operateur()){
				$individu_id = $id;
			}else
			outils::http401();
		}
		else
		{
			F3::call('outils::verif_individu');
			$individu_id = F3::get('SESSION.id');
		}

		require_once 'lib/pdf.php';
		require_once 'lib/barcode-ean13.php';

		//Instanciation de la classe dérivée
		$pdf=new PDF_EAN13();
		$pdf->SetMargins(15, 15);


		//Affectations
		$affectations = DB::sql("SELECT fj.jour, v.heure_debut, v.heure_fin, l.libelle FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id ORDER BY fj.jour, v.heure_debut;");
		if (count($affectations)>0)
		{
			$pdf->AddPage();
			$pdf->SetFont('Arial','',15);

			$pdf->EAN13(116,30,$individu_id + 000000000000);

			$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

			$pdf->SetY(55);

			//Organisme
			$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
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

			$pdf->AddFont('ocraextended');
			$pdf->SetFont('ocraextended');

			//echo $individu[0]['prenom'];
			$i_adresse = $individu[0]["i_nom"] . " " .   $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
			if ($individu[0]['adresse2'] != NULL)
			$i_adresse .= $individu[0]['adresse2'] . "\n";
			$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

			$pdf->Cell(100);
			$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');

			$pdf->SetY(140);
			$pdf->SetFont('Arial','',12);

			//Affectations
			foreach($affectations as $cle=>$valeur)
			{
				$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
				$pdf->Cell(0,5,$affectation,0,1);
			}
		}
		$pdf->Output("Affectations-".$individu_id."-".$festival->annee.".pdf", 'D');
	}

	static function imprimer_vacations_organisme() {
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

				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13();
				$pdf->SetMargins(5, 5);

				$festival_id = F3::get('SESSION.festival_id');

				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");

				$individus = Organismes::membres_organisme($organisme_id, $festival_id);

				foreach ($individus as $individu) {
					$individu_id = $individu["id"];

					//Affectations
					$affectations = DB::sql("SELECT fj.jour, v.heure_debut, v.heure_fin, l.libelle FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id ORDER BY fj.jour, v.heure_debut;");
					if (count($affectations)>0)
					{
						$pdf->AddPage();
						$pdf->SetFont('Arial','',15);

						$pdf->EAN13(116,30,$individu_id + 000000000000);

						$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

						$pdf->SetY(55);

						//Organisme
						$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
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

						$i_adresse = $individu[0]["i_nom"] . " " . $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
						if ($individu[0]['adresse2'] != NULL)
						$i_adresse .= $individu[0]['adresse2'];
						$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

						$pdf->Cell(100);

						$pdf->AddFont('ocraextended');
						$pdf->SetFont('ocraextended');

						$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');

						$pdf->SetY(140);
						$pdf->SetFont('Arial','',12);

						//Affectations
						foreach($affectations as $cle=>$valeur)
						{
							$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
							$pdf->Cell(0,5,$affectation,0,1);
						}
					}
				}
				$pdf->Output("Affectations-organisme-".$organisme_id."-".$festival->annee.".pdf", 'D');
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	static function imprimer_vacations_lieu() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$lieu_id = F3::get('PARAMS.id');

		if(is_numeric($lieu_id))
		{
			$lieu = new Axon('lieux');
			$lieu->load("id=$lieu_id");

			if(!$lieu->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13();
				$pdf->SetMargins(5, 5);

				$festival_id = F3::get('SESSION.festival_id');

				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");

				$individus = Lieux::membres_lieu($lieu_id, $festival_id);

				foreach ($individus as $individu) {
					$individu_id = $individu["id"];

					//Affectations
					$affectations = DB::sql("SELECT fj.jour, v.heure_debut, v.heure_fin, l.libelle FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id ORDER BY fj.jour, v.heure_debut;");
					if (count($affectations)>0)
					{
						$pdf->AddPage();
						$pdf->SetFont('Arial','',15);

						$pdf->EAN13(116,30,$individu_id + 000000000000);

						$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

						$pdf->SetY(55);

						//Organisme
						$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
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

						$pdf->AddFont('ocraextended');
						$pdf->SetFont('ocraextended');

						$i_adresse = $individu[0]["i_nom"] . " " . $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
						if ($individu[0]['adresse2'] != NULL)
						$i_adresse .= $individu[0]['adresse2'];
						$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

						$pdf->Cell(100);
						$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');

						$pdf->SetY(140);
						$pdf->SetFont('Arial','',12);

						//Affectations
						foreach($affectations as $cle=>$valeur)
						{
							$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
							$pdf->Cell(0,5,$affectation,0,1);
						}
					}
				}
				$pdf->Output("Affectations-lieu-".$lieu_id."-".$festival->annee.".pdf", 'D');
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	static function imprimer_vacations_domaine() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$domaine_id = F3::get('PARAMS.id');

		if(is_numeric($domaine_id))
		{
			$domaine = new Axon('domaines');
			$domaine->load("id=$domaine_id");

			if(!$domaine->dry())
			{
				require_once 'lib/pdf.php';
				require_once 'lib/barcode-ean13.php';

				//Instanciation de la classe dérivée
				$pdf=new PDF_EAN13();
				$pdf->SetMargins(5, 5);

				$festival_id = F3::get('SESSION.festival_id');

				$festival = new Axon('festivals');
				$festival->load("id=$festival_id");

				$individus = Domaines::membres_domaine($domaine_id, $festival_id);

				foreach ($individus as $individu) {
					$individu_id = $individu["id"];

					//Affectations
					$affectations = DB::sql("SELECT fj.jour, v.heure_debut, v.heure_fin, l.libelle FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id ORDER BY fj.jour, v.heure_debut;");
					if (count($affectations)>0)
					{
						$pdf->AddPage();
						$pdf->SetFont('Arial','',15);

						$pdf->EAN13(116,30,$individu_id + 000000000000);

						$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

						$pdf->SetY(55);

						//Organisme
						$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
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

						$pdf->AddFont('ocraextended');
						$pdf->SetFont('ocraextended');

						$i_adresse = $individu[0]["i_nom"] . " " . $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
						if ($individu[0]['adresse2'] != NULL)
						$i_adresse .= $individu[0]['adresse2'];
						$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

						$pdf->Cell(100);
						$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');

						$pdf->SetY(140);
						$pdf->SetFont('Arial','',12);

						//Affectations
						foreach($affectations as $cle=>$valeur)
						{
							$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
							$pdf->Cell(0,5,$affectation,0,1);
						}
					}
				}
				$pdf->Output("Affectations-domaine-".$domaine_id."-".$festival->annee.".pdf", 'D');
			}
			else
			Outils::http401();
		}
		else
		Outils::http401();
	}

	static function imprimer_tout() {
		F3::call('outils::verif_admin');

		ini_set('max_execution_time', '0');

		$festival_id = F3::get('SESSION.festival_id');

		require_once 'lib/pdf.php';
		require_once 'lib/barcode-ean13.php';

		//Instanciation de la classe dérivée
		$pdf=new PDF_EAN13();
		$pdf->SetMargins(15, 15);

		$festival = new Axon('festivals');
		$festival->load("id=$festival_id");

		$code_postal_debut = F3::get('REQUEST.code_postal_debut');
		$code_postal_fin = F3::get('REQUEST.code_postal_fin');

		if($code_postal_debut!="" && $code_postal_fin!="")
		$individus = DB::sql("SELECT individus.id AS individu_id, organismes.id AS organisme_id FROM `organismes`, `historique_organismes`, `individus`, `villes` WHERE historique_organismes.individu_id = individus.id AND historique_organismes.festival_id = $festival_id AND historique_organismes.organisme_id = organismes.id AND individus.ville_id = villes.id AND villes.cp >= $code_postal_debut AND villes.cp <= $code_postal_fin ORDER BY individus.id;");
		else
		$individus = DB::sql("SELECT individus.id AS individu_id, organismes.id AS organisme_id FROM `organismes`, `historique_organismes`, `individus` WHERE historique_organismes.individu_id = individus.id AND historique_organismes.festival_id = $festival_id AND historique_organismes.organisme_id = organismes.id ORDER BY individus.id;");


		foreach($individus as $cle=>$valeur)
		{
			$individu_id = $valeur["individu_id"];

			//Affectations
			$affectations = DB::sql("SELECT fj.jour, v.heure_debut, v.heure_fin, l.libelle FROM lieux AS l, affectations AS a, vacations AS v, festivals_jours AS fj WHERE v.lieu_id = l.id AND a.vacation_id = v.id AND fj.id = v.festival_jour_id AND fj.festival_id = $festival_id AND a.individu_id = $individu_id ORDER BY fj.jour, v.heure_debut;");
			if (count($affectations)>0)
			{
				$pdf->AddPage();
				$pdf->SetFont('Arial','',15);

				$pdf->EAN13(116,30,$individu_id + 000000000000);

				$individu = DB::sql("SELECT i.nom AS i_nom, i.prenom, i.adresse1, i.adresse2, v.cp, v.nom AS v_nom FROM individus AS i, villes AS v WHERE i.ville_id = v.id AND i.id=$individu_id");

				$pdf->SetY(55);

				//Organisme
				$organisme = DB::sql("SELECT o.id, o.libelle FROM organismes AS o, historique_organismes AS ho WHERE ho.individu_id=$individu_id AND ho.festival_id=$festival_id AND ho.organisme_id=o.id");
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

				$pdf->AddFont('ocraextended');
				$pdf->SetFont('ocraextended');

				$i_adresse = $individu[0]["i_nom"] . " " . $individu[0]['prenom'] . "\n" . $individu[0]['adresse1'] . "\n";
				if ($individu[0]['adresse2'] != NULL)
				$i_adresse .= $individu[0]['adresse2'];
				$i_adresse .= $individu[0]['cp'] . " " . $individu[0]['v_nom'];

				$pdf->Cell(100);
				$pdf->MultiCell(0, 6, $i_adresse, 0, 'L');

				$pdf->SetY(140);
				$pdf->SetFont('Arial','',12);

				//Affectations
				foreach($affectations as $cle=>$valeur)
				{
					$affectation = outils::date_sql_fr($valeur["jour"]) . " : " . $valeur["libelle"] . " de " . $valeur["heure_debut"] . " à " . $valeur["heure_fin"];
					$pdf->Cell(0,5,$affectation,0,1);
				}
			}
		}

		$pdf->Output("Affectations-".$individu_id."-".$festival->annee.".pdf", 'D');
	}

	static function verif_vacation_id() {
		F3::input('vacation_id',
		function($value) {
			if (!F3::exists('message')) {
				if ( (preg_match('/^[0-9]+$/', $value) == 0) || $value == 0)
				F3::set('message','Vacation incorrecte');
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

	static function recuperation_lieux() {
		$festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT lieux.id, libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id ORDER BY libelle");
		F3::set('lieux',F3::get('DB')->result);
	}

	static function est_responsable() {
		if(outils::est_admin()) return true;
		$festival_id = F3::get('SESSION.festival_id');
		$individu_id = F3::get('SESSION.id');
		$vacation_id = F3::get('PARAMS.id');
		if(!isset($individu_id) || !isset($festival_id) || !isset($vacation_id)) return false;
		DB::sql('SELECT COUNT(id) as count FROM responsables_domaines WHERE individu_id = :individu_id AND festival_id = :festival_id AND domaine_id = (SELECT domaine_id FROM lieux WHERE id = (SELECT lieu_id FROM vacations WHERE id= :vacation_id));',array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':festival_id'=>array($festival_id,PDO::PARAM_INT),':vacation_id'=>array($vacation_id,PDO::PARAM_INT)));

		$result = F3::get('DB')->result;
		if($result[0]['count'] == 1) return true;
		return false;
	}
}
?>
