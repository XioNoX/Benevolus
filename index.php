<?php
require_once __DIR__.'/lib/base.php'; // Appel du framework Fat-Free
setlocale(LC_ALL,"fr_FR.utf8" );  //Definition de la locale à utiliser
F3::set('hostname',$_SERVER['HTTP_HOST']); // Récupération du nom d'hôte du site
F3::call('config.php'); //Appel du fichier config.php
F3::call('routes.php'); //Appel du fichier routes.php
F3::run(); //Execution du framework

?>
