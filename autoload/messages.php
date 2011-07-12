<?php
class Messages {

	static function envoyer_message($expediteur, $destinataires, $sujet, $corps) {
		if($expediteur == '') $expediteur = F3::get('SESSION.id');
		
		if(empty($destinataires)) return false;
		$message=new Axon('messages');
		$message->sujet = $sujet;
		$message->message = $corps;
		$message->individu_id=$expediteur;
		$message->save();
		$message_id = $message->_id;
			
		for($i=0;$i<sizeof($destinataires);$i++) // tant que $i est inferieur au nombre d'éléments du tableau...
		{
			$destinataire=new Axon('destinataires');
			$destinataire->message_id=$message_id;
			$destinataire->individu_id=$destinataires[$i];
			$destinataire->lu=0;
			$destinataire->save();
		}
		return true;
	}

	static function envoyer_message_multiples($expediteur,$type, $destinataires, $sujet, $corps) {
		if($expediteur == '') $expediteur = F3::get('SESSION.id');
		if(($type == "") || ($type == "individu")) return messages::envoyer_message($expediteur, $destinataires, $sujet, $corps);
			
			
		$type = F3::get("PARAMS.type");
		$liste_membres = array();

		switch ($type) {
			case 'domaine':
				return false; // TODO 
				break;
			case 'lieu':
				for($i=0;$i<sizeof($destinataires);$i++) // tant que $i est inferieur au nombre d'éléments du tableau...
				{	
					$membres_lieu = lieux::membres_lieu($destinataires[$i], "");
					foreach ($membres_lieu as $row) {
						array_push($liste_membres, $row['id']);
					}
				}
				break;
			case 'organisme':
				for($i=0;$i<sizeof($destinataires);$i++) // tant que $i est inferieur au nombre d'éléments du tableau...
				{	
					$membres_organisme = organismes::membres_organisme($destinataires[$i], "");
					foreach ($membres_organisme as $row) {
						array_push($liste_membres, $row['id']);
					}
				}
				break;
		}
		return messages::envoyer_message($expediteur, $liste_membres, $sujet, $corps);
	}

	static function envoyes() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');

		$individu_id = F3::get('SESSION.id');

		DB::sql('SELECT i2.prenom, i2.nom, messages.id, sujet, message FROM individus i1, individus i2, messages, destinataires WHERE i1.id = messages.individu_id AND messages.id = destinataires.message_id AND destinataires.individu_id = i2.id AND i1.id = :individu_id;',array(':individu_id'=>array(F3::get('SESSION.id'),PDO::PARAM_INT)));

		if(count(F3::get('DB')->result)>0)
		{
			F3::set('messages',F3::get('DB')->result);
			F3::set('envoyes','1');
		}

