<?php
// (Ré)installe la base kinegest et les comptes dans leur état initial.
//   php installer.php
// À relancer pour repartir de zéro, puis réappliquer sa correction.
require __DIR__ . '/lib/commun.php';

try {
    $pdo = connexionAdmin();
    executerFichier($pdo, __DIR__ . '/sql/base.sql');
    executerFichier($pdo, __DIR__ . '/sql/etat-initial.sql');
    echo "Base kinegest et comptes installés dans leur état initial.\n";
    echo "SGBD : " . $pdo->query('SELECT VERSION()')->fetchColumn() . "\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Installation impossible : " . $e->getMessage() . "\n"
        . "Vérifiez config.local.php (voir LISEZMOI.md).\n");
    exit(1);
}
