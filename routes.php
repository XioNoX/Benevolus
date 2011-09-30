<?php

$individu_id = F3::get('SESSION.id'); //Récupération de l'ID de la personne connectée
if(isset($individu_id)) //Si existante (personne connectée)
  F3::route('GET /','accueil::affichage');  //Affichage de la page d'accueil pour la racine du site
else
  F3::route('GET /','connexion::login');  //Sinon affichage du formulaire de conneixon

F3::route('GET /connexion','connexion::login');
F3::route('POST /connexion','connexion::auth');
F3::route('GET /sortie','connexion::sortie');

F3::route('GET /festivals/ajouter','festivals::ajouter');
F3::route('POST /festivals/ajouter','festivals::ajouter_post');
F3::route('GET /festivals/editer/@id','festivals::editer');	
F3::route('POST /festivals/editer/@id','festivals::editer_post');
F3::route('GET /festivals','festivals::lister');
F3::route('GET /festivals/@id','festivals::afficher');
F3::route('POST /festivals/editer','festivals::editer_tableau_post');

F3::route('GET /organismes/ajouter','organismes::ajouter');
F3::route('POST /organismes/ajouter','organismes::ajouter_post');
F3::route('GET /organismes','organismes::lister');
//F3::route('GET /organismes/@limite','organismes::lister');
F3::route('GET /organismes/editer/@id','organismes::editer');
F3::route('POST /organismes/editer/@id','organismes::editer_post');
F3::route('POST /organismes/editer/@id/supprMembre','organismes::suppr_membre');
F3::route('POST /organismes/editer/@id/ajouterMembre','organismes::ajouter_membre');
F3::route('POST /organismes/editer/@id/supprInvitation','organismes::suppr_invitation');
F3::route('POST /organismes/editer','organismes::editer_tableau_post');
F3::route('GET /organismes/@id/recommandations','organismes::recommandations');
F3::route('POST /organismes/@id/recommandations','organismes::recommandations_post');
F3::route('GET /organisme','organismes::mon_organisme');
F3::route('GET /organismes/imprimer','organismes::imprimer_liste');
F3::route('GET /organisme/imprimer/@id','organismes::imprimer_liste_membres');
F3::route('GET /organisme/imprimer/@id/@option','organismes::imprimer_liste_membres');
F3::route('GET /organisme/tous_membres/@id','organismes::tous_membres');
F3::route('GET /organismes/editer/@id/ajax_action','organismes::ajax_action');


F3::route('GET /lieux/ajouter','lieux::ajouter');
F3::route('GET /lieux/tous','lieux::lister');
F3::route('POST /lieux/ajouter','lieux::ajouter_post');
F3::route('GET /lieux','lieux::lister_festival');
F3::route('GET /lieux/editer/@id','lieux::editer');
F3::route('POST /lieux/editer/@id','lieux::editer_post');
F3::route('POST /lieux/editer/@id/supprimerVacation','lieux::suppr_vacation');
F3::route('POST /lieux/editer/@id/supprimerVacation','lieux::ajouter_vacation');
F3::route('POST /lieux/@id/responsable','lieux::ajax_responsable');
F3::route('POST /lieux/editer','lieux::editer_tableau_post');
F3::route('POST /lieux/editer_festival','lieux::editer_tableau_festival_post');
F3::route('GET /meslieux','lieux::mes_lieux');
F3::route('GET /monlieu/@id','lieux::mon_lieu');

F3::route('GET /domaines/ajouter','domaines::ajouter');
F3::route('POST /domaines/ajouter','domaines::ajouter_post');
F3::route('POST /domaines/editer','domaines::editer_tableau_post');
F3::route('POST /domaines/editer_festival','domaines::editer_tableau_festival_post');
F3::route('GET /domaines/editer/@id','domaines::editer');	
F3::route('POST /domaines/editer/@id','domaines::editer_post');
F3::route('POST /domaines/@id/responsable','domaines::ajax_responsable');

F3::route('GET /domaines','domaines::lister_festival');
F3::route('GET /domaines/tous','domaines::lister');
//F3::route('GET /domaines/@id/affecter','domaines::affecter');
//F3::route('POST /domaines/@id/affecter','domaines::affecter_post');
F3::route('GET /mesdomaines','domaines::mes_domaines');
F3::route('GET /mondomaine/@id','domaines::mon_domaine');

