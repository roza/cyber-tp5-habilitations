<?php
// Exécute un fichier SQL avec le compte d'administration :
//   php appliquer.php sql/correction.sql
// (équivalent : copier-coller le fichier dans phpMyAdmin, onglet SQL)
require __DIR__ . '/lib/commun.php';

$fichier = $argv[1] ?? 'sql/correction.sql';
try {
    $n = executerFichier(connexionAdmin(), $fichier);
    echo "$fichier : $n instruction(s) exécutée(s).\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Erreur dans $fichier : " . $e->getMessage() . "\n");
    exit(1);
}
