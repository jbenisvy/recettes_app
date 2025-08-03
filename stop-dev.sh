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

info "🛑 Arrêt de l'environnement de développement..."

# Arrêt du serveur Apache
info "Arrêt d'Apache..."
sudo systemctl stop apache2
success "Apache arrêté !"

# Vérifier si MySQL doit être arrêté
read -p "Voulez-vous arrêter MySQL aussi ? (o/N) " response
if [[ "$response" =~ ^([oO][uU][iI]|[oO])$ ]]; then
    info "Arrêt de MySQL..."
    sudo systemctl stop mysql
    success "MySQL arrêté"
else
    info "MySQL reste actif"
fi

success "Environnement de développement arrêté !"
