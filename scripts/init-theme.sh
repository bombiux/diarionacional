#!/bin/bash

#==============================================================================
# Inicializador del Bonilla Stack
# Este script renombra el tema actual (in-place) para un nuevo proyecto.
# Ideal para usar después de clonar el Starter Kit desde GitHub.
#==============================================================================

echo "==============================================="
echo "  🚀 Inicializador del Bonilla Stack           "
echo "==============================================="

# Solicitar datos del nuevo tema
read -p "1. Nombre del nuevo tema (ej. Radio Mundial): " THEME_NAME
read -p "2. Slug del directorio/tema (ej. radio-mundial): " THEME_SLUG

if [ -z "$THEME_NAME" ] || [ -z "$THEME_SLUG" ]; then
    echo "❌ Error: Debes proporcionar un nombre y un slug."
    exit 1
fi

CURRENT_DIR=$(pwd)
CURRENT_SLUG=$(basename "$CURRENT_DIR")

echo "🔧 Renombrando referencias internas en el directorio actual..."

# 1. Actualizar style.css (Cabecera de WordPress)
if [ -f "style.css" ]; then
    sed -i "s/Theme Name: .*/Theme Name: $THEME_NAME/" style.css
    sed -i "s/Text Domain: .*/Text Domain: $THEME_SLUG/" style.css
    sed -i "s/Description: .*/Description: Tema basado en Bonilla Stack para $THEME_NAME/" style.css
fi

# 2. Actualizar package.json
if [ -f "package.json" ]; then
    sed -i "s/\"name\": \".*\"/\"name\": \"$THEME_SLUG\"/" package.json
fi

# 3. Actualizar composer.json
if [ -f "composer.json" ]; then
    sed -i "s/\"name\": \"ibonilla\/.*\"/\"name\": \"ibonilla\/$THEME_SLUG\"/" composer.json
fi

# 4. Actualizar prefijos de tamaños de imágenes en Theme.php
if [ -f "app/Setup/Theme.php" ]; then
    # Busca el prefijo actual (ej. dbarricada-) y lo reemplaza por el nuevo slug
    # Busca el patrón add_image_size('PREFIJO-loquesea'
    OLD_IMAGE_PREFIX=$(grep -oP "(?<=add_image_size\(')[a-z0-9-]+(?=-hero)" app/Setup/Theme.php | head -1)
    if [ ! -z "$OLD_IMAGE_PREFIX" ]; then
        sed -i "s/${OLD_IMAGE_PREFIX}-/$THEME_SLUG-/g" app/Setup/Theme.php
    fi
fi

# Auto-destrucción opcional (comentada por seguridad, puedes descomentarla si quieres)
# rm -- "$0"

echo -e "\n✅ ¡Inicialización completada con éxito!"
echo "==============================================="
if [ "$CURRENT_SLUG" != "$THEME_SLUG" ]; then
    echo "⚠️  Nota: La carpeta actual se llama '$CURRENT_SLUG'."
    echo "   Recuerda renombrarla a '$THEME_SLUG' si es necesario."
fi
echo ""
echo "👉 Siguientes pasos recomendados:"
echo "   1. composer install"
echo "   2. npm install"
echo "   3. Revisa tailwind.config.js para cambiar los colores."
echo "   4. npm run dev"
echo "==============================================="
