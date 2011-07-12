<?php
class Ajax {

	static function villes() {
		F3::call('outils::menu');
		$recherche = F3::get('REQUEST.term');
		if(strlen($recherche) < 2)
		exit('Trop court');
		DB::sql("SELECT nom,id,cp FROM villes WHERE CONCAT(nom, ' ', cp) LIKE :recherche ;",array(':recherche'=> '%' . $recherche. '%'));
		 
		$data = array();
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

	static function festivals() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;
		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
					case 'annee':
						$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'libelle':
					case 'taux_horaire':
						$wh .= " WHERE ".$k." LIKE '%".$v."%'";
						//				$wh .= " WHERE ".$k." = ".$v;
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM festivals;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT id, libelle, annee, taux_horaire FROM festivals".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['annee'],$row['libelle'],$row['taux_horaire']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function organismes() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$action = F3::get('REQUEST.action');
		if($action == "afficher")
		{
			$message = "";
			$organisme_id = F3::get('REQUEST.id');
			if(!is_numeric($organisme_id)) exit('ID incorrect');

			// Assignations :
			DB::sql("SELECT organismes_vacations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `organismes_vacations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND 	organismes_vacations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND organismes_vacations.organisme_id = $organisme_id;");
			$message .= '<br>Disponible pour cet organisme : <ul>';
			foreach (F3::get('DB')->result as $row) {
				$message .= '<li> Ã  ' . $row['libelle'] . ' le ' . $row['jour'] . ' de ' . $row['heure_debut'] . ' Ã  ' . $row['heure_fin'] . '<a id="' . $row['id'] . '" class="supprimerAffectation" href="#"> X</a></li>';
			}
			$message .= '</ul>';
			echo $message;

		}
		elseif($action == "supprimer")
		{     $id = F3::get('REQUEST.id');
		if(is_numeric($id)){
			DB::sql("DELETE FROM organismes_vacations WHERE id=$id");
			echo 'OK';
		}
		else
		echo 'Erreur lors de la suppression';

		}
		else
		{
			 
			$page = F3::get('REQUEST.page');
			if(!is_numeric($page)) $page = 0;
			$limit = F3::get('REQUEST.rows');
			$totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
			if(is_numeric($totalrows)) { $limit = $totalrows; }
			if(!is_numeric($limit)) $limit = 0;
			$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
			$sord = mysql_escape_string(F3::get('REQUEST.sord'));
			if(!$sidx) $sidx =1;

			$wh = "";
			$searchOn = F3::get('REQUEST._search');
			if($searchOn=='true') {
				$sarr = F3::get('REQUEST');
				foreach( $sarr as $k=>$v) {
					mysql_escape_string($k);
					mysql_escape_string($v);
					switch ($k) {
						case 'id':
							$wh .= " WHERE ".$k." = ".$v;
							break;
						case 'libelle':
						case 'nom':
						case 'type_struct':
							$wh .= " WHERE ".$k." LIKE '%".$v."%'";
							//				$wh .= " WHERE ".$k." = ".$v;
							break;
					}
				}
			}

			DB::sql("SELECT COUNT(*) AS count FROM organismes;");
			$result = F3::get('DB')->result;
			$count = $result[0]['count'];

			if( $count >0 ) {
				$total_pages = ceil($count/$limit);
			} else {
				$total_pages = 0;
			}
			if ($page > $total_pages) $page=$total_pages;
			$start = $limit*$page - $limit; // do not put $limit*($page - 1)
			if ($start<0) $start = 0;
			DB::sql("SELECT id, libelle, nom, type_struct FROM view_organismes_villes_type".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;"); //XXX : Se dÃ©barasser de la vue
			$reponse = new stdClass();
			$reponse->page = $page;
			$reponse->total = $total_pages;
			$reponse->records = $count;
			$i=0;
			foreach (F3::get('DB')->result as $row) {
				DB::sql('SELECT COUNT(historique_organismes.id) as nb_membres FROM historique_organismes WHERE historique_organismes.organisme_id = :organisme_id AND historique_organismes.festival_id = :festival_id;',array(':festival_id'=>array($festival_id,PDO::PARAM_INT),':organisme_id'=>array($row['id'],PDO::PARAM_INT)));
				$reponse->rows[$i]['id']=$row['id'];
				$reponse->rows[$i]['cell']=array($row['id'],$row['libelle'],$row['nom'],F3::get('DB')->result[0]['nb_membres'],$row['type_struct']);
				$i++;
			}
			echo json_encode($reponse);
		}
	}

	static function destinataires_messages() { //A modifier pour le systeme d'amis
		$recherche = mysql_escape_string(F3::get('REQUEST.search'));
		$data[] = array();
		switch ( F3::get('REQUEST.type_destinataire')) {
			case 'domaine':
				F3::call('outils::verif_admin');
				DB::sql("select id, libelle from domaines");
				foreach (F3::get('DB')->result as $row) {
					$data[] = array(str_replace('"', '', $row['id']), $row['libelle'], null, $row['libelle']);
				}
				break;
			case 'lieu':
				F3::call('outils::verif_admin');
				DB::sql("select id, libelle from lieux");
				foreach (F3::get('DB')->result as $row) {
					$data[] = array(str_replace('"', '', $row['id']), $row['libelle'], null, $row['libelle']);
				}
				break;
			case 'organisme':
				F3::call('outils::verif_admin');
				DB::sql("select id, libelle from organismes");
				foreach (F3::get('DB')->result as $row) {
					$data[] = array(str_replace('"', '', $row['id']), $row['libelle'], null, $row['libelle']);
				}
				break;
			case '':
			default:
				F3::call('outils::verif_admin');
				DB::sql("SELECT individus.id,individus.nom,individus.prenom,individus.date_naissance, villes.nom as ville, villes.cp  FROM `villes`, `individus` WHERE villes.id = individus.ville_id AND CONCAT(individus.prenom, ' ', individus.nom) LIKE '%$recherche%' ORDER BY individus.nom;");
				foreach (F3::get('DB')->result as $row) {
					$data[] = array(str_replace('"', '', $row['id']), $row['prenom'] . " " . $row['nom'], null, $row['prenom'] . " " . $row['nom'] . " - " . $row['date_naissance']  . " - " . $row['cp']  . " " . $row['ville'] );

				}
				break;
		}

			
		header("Content-type: application/json");
		echo json_encode($data);
	}


	static function groupe() {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$recherche = mysql_escape_string(F3::get('REQUEST.term'));
		switch ( F3::get('REQUEST.type')) {
			case 'domaine':
				DB::sql("SELECT domaines.id, domaines.libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id AND  domaines.libelle LIKE '%$recherche%' ORDER BY libelle;");
				break;

			case 'lieu':
				DB::sql("SELECT lieux.id, lieux.libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id AND lieux.libelle LIKE '%$recherche%' ORDER BY libelle;");

				break;

			case 'organisme':
				DB::sql("SELECT DISTINCT organismes.id, organismes.libelle FROM `organismes`, `historique_organismes` WHERE organismes.id = historique_organismes.organisme_id AND historique_organismes.festival_id = $festival_id  AND organismes.libelle LIKE '%$recherche%' ORDER BY libelle;");

				break;
			default:
				exit("Pas de type");
				break;
		}
		$data = array();
		foreach (F3::get('DB')->result as $row) {
			$json = array();
			$json['id'] = $row['id'];
			$json['label'] = $row['id'] . ' ' . $row['libelle'];
			$json['value'] = $row['libelle'];
			$data[] = $json;
		}
		header("Content-type: application/json");
		echo json_encode($data);
	}

	static function profils() {
		F3::call('outils::menu');
		F3::set('type','global');
		F3::call('outils::verif_admin');
		$recherche = mysql_escape_string(F3::get('REQUEST.term'));

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));

