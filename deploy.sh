#!/bin/bash
# Script de déploiement automatisé pour l'application Recettes_Application sur o2switch
# 1. Ajoute et commit les modifications locales
# 2. Push sur GitHub
# 3. Déploie via SSH et git pull sur le serveur distant

# Variables à personnaliser si besoin
USER="jutx2682"
HOST="brome.o2switch.net"
REMOTE_PATH="public_html/Recettes_Application"
BRANCH="main"

# Étape 1 : Ajouter tous les fichiers modifiés et non suivis
cd "$(dirname "$0")" || exit 1

git add .

# Étape 2 : Commit si besoin
if ! git diff --cached --quiet; then
    COMMIT_MSG="Déploiement automatique du $(date '+%Y-%m-%d %H:%M:%S')"
    git commit -m "$COMMIT_MSG"
    echo "Commit effectué : $COMMIT_MSG"
else
    echo "Aucun changement à committer."
fi

# Étape 3 : Push sur GitHub
if git log origin/$BRANCH..HEAD --oneline | grep .; then
    git push origin $BRANCH
    echo "Modifications poussées sur GitHub."
else
    echo "Aucun nouveau commit à pousser."
fi

# Étape 4 : Déploiement sur le serveur distant
ssh "$USER@$HOST" "cd $REMOTE_PATH && git pull origin $BRANCH"

if [ $? -eq 0 ]; then
  echo "Déploiement terminé avec succès !"
else
  echo "Erreur lors du déploiement. Vérifiez la connexion SSH ou les droits Git."
  exit 1
fi
