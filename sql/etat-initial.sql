-- Les comptes de KinéGest TELS QU'ILS SONT AUJOURD'HUI. Ne pas modifier :
-- votre travail consiste à les auditer, puis à écrire correction.sql.
-- Exécuté par installer.php avec le compte d'administration.

DROP USER IF EXISTS 'kinegest_app'@'%', 'kinegest_app'@'localhost',
                    'test'@'%', 'test'@'localhost',
                    'stagiaire_moreau'@'%', 'stagiaire_moreau'@'localhost',
                    'secretariat'@'%', 'secretariat'@'localhost';

-- Compte de connexion de l'application web KinéGest
CREATE USER 'kinegest_app'@'%' IDENTIFIED BY 'App-Kineo-2026';
GRANT ALL PRIVILEGES ON kinegest.* TO 'kinegest_app'@'%' WITH GRANT OPTION;

-- Compte créé par le prestataire lors de l'installation
CREATE USER 'test'@'%' IDENTIFIED BY 'test';
GRANT ALL PRIVILEGES ON kinegest.* TO 'test'@'%';

-- Compte d'Angèle Moreau, stagiaire du 2 mai au 30 juin 2026
CREATE USER 'stagiaire_moreau'@'%' IDENTIFIED BY 'Stage-2026';
GRANT SELECT, INSERT, UPDATE ON kinegest.* TO 'stagiaire_moreau'@'%';

-- Compte de l'accueil, utilisé par la secrétaire et par les remplaçants
CREATE USER 'secretariat'@'localhost' IDENTIFIED BY 'Accueil-2026';
GRANT SELECT ON kinegest.* TO 'secretariat'@'localhost';