		if(strlen($recherche) > 1) {
			DB::sql("SELECT `individus`.id,`individus`.nom,`individus`.prenom,`individus`.date_naissance, `individus`.adresse1, `individus`.adresse2, villes.cp , villes.nom as ville FROM `individus`, villes WHERE villes.id = `individus`.ville_id AND (CONCAT(`individus`.prenom, ' ', `individus`.nom) LIKE '%$recherche%' OR  CONCAT(`individus`.nom, ' ', `individus`.prenom) LIKE '%$recherche%') ORDER BY `individus`.nom;");
			$data = array();
			foreach (F3::get('DB')->result as $row) {
				DB::sql('SELECT organismes.libelle FROM organismes, historique_organismes WHERE historique_organismes.organisme_id = organismes.id AND historique_organismes.individu_id = :individu_id ORDER BY historique_organismes.festival_id DESC LIMIT 1;',array(':individu_id'=>array($row['id'],PDO::PARAM_STR))); //XXX surveiller la charge

				$json = array();
				$json['id'] = $row['id'];
				if(isset(F3::get('DB')->result[0]))
					$json['label'] = $row['prenom'] . " " . $row['nom'] . " - " . outils::date_sql_fr($row['date_naissance']) . " - " .  $row['adresse1'] . " " .  $row['adresse2'] . " " .  $row['cp'] . "-" .  $row['ville'] . " - " . F3::get('DB')->result[0]['libelle'];
				else
					$json['label'] = $row['prenom'] . " " . $row['nom'] . " - " . outils::date_sql_fr($row['date_naissance']) . " - " .  $row['adresse1'] . " " .  $row['adresse2'] . " " .  $row['cp'] . "-" .  $row['ville'];

				$json['value'] = $row['prenom'] . " " . $row['nom'] . " - " . outils::date_sql_fr($row['date_naissance']);
				$data[] = $json;
			}
			header("Content-type: application/json");
			echo json_encode($data);
		}
		elseif(isset($page))
		{

			if(!$sidx) $sidx =1;

			$wh = "";
			$searchOn = F3::get('REQUEST._search');
			if($searchOn=='true') {
				$sarr = F3::get('REQUEST');
				foreach( $sarr as $k=>$v) {
					mysql_escape_string($k);
					mysql_escape_string($v);
					switch ($k) {
						case 'id':
							$wh .= " AND individus.id = ".$v;
							break;
						case 'email':
						case 'prenom':
						case 'nom':
						case 'date_naissance':
						case 'telephone_fixe':
						case 'telephone_portable':
						case 'commentaire':
							$wh .= " AND ".$k." LIKE '%".$v."%'";
							//				$wh .= " WHERE ".$k." = ".$v;
							break;
					}
				}
			}

			DB::sql("SELECT COUNT(*) AS count FROM individus;");
			$result = F3::get('DB')->result;
			$count = $result[0]['count'];
			if( $count >0 ) {
				$total_pages = ceil($count/$limit);
			} else {
				$total_pages = 0;
			}
			if ($page > $total_pages) $page=$total_pages;
			$start = $limit*$page - $limit; // do not put $limit*($page - 1)
			if ($start<0) $start = 0;
			DB::sql("SELECT DISTINCT individus.id, individus.email, individus.prenom, individus.nom, individus.date_naissance, individus.telephone_fixe, individus.telephone_portable, individus.commentaire, statuts.libelle as statut FROM  `individus`, `statuts` WHERE individus.statut_id = statuts.id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
			$reponse = new stdClass();
			$reponse->page = $page;
			$reponse->total = $total_pages;
			$reponse->records = $count;
			$i=0;
			foreach (F3::get('DB')->result as $row) {
				$organisme = DB::sql('SELECT organismes.libelle FROM organismes, historique_organismes WHERE historique_organismes.organisme_id = organismes.id AND historique_organismes.individu_id = :individu_id ORDER BY historique_organismes.festival_id DESC LIMIT 1;',array(':individu_id'=>array($row['id'],PDO::PARAM_STR)));
				$reponse->rows[$i]['id']=$row['id'];
				if(isset(F3::get('DB')->result[0]))
					$reponse->rows[$i]['cell']=array($row['id'],$row['email'],$row['prenom'],$row['nom'], outils::date_sql_fr($row['date_naissance']),$row['telephone_fixe'],$row['telephone_portable'],F3::get('DB')->result[0]['libelle'],$row['statut'],$row['commentaire']);
				else
					$reponse->rows[$i]['cell']=array($row['id'],$row['email'],$row['prenom'],$row['nom'], outils::date_sql_fr($row['date_naissance']),$row['telephone_fixe'],$row['telephone_portable'],"",$row['statut'],$row['commentaire']);
				$i++;
			}
			echo json_encode($reponse);


		}
	}