F3::route('GET /inscription/@id','profils::inscription');
F3::route('GET /inscription/@id/@organisme_id','profils::inscription');
F3::route('POST /inscription/@id','profils::inscription_post');
F3::route('POST /inscription/@id/@organisme_id','profils::inscription_post');
F3::route('GET /inscription/succes','profils::inscription_succes');
F3::route('GET /profil','profils::afficher');
F3::route('GET /profil/editer','profils::editer');
//F3::route('GET /profil/anonymiser','profils::anonymiser');
F3::route('POST /profil/editer','profils::editer_post');
F3::route('GET /profil/@id','profils::afficher');
F3::route('POST /profil/photo/envoi','profils::envoi_photo');
//F3::route('GET /profil/photos','profils::photos');
F3::route('GET /profil/imprimer/courrier/haut','profils::imprimer_courrier_haut');
F3::route('GET /oublie','profils::oublie_pass');

F3::route('GET /profils','profils::lister');
F3::route('GET /profils/tous','profils::lister_tous');	
F3::route('GET /historique','historique::lister');
F3::route('GET /profils/editer/@id','profils::editer');	
F3::route('POST /profils/editer/@id','profils::editer_post');
F3::route('POST /profils/editer','profils::editer_tableau_post');
F3::route('GET /profils/anonymiser/@id','profils::anonymiser');

F3::route('GET /profils/invitations/attente','profils::invitations_attente');
F3::route('GET /profils/invitations/activer/@id','profils::activer_invitation');
F3::route('GET /profils/invitations/supprimer/@id','profils::supprimer_invitation');
F3::route('GET /profils/invitations/fusionner','profils::fusionner_invitation');


F3::route('GET /admin','accueil::affichage_admin');
F3::route('GET /festival_actif/@id','festivals::festival_actif');

F3::route('GET /vacations','vacations::lister');
F3::route('GET /vacations/ajouter','vacations::ajouter');
F3::route('POST /vacations/ajouter','vacations::ajouter_post');
F3::route('GET /vacations/editer/@id','vacations::editer');	
F3::route('POST /vacations/editer/@id','vacations::editer_post');
F3::route('POST /vacations/editer','vacations::editer_tableau_post');
F3::route('GET /vacations/affecter','vacations::affecter');
F3::route('POST /vacations/affecter','vacations::affecter_post');
F3::route('GET /vacations/affectations/supprimer/@id','vacations::supprimer_affectations_vacation');
F3::route('GET /vacations/organismes','vacations::distribuer_organismes');
F3::route('POST /vacations/organismes','vacations::distribuer_organismes_post');
F3::route('POST /vacations/echanger','vacations::echanger_vacations_post');
F3::route('GET /vacations/echanger','vacations::echanger_vacations');
F3::route('GET /vacations/imprimer', 'vacations::imprimer_vacations_individu');
F3::route('GET /vacations/imprimer/@id', 'vacations::imprimer_vacations_individu');
F3::route('GET /vacations/imprimer/tout', 'vacations::imprimer_tout');
F3::route('POST /vacations/imprimer/tout', 'vacations::imprimer_tout');
F3::route('GET /vacations/imprimer/organisme/@id', 'vacations::imprimer_vacations_organisme');
F3::route('GET /vacations/imprimer/lieu/@id', 'vacations::imprimer_vacations_lieu');
F3::route('GET /vacations/imprimer/domaine/@id', 'vacations::imprimer_vacations_domaine');
F3::route('GET /vacations/imprimer/emargement/@id', 'vacations::imprimer_emargement_vacation');
F3::route('GET /vacations/imprimer/emargement/lieu/@id', 'vacations::imprimer_emargement_lieu');
F3::route('POST /vacations/imprimer/emargement/lieu/@id', 'vacations::imprimer_emargement_lieu');
F3::route('GET /vacations/imprimer/emargement/domaine/@id', 'vacations::imprimer_emargement_domaine');

F3::route('GET /vacations/emargement/lieu/@id', 'vacations::emargement_lieu');
F3::route('GET /vacations/emargement/organisme/@id', 'vacations::emargement_organisme');
F3::route('GET /vacations/emargement/vacation/', 'vacations::emargement_vacation');
F3::route('GET /vacations/emargement/vacation/@id', 'vacations::emargement_vacation');
F3::route('POST /vacations/emargement/vacation/@id', 'vacations::emargement_vacation_post');


