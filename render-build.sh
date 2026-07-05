#!/bin/bash

# Script de déploiement pour Render
# Ce script s'exécute après le clone du repo

set -e

echo "🚀 Déploiement FoodExpress Dakar sur Render..."

# 1. Créer les répertoires d'upload
echo "📁 Création des répertoires..."
mkdir -p assets/uploads/plats
mkdir -p assets/uploads/avatars
chmod 755 assets/uploads
chmod 755 assets/uploads/plats
chmod 755 assets/uploads/avatars

# 2. Importer la base de données (optionnel, à faire via Render Dashboard)
echo "✅ Configuration initiale terminée"
echo "⚠️ Important: Importez la base de données (database/foodexpress_dakar.sql) via le Dashboard Render"
echo "⚠️ Configurez les identifiants SMTP dans les variables d'environnement"
