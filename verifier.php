<?php
// Vérifie les habilitations de KinéGest après votre correction :
//   php verifier.php
// Chaque ligne [KO] indique ce qui ne respecte pas encore la politique de
// sécurité décrite dans l'énoncé.
require __DIR__ . '/lib/commun.php';

define('APP', ['kinegest_app', config()['app_mdp']]);
const SECRETARIAT = ['secretariat', 'Accueil-2026'];
const CRUD = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];

$resultats = [];

function verifier(string $libelle, callable $test): void
{
    global $resultats;
    try {
        $message = $test();           // null = réussi, sinon l'explication
    } catch (PDOException $e) {
        $message = expliquer($e);
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
    $resultats[] = $message === null;
    echo ($message === null ? '[OK] ' : '[KO] ') . $libelle . "\n";
    if ($message !== null) {
        echo "     -> $message\n";
    }
}

/** Traduit les erreurs SGBD les plus fréquentes en une phrase compréhensible. */
function expliquer(PDOException $e): string
{
    return match ((int) ($e->errorInfo[1] ?? 0)) {
        1141    => "ce compte n'existe pas (vérifiez le nom et l'hôte)",
        1045    => "connexion refusée : le compte n'existe pas pour cet hôte, ou le mot de passe a changé",
        1146    => "table ou vue introuvable : " . $e->getMessage(),
        1142, 1143 => "accès refusé : " . $e->getMessage(),
        default => $e->getMessage(),
    };
}

/** Le test réussit si l'instruction est REFUSÉE par le SGBD. */
function estRefuse(PDO $pdo, string $sql): bool
{
    try {
        $pdo->exec($sql);
        return false;
    } catch (PDOException $e) {
        return true;
    }
}

/** Droits d'un compte : [objet => [privilèges]] et présence de GRANT OPTION. */
function droits(PDO $admin, string $user, string $host): array
{
    $par = [];
    $grantOption = false;
    $q = $admin->query("SHOW GRANTS FOR " . $admin->quote($user) . "@" . $admin->quote($host));
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $ligne) {
        if (!preg_match('/^GRANT (.+?) ON (\S+) TO /', $ligne, $m)) {
            continue;                 // rôle, PROXY…
        }
        $objet = str_replace('`', '', $m[2]);
        $privs = array_map('trim', explode(',', strtoupper($m[1])));
        $par[$objet] = array_merge($par[$objet] ?? [], $privs);
        $grantOption = $grantOption || str_contains($ligne, 'WITH GRANT OPTION');
    }
    return [$par, $grantOption];
}

echo "Vérification des habilitations de KinéGest\n\n";

try {
    $admin = connexionAdmin();
} catch (PDOException $e) {
    fwrite(STDERR, "Connexion d'administration impossible : " . $e->getMessage() . "\n");
    exit(1);
}

$comptes = fn(string $user) => $admin
    ->query("SELECT host FROM mysql.user WHERE user = " . $admin->quote($user))
    ->fetchAll(PDO::FETCH_COLUMN);

// --- Politique générale -----------------------------------------------------
verifier("Aucun compte n'accepte les connexions de toutes les machines (hôte '%')", function () use ($admin) {
    $ouverts = $admin->query("SELECT CONCAT(user, '@', host) FROM mysql.user WHERE host = '%'")
        ->fetchAll(PDO::FETCH_COLUMN);
    return $ouverts ? 'comptes en %: ' . implode(', ', $ouverts) : null;
});

verifier("Le compte 'test' du prestataire n'existe plus", function () use ($comptes) {
    return $comptes('test') ? "le compte test existe encore" : null;
});

verifier("Le compte de la stagiaire partie est supprimé ou verrouillé", function () use ($admin, $comptes) {
    foreach ($comptes('stagiaire_moreau') as $host) {
        $def = $admin->query("SHOW CREATE USER 'stagiaire_moreau'@" . $admin->quote($host))->fetchColumn();
        if (!str_contains(strtoupper($def), 'ACCOUNT LOCK')) {
            return "stagiaire_moreau@$host est encore utilisable";
        }
    }
    return null;
});