	static function profils_vacations() {
		F3::call('outils::menu');
		F3::set('type',F3::get('PARAMS.type'));
		F3::call('outils::verif_responsable');
		$organisme_id = F3::get('PARAMS.id');
		$festival_id = F3::get('SESSION.festival_id');
		$action = F3::get('REQUEST.action');
		if($action == "afficher")
		{
			$message = "";
			$individus_id = F3::get('REQUEST.id');
			if(!is_numeric($individus_id)) exit('ID incorrect');

			// DisponibilitÃ©es :
			DB::sql("SELECT disponibilites.heure_debut, disponibilites.heure_fin, festivals_jours.jour FROM `disponibilites`, `festivals_jours`  WHERE festivals_jours.id = disponibilites.festival_jour_id AND festivals_jours.festival_id = $festival_id AND disponibilites.individu_id = $individus_id;");
			$message .= 'DisponibilitÃ©es : <ul>';
			if(count(F3::get('DB')->result)>0)
			foreach (F3::get('DB')->result as $row) {
				$message .= '<li> Le ' . outils::date_sql_fr($row['jour']) . ' de ' . outils::date_sql_timepicker($row['heure_debut']) . ' Ã  ' . outils::date_sql_timepicker($row['heure_fin']) . '</li>';
			}
			$message .= '</ul>';


			// Recommandations :
			DB::sql("SELECT recommandations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `recommandations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND recommandations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND recommandations.individu_id = $individus_id;");
			$message .= '<br>Recommandations : <ul>';
			//if(count(F3::get('DB')->result)>0)
			foreach (F3::get('DB')->result as $row) {
				$message .= '<li> Ã  ' . $row['libelle'] . ' le ' . outils::date_sql_fr($row['jour']) . ' de ' . outils::date_sql_timepicker($row['heure_debut']) . ' Ã  ' . outils::date_sql_timepicker($row['heure_fin']) . '</li>';
			}
			$message .= '</ul>';





			// Assignations :
			DB::sql("SELECT affectations.id, festivals_jours.jour, vacations.heure_debut, vacations.heure_fin, lieux.libelle FROM `lieux`, `affectations`, `vacations`, `festivals_jours` WHERE vacations.lieu_id = lieux.id AND affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND affectations.individu_id = $individus_id;");
			$message .= '<br>Affectations : <ul>';
			if(count(F3::get('DB')->result)>0)
			foreach (F3::get('DB')->result as $row) {
				$message .= '<li> Ã  ' . $row['libelle'] . ' le ' . outils::date_sql_fr($row['jour']) . ' de ' . outils::date_sql_timepicker($row['heure_debut']) . ' Ã  ' . outils::date_sql_timepicker($row['heure_fin']) . '<a id="' . $row['id'] . '" class="supprimerAffectation" href="#"> X</a></li>';
			}
			$message .= '</ul>';
			echo $message;

		}
		elseif($action == "supprimer")
		{     $id = F3::get('REQUEST.individu_id');
		if(is_numeric($id)){
			DB::sql("DELETE FROM affectations WHERE id=$id");
			echo 'OK';
		}
		else
		echo 'Erreur lors de la suppression';

		}
		else
		{
			$page = F3::get('REQUEST.page');
			if(!is_numeric($page)) $page = 0; //Tenter avec if isset F3::get('REQUEST.page')
			$limit = F3::get('REQUEST.rows');
			if(!is_numeric($limit)) $limit = 0;
			$totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
			if(is_numeric($totalrows)) { $limit = $totalrows; }
			$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
			$sord = mysql_escape_string(F3::get('REQUEST.sord'));

			if(!$sidx) $sidx =1;

			$wh = "";
			$searchOn = F3::get('REQUEST._search');
			if($searchOn=='true') {
				$sarr = F3::get('REQUEST');
				foreach( $sarr as $k=>$v) {
					mysql_escape_string($k);
					mysql_escape_string($v);
					switch ($k) {
						case 'id':
							$wh .= " AND individus.id = ".$v;
							break;
						case 'prenom':
						case 'nom':
						case 'date_naissance':
						case 'organisme':
							$wh .= " AND ".$k." LIKE '%".$v."%'";
							//				$wh .= " WHERE ".$k." = ".$v;
							break;
					}
				}
			}
			if(isset($organisme_id) && is_numeric($organisme_id)) //Si c'est un responsable de organisme qui y accâ€�dÃ¨de
			DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE historique_organismes.organisme_id = $organisme_id AND historique_organismes.festival_id = $festival_id ;");
			else //Si admin
			DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE historique_organismes.festival_id = $festival_id ;");
			$result = F3::get('DB')->result;
			$count = $result[0]['count'];
			if( $count >0 ) {
				$total_pages = ceil($count/$limit);
			} else {
				$total_pages = 0;
			}
			if ($page > $total_pages) $page=$total_pages;
			$start = $limit*$page - $limit; // do not put $limit*($page - 1)
			if ($start<0) $start = 0;
			if(isset($organisme_id)) //Si c'est un responsable de organisme qui y accâ€�dÃ¨de
			DB::sql("SELECT individus.id, individus.email, individus.prenom, individus.nom, organismes.libelle FROM `organismes`, `historique_organismes`, `individus` WHERE historique_organismes.organisme_id = $organisme_id AND  historique_organismes.individu_id = individus.id AND historique_organismes.festival_id = $festival_id AND historique_organismes.organisme_id = organismes.id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
			else //Si admin
			DB::sql("SELECT individus.id, individus.email, individus.prenom, individus.nom, organismes.libelle FROM `organismes`, `historique_organismes`, `individus` WHERE historique_organismes.individu_id = individus.id AND historique_organismes.festival_id = $festival_id AND historique_organismes.organisme_id = organismes.id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
			$reponse = new stdClass();
			$reponse->page = $page;
			$reponse->total = $total_pages;
			$reponse->records = $count;
			$i=0;
			foreach (F3::get('DB')->result as $row) {
				$individu_id = $row['id'];
				DB::sql("SELECT count(affectations.id) as vacations FROM `vacations`,`affectations`, `festivals_jours` WHERE affectations.vacation_id = vacations.id AND festivals_jours.id = vacations.festival_jour_id AND festivals_jours.festival_id = $festival_id AND affectations.individu_id = $individu_id;");
				F3::get('DB')->result[0]['vacations'];
				$reponse->rows[$i]['id']=$row['id'];
				$reponse->rows[$i]['cell']=array($row['id'],$row['prenom'],$row['nom'],$row['libelle'],F3::get('DB')->result[0]['vacations']);
				$i++;
			}
			echo json_encode($reponse);
		}
	}



	static function profils_festival() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));

		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND individus.id = ".$v;
						break;
					case 'email':
					case 'prenom':
					case 'nom':
					case 'telephone_fixe':
					case 'telephone_portable':
					case 'libelle':
					case 'commentaire':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						//				$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'date_naissance':
						$wh .= " AND ".$k." = '". outils::date_fr_sql($v) . "'";
						break;
					case 'statut':
						$wh .= " AND statuts.libelle = '". $v . "'";
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(DISTINCT individus.id) AS count FROM `individus`, `historique_organismes` WHERE historique_organismes.festival_id = $festival_id AND historique_organismes.individu_id = individus.id ;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];
		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT DISTINCT individus.id, individus.email, individus.prenom, individus.nom, individus.date_naissance, individus.telephone_fixe, individus.telephone_portable, individus.commentaire, organismes.libelle, statuts.libelle as statut FROM  `individus`, `historique_organismes`, `organismes`, `statuts` WHERE individus.statut_id = statuts.id AND historique_organismes.festival_id = $festival_id AND historique_organismes.individu_id = individus.id AND historique_organismes.organisme_id = organismes.id AND historique_organismes.individu_id = individus.id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['email'],$row['prenom'],$row['nom'], outils::date_sql_fr($row['date_naissance']),$row['telephone_fixe'],$row['telephone_portable'],$row['libelle'],$row['statut'],$row['commentaire']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function domaines() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'libelle':
					case 'adresse1':
					case 'adresse2':
					case 'cp':
					case 'nom':
					case 'type_struct':
						$wh .= " WHERE ".$k." LIKE '%".$v."%'";
						//				$wh .= " WHERE ".$k." = ".$v;
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM domaines;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT id, libelle FROM domaines".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function domaines_festival() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':
					case 'adresse1':
					case 'adresse2':
					case 'cp':
					case 'nom':
					case 'type_struct':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						//				$wh .= " WHERE ".$k." = ".$v;
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT domaines.id, domaines.libelle FROM domaines, responsables_domaines WHERE domaines.id = responsables_domaines.domaine_id AND responsables_domaines.festival_id = $festival_id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function lieux() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'libelle':
						$wh .= " WHERE ".$k." LIKE '%".$v."%'";
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM lieux;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT id, libelle FROM lieux".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function lieux_festival() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id;");

		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT lieux.id, lieux.libelle FROM lieux, responsables_lieux WHERE lieux.id = responsables_lieux.lieu_id AND responsables_lieux.festival_id = $festival_id".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function domaines_id_lieux() {
		F3::call('outils::menu');
		F3::set('type','domaine');
		F3::call('outils::verif_responsable');
		$domaine_id = mysql_escape_string(F3::get('PARAMS.id'));

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(*) AS count FROM lieux WHERE domaine_id=$domaine_id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT id, libelle FROM lieux WHERE domaine_id=$domaine_id".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function gerer_organismes() {


	}

	static function historique() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'individu':
					case 'action':
						//case 'action': //Filtrage par action desactivÃ©
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						//				$wh .= " WHERE ".$k." = ".$v;
						break;
					case 'date':
						break;
				}
			}
		}

		DB::sql("SELECT COUNT(id) AS count FROM historique;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT historique.id as id, UNIX_TIMESTAMP(historique.date) as date, historique.action as action, CONCAT(individus.prenom, ' ', individus.nom) as individu FROM individus, historique WHERE individus.id=historique.individu_id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'], date("d/m/Y H:i:s",$row['date']),$row['individu'],$row['action']);
			$i++;
		}
		echo json_encode($reponse);

	}

	static function ajout_profil_organisme() {

		// Est-ce que le formulaire a Ã©tÃ© envoyÃ© ?
		if($_POST['submit']){
			$content = $_POST['content'];

			//Insertion
			$hist_organismes=new Axon('historique_organismes'); //Chargement de la table historique organismes

			echo $organisme_id;
			$hist_organismes->id='';
			$hist_organismes->responsable = 0;
			$hist_organismes->festival_id = $festival_id;
			$hist_organismes->organisme_id = $organisme_id;
			$hist_organismes->individu_id = $responsable_id;
			$hist_organismes->save();
				
			//Redirection vers index.php
			//header("Location:index.php");
			echo "erreur";
		}

		/* Affichage de la liste */

		// SÃ©lection des notes
		$find = mysql_query("SELECT * FROM 'notes' ORDER BY id DESC");

		// CrÃ©ation de la liste
		echo '<ul>';

		while($row = mysql_fetch_array($find)){
			echo '<li>' . $row['content'] . ' <a id="' . $row['id'] . '" href="delete.php?id=' . $row['id'] . '"><img src="cancel.png" alt="Delete?" /></a></li>';
		}

		echo '</ul>';
	}

	static function vacations_lieux() {
		F3::call('outils::menu');
		F3::set('type','lieu');
		F3::call('outils::verif_responsable');
		$id = mysql_escape_string(F3::get('PARAMS.id'));
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':

					case 'nombre_minimum':
					case 'nombre_maximum':
					case 'responsable':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
					case 'heure_debut':
						$wh .= " AND heure_debut LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'heure_fin':
						$wh .= " AND heure_fin LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'responsable':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
					case 'jour':
						$wh .= " AND ".$k." LIKE '". outils::date_fr_sql($v) . "'";
						break;
				}
			}
		}


		DB::sql("SELECT COUNT(*) AS count FROM vacations WHERE lieu_id=$id;");
		$result = F3::get('DB')->result;
		$count = $result[0]['count'];


		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT vacations.id, vacations.libelle, vacations.heure_debut, vacations.heure_fin, vacations.nombre_minimum, vacations.nombre_maximum, jour, CONCAT(prenom, ' ', nom) AS prenomnom FROM vacations, individus, festivals_jours WHERE vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id AND vacations.responsable_id=individus.id AND lieu_id=$id".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");

		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle'], outils::date_sql_timepicker($row['heure_debut']), outils::date_sql_timepicker($row['heure_fin']),$row['nombre_minimum'],$row['nombre_maximum'],outils::date_sql_fr($row['jour']),$row['prenomnom']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function vacations_assigner() {
		F3::call('outils::menu');
		$id = F3::get('PARAMS.id');
		$type = F3::get('PARAMS.type');
		if(isset($type)) F3::set('type',$type);
		F3::call('outils::verif_responsable');
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;

		$totalrows = isset($_REQUEST['totalrows']) ? $_REQUEST['totalrows']: false;
		if(is_numeric($totalrows)) { $limit = $totalrows; }

		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND vacations.id = ".$v;
						break;
					case 'places_libres':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':
						$wh .= " AND vacations.libelle LIKE '%".$v."%'";
						break;
					case 'heure_debut':
						$wh .= " AND heure_debut LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'heure_fin':
						$wh .= " AND heure_fin LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'responsable':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
					case 'jour':
						$wh .= " AND ".$k." LIKE '". outils::date_fr_sql($v) . "'";
						break;
					case 'lieu':
						$wh .= " AND lieux.libelle LIKE '%".$v."%'";
					case 'domaine':
						$wh .= " AND domaines.libelle LIKE '%".$v."%'";
						break;
				}
			}
		}

		if(isset($type) && ($type == 'domaine') && is_numeric($id))
		DB::sql("SELECT COUNT(vacations.id) AS count FROM vacations, festivals_jours WHERE vacations.lieu_id IN (SELECT lieux.id FROM lieux WHERE domaine_id = $id) AND vacations.festival_jour_id = festivals_jours.id AND festivals_jours.festival_id = $festival_id;");
		if(isset($type) && ($type == 'organisme') && is_numeric($id))
		DB::sql("SELECT COUNT(organismes_vacations.id) AS count FROM organismes_vacations, vacations, festivals_jours WHERE festivals_jours.festival_id = $festival_id AND vacations.festival_jour_id = festivals_jours.id AND vacations.id = organismes_vacations.vacation_id AND organisme_id = $id;");
		else
		DB::sql("SELECT COUNT(vacations.id) AS count FROM vacations, festivals_jours WHERE vacations.festival_jour_id = festivals_jours.id AND festivals_jours.festival_id = $festival_id;");

		$result = F3::get('DB')->result;
		$count = $result[0]['count'];


		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		if(isset($type) && ($type == 'domaine') && is_numeric($id))
		DB::sql("SELECT vacations.id, vacations.libelle, vacations.heure_debut, vacations.heure_fin, vacations.nombre_minimum, vacations.nombre_maximum, lieux.libelle AS lieu, domaines.libelle AS domaines, festivals_jours.jour, CONCAT(prenom, ' ', nom) AS responsable FROM vacations, individus, festivals_jours, lieux, domaines WHERE domaines.id = $id AND domaines.id = lieux.domaine_id AND vacations.lieu_id=lieux.id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id AND vacations.responsable_id=individus.id".$wh." ORDER BY domaines.libelle, lieux.libelle, festivals_jours.jour LIMIT $start, $limit;");
		if(isset($type) && ($type == 'organisme') && is_numeric($id))
		DB::sql("SELECT vacations.id, vacations.libelle, vacations.heure_debut, vacations.heure_fin, vacations.nombre_minimum, vacations.nombre_maximum, lieux.libelle AS lieu, domaines.libelle AS domaines, festivals_jours.jour, CONCAT(prenom, ' ', nom) AS responsable FROM vacations, individus, festivals_jours, lieux, domaines, organismes_vacations WHERE organismes_vacations.vacation_id = vacations.id AND organismes_vacations.organisme_id = $id AND domaines.id = lieux.domaine_id AND vacations.lieu_id=lieux.id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id AND vacations.responsable_id=individus.id".$wh." ORDER BY domaines.libelle, lieux.libelle, festivals_jours.jour LIMIT $start, $limit;");
		else
		DB::sql("SELECT vacations.id, vacations.libelle, vacations.heure_debut, vacations.heure_fin, vacations.nombre_minimum, vacations.nombre_maximum, lieux.libelle AS lieu, domaines.libelle AS domaines, festivals_jours.jour, CONCAT(prenom, ' ', nom) AS responsable FROM vacations, individus, festivals_jours, lieux, domaines WHERE domaines.id = lieux.domaine_id AND vacations.lieu_id=lieux.id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id AND vacations.responsable_id=individus.id".$wh." ORDER BY domaines.libelle, lieux.libelle, festivals_jours.jour LIMIT $start, $limit;");
		$result = F3::get('DB')->result;
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach ($result as $row) {
			$vacation_id = $row['id'];
			DB::sql("SELECT count(id) as occupe FROM affectations  WHERE affectations.vacation_id = $vacation_id");
			$occupe = F3::get('DB')->result[0]['occupe'];
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle'],$row['domaines'],$row['lieu'], outils::date_sql_fr($row['jour']), outils::date_sql_timepicker($row['heure_debut']), outils::date_sql_timepicker($row['heure_fin']),$occupe,$row['nombre_minimum'],$row['nombre_maximum'],$row['responsable']);
			$i++;
		}
		echo json_encode($reponse);
	}

	static function vacations() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		$page = F3::get('REQUEST.page');
		if(!is_numeric($page)) $page = 0;
		$limit = F3::get('REQUEST.rows');
		if(!is_numeric($limit)) $limit = 0;
		$sidx = mysql_escape_string(F3::get('REQUEST.sidx'));
		$sord = mysql_escape_string(F3::get('REQUEST.sord'));
		if(!$sidx) $sidx =1;

		$wh = "";
		$searchOn = F3::get('REQUEST._search');
		if($searchOn=='true') {
			$sarr = F3::get('REQUEST');
			foreach( $sarr as $k=>$v) {
				mysql_escape_string($k);
				mysql_escape_string($v);
				switch ($k) {
					case 'id':
						$wh .= " AND vacations.id = ".$v;
						break;
					case 'nombre_minimum':
					case 'nombre_maximum':
						$wh .= " AND ".$k." = ".$v;
						break;
					case 'libelle':
						$wh .= " AND vacations.libelle LIKE '%".$v."%'";
						break;
					case 'heure_debut':
						$wh .= " AND heure_debut LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'heure_fin':
						$wh .= " AND heure_fin LIKE '". outils::date_timepicker_sql($v) . "'";
						break;
					case 'responsable':
						$wh .= " AND ".$k." LIKE '%".$v."%'";
						break;
					case 'jour':
						$wh .= " AND ".$k." LIKE '". outils::date_fr_sql($v) . "'";
						break;
					case 'lieu':
						$wh .= " AND lieux.libelle LIKE '%".$v."%'";
						break;
				}
			}
		}


		DB::sql("SELECT COUNT(*) AS count FROM vacations;");
		$count = F3::get('DB')->result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT vacations.id, jour, vacations.libelle, lieux.libelle  AS lieu, vacations.heure_debut, vacations.heure_fin, vacations.nombre_maximum, CONCAT(prenom, ' ', nom) AS responsable FROM vacations, individus, festivals_jours, lieux WHERE lieu_id=lieux.id AND vacations.festival_jour_id=festivals_jours.id AND festivals_jours.festival_id=$festival_id AND vacations.responsable_id=individus.id".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			DB::sql("SELECT count(id) as occupe FROM affectations  WHERE affectations.vacation_id = ". $row['id'] .";");
			$occupe = F3::get('DB')->result[0]['occupe'];
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'], outils::date_sql_fr($row['jour']), $row['libelle'], $row['lieu'], outils::date_sql_timepicker($row['heure_debut']), outils::date_sql_timepicker($row['heure_fin']), $row['nombre_maximum'],F3::get('DB')->result[0]['occupe'], $row['responsable']);
			$i++;
		}
		echo json_encode($reponse);
	}

}

?>
