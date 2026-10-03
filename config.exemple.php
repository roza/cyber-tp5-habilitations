<?php
// Copiez ce fichier en config.local.php et adaptez-le à votre poste.
// Les valeurs ci-dessous sont celles d'une installation WAMP par défaut.
return [
    'hote'      => 'localhost',
    'port'      => 3306,     // WAMP : 3306 ou 3308 selon le SGBD (voir LISEZMOI)
    'socket'    => '',       // inutile sous Windows
    'admin'     => 'root',   // compte d'administration du SGBD
    'admin_mdp' => '',
    // mot de passe de kinegest_app, si vous l'avez changé (bonus)
    'app_mdp'   => 'App-Kineo-2026',
];
