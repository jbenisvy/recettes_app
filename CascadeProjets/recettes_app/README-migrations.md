# Système de migration SQL automatisé avec sauvegarde

Ce projet contient un système simple pour appliquer automatiquement les scripts de migration SQL sur votre base MySQL, avec sauvegarde automatique avant chaque migration.

## 1. Organisation recommandée

```
recettes-app/
├── migrations/                # Placez ici tous vos scripts .sql
│   ├── 2025_04_30_add_colonne.sql
│   └── ...
├── backups/                   # Les sauvegardes de la base seront créées ici
├── run-migrations.sh          # Script d'application/sauvegarde automatisé
├── migrations_appliquees.txt  # Historique des migrations appliquées
├── ...
```

## 2. Configuration du script

Ouvrez `run-migrations.sh` et personnalisez les variables en haut du fichier :
- `DB_USER` : votre identifiant MySQL
- `DB_PASS` : votre mot de passe MySQL
- `DB_NAME` : le nom de votre base de données

## 3. Utilisation

1. **Ajoutez vos scripts SQL** dans le dossier `migrations/` (ex: `2025_05_01_ajout_table.sql`)
2. **Connectez-vous en SSH** sur votre hébergement o2switch :
   ```bash
   ssh votre_user@votre_domaine
   cd ~/recettes-app
   ```
3. **Rendez le script exécutable** :
   ```bash
   chmod +x run-migrations.sh
   ```
4. **Lancez le script** :
   ```bash
   ./run-migrations.sh
   ```
   - Une sauvegarde complète de la base est créée dans `backups/` avant toute migration.
   - Chaque script `.sql` non encore appliqué est exécuté, puis archivé dans `migrations_appliquees.txt`.

## 4. Bonnes pratiques

- **Testez toujours vos migrations en local avant de les appliquer en production.**
- **Gardez une copie de vos sauvegardes** (backups) dans un endroit sûr.
- **Nettoyez régulièrement le dossier backups/** si besoin (pour éviter d’occuper trop d’espace disque).
- **Versionnez** vos scripts SQL et ce script shell (git recommandé).

## 5. Restauration d’une sauvegarde

Pour restaurer une sauvegarde, utilisez :
```bash
mysql -u DB_USER -pDB_PASS DB_NAME < backups/backup_nomdelabase_YYYYMMDD_HHMMSS.sql
```

---

Pour toute question ou adaptation avancée (compression, rotation, rollback...), contactez votre assistant Cascade.
