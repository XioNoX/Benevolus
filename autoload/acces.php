<?php
class Acces {

	static function lister() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		outils::activerJqgrid();
		F3::set('pagetitle','Liste des accès particuliers');
		F3::set('lien_ajouter','<a href=/acces/ajouter>Ajouter un accès particulier</a> - <a href=/tickets>Tickets particuliers</a>');
		F3::set('jquery_url_list','/acces/lister_ajax');
		F3::set('jquery_url_edit','/acces/editer/');
		F3::set('jquery_url_edit2','/acces/editer');
		F3::set('jquery_largeur','100%');
		F3::set('jquery_col_names',"['id','Libelle','Nom','Prenom']");

		F3::set('jquery_col_model',"[
	      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
	      {name:'libelle', index:'libelle', width:100},
	      {name:'nom', index:'nom', width:100},
	      {name:'prenom', index:'prenom', width:100}
	    ]");
		F3::set('template','liste_generique2');
		F3::call('outils::generer');
	}

	static function lister_ajax() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
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
						$wh .= " WHERE ".$k." = ".$v;
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
		DB::sql("SELECT COUNT(*) AS count FROM `acces`, `acces_types` WHERE acces.acces_type_id=acces_types.id AND acces_types.festival_id = $festival_id ;");
		$count = F3::get('DB')->result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT acces.id, acces_types.libelle, individus.nom, individus.prenom FROM acces,acces_types, individus WHERE individus.id = acces.individu_id AND  acces.acces_type_id=acces_types.id AND acces_types.festival_id = $festival_id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");

		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle'],$row['nom'],$row['prenom']);
			$i++;
		}
		echo json_encode($reponse);
	}
	static function editer_tableau_post() {
		if(F3::get('REQUEST.oper') == "del")
		$liste_ids = F3::get('REQUEST.id');
		$tableau_ids = explode(',', $liste_ids);
		foreach( $tableau_ids as $id) {
			if(is_numeric($id)){
				F3::set('PARAMS.id',$id);
				$acces=new Axon('acces');
				$acces->load("id=$id");
				if(outils::est_admin())
				{
					DB::sql('DELETE FROM acces WHERE id = :id ;',array(':id'=>array($id,PDO::PARAM_INT)));
					historique::logger("Suppréssion de l'accès spécial de " . $acces->individu_id);
				}
			}
		}
	}


	static function ajouter() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		outils::activerJquery();
		$festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT acces_types.id, acces_types.libelle FROM acces_types WHERE festival_id = $festival_id ORDER BY libelle;");
		F3::set('acces_types',F3::get('DB')->result);
		F3::set('pagetitle','Ajouter un accès');
		F3::set('ajouter','1');
		F3::set('template','form_acces');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::call('outils::verif_admin');
		$festival_id = F3::get('SESSION.festival_id');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs

		if (is_numeric(F3::get('REQUEST.groupe_id'))){
			$groupe_id = F3::get('REQUEST.groupe_id');
			$type = F3::get('REQUEST.type_groupe');

			switch (F3::get('REQUEST.type_groupe')) {
				case 'domaine':
					$membres = domaines::membres_domaine($groupe_id,$festival_id);
					break;

				case 'lieu':
					$membres = lieux::membres_lieu($groupe_id,$festival_id);

					break;

				case 'organisme':
					$membres = organismes::membres_organisme($groupe_id,$festival_id);

					break;
				default:
					F3::set('messages','Type de groupe inconnu');
					break;
			}
			if(isset($membres)){
				foreach ($membres as $row) {
					$acces=new Axon('acces');
					$acces->acces_type_id = F3::get('REQUEST.acces_type_id');
					$acces->individu_id = $row['id'];
					$acces->save();
				}

				historique::logger("Création de l'accès particulier pour $groupe_id ($type)");
				F3::reroute('/acces');
			}
			F3::call('acces::ajouter');

		}
		else
		{
			F3::call('outils::verif_individu_id');
			if (!F3::exists('message')) {
				$acces=new Axon('acces');
				$acces->copyFrom('REQUEST');
				$acces->save();
				$acces_id = $acces->_id;
				historique::logger("Création de l'accès particulier $acces_id");
				F3::reroute('/acces');
			}
			// Ré-Affichage du formulaire
			F3::call('acces::ajouter');
		}
	}

	static function editer() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		outils::activerJqgrid();
		$id = F3::get('PARAMS.id');

		if(!is_numeric($id)) $id = 0;
		$festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT acces_types.id, acces_types.libelle FROM acces_types WHERE festival_id = $festival_id ORDER BY libelle;");
		F3::set('acces_types',F3::get('DB')->result);
		$acces=new Axon('acces');
		$acces->load("id=$id");
		if (!$acces->dry()) {
			$acces->copyTo('REQUEST');
			$individu_id = $acces->individu_id;
			F3::set('REQUEST.responsable_id',$individu_id);
			if ($individu_id != '') {
				$individus=new Axon('individus');
				$individus->load("id=$individu_id");
				F3::set('REQUEST.individu', $individus->prenom . ' ' . $individus->nom . ' - ' . outils::date_sql_fr($individus->date_naissance));
			}
			F3::set('pagetitle','Editer un accès');
			F3::set('template','form_acces');
			F3::call('outils::generer');
		}
		else F3::http404();
	}

	static function editer_post() {
		F3::call('outils::verif_admin');
		$acces_id = F3::get('PARAMS.id');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('outils::verif_individu_id');
		if (!F3::exists('message')) {
			if(!is_numeric($acces_id)) $acces_id = 0;
			// Pas d'erreur, enregistrement de la MAJ
			$acces=new Axon('acces');
			$acces->load("id=$acces_id");
			//$acces->individu_id=F3::get("REQUEST.individu_id");
			//$acces->acces_type_id=F3::get("REQUEST.acces_type_id");
			$acces->copyFrom('REQUEST'); //bug
			//print_r(F3::get("REQUEST"));
			$acces->save();

			historique::logger("Édition de l'accès numéro $acces_id");
			F3::reroute('/acces');
		}
		// Ré-Affichage du formulaire
		F3::call('acces::editer');
	}

	static function entrees() {

		F3::call('outils::verif_operateur');
		$festival_id = F3::get('SESSION.festival_id');

		$sql_acces_remis = DB::sql("SELECT COUNT(*) AS nb_acces_remis FROM historique_organismes WHERE `present` = 1 AND `festival_id` = $festival_id;");
		F3::set('nb_acces_remis', $sql_acces_remis[0]["nb_acces_remis"]);

		$codebarres = F3::get('REQUEST.codebarres');//TODO vérif du checksum
		$present = F3::get('REQUEST.present');
		$individu_id = F3::get('REQUEST.individu_id');
		
		if($present != "")
		{
			DB::sql("UPDATE `historique_organismes` SET `present` = 1 WHERE `individu_id` = $individu_id AND `festival_id` = $festival_id;");
			$operateur_id = F3::get('SESSION.id');
			historique::logger("Accès remis a $individu_id par $operateur_id");
			F3::set('succes','Enregistré');
		}
		else
		{
			if($codebarres == "")
			{
				F3::set('message','Veuillez scanner un code barre');
			}
			else if(!is_numeric($codebarres))
			{
				F3::set('message','Code barres incorrect');
			}
			else if(strlen($codebarres) != 13){
				F3::set('PARAMS.id',$codebarres);
			}
			else
			{
				$individu_id = substr($codebarres, 0, -1);
				F3::set('PARAMS.id',$individu_id);
			}
		}
				
		F3::set('acces',1);
		F3::call('profils::afficher');
	}


	static function present($individu_id, $festival_id) {
		if ($festival_id == "") $festival_id = F3::get('SESSION.festival_id');
		DB::sql("SELECT `present` FROM `historique_organismes` WHERE `individu_id` = $individu_id AND `festival_id` = $festival_id;");
		if(F3::get('DB')->result[0]['present'] == 1)
		return "oui";
		else
		return "non";
	}


	static function acces_profil() {

	}

	static function autorisation_individu($individu_id) {
		$festival_id = F3::get('SESSION.festival_id');

		// Si pas de bypass dans la bdd
		// Si responsable alors badge
		// Sinon bracelet bénévole


		// Si pas de bypass dans la bdd
		DB::sql("SELECT libelle FROM `acces_types`, `acces` WHERE acces.acces_type_id=acces_types.id AND `acces`.individu_id = $individu_id AND `acces_types`.festival_id = $festival_id;");
		if(count(F3::get('DB')->result)>0){
			$retour = "";
			foreach (F3::get('DB')->result as $row){
				$retour .= $row['libelle'].'<br>';
			}
			return $retour;
		}
		DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE `historique_organismes`.responsable = 1 AND `historique_organismes`.individu_id = $individu_id AND `historique_organismes`.festival_id = $festival_id;");
		if(F3::get('DB')->result[0]['count'] > 0)
		return "Bracelet bénévole";

		DB::sql("SELECT count(`affectations`.id) as count FROM `affectations`, `vacations`, `festivals_jours` WHERE vacations.festival_jour_id = `festivals_jours`.id AND `affectations`.vacation_id = vacations.id AND `affectations`.individu_id = $individu_id AND `festivals_jours`.festival_id = $festival_id;");
		if(F3::get('DB')->result[0]['count'] > 0)
		return "Bracelet bénévole";

		return "Aucune";
	}

	static function tickets_individu($individu_id) {
		$festival_id = F3::get('SESSION.festival_id');

		// Si pas de bypass dans la bdd
		DB::sql("SELECT libelle FROM tickets WHERE tickets.individu_id = $individu_id AND tickets.festival_id = $festival_id;");
		if(count(F3::get('DB')->result)>0){
			$retour = "";
			foreach (F3::get('DB')->result as $row){
				$retour .= $row['libelle'].'<br>';
			}
			return $retour;
		}
		//DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE `historique_organismes`.responsable = 1 AND `historique_organismes`.individu_id = $individu_id AND `historique_organismes`.festival_id = $festival_id;");
		//if(F3::get('DB')->result[0]['count'] > 0)
		DB::sql("SELECT `acces_types`.libelle FROM `acces_types`, `acces` WHERE `acces`.individu_id = $individu_id AND `acces`.acces_type_id = `acces_types`.id;");
		//print_r(F3::get('DB')->result[0]['libelle']);
		if(count(F3::get('DB')->result)>0)
		if(F3::get('DB')->result[0]['libelle'] == "Badge")
		return 0;

		DB::sql("SELECT count(`affectations`.id) as count FROM `affectations`, `vacations`, `festivals_jours` WHERE vacations.festival_jour_id = `festivals_jours`.id AND `affectations`.vacation_id = vacations.id AND `affectations`.individu_id = $individu_id AND `festivals_jours`.festival_id = $festival_id;");

		return 3 * F3::get('DB')->result[0]['count'];
	}

	static function acces_profil_post() {


	}
}
?>