// --- Le compte de l'application ---------------------------------------------
verifier("kinegest_app n'existe que pour le serveur local (localhost)", function () use ($comptes) {
    $hosts = $comptes('kinegest_app');
    return $hosts === ['localhost'] ? null : 'hôtes actuels : ' . ($hosts ? implode(', ', $hosts) : 'aucun');
});

verifier("kinegest_app a exactement SELECT, INSERT, UPDATE, DELETE sur kinegest", function () use ($admin) {
    [$par, ] = droits($admin, 'kinegest_app', 'localhost');
    $base = $par['kinegest.*'] ?? [];
    sort($base);
    $attendu = CRUD;
    sort($attendu);
    if ($base !== $attendu) {
        return 'droits sur kinegest.* : ' . ($base ? implode(', ', $base) : 'aucun');
    }
    foreach ($par as $objet => $privs) {
        if ($objet !== 'kinegest.*' && $privs !== ['USAGE']) {
            return "droits en trop sur $objet : " . implode(', ', $privs);
        }
    }
    return null;
});

verifier("kinegest_app ne peut pas distribuer de droits (pas de GRANT OPTION)", function () use ($admin) {
    [, $grantOption] = droits($admin, 'kinegest_app', 'localhost');
    return $grantOption ? 'WITH GRANT OPTION est toujours présent' : null;
});

verifier("kinegest_app lit et modifie les données", function () {
    $app = connexion(APP[0], APP[1], 'kinegest');
    $app->beginTransaction();
    $app->exec("INSERT INTO Patient (nom, prenom, ville, dateNaissance) VALUES ('Essai', 'Test', 'Blois', '2000-01-01')");
    $app->exec("UPDATE Patient SET ville = 'Tours' WHERE nom = 'Essai'");
    $app->exec("DELETE FROM Patient WHERE nom = 'Essai'");
    $app->rollBack();
    $n = $app->query('SELECT COUNT(*) FROM Patient')->fetchColumn();
    return $n > 0 ? null : 'aucun patient lu';
});

verifier("kinegest_app ne peut pas modifier la structure (CREATE, DROP)", function () {
    $app = connexion(APP[0], APP[1], 'kinegest');
    if (!estRefuse($app, 'CREATE TABLE intrus (id INT)')) {
        $app->exec('DROP TABLE IF EXISTS intrus');
        return 'CREATE TABLE est accepté';
    }
    return estRefuse($app, 'DROP TABLE Seance') ? null : 'DROP TABLE est accepté (base à réinstaller)';
});

// --- Le compte de l'accueil -------------------------------------------------
verifier("secretariat consulte l'agenda par la vue kinegest.v_agenda, sans compte rendu", function () {
    $sec = connexion(SECRETARIAT[0], SECRETARIAT[1], 'kinegest');
    $ligne = $sec->query('SELECT * FROM v_agenda LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($ligne === false) {
        return 'la vue v_agenda est vide';
    }
    $colonnes = array_map('strtolower', array_keys($ligne));
    if (in_array('compterendu', $colonnes, true)) {
        return 'la vue expose le compte rendu de séance';
    }
    return in_array('dateheure', $colonnes, true) ? null : 'la vue ne contient pas dateHeure';
});

verifier("secretariat n'a accès ni aux comptes rendus ni aux tables", function () {
    $sec = connexion(SECRETARIAT[0], SECRETARIAT[1], 'kinegest');
    if (!estRefuse($sec, 'SELECT compteRendu FROM Seance')) {
        return 'les comptes rendus sont lisibles';
    }
    return estRefuse($sec, 'SELECT * FROM Patient') ? null : 'la table Patient est lisible';
});

// --- Bilan ------------------------------------------------------------------
$ok = count(array_filter($resultats));
echo "\n$ok / " . count($resultats) . " vérifications réussies.\n";

$c = config();
if ($c['admin'] === 'root' && $c['admin_mdp'] === '') {
    echo "\n[!] Votre compte root n'a pas de mot de passe. Sur un poste de développement\n"
       . "    isolé, c'est toléré ; sur un serveur, c'est la première chose à corriger.\n";
}
exit($ok === count($resultats) ? 0 : 1);
