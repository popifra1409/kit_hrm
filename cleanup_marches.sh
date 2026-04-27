#!/bin/bash

echo "🗑️ NETTOYAGE COMPLET - PROCUREMENT"
echo "=================================="

# ========================================
# 1. RESOURCES FILAMENT
# ========================================
echo ""
echo "📂 1. Suppression des Resources..."

find app/Filament/Resources/ -type f -iname "*procurement*" -delete
find app/Filament/Resources/ -type d -iname "*procurement*" -exec rm -rf {} + 2>/dev/null

echo "  ✓ Resources supprimés"

# ========================================
# 2. WIDGETS / DASHBOARDS
# ========================================
echo ""
echo "📊 2. Suppression des Widgets/Dashboards..."

find app/Filament/Widgets/ -type f -iname "*procurement*" -delete
find app/Filament/Pages/ -type f -iname "*procurement*" -delete

echo "  ✓ Widgets/Dashboards supprimés"

# ========================================
# 3. MODÈLES
# ========================================
echo ""
echo "🏗️ 3. Suppression des Modèles..."

find app/Models/ -type f -iname "*procurement*" -delete

echo "  ✓ Modèles supprimés"

# ========================================
# 4. VUES BLADE
# ========================================
echo ""
echo "👁️ 4. Suppression des vues Blade..."

find resources/views/ -type f -iname "*procurement*" -delete
find resources/views/ -type d -iname "*procurement*" -exec rm -rf {} + 2>/dev/null

echo "  ✓ Vues Blade supprimées"

# ========================================
# 5. SEEDERS
# ========================================
echo ""
echo "🌱 5. Suppression des Seeders..."

find database/seeders/ -type f -iname "*procurement*" -delete

echo "  ✓ Seeders supprimés"

# ========================================
# 6. MIGRATIONS
# ========================================
echo ""
echo "📋 6. Liste des Migrations à supprimer manuellement..."

find database/migrations/ -type f -iname "*procurement*" | while read file; do
    echo "  → $file"
done

echo ""
echo "✅ NETTOYAGE TERMINÉ"
echo ""
echo "⚠️ PROCHAINES ÉTAPES MANUELLES :"
echo "  1. Vérifier la liste des migrations ci-dessus"
echo "  2. Supprimer les migrations avec: rm database/migrations/*procurement*"
echo "  3. Créer une migration pour supprimer les tables"
echo "  4. Exécuter: php artisan migrate"
echo "  5. Nettoyer le cache: php artisan optimize:clear"