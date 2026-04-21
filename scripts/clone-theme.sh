#!/bin/bash

#==============================================================================
# Clonador del Bonilla Stack (Basado en DBarricada)
# Este script crea una copia limpia del tema actual para un nuevo proyecto.
#==============================================================================

echo "==============================================="
echo "  🚀 Clonador del Bonilla Stack                "
echo "==============================================="

# Solicitar datos del nuevo tema
read -p "1. Nombre del nuevo tema (ej. Radio Mundial): " THEME_NAME
read -p "2. Slug del directorio (ej. radio-mundial): " THEME_SLUG

if [ -z "$THEME_NAME" ] || [ -z "$THEME_SLUG" ]; then
    echo "❌ Error: Debes proporcionar un nombre y un slug."
    exit 1
fi

# Definir rutas
CURRENT_DIR=$(pwd)
THEMES_DIR=$(dirname "$CURRENT_DIR")
NEW_THEME_DIR="$THEMES_DIR/$THEME_SLUG"

if [ -d "$NEW_THEME_DIR" ]; then
    echo "❌ Error: El directorio $NEW_THEME_DIR ya existe. Borra la carpeta o elige otro slug."
    exit 1
fi

echo -e "\n📦 Copiando archivos hacia: wp-content/themes/$THEME_SLUG..."

# Copiar excluyendo carpetas de dependencias y builds
rsync -a --exclude='node_modules' \
         --exclude='vendor' \
         --exclude='dist' \
         --exclude='.git' \
         --exclude='.env' \
         --exclude='.gemini' \
         "$CURRENT_DIR/" "$NEW_THEME_DIR/"

# Crear un .env limpio
if [ -f "$CURRENT_DIR/.env.example" ]; then
    cp "$CURRENT_DIR/.env.example" "$NEW_THEME_DIR/.env"
fi

echo "🔧 Renombrando referencias internas..."

# Ir al nuevo directorio
cd "$NEW_THEME_DIR" || exit

# 1. Actualizar style.css (Cabecera de WordPress)
sed -i "s/Theme Name: .*/Theme Name: $THEME_NAME/" style.css
sed -i "s/Text Domain: .*/Text Domain: $THEME_SLUG/" style.css
sed -i "s/Description: .*/Description: Tema basado en Bonilla Stack para $THEME_NAME/" style.css

# 2. Actualizar package.json
sed -i "s/\"name\": \".*\"/\"name\": \"$THEME_SLUG\"/" package.json

# 3. Actualizar composer.json (Reemplaza ibonilla/loquesea por ibonilla/slug)
sed -i "s/\"name\": \"ibonilla\/.*\"/\"name\": \"ibonilla\/$THEME_SLUG\"/" composer.json

# 4. Actualizar tamaños de imágenes en Theme.php (de dbarricada-hero a slug-hero)
if [ -f "app/Setup/Theme.php" ]; then
    sed -i "s/dbarricada-/$THEME_SLUG-/g" app/Setup/Theme.php
fi

echo -e "\n✅ ¡Clonación completada con éxito!"
echo "==============================================="
echo "Tu nuevo tema está listo en: wp-content/themes/$THEME_SLUG"
echo ""
echo "👉 Siguientes pasos recomendados:"
echo "   1. cd ../$THEME_SLUG"
echo "   2. composer install"
echo "   3. npm install"
echo "   4. Revisa tailwind.config.js para cambiar los colores de tu marca."
echo "   5. npm run dev"
echo "==============================================="
