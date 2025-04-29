

#!/bin/bash

# === GIT : Validation et push ===
echo "[1/4] Vérification des modifications locales :"
git status

echo "[2/4] Ajout de tous les fichiers modifiés :"
git add .

echo "[3/4] Commit des modifications :"
GIT_MSG="Refonte sécurité, responsive mobile, debug liste de courses, corrections diverses"
git commit -m "$GIT_MSG"

echo "[4/4] Push sur GitHub :"
git push

# === CONFIGURATION ===
REMOTE_USER="jutx2682"  # Ton identifiant SSH o2switch
REMOTE_HOST="brome.o2switch.net"  # Adresse SSH de ton hébergement
REMOTE_DIR="/home2/jutx2682/public_html/Recettes_Application/public"
LOCAL_DIR="$(pwd)/public"

# === SYNCHRONISATION ===
rsync -avz --delete \
  --exclude=".git/" \
  --exclude=".DS_Store" \
  "$LOCAL_DIR/" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/"

echo "Déploiement terminé !"
