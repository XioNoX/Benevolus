<?php

F3::set('AUTOLOAD','autoload/');

F3::set('DEBUG',3);

// Use custom 404 page
F3::set('E404','modele_no_auth.htm');
F3::set('E401','layout.htm');

// Common inline Javascript
F3::set('extlink','window.open(this.href); return false;');

// Path to our templates
F3::set('GUI','gui/');

// Define application globals
F3::set('site','myCharrues');

F3::set('salt','salt');
F3::set('acces_admin',2);
F3::set('acces_operateur',1);
F3::set('acces_individu',0);

F3::set('email_expediteur','mycharrues@mycharrues.vieillescharrues.asso.fr');

F3::set('timeformat','d M Y H:i:s');

F3::set('DB',
       new DB(
               'mysql:host=127.0.0.1;port=3306;dbname=mycharrues',
               'root',
               ''
       )
);
?>