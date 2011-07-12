<?php

class historique {

	static function lister() {
    outils::activerJqgrid();
		F3::call('outils::menu');
		F3::call('outils::verif_admin');
		F3::set('pagetitle','Historique des modifications');
		//F3::set('lien_ajouter','<a href=/benevoles/ajouter>Ajouter un bénévole</a>');
		F3::set('jquery_url_list','../../ajax/historique');
    F3::set('jquery_largeur','968'); 
    F3::set('jquery_col_names',"['id', 'Date','Individu', 'Action']");
		
    F3::set('jquery_col_model',"[ 
      {name:'id', index:'id', width:35, hidden:true}, 
      {name:'date', index:'date', width:50 }, 
      {name:'individu', index:'individu', width:50}, 
      {name:'action', index:'action', width:55, align:'right'}
    ]");
    F3::set('template','liste_generique1');
		F3::call('outils::generer');
  }

	static function logger($action) { 
    $individu_id = F3::get('SESSION.id');
    if(!is_numeric($individu_id))
    {
      $individu_id = 0;
    }

    DB::sql("INSERT INTO `historique` VALUES('',NOW(), :action, :individu_id );", array(':individu_id'=>array($individu_id,PDO::PARAM_INT),':action'=>array($action,PDO::PARAM_STR)));

  }

}

?>
