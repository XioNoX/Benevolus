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
 * @file outils.php
 * @brief Classe contenant plusieurs fonctions "outils" utilisées dans d'autres parties du site.
 *
 */
class Outils {

	/**
	 * Affiche (génere) la page à l'aide du modéle standard : menu et javascript. Pour utilisateur connecté.
	 */
	static function generer() {
		echo Template::serve('layout.htm');
	}

	/**
	 * Affiche (génere) la page à l'aide du modéle réduit. Pour utilisateur non connecté.
	 */
	static function generer_no_auth() {
		echo Template::serve('modele_no_auth.htm');
	}

	/**
	 * Défini et stock le menu dans une variable FatFree
	 */
	static function menu() {
		$individu_id = F3::get('SESSION.id');

		F3::set('menu',
		array(
		array(
				"titre"=>"Accueil",  //Couples titres => liens
				"lien"=>"/"
				)
				)
				);
					
				if(F3::get('SESSION.acces_web')=="") //Si l'utilisateur n'est pas connecté	
				{
					F3::set('menu',
					array_merge(F3::get('menu'),
					array(
					array(
						"titre"=>"Connexion",
						"lien"=>"/connexion"
						)
						)
						)
						);
				}
				else
				{

					$messages = DB::sql("SELECT COUNT(id) AS nb_messages FROM destinataires WHERE individu_id=$individu_id AND lu=0"); //Nombre de messages non lus
					$menu_messages = "Messages";
					if ($messages[0]['nb_messages'] > 0) //Si messages non lu présents
					$menu_messages .= " (". $messages[0]['nb_messages'] . ")"; //ajout au titre "menu"

					//Menu individu
					if(F3::get('SESSION.acces_web') >= F3::get('acces_individu'))
					{
						F3::set('menu',
						array_merge(F3::get('menu'),
						array(
						array(
							"titre"=>"Mon festival",
							"lien"=>"#",
							"sous_menu"=>array(
						array("titre"=>"Mon organisme","lien"=>"/organisme"),
						array("titre"=>"Mes domaines","lien"=>"/mesdomaines"),
						array("titre"=>"Mes lieux","lien"=>"/meslieux"),
						array("titre"=>"Mes dispos","lien"=>"/disponibilites/gerer")
						)
						),
						array(
							"titre"=>"Profil",
							"lien"=>"/profil",
							"sous_menu"=>array(
						array("titre"=>"Mon profil","lien"=>"/profil"),
						array("titre"=>"Editer mon profil","lien"=>"/profil/editer"),
						array("titre"=>"Amis","lien"=>"#")
						)
						),
						array(
							"titre"=>$menu_messages,
							"lien"=>"/messages",
							"sous_menu"=>array(
						array("titre"=>"Reçus","lien"=>"/messages"),
						array("titre"=>"Envoyés","lien"=>"/messages/envoyes"),
						array("titre"=>"Envoyer un message","lien"=>"/messages/envoyer")
						)
						)//,array("titre"=>"Préférences","lien"=>"#")
						)
						)
						);
					}
					//Si l'individu est administrateur
					if(F3::get('SESSION.acces_web') == F3::get('acces_admin'))
					{
						F3::set('menu',
						array_merge(F3::get('menu'),
						array(
						array(
							"titre"=>"Admin",
							"lien"=>"/admin",
							"sous_menu"=>array(
						array("titre"=>"Festivals","lien"=>"/festivals"),
						array("titre"=>"Profils","lien"=>"/profils"),
						array("titre"=>"Organismes","lien"=>"/organismes"),
						array("titre"=>"Domaines","lien"=>"/domaines"),
						array("titre"=>"Lieux","lien"=>"/lieux"),
						array("titre"=>"Vacations","lien"=>"/vacations"),
						array("titre"=>"Historique","lien"=>"/historique"),
						array("titre"=>"Affectations","lien"=>"/vacations/affecter"),
						array("titre"=>"Accès","lien"=>"/acces"),
						array("titre"=>"Statistiques","lien"=>"/statistiques")
							
						)
						)
						)
						)
						);
					}

					if(F3::get('SESSION.acces_web') >= F3::get('acces_operateur')) //Si l'individu est opérateur
					{
						F3::set('menu',
						array_merge(F3::get('menu'),
						array(
						array(
							"titre"=>"Opérateur",
							"lien"=>"#",
							"sous_menu"=>array(
						array("titre"=>"Entrées","lien"=>"/acces/entrees")
						)
						)
						)
						)
						);
					}

					//Liste déroulante des festivals
					$festivals = new Axon('festivals'); //Utilisation la table des festivals
					F3::set('festivals', $festivals->afind('1=1','id DESC')); //Récupérations des festivals dans l'ordre décroisant

					$festival = new Axon('festivals');  //Utilisation la table des festivals
					$festival->load(array('id=:id',array(':id'=>$_SESSION['festival_id'])));  //Chargement du festival courant
		//F3::set('festivals_jours',$festivals_jours->afind(array('festival_id=:id',array(':id'=>$_SESSION['festival_id'])),'jour'));
					F3::set('festival_annee', $festival->annee);

					F3::set('menu',
					array_merge(F3::get('menu'),
					array(
					array(
						"titre"=>"Deconnexion",
						"lien"=>"/sortie"
						)
						)
						)
						);
				}
	}

