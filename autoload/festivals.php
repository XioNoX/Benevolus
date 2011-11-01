<?php
class Festivals {

	static function lister() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
    outils::activerJqgrid();
		F3::set('pagetitle','Liste des festivals');
		F3::set('lien_ajouter','<a href=/festivals/ajouter>Ajouter un festival</a>');
		F3::set('jquery_largeur','975');
		F3::set('jquery_url_list','../../ajax/festivals');
		F3::set('jquery_url_edit','../../festivals/editer/');
		F3::set('jquery_url_edit2','/festivals/editer');
		F3::set('jquery_col_names',"['id', 'annee', 'libelle', 'taux_horaire']");
		F3::set('jquery_col_model',"[
      {name:'id', index:'id', width:55, formatter: formateadorLink}, 
      {name:'annee', index:'libelle', width:90}, 
      {name:'libelle', index:'adresse1', width:80, align:'right'}, 
      {name:'taux_horaire', index:'type_struct', width:80} 
    ]");
		F3::set('template','liste_generique1');
		F3::call('outils::generer');
	}

  static function editer_tableau_post() {
      if(F3::get('REQUEST.oper') == "del")
        $liste_ids = F3::get('REQUEST.id');
          $tableau_ids = explode(',', $liste_ids);
          foreach( $tableau_ids as $id) {
          $festival=new Axon('festivals');
			    $festival->load("id=$id");		
            if(is_numeric($id)){
              if(outils::est_admin())
              {
                DB::sql("DELETE FROM festivals WHERE id=$id");
                historique::logger("Suppréssion du festival ". $festival->libelle);
              }
           }
          }
  }
	
  static function festival_actif() {
  	F3::call('outils::menu');
  	F3::call('outils::verif_admin');

  	if ( is_numeric(F3::get('PARAMS.id')) && F3::get('REQUEST.url') != "" )
  	{
  		$festival_id = F3::get('PARAMS.id');
  		$url = F3::get('REQUEST.url');
  		
		
  		F3::set('SESSION.festival_id', $festival_id);
  		F3::reroute($url);
  		
  	}
  		F3::reroute('/');
  	
  }

 static	function recuperation_dates()
	{
    $id = F3::get('PARAMS.id');
    if(is_numeric($id)){
		  $debut = DB::sql("SELECT * FROM festivals_jours WHERE festival_id=$id ORDER BY jour ASC LIMIT 1");
		  list($year, $month, $day) = explode('-', $debut[0]['jour']);
		  $ts_debut = mktime(0, 0, 0, $month, $day, $year);
		  F3::set('debut', date("d/m/Y", $ts_debut));

		  $fin = DB::sql("SELECT * FROM festivals_jours WHERE festival_id=$id ORDER BY jour DESC LIMIT 1");
		  list($year, $month, $day) = explode('-', $fin[0]['jour']);
		  $ts_fin = mktime(0, 0, 0, $month, $day, $year);
		  F3::set('fin', date("d/m/Y", $ts_fin));
    }
	}

	static function ajouter() {
    outils::activerJquery();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');

		F3::set('pagetitle','Ajouter un festival');
		F3::set('template','festival');
		F3::call('outils::generer');
	}

	static function ajouter_post() {
		F3::call('outils::verif_admin');
		// Suppression d'un éventuel précédent message d'erreur
		F3::clear('message');
		// Vérification des champs
		F3::call('festivals::verif_annee|festivals::verif_libelle|festivals::verif_taux_horaire|festivals::verif_debut|festivals::verif_fin');
		if (!F3::exists('message')) {
			// Pas d'erreur, enregistrement dans la base de données
			$festival=new Axon('festivals');
			$festival->copyFrom('REQUEST');
			$festival->save();

			$debut = F3::get('REQUEST.debut');
			$fin = F3::get('REQUEST.fin');

			list($day, $month, $year) = explode('/', $debut);
			$ts_tmp = mktime(0, 0, 0, $month, $day, $year);

			list($day, $month, $year) = explode('/', $fin);
			$ts_fin = mktime(0, 0, 0, $month, $day, $year);

			$festival_id = $festival->_id;

			while ($ts_tmp <= $ts_fin)
			{
				$jour = date("Y-m-d", $ts_tmp);
				DB::sql("INSERT INTO festivals_jours VALUES ('', '$jour', $festival_id)");
				$ts_tmp = mktime(0,0,0,date("m", $ts_tmp )  ,date("d",$ts_tmp ) + 1, date("Y", $ts_tmp ));
			}
     
      F3::set('SESSION.festival_id',$festival_id); 
      festivals::importFestivalPrecedent();
      historique::logger("Création du festival numéro $festival_id");
			// Retour à la liste des festivals. Le nouveau festival doit être présent
			F3::reroute('/festivals');
		}
		// Ré-Affichage du formulaire
		F3::call('festivals::ajouter');
	}


  static function importFestivalPrecedent() {
		F3::call('outils::verif_admin');
      // Importation historique_organismes
      // Vérification du nombre de festivals 
    DB::sql('SELECT count(*) as nb_festivals FROM festivals');
    $nbFestivalsExistants = F3::get('DB')->result[0]['nb_festivals'];
    if($nbFestivalsExistants > 2){
    	DB::sql('SELECT id FROM festivals ORDER BY id DESC LIMIT 2');
    	$result = F3::get('DB')->result;
	$nouveau_festival = $result[0]['id'];
	$ancien_festival = $result[1]['id'];
	DB::sql("SELECT responsable, organisme_id, individu_id FROM historique_organismes WHERE festival_id = $ancien_festival ORDER BY id");
	foreach (F3::get('DB')->result as $row) {
   			$historique_organismes=new Axon('historique_organismes');
	   		$historique_organismes->responsable = $row['responsable'];
	   		$historique_organismes->organisme_id =  $row['organisme_id'];
	   		$historique_organismes->individu_id = $row['individu_id'];
	   		$historique_organismes->festival_id = $nouveau_festival;
	   		$historique_organismes->present = 0;
			$historique_organismes->save();
	   	}
	}
  }



	static function editer() {
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
   		outils::activerJquery();
		$festival_id = F3::get('PARAMS.id');
		// Retrieve matching record
		$festival=new Axon('festivals');
		$festival->load("id=$festival_id");
		if (!$festival->dry()) {
			// Populate REQUEST global with retrieved values
			$festival->copyTo('REQUEST');
			// Render blog.htm template
			F3::set('pagetitle','Editer un festival');
			F3::set('editer','editer');

			festivals::recuperation_dates();

			F3::set('template','festival');
			F3::call('outils::generer');
		}
		else
		// Invalid blog entry; display our 404 page
		F3::http404();
	}

	static function editer_post() {
		F3::call('outils::menu');
		// Reset previous error message, if any
		F3::clear('message');
		$festival_id = F3::get('PARAMS.id');
		// Form field validation
		F3::call('festivals::verif_annee|festivals::verif_libelle|festivals::verif_taux_horaire|festivals::verif_debut|festivals::verif_fin');
		if (!F3::exists('message')) {
			// No input errors; update record
			$blog=new Axon('festivals');
			$blog->load("id=$festival_id");
			$blog->copyFrom('REQUEST'); // TODO : convertir la virgule en point si présent
      //$solde = str_replace(",",".",$solde);
			$blog->save();

			$debut = F3::get('REQUEST.debut');
			$fin = F3::get('REQUEST.fin');

			list($day, $month, $year) = explode('/', $debut);
			$ts_tmp = mktime(0, 0, 0, $month, $day, $year);
			$jour_debut = date("Y-m-d", $ts_tmp);

			list($day, $month, $year) = explode('/', $fin);
			$ts_fin = mktime(0, 0, 0, $month, $day, $year);
			$jour_fin = date("Y-m-d", $ts_fin);

			echo "<br>debut:" . $ts_tmp;
			echo "<br>fin:" . $ts_fin;

			DB::sql("DELETE FROM festivals_jours WHERE festival_id='$festival_id' AND (jour < '$jour_debut' OR jour > '$jour_fin')");

			while ($ts_tmp <= $ts_fin)
			{
				//Est-ce que la date est deja présente?
				$jour_tmp = date("Y-m-d", $ts_tmp);
				$result = DB::sql("SELECT COUNT(*) AS count FROM festivals_jours WHERE jour='$jour_tmp' AND festival_id='$festival_id'");
				$count = $result[0]['count'];

				if ($count < 1)
				{
					DB::sql("INSERT INTO festivals_jours VALUES ('', '$jour_tmp', '$festival_id')");
				}
				$ts_tmp = mktime(0,0,0,date("m", $ts_tmp )  ,date("d",$ts_tmp ) + 1, date("Y", $ts_tmp ));

				echo $ts_tmp;
			}
      historique::logger("Édition du festival numéro $festival_id");
			F3::reroute('/festivals');
		}
		F3::call('festivals::editer');
	}

	static function afficher() {

	}
	/* Validation des formulaires */

	static function verif_annee() {
		F3::input('annee',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Année non renseignée');
				//Verifier que c'est une annee
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

	static function verif_taux_horaire() {
		F3::input('taux_horaire',
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value))
				F3::set('message','Taux horaire non renseigné');
				//Verifier que c'est un taux horaire
			}
		}
		);
	}

	static function verif_debut() {
		F3::input('debut',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $value) == 0 )
				F3::set('message','Date de début incorrecte');
			}
		}
		);
	}

	static function verif_fin() {
		F3::input('fin',
		function($value) {
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/', $value) == 0 )
				F3::set('message','Date de fin incorrecte');
			}
		}
		);
	}
}

?>
