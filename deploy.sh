#!/bin/bash
# Script de déploiement pour l'application Recettes_Application sur o2switch
# Ce script met à jour le code sur le serveur distant via SSH et git pull

# Variables à personnaliser si besoin
USER="jutx2682"
HOST="brome.o2switch.net"
REMOTE_PATH="public_html/Recettes_Application"

# Commande de déploiement
ssh "$USER@$HOST" "cd $REMOTE_PATH && git pull origin main"

# Message de fin
if [ $? -eq 0 ]; then
  echo "Déploiement terminé avec succès !"
else
  echo "Erreur lors du déploiement. Vérifiez la connexion SSH ou les droits Git."
  exit 1
fi
