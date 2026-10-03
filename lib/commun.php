<?php
// Configuration et connexion, communes à installer.php, appliquer.php et
// verifier.php. Ordre de priorité : variables d'environnement (CI, Codespaces),
// puis config.local.php, puis les valeurs par défaut de WAMP.

function config(): array
{
    $c = ['hote' => 'localhost', 'port' => 3306, 'socket' => '', 'admin' => 'root', 'admin_mdp' => '',
          'app_mdp' => 'App-Kineo-2026'];
    $local = __DIR__ . '/../config.local.php';
    if (is_file($local)) {
        $c = array_merge($c, require $local);
    }
    $env = ['hote' => 'DB_HOST', 'port' => 'DB_PORT', 'socket' => 'DB_SOCKET',
            'admin' => 'DB_ADMIN', 'admin_mdp' => 'DB_ADMIN_PASS', 'app_mdp' => 'APP_PASS'];
    foreach ($env as $cle => $variable) {
        if (getenv($variable) !== false) {
            $c[$cle] = getenv($variable);
        }
    }
    return $c;
}

function connexion(string $utilisateur, string $mdp, string $base = ''): PDO
{
    $c = config();
    $dsn = $c['socket'] !== ''
        ? "mysql:unix_socket={$c['socket']}"
        : "mysql:host={$c['hote']};port={$c['port']}";
    $dsn .= ($base !== '' ? ";dbname=$base" : '') . ';charset=utf8mb4';
    return new PDO($dsn, $utilisateur, $mdp, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

function connexionAdmin(): PDO
{
    $c = config();
    return connexion($c['admin'], $c['admin_mdp']);
}

/** Exécute un fichier SQL, instruction par instruction (séparateur : « ; » en fin de ligne). */
function executerFichier(PDO $pdo, string $fichier): int
{
    $n = 0;
    foreach (preg_split('/;\s*$/m', file_get_contents($fichier)) as $instruction) {
        if (trim(preg_replace('/^\s*--.*$/m', '', $instruction)) !== '') {
            $pdo->exec($instruction);
            $n++;
        }
    }
    return $n;
}
