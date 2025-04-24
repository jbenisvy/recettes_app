#!/bin/bash

# Fonction pour afficher les messages d'erreur en rouge
error() {
    echo -e "\033[31m❌ $1\033[0m"
}

# Fonction pour afficher les messages de succès en vert
success() {
    echo -e "\033[32m✅ $1\033[0m"
}

# Fonction pour afficher les messages d'info en bleu
info() {
    echo -e "\033[34m🔵 $1\033[0m"
}

# Vérifier si nous sommes dans le bon répertoire
if [ ! -d "public" ] || [ ! -d "config" ]; then
    error "Ce script doit être exécuté depuis le répertoire racine du projet Symfony (où se trouvent les dossiers public/ et config/)"
    exit 1
fi

info "🚀 Démarrage de l'environnement de développement..."

# Arrêt des services existants
info "Arrêt d'Apache pour redémarrage propre..."
sudo systemctl stop apache2 2>/dev/null

# Vérification que MySQL est démarré
info "Vérification de MySQL..."
if ! systemctl is-active --quiet mysql; then
    info "Démarrage de MySQL..."
    if ! sudo systemctl start mysql; then
        error "Impossible de démarrer MySQL. Veuillez vérifier le service."
        exit 1
    fi
fi

# Démarrage d'Apache
info "Démarrage d'Apache..."
if ! sudo systemctl start apache2; then
    error "Impossible de démarrer Apache. Veuillez vérifier le service."
    exit 1
fi
success "Apache démarré !"

# Vérifier si le port 8000 est déjà utilisé
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null ; then
    error "Le port 8000 est déjà utilisé. Arrêt du processus..."
    sudo lsof -ti:8000 | xargs kill -9 2>/dev/null
fi

# Vérifier si Symfony est installé
if ! command -v symfony &> /dev/null; then
    error "La commande 'symfony' n'est pas installée. Veuillez installer Symfony CLI."
    exit 1
fi

# Démarrage du serveur Symfony
info "Démarrage du serveur Symfony..."
if symfony server:start -d; then
    success "Environnement de développement prêt!"
    success "Application accessible sur: http://localhost:8000"
    info "Pour arrêter le serveur, utilisez: symfony server:stop"
else
    error "Erreur lors du démarrage du serveur Symfony"
    exit 1
fi
