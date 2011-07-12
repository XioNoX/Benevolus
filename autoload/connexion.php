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
 * @file connexion.php
 * @brief Classe en charge de la connexion/deconnexion des utilisateurs
 *
 */

class Connexion {

	/**
	 * Affiche la page de connexion (formulaire)
	 */
	static function login() {
		F3::clear('SESSION'); // RAZ de la sessions

		F3::set('pagetitle','Connexion');    // Titre de la page
		F3::set('template','connexion');     // Utiliser le template connexion.htm
		F3::call('outils::generer_no_auth'); // affiche la page
	}

	/**
	 * Authentification d'une personne qui a validée le formulaire de connexion
	 */
	static function auth() {
		F3::clear('message'); //Efface les eventuels message d'erreur restés en mêmoire
		F3::call('connexion::verif_login|connexion::verif_password'); //Vérifie la syntaxe du nom d'utilisateur et mot de passe
		if (!F3::exists('message')) {  //Si les fonctions précédentes n'ont pas renvoyées d'erreurs (pas d'erreurs de syntaxe)
			$login = F3::get('REQUEST.login'); //Récupére le login de l'utilisateur

			if(!is_numeric($login)) //Si le login n'est pas numerique (email)
			{
				DB::sql("SELECT id, email, mot_de_passe, prenom, acces_web FROM individus WHERE email = '$login' LIMIT 1;"); //Fait une recherche dans la base de donnée basée sur l'email
				$result = F3::get('DB')->result; //Stock le résultat de la requéte
			}
			else // Si login numérique (ID)
			{
				DB::sql("SELECT id, email, mot_de_passe, prenom, acces_web FROM individus WHERE id = '$login' LIMIT 1;"); //Fait une recherche dans la base de donnée basée sur l'id
				$result = F3::get('DB')->result; //Stock le résultat de la requéte
			}
			if(isset($result)) //Si la requéte sql a renvoyé un resultat
			{
				if (sha1(F3::get('salt').F3::get('REQUEST.password')) == $result[0]['mot_de_passe']) //Vérification du mot de passe en générant le hash sha1 du salt concaténé au mot de pass
				{   //Si mot de passe correct
					F3::set('SESSION.email',$result[0]['email']);  //Définition des variables globales de session (email, id, acces_web et prénom)
					F3::set('SESSION.id',$result[0]['id']);
					F3::set('SESSION.acces_web',$result[0]['acces_web']);
					F3::set('SESSION.prenom',$result[0]['prenom']);

					F3::call('outils::dernier_festival'); //Récupération de l'id du festival le plus récent

					F3::reroute('/'); //Redirection de l'utilisateur vers la racine du site.
				}  //Si mot de passe incorrect
				else
				F3::set('message','Email ou mot de passe invalide.'); //Création d'un message d'erreur
			}
		}
		F3::call('connexion::login');  // Rappel du formulaire de connexion
	}

	/**
	 * deconnexion d'une personne authentifiée
	 */
	static function sortie() {
		session_destroy();	//Destruction de la séssion
		F3::reroute('/');   //Redirection de l'utilisateur vers la racine du site.
	}

	/**
	 * Vérification de la syntaxe du login
	 */
	static function verif_login() {
		F3::input('login',   //Récupération de la donnée POST login
		function($value) {   // Fonction anonyme faisant la vérification
			if (!F3::exists('message')) { //Si pas de message d'erreur (par exemple un autre test ayant échoué)
				if (empty($value))	F3::set('message','Tu as oublié ton identifiant (email ou numéro)'); //Message d'erreur sur champs vide
				if(!is_numeric($value)) { //Si pas numérique
					if (!filter_var($value, FILTER_VALIDATE_EMAIL)) F3::set('message','Email incorrect');  //Validation de l'adresse email suivant la RFC 5322
				}
			}
		}
		);
	}

	/**
	 * Vérification de la syntaxe du mot de passe
	 */
	static function verif_password() {
		F3::input('password', //Récupération de la donnée POST password
		function($value) {    // Fonction anonyme faisant la vérification
			if (!F3::exists('message')) { //Si pas de message d'erreur (par exemple un autre test ayant échoué)
				if (empty($value)) F3::set('message','Mot de passe manquant'); //Message d'erreur sur champs vide
				elseif (strlen($value)>100)	F3::set('message','Mot de passe incorrect'); //Message d'erreur sur mot de passe trop long
			}
		}
		);
	}

}

?>