F3::route('GET /disponibilites/gerer','disponibilites::gerer');
F3::route('GET /disponibilites/@id/gerer','disponibilites::gerer');
F3::route('POST /disponibilites/gerer','disponibilites::gerer_post');
F3::route('POST /disponibilites/@id/gerer','disponibilites::gerer_post');

F3::route('GET /messages/envoyes','messages::envoyes');
F3::route('GET /messages','messages::recus');
F3::route('GET /messages/@id','messages::afficher_message');
F3::route('GET /messages/envoyer','messages::envoyer');
F3::route('POST /messages/envoyer','messages::envoyer_post');
F3::route('GET /messages/envoyer/@type/@id','messages::envoyer');
F3::route('GET /messages/envoyer/@type','messages::envoyer');
F3::route('POST /messages/envoyer/@type/@id','messages::envoyer_post');
F3::route('POST /messages/envoyer/@type','messages::envoyer_post');
F3::route('GET /messages/non_lu/@id','messages::non_lu');
F3::route('GET /messages/supprimer/@id','messages::supprimer');

//Routes concernant les requéttes AJAX
//TODO : À déplacer dans les fichiers respectifs
F3::route('GET /ajax/ville','ajax::villes');
F3::route('GET /ajax/profils','ajax::profils');
F3::route('GET /ajax/destinataires_messages','ajax::destinataires_messages');
F3::route('GET /ajax/profils_festival','ajax::profils_festival');
F3::route('GET /ajax/organismes','ajax::organismes');
F3::route('GET /ajax/gerer_organismes','ajax::gerer_organismes');
F3::route('GET /ajax/festivals','ajax::festivals');
F3::route('GET /ajax/domaines','ajax::domaines');
F3::route('GET /ajax/domaines_festival','ajax::domaines_festival');
F3::route('GET /ajax/historique','ajax::historique');
F3::route('GET /ajax/lieux','ajax::lieux');
F3::route('GET /ajax/lieux_festival','ajax::lieux_festival');
F3::route('GET /ajax/domaines/@id/lieux','ajax::domaines_id_lieux');
F3::route('GET /ajax/vacations/lieux/@id','ajax::vacations_lieux');
F3::route('GET /ajax/vacations','ajax::vacations');
F3::route('GET /ajax/vacations_assigner/@type/@id','ajax::vacations_assigner');
F3::route('GET /ajax/vacations_assigner','ajax::vacations_assigner');
F3::route('GET /ajax/profils_vacations','ajax::profils_vacations');
F3::route('GET /ajax/profils_vacations/@type/@id','ajax::profils_vacations');
F3::route('GET /ajax/groupe','ajax::groupe');

F3::route('GET /acces/entrees','acces::entrees');
F3::route('POST /acces/entrees','acces::entrees');
F3::route('GET /acces/profil/@id','acces::acces_profil'); //NOK
F3::route('POST /acces/profil/@id','acces::acces_profil_post'); //NOK
F3::route('GET /acces','acces::lister');
F3::route('GET /acces/lister_ajax','acces::lister_ajax');
F3::route('GET /acces/ajouter','acces::ajouter');
F3::route('POST /acces/ajouter','acces::ajouter_post');
F3::route('GET /acces/editer/@id','acces::editer');
F3::route('POST /acces/editer/@id','acces::editer_post');
F3::route('POST /acces/editer','acces::editer_tableau_post');

F3::route('GET /tickets','tickets::lister');
F3::route('GET /tickets/lister_ajax','tickets::lister_ajax');
F3::route('GET /tickets/ajouter','tickets::ajouter');
F3::route('POST /tickets/ajouter','tickets::ajouter_post');
F3::route('GET /tickets/editer/@id','tickets::editer');
F3::route('POST /tickets/editer/@id','tickets::editer_post');
F3::route('POST /tickets/editer','tickets::editer_tableau_post');

F3::route('GET /statistiques/dons','statistiques::dons');
F3::route('GET /statistiques','statistiques::accueil');



F3::route('GET /sitemap', //Utilisation d'une fonction de fatfree pour générer le sitemap
	function() {
		F3::sitemap();
	}
);

?>
