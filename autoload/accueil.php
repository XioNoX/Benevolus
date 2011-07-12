<?php
/** -- BEGIN LICENSE BLOCK ---------------------------------------
 * This file is part of  myCharrues
 * @package    myCharrues
 *
 * @copyright  Copyright (c) 2011, Julien Vermet, Arzhel Younsi
 * Licensed under the AGPL version 3.0 license.
 * @license    http://www.gnu.org/licenses/agpl.txt  AGPL
 *
 * -- END LICENSE BLOCK ----------------------------------------*/

/**
 * @file accueil.php
 * @brief Classe en charge des pages d'accueil des utilisateurs
 *
 */

class Accueil {

	/**
	 * Affiche la page d'accueil d'un bénévole standard
	 */
	static function affichage() { //TODO : déplacer dans profils.php
		F3::call('outils::menu'); //appel du menu
		F3::call('outils::verif_individu'); //vérification du niveau de l'utilisateur (ici connecté)
		F3::call('accueil::verif_profil');  //vérifie si l'utilisateur a saisi ses information personnelles
		disponibilites::verif_dispos('',''); //vérifie si l'utilisateur a saisi ses disponibilités
		F3::set('template','individus_accueil'); //Définition du template à utiliser
		F3::call('outils::generer');  //Affichage de la page
	}

	/**
	 * Vérifie si l'utilisateur a saisi ses information personnelles
	 */
	static function verif_profil() { //TODO : déplacer dans profils.php
		
		$individu=new Axon('individus'); //utilisation de la table individus
		$individu->load(array('id=:id',array(':id'=>$_SESSION['id']))); //Chargement des informations concernant l'individu concerné
		
		if ( (empty($individu->adresse1) && empty($individu->adresse2)) || empty($individu->email) || $individu->date_naissance=='0000-00-00' || $individu->ville_id==0 || (empty($individu->telephone_fixe) && empty($individu->telephone_portable)) )
		{ // Vérification des informations, si certaines sont manquantes, afficher un message d'erreur
			F3::set('message','Veuillez mettre à jour vos informations personnelles : <a href=/profil/editer title="Profil">Gérer mon profil</a>');
		}
	}
	
	/**
	 * Vérifie si l'utilisateur a saisi ses information personnelles
	 */
	static function affichage_admin() { //TODO : déplacer dans vacations.php
		F3::call('outils::menu'); //appel du menu
		F3::call('outils::verif_admin'); //vérification du niveau de l'utilisateur (ici admin)

		outils::activerJquery(); //Activation de jquery
		
		if ( F3::get('PARAMS.id') != "")  //TODO : déplacer dans festivals.php
    	{
    		$festival_id = F3::get('PARAMS.id');
	    	if(!is_numeric($festival_id)) 
	    		Outils::http401();
	    	else
	    		F3::set('SESSION.festival_id', $festival_id);
    	}
		
		F3::set('template','admin_accueil');
		F3::call('outils::generer');
	}
}
?>