	/**
	 * Fonction appelée quand un accès non autorisée à une page est effectué
	 */
	static function http401() {
		$requete = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); //Récupération de l'adresse de la page	
		F3::error(401,"Vous n'avez pas l'autorisation d'accéder à $requete",debug_backtrace(FALSE)); //Affichage de l'erreur 401
		self::generer_no_auth();
		exit(); //Arret du script
	}

	/**
	 * L'utilisateur est il administrateur?
	 * @return	boolean  est un admininstrateur
	 */
	static function est_admin() {
		if(F3::get('SESSION.acces_web') == F3::get('acces_admin')) return true;
		else return false;
	}

	/**
	 * L'utilisateur est il opérateur?
	 * @return	boolean  est un opérateur
	 */
	static function est_operateur() {
		if(F3::get('SESSION.acces_web') >= F3::get('acces_operateur')) return true;
		else return false;
	}

	/**
	 * L'utilisateur est il connecté?
	 * @return	boolean  est un individu (connecté)
	 */
	static function est_individu() {
		//		if(F3::get('SESSION.acces_web') >= F3::get('acces_individu')) return true;
		if( !is_null(F3::get('SESSION.acces_web'))) return true;
		else return false;
	}

	/**
	 * L'utilisateur est il un responsable
	 * @return	boolean  est un responsable
	 */
	static function est_responsable() {
		if(outils::est_admin()) return true; //Si administrateur, alors forcément responsable
		//Detection du type de la page
		//Pages avec accès responsables : organismes (historique_organismes), domaines (responsables_domaines)
		$entite_id = F3::get('PARAMS.id');

		if(!is_numeric($entite_id)) $entite_id = 0; // si $entite_id n'est pas numérique, mise à 0 (protection contre les SQLi)
		$type = F3::get('type'); //Récupération du type de responsable

		switch ($type) {  //Appel de fonctions différents en fonction du type de responsable demandé
			case 'organisme':  return organismes::est_responsable();
			case 'domaine':		 return domaines::est_responsable();
			case 'lieu':		 return lieux::est_responsable();
			case 'vacation':		 return vacations::est_responsable();
			case 'global': if(domaines::est_responsable() || organismes::est_responsable() || lieux::est_responsable()) return true;
		}
		return false;
	}

	/**
	 * Vérification du statut administrateur 
	 */
	static function verif_admin() {
		if(!outils::est_admin()) 
		F3::call('outils::http401');  //Si l'utilisateur n'est pas admin, alors erreur 401
	}
	
	/**
	 * Vérification du statut opérateur 
	 */
	static function verif_operateur() {
		if(!outils::est_operateur())
		F3::call('outils::http401');  //Si l'utilisateur n'est pas opérateur, alors erreur 401
	}

	/**
	 * Vérification du statut responsable 
	 */
	static function verif_responsable() {
		if(!outils::est_operateur() && !outils::est_responsable())
		F3::call('outils::http401');  //Si l'utilisateur n'est pas responsable ni opérateur, alors erreur 401
	}

	/**
	 * Vérification du statut individu (connecté) 
	 */	
	static function verif_individu() {
		if(!outils::est_individu())
		F3::call('outils::http401'); //Si l'utilisateur n'est pas connecté, alors erreur 401
	}

	/**
	 * Convertion d'une date du format 21/04/2043 au format SQL 2043-04-21
	 * @param	date_fr		string		date au format 21/04/2043
	 * @return	string		date au format SQL 2043-04-21
	 */
	static function date_fr_sql($date_fr) {
		list($day, $month, $year) = explode('/', $date_fr);
		$ts_date_fr = mktime(0, 0, 0, $month, $day, $year);
		return date("Y-m-d", $ts_date_fr);
	}

	/**
	 * Convertion d'une date du format SQL 2043-04-21 au format 21/04/2043
	 * @param	date_sql		string		date au format SQL 2043-04-21
	 * @return		string		date au format 21/04/2043	
	 */	
	static function date_sql_fr($date_sql) {
		//list($annee, $mois, $jour) = explode('-', $date_sql);
		//$ts_date_fr = mktime(0, 0, 0, $mois, $jour, $annee);
		//return date("d/m/Y", $ts_date_fr);
		return date('d/m/Y',strtotime($date_sql));
	}

	/**
	 * Récupération de la liste des jours du festival courrant
	 */	
	static function recuperation_festivals_jours() {
		$festivals_jours=new Axon('festivals_jours');
		F3::set('festivals_jours',$festivals_jours->afind(array('festival_id=:id',array(':id'=>$_SESSION['festival_id'])),'jour'));


	}

	/**
	 * Convertion d'une heure du format 21h43 au format SQL 21:43:00
	 * @param	$date_timepicker		string		date au format 21h43
	 * @return		string		date au format SQL 21:43:00
	 */	
	static function date_timepicker_sql($date_timepicker) {
		list($heures, $minutes) = explode('h', $date_timepicker);
		return $heures . ":" . $minutes . ":00";
	}

	/**
	 * Convertion d'une heure du format SQL 21:43:00 au format 21h43
	 * @param	$date_sql		string		date au format SQL 21:43:00
	 * @return		string		date au format 21h43
	 */	
	static function date_sql_timepicker($date_sql) {
		list($heures, $minutes, $secondes) = explode(':', $date_sql);
		return $heures . "h" . $minutes;
	}

	/**
	 * Récupération de l'id du festival le plus récent
	 */	
	static function dernier_festival() {  //TODO utiliser les dates de festival_jours
		DB::sql('SELECT id FROM festivals ORDER BY id DESC LIMIT 1'); //Recherche du festival le plus récent (id le plus élevé)
		F3::set('SESSION.festival_id', F3::get('DB')->result[0]['id']); //définition de la variable de session
	}

	/**
	 * Redimentionner une image définie
	 * @param	$img_src_chemin		string		chemin de l'image à modifier
	 * @param	$img_dst_chemin		string		chemin de l'emplacement où l'enregistrer
	 */		
	static function redimmensionnerImage($img_src_chemin, $img_dst_chemin) {
		// Déterminer l'extension à partir du nom de fichier
		$extension = substr( $img_src_chemin, -3 );
		// Afin de simplifier les comparaisons, on met tout en minuscule
		$extension = strtolower( $extension );

		switch ( $extension ) {
			case "jpg":
			case "peg": //pour le cas où l'extension est "jpeg"
				$img_src_resource = imagecreatefromjpeg( $img_src_chemin );
				break;

			case "gif": //pour le cas où l'extension est "gif"
				$img_src_resource = imagecreatefromgif( $img_src_chemin );
				break;

			case "png": //pour le cas où l'extension est "png"
				$img_src_resource = imagecreatefrompng( $img_src_chemin );
				break;
		}

		list( $img_src_width, $img_src_height ) = getimagesize($img_src_chemin);

		$img_dst_width = 169;
		$img_dst_height = ( $img_src_height * 169 ) / $img_src_width ;

		$tmp=imagecreatetruecolor($img_dst_width,$img_dst_height);

		imagecopyresampled($tmp,$img_src_resource,0,0,0,0,$img_dst_width,$img_dst_height,$img_src_width,$img_src_height);

		// Pour enregistrer au format jpg
		imagejpeg( $tmp, $img_dst_chemin );
	}

	
	/**
	 * Envoi d'un email
	 * @param	$destinataire		string		email du destinataire
	 * @param	$sujet		string		sujet de l'email
	 * @param	$corps		string		corps de l'email en texte brut ou html
	 * @return	int		statut de la livraison (true : livré)
	 */		
	static function email($destinataire, $sujet, $corps) {
		$email_expediteur = F3::get('email_expediteur');
		$nom_expediteur = F3::get('site');
		$headers ="From: \"$nom_expediteur\"<$email_expediteur>"."\n";
		$headers .="Reply-To: $email_expediteur"."\n";
		$headers .='Content-Type: text/html; charset="utf-8"'."\n";
		$headers .='Content-Transfer-Encoding: 8bit';
		return mail($emailInvite, $sujet, $corps, $headers);
	}

	// Pour une plus grande légèretée du site, les bibliothéques javascript ainsi que les fichiers CSS respectifs sont inclus uniquement si besoin.
	
	/**
	 * Activer le jquery (lier le javascript à la page)
	 */		
	static function activerJquery() {
		F3::set('jquery',1);
	}
	
	/**
	 * Activer jqgrid (lier le javascript à la page)
	 */		
	static function activerJqgrid() {
		outils::activerJquery();
		F3::set('jqgrid',1);
	}
	
	/**
	 * Activer Textboxlist (lier le javascript à la page)
	 */		
	static function activerTextboxlist() {
		outils::activerJquery();
		F3::set('textboxlist',1);
	}
	
	/**
	 * Activer FacyBox (lier le javascript à la page)
	 */	
	static function activerFacyBox() {
		outils::activerJquery();
		F3::set('facybox',1);
	}

	
	/**
	 * Génération aléatoire de mot de passe
	 * @return	string		mot de passe généré
	 */		
	static function genererMDP() {
		// on declare une chaine de caractéres privée de caractéres pouvant être mal interprétés (Ii1Oo0)
		$chaine = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@$-_";
		$nb_caract = 8; 		//nombre de caractéres dans le mot de passe
		$pass = "";
		for($u = 1; $u <= $nb_caract; $u++) {
			//on compte le nombre de caractéres présents dans notre chaine
			$nb = strlen($chaine);
			// on choisie un nombre au hasard entre 0 et le nombre de caractéres de la chaine
			$nb = mt_rand(0,($nb-1));
			// on ecrit  le résultat
			$pass.=$chaine[$nb];
		}
		return $pass; //on renvoi le mot de passe
	}


	/**
	 * Vérification de la syntaxe du mot de passe
	 */
	static function verif_password() { //TODO doublon avec connexion::verif_password
		F3::input('password', //Récupération de la donnée POST password
		function($value) {    // Fonction anonyme faisant la vérification
			if (!F3::exists('message')) { //Si pas de message d'erreur (par exemple un autre test ayant échoué)
				if (empty($value)) F3::set('message','Mot de passe manquant'); //Message d'erreur sur champs vide
				elseif (strlen($value)>100)	F3::set('message','Mot de passe incorrect'); //Message d'erreur sur mot de passe trop long
			}
		}
		);
	}
	
	/**
	 * Vérification de l'id de l'individu
	 */
	static function verif_individu_id() {
		F3::input('individu_id', //Récupération de la donnée POST individu_id
		function($value) {
			if (empty($value)) //Si vide : erreur
			F3::set('message','Individu non renseigné');
			if (!F3::exists('message')) {
				if (preg_match('/^[0-9]+$/', $value) == 0 ) //Si pas numérique : erreur
				F3::set('message','Individu incorrect');
			}
		}
		);
	}

	/**
	 * Vérification d'un libellé
	 */
	static function verif_libelle() {
		F3::input('libelle', //Récupération de la donnée POST libelle
		function($value) {
			if (!F3::exists('message')) {
				if (empty($value)) //Si vide : erreur
				F3::set('message','Libelle non renseigné');
				elseif (strlen($value)>255) //Si trop long : erreur
				F3::set('message','Libelle trop long');
			}
		}
		);
	}


}

?>
