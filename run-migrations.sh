#!/bin/bash

# === CONFIGURATION À PERSONNALISER ===
DB_USER="jutx2682_jbenisvy_Recettes"
DB_PASS="yeelOCALL!stik"
DB_NAME="jutx2682_jbenisvy_recettes"
MIGRATIONS_DIR="./migrations"
HISTORIQUE="./migrations_appliquees.txt"
BACKUP_DIR="./backups"

# Créer le dossier de backup s'il n'existe pas
mkdir -p "$BACKUP_DIR"

# Sauvegarde avant chaque session de migration
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/backup_${DB_NAME}_$DATE.sql"
echo "Sauvegarde de la base avant migration : $BACKUP_FILE"
mysqldump -h brome.o2switch.net -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
if [ $? -eq 0 ]; then
    echo "Sauvegarde OK"
else
    echo "Erreur lors de la sauvegarde ! Migration annulée."
    exit 1
fi

# S'assurer que le fichier d'historique existe
if [ ! -f "$HISTORIQUE" ]; then
    touch "$HISTORIQUE"
fi

echo "Liste des fichiers SQL détectés :"
ls -l "$MIGRATIONS_DIR"/*.sql

echo "DEBUG - Fichiers SQL détectés :"
ls -l $MIGRATIONS_DIR/*.sql

find "$MIGRATIONS_DIR" -maxdepth 1 -name "*.sql" | while read file; do
    if ! grep -Fxq "$(basename "$file")" "$HISTORIQUE"; then
        echo "Application de $(basename "$file") ..."
        mysql -h mysql1.o2switch.net -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$file"
        if [ $? -eq 0 ]; then
            echo "$(basename "$file")" >> "$HISTORIQUE"
            echo "OK"
        else
            echo "Erreur lors de l'application de $(basename "$file")"
            exit 1
        fi
    fi
done

echo "Toutes les migrations sont à jour."
