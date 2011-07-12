<?php
class Tickets {

	static function lister() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		outils::activerJqgrid();
		F3::set('pagetitle','Liste des tickets particuliers');
		F3::set('lien_ajouter','<a href=/tickets/ajouter>Ajouter un ticket particulier</a>');
		F3::set('jquery_url_list','/tickets/lister_ajax');
		F3::set('jquery_url_edit','/tickets/editer/');
		F3::set('jquery_url_edit2','/tickets/editer');
		F3::set('jquery_largeur','100%');
		F3::set('jquery_col_names',"['id','Libelle','Individu']");

		F3::set('jquery_col_model',"[
	      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
	      {name:'libelle', index:'libelle', width:100},
	      {name:'individu', index:'individu', width:100}
	    ]");
		F3::set('template','liste_generique1');
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
		DB::sql("SELECT COUNT(*) AS count FROM `tickets` WHERE tickets.festival_id = $festival_id ;");
		$count = F3::get('DB')->result[0]['count'];

		if( $count >0 ) {
			$total_pages = ceil($count/$limit);
		} else {
			$total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit; // do not put $limit*($page - 1)
		if ($start<0) $start = 0;
		DB::sql("SELECT tickets.id, tickets.libelle,  CONCAT(individus.prenom, ' ', individus.nom) as individu FROM tickets, individus WHERE individus.id = tickets.individu_id AND tickets.festival_id = $festival_id ".$wh." ORDER BY $sidx $sord LIMIT $start, $limit;");
		
		$reponse = new stdClass();
		$reponse->page = $page;
		$reponse->total = $total_pages;
		$reponse->records = $count;
		$i=0;
		foreach (F3::get('DB')->result as $row) {
			$reponse->rows[$i]['id']=$row['id'];
			$reponse->rows[$i]['cell']=array($row['id'],$row['libelle'],$row['individu']);
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
				$tickets=new Axon('tickets');
				$tickets->load("id=$id");
				if(outils::est_admin())
				{
					DB::sql('DELETE FROM tickets WHERE id = :id ;',array(':id'=>array($id,PDO::PARAM_INT)));
					historique::logger("Suppréssion du ticket spécial ". $tickets->libelle . ' de ' . $tickets->individu_id);
				}
			}
		}
	}


	static function ajouter() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		outils::activerJquery();
		F3::set('ajouter','1');
		F3::set('pagetitle','Ajouter un tickets');
		F3::set('template','form_tickets');
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
				$tickets=new Axon('tickets');
				$tickets->individu_id = $row['id'];
				$tickets->festival_id = $festival_id;
				$tickets->libelle = F3::get('REQUEST.libelle');
				$tickets->save();
			}
				
			historique::logger("Création de l'accès particulier pour $groupe_id ($type)");
			F3::reroute('/tickets');
		}
		F3::call('acces::ajouter');
		
	}
	else
	{
		F3::call('outils::verif_individu_id');
		if (!F3::exists('message')) {
			$tickets=new Axon('tickets');
			$tickets->copyFrom('REQUEST');
			$tickets->festival_id = $festival_id;
			$tickets->save();
			$tickets_id = $tickets->_id;
			historique::logger("Création de l'accès particulier $tickets_id");
			F3::reroute('/tickets');
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
		$tickets=new Axon('tickets');
		$tickets->load("id=$id");
		if (!$tickets->dry()) {
			$tickets->copyTo('REQUEST');
			$individu_id = $tickets->individu_id;
			F3::set('REQUEST.responsable_id',$individu_id);
			if ($individu_id != '') {
				$individus=new Axon('individus');
				$individus->load("id=$individu_id");
				F3::set('REQUEST.individu', $individus->prenom . ' ' . $individus->nom . ' - ' . outils::date_sql_fr($individus->date_naissance));
			}
			F3::set('pagetitle','Editer un tickets');
			F3::set('template','form_tickets');
			F3::call('outils::generer');
		}
		else F3::http404();
	}

	static function editer_post() {
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('outils::verif_libelle|outils::verif_individu_id');
		if (!F3::exists('message')) {
			$festival_id = F3::get('SESSION.festival_id');
			$tickets_id = F3::get('PARAMS.id');
			if(!is_numeric($tickets_id)) $acces_types_id = 0;
			// Pas d'erreur, enregistrement de la MAJ
			$tickets=new Axon('tickets');
			$tickets->load("id=$tickets_id");
			$tickets->copyFrom('REQUEST');
			$tickets->festival_id = $festival_id;
			$tickets->save();

			
			historique::logger("Édition du ticket numéro $acces_types_id");
			F3::reroute('/tickets');
		}
		// Ré-Affichage du formulaire
		F3::call('tickets::editer');
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
		DB::sql("SELECT count(id) as count FROM `historique_organismes` WHERE `historique_organismes`.responsable = 1 AND `historique_organismes`.individu_id = $individu_id AND `historique_organismes`.festival_id = $festival_id;");
		if(F3::get('DB')->result[0]['count'] > 0)
		return 0;

		DB::sql("SELECT count(id) as count FROM `affectations`, `vacations`, `festivals_jours` WHERE vacations.festival_jour_id = `festivals_jours`.id AND `affectations`.vacation_id = vacations.id AND `affectations`.individu_id = $individu_id AND `festivals_jours`.festival_id = $festival_id;");

		return 3 * F3::get('DB')->result[0]['count'];
	}


}
?>
