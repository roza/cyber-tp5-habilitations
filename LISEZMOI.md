# TP5 — Auditer et corriger les habilitations

**BTS SIO 2ᵉ année · option SLAM · Bloc 3 — cybersécurité**

La base **KinéGest** du cabinet Kinéo tourne sur MySQL / MariaDB, avec quatre
comptes de connexion. Leurs droits ont été donnés « pour que ça marche », au
fil des années. Votre travail : les **auditer**, écrire leur **correction** dans
`sql/correction.sql`, et le **prouver** avec `verifier.php`. L'énoncé complet
est dans le PDF du TP.

## 1. Récupérer le projet

1. Sur la page de ce dépôt : **Use this template** › **Create a new repository**.
2. Gardez le nom `cyber-tp5-habilitations`, cochez **Private**.
3. **Settings** › **Collaborators** : ajoutez `roza`.
4. Clonez **votre** dépôt et ouvrez le **dossier** dans VS Code.

## 2. Avec WAMP (en classe)

**Démarrez WAMP** (icône verte). Un clic gauche sur l'icône, menu **MariaDB** ou
**MySQL** : le **port** utilisé y est indiqué (souvent 3306, parfois 3308).

Copiez `config.exemple.php` en **`config.local.php`** et adaptez le port, et le
mot de passe de `root` si vous en avez mis un.

PHP en ligne de commande est fourni par WAMP. S'il n'est pas dans le `PATH`,
utilisez son chemin complet (la version peut différer) :

```powershell
C:\wamp64\bin\php\php8.3.14\php.exe installer.php
```

Les trois commandes du TP :

```sh
php installer.php                    # (ré)installe la base et les comptes, état initial
php appliquer.php sql/correction.sql # applique votre correction
php verifier.php                     # vérifie : 10 contrôles [OK] / [KO]
```

Vous pouvez aussi exécuter `correction.sql` dans **phpMyAdmin** (onglet SQL),
connecté en `root`.

## 3. Sans WAMP : GitHub Codespaces

Depuis la page de **votre** dépôt : **Code** › **Codespaces** › **Create
codespace on main**. Au premier lancement, MariaDB s'installe (quelques
minutes). Les mêmes commandes fonctionnent ensuite dans le terminal, sans
`config.local.php`.

*Pensez à arrêter le Codespace en fin de séance : le quota gratuit est mensuel.*

## 4. L'intégration continue

À chaque `git push`, GitHub réinstalle l'état initial, applique **votre**
`sql/correction.sql` et lance `verifier.php` (onglet **Actions**). **Au départ,
la vérification échoue : c'est normal.** Le TP est terminé quand elle est verte.

## Les comptes

| Compte | Mot de passe | Usage |
|---|---|---|
| `kinegest_app` | `App-Kineo-2026` | connexion de l'application web |
| `test` | `test` | installé par le prestataire |
| `stagiaire_moreau` | `Stage-2026` | stagiaire, du 2 mai au 30 juin 2026 |
| `secretariat` | `Accueil-2026` | accueil du cabinet |

*Ces mots de passe sont publiés ici pour le TP. Dans un vrai projet, aucun mot
de passe n'a sa place dans un dépôt.*

## Organisation

```
sql/base.sql          la base kinegest (Patient, Seance) et ses données
sql/etat-initial.sql  les comptes tels qu'ils sont aujourd'hui — ne pas modifier
sql/correction.sql    VOTRE correction
installer.php         (ré)installe base.sql + etat-initial.sql
appliquer.php         exécute un fichier SQL avec le compte d'administration
verifier.php          les 10 contrôles de la politique de sécurité
```