		F3::set('pagetitle','Messages envoyés');
		F3::set('template','messages');
		F3::call('outils::generer');
	}

	static function recus() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');

		$individu_id = F3::get('SESSION.id');

		DB::sql('SELECT destinataires.individu_id, lu, nom, prenom, sujet, message, messages.id FROM individus, messages, destinataires WHERE destinataires.individu_id = :individu_id AND destinataires.message_id = messages.id AND messages.individu_id = individus.id',array(':individu_id'=>array($individu_id,PDO::PARAM_INT)));

		if(count(F3::get('DB')->result)>0)
		{
			F3::set('messages',F3::get('DB')->result);
			F3::set('recus','1');
		}

		F3::set('pagetitle','Boite de réception');
		F3::set('template','messages');
		F3::call('outils::generer');
	}

	static function afficher_message() {
		F3::call('outils::verif_individu');
		$message_id = F3::get('PARAMS.id');
		$individu_id = F3::get('SESSION.id');

		$message=new Axon('messages');
		$message->load("id=$message_id");
			
		if (!$message->dry())
		{
			$destinataire=new Axon('destinataires');
			$destinataire->load("message_id=$message_id AND individu_id=$individu_id");

			if (!$destinataire->dry())
			{
				$message->copyTo('REQUEST');
				$expediteur=new Axon('individus');
				$expediteur->load("id=$message->individu_id");

				F3::set('expediteur',$expediteur->prenom . " " . $expediteur->nom);

				$destinataire->lu=1;
				$destinataire->save();
			}
			else F3::http404();
		}
		else F3::http404();

		F3::set('pagetitle','Message');
		F3::call('outils::menu');
		F3::set('template','message');
		F3::call('outils::generer');
	}

	static function envoyer() {
		outils::activerTextboxlist();
		F3::call('outils::menu');
		if(is_numeric(F3::get("PARAMS.id"))) $id=F3::get("PARAMS.id");
		unset(F3::get('DB')->result);
		F3::set('type',F3::get("PARAMS.type"));
		switch (F3::get("PARAMS.type")) {
			case 'domaine':
				F3::call('outils::verif_responsable');
				if(isset($id)){
					DB::sql("select id, libelle from domaines where id=$id");
					F3::set('destinataire',F3::get('DB')->result[0]);
					
				}
				
				break;
			case 'lieu':
				F3::call('outils::verif_responsable');
				if(isset($id)){
					DB::sql("select id, libelle from lieux where id=$id");
					F3::set('destinataire',F3::get('DB')->result[0]);
				}
				
				break;
			case 'organisme':
				F3::call('outils::verif_responsable');
				if(isset($id)){
					DB::sql("select id, libelle from organismes where id=$id");
					F3::set('destinataire',F3::get('DB')->result[0]);
				}
				
				break;
			default:
				F3::call('outils::verif_individu');
				F3::set('type','');
				break;
		}

		if(outils::est_admin()) F3::set('admin','1');
		
		F3::set('pagetitle','Nouveau message');
		F3::set('template','form_messages');
		F3::call('outils::generer');
	}

	static function envoyer_post() {
		F3::clear('message');
		F3::clear('succes');

		outils::activerTextboxlist();
		F3::call('outils::menu');
		if(is_numeric(F3::get("PARAMS.id"))) $id=F3::get("PARAMS.id");
		
		if(is_numeric(F3::get('REQUEST.destinataires_id'))) $destinataires=explode(",", F3::get('REQUEST.destinataires_id'));
		else
		$destinataires = explode(",", F3::get('REQUEST.destinataires'));
		
		unset(F3::get('DB')->result);
		F3::set('type',F3::get("PARAMS.type"));
		$expediteur = F3::get('SESSION.id');
		$type = F3::get("PARAMS.type");
		$sujet = F3::get('REQUEST.sujet');
		$corps = F3::get('REQUEST.message');
			
		switch ($type) {
			case 'domaine':
				F3::call('outils::verif_responsable');
				break;
			case 'lieu':
				F3::call('outils::verif_responsable');
				break;
			case 'organisme':
				F3::call('outils::verif_responsable');
				break;
			default:
				F3::call('outils::verif_individu');
				F3::set('type','');
				break;
		}

		if(messages::envoyer_message_multiples($expediteur,$type, $destinataires, $sujet, $corps)) F3::set('succes','Message correctement envoyé');
		else F3::set('message',"Erreur lors de l'envoi du message.");

		F3::call('messages::envoyer');
	}

	static function non_lu() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		$message_id = F3::get('PARAMS.id');
		$individu_id = F3::get('SESSION.id');

		$message=new Axon('messages');
		$message->load("id=$message_id");
			
		if (!$message->dry())
		{

			$destinataire=new Axon('destinataires');
			$destinataire->load("message_id='$message_id' AND individu_id='$individu_id'");

			if (!$destinataire->dry())
			{
				$message->copyTo('REQUEST');

				$destinataire->lu=0;
				$destinataire->save();

				F3::reroute('/messages');
			}
			else F3::http404();
		}
		else F3::http404();

		F3::set('pagetitle','Messages envoyés');
		F3::set('template','form_messages');
		F3::call('outils::generer');
	}

	static function supprimer() {
		F3::call('outils::menu');
		F3::call('outils::verif_individu');
		$message_id = F3::get('PARAMS.id');
		$message=new Axon('messages');
		$message->load("id=$message_id");
			
		//FIXME : Booleen pour la suppression d'un message

		if (!$message->dry())
		{
			$individu_id = F3::get('SESSION.id');

			$destinataire=new Axon('destinataires');
			$destinataire->load("message_id='$message_id' AND individu_id='$individu_id'");

			if (!$destinataire->dry())
			{
				$message->copyTo('REQUEST');

				$destinataire->erase();

				//				//Qui avait reçu ce message?
				//				$destinataire_autre=new Axon('destinataires');
				//				$destinataire->load("message_id='$message_id'");
				//
				//				//Personne d'autre n'avait reçu ce message, on peut le supprimer
				//				if ($destinataire->dry())
				//				{
				//					$message->erase();
				//				}

				F3::reroute('/messages');
			}
			else F3::http404();
		}
		else F3::http404();

		F3::set('pagetitle','Messages envoyés');
		F3::set('template','form_messages');
		F3::call('outils::generer');
	}
}
?>
