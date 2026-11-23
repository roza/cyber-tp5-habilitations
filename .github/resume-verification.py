#!/usr/bin/env python3
"""Résumé de verifier.php pour GitHub Actions.

    python3 .github/resume-verification.py verification.txt

Lit la sortie de verifier.php ([OK] / [KO]) et affiche le compteur :
  - dans le résumé de l'exécution ($GITHUB_STEP_SUMMARY), avec la liste des
    contrôles encore [KO] et leur explication ;
  - en annotation, en haut de la page de l'exécution ;
  - en statut du commit (« Habilitations — 6 / 10 contrôles réussis »), visible
    en cliquant sur ✓ / ✗ à côté du dernier commit, sur la page d'accueil du dépôt.
    Il faut pour cela GH_TOKEN et SHA dans l'environnement (voir verifier.yml).
"""
import json
import os
import sys
import urllib.request
from pathlib import Path

TITRE = 'Habilitations'
fichier = Path(sys.argv[1] if len(sys.argv) > 1 else 'verification.txt')

ok, ko = [], []                     # ko : [libellé, explication]
if fichier.exists():
    for ligne in fichier.read_text(encoding='utf-8', errors='replace').splitlines():
        if ligne.startswith('[OK] '):
            ok.append(ligne[5:])
        elif ligne.startswith('[KO] '):
            ko.append([ligne[5:], ''])
        elif ligne.strip().startswith('->') and ko:
            ko[-1][1] = ligne.strip()[2:].strip()
total = len(ok) + len(ko)

if total == 0:
    court, etat = "vérification non lancée : voir le journal", 'error'
    lignes = [f"### ⚠️ {TITRE} : vérification non lancée", "",
              "Ouvrez le journal du job : le plus souvent, une erreur SQL dans "
              "`sql/correction.sql` a arrêté l'étape « appliquer ».", ""]
else:
    court = f"{len(ok)} / {total} contrôles réussis"
    etat = 'success' if not ko else 'failure'
    lignes = [f"### {'✅' if not ko else '❌'} {TITRE} : {court}", ""]
    if ko:
        lignes += ["<details open><summary>Contrôles encore [KO]</summary>", ""]
        lignes += [f"- {libelle}" + (f" — *{pourquoi}*" if pourquoi else '') for libelle, pourquoi in ko]
        lignes += ["", "</details>", ""]

texte = '\n'.join(lignes) + '\n'
sortie = os.environ.get('GITHUB_STEP_SUMMARY')
if sortie:
    with open(sortie, 'a', encoding='utf-8') as s:
        s.write(texte)
print(texte)

# annotation en haut de la page de l'exécution
print(f"::{'notice' if etat == 'success' else 'warning'} title={TITRE}::{court}")

# statut du commit (sans bloquer le job si l'API refuse)
jeton, sha, depot = os.environ.get('GH_TOKEN'), os.environ.get('SHA'), os.environ.get('GITHUB_REPOSITORY')
if jeton and sha and depot:
    serveur = os.environ.get('GITHUB_SERVER_URL', 'https://github.com')
    lien = f"{serveur}/{depot}/actions/runs/{os.environ.get('GITHUB_RUN_ID', '')}"
    requete = urllib.request.Request(
        f"{os.environ.get('GITHUB_API_URL', 'https://api.github.com')}/repos/{depot}/statuses/{sha}",
        data=json.dumps({'state': etat, 'context': TITRE, 'description': court,
                         'target_url': lien}).encode(),
        headers={'Authorization': f'Bearer {jeton}', 'Accept': 'application/vnd.github+json'},
        method='POST')
    try:
        urllib.request.urlopen(requete, timeout=15)
    except Exception as e:      # droits insuffisants, réseau… : le résumé suffit
        print(f"::notice::statut de commit non publié ({e})")
