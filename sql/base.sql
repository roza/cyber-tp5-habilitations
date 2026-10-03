-- Base KinéGest (version simplifiée) — MySQL / MariaDB
-- Exécuté par installer.php avec le compte d'administration.
DROP DATABASE IF EXISTS kinegest;
CREATE DATABASE kinegest CHARACTER SET utf8mb4;
USE kinegest;

CREATE TABLE Patient (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(80) NOT NULL,
    prenom         VARCHAR(80) NOT NULL,
    ville          VARCHAR(80) NOT NULL,
    dateNaissance  DATE        NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Seance (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    idPatient    INT          NOT NULL,
    dateHeure    DATETIME     NOT NULL,
    compteRendu  TEXT         NOT NULL,   -- donnée de santé
    FOREIGN KEY (idPatient) REFERENCES Patient(id)
) ENGINE=InnoDB;

INSERT INTO Patient (nom, prenom, ville, dateNaissance) VALUES
  ('Martin',    'Claire', 'Orleans', '1985-03-12'),
  ('Bernard',   'Louis',  'Tours',   '1972-11-02'),
  ('N''Diaye',  'Awa',    'Orleans', '1988-01-17'),
  ('D''Almeida','Joao',   'Tours',   '1965-09-30');

INSERT INTO Seance (idPatient, dateHeure, compteRendu) VALUES
  (1, '2026-10-05 09:00:00', 'Entorse cheville droite, rééducation proprioceptive, 2e séance.'),
  (2, '2026-10-05 10:00:00', 'Lombalgie chronique, renforcement du gainage.'),
  (3, '2026-10-06 14:30:00', 'Rééducation post-opératoire du genou gauche.'),
  (4, '2026-10-06 16:00:00', 'Tendinopathie de l''épaule, travail excentrique.');
