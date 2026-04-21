#!/usr/bin/env bash
#
# publish.sh — Script para aplicar cambios hechos en el servidor de producción
#
# Uso (desde el servidor):
#   ./scripts/publish.sh              → Aplica cambios PHP/Twig + limpia caché
#   ./scripts/publish.sh --composer   → También actualiza dependencias PHP
#   ./scripts/publish.sh --assets     → También recompila assets (requiere Node.js)
#

set -euo pipefail

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Funciones auxiliares
info()    { echo -e "${CYAN}▸${NC} $*"; }
success() { echo -e "${GREEN}✔${NC} $*"; }
warn()    { echo -e "${YELLOW}⚠${NC} $*"; }
error()   { echo -e "${RED}✘${NC} $*" >&2; }
header()  { echo -e "\n${BOLD}${CYAN}━━━ $* ━━━${NC}\n"; }

# Flags
RUN_COMPOSER=false
RUN_ASSETS=false

for arg in "$@"; do
    case "$arg" in
        --composer) RUN_COMPOSER=true ;;
        --assets)   RUN_ASSETS=true ;;
        --help|-h)
            echo "Uso: ./scripts/publish.sh [--composer] [--assets]"
            echo ""
            echo "  --composer   Ejecuta 'composer install --no-dev'"
            echo "  --assets     Recompila assets con npm (requiere Node.js instalado)"
            echo ""
            echo "Sin flags: solo limpia caché y verifica .env"
            exit 0
            ;;
    esac
done

cd "$THEME_DIR"

STEPS=3
[ "$RUN_COMPOSER" = true ] && STEPS=$((STEPS + 1))
[ "$RUN_ASSETS" = true ] && STEPS=$((STEPS + 1))
CURRENT=0

# ─── 1. Verificar entorno ───────────────────────────────────────
CURRENT=$((CURRENT + 1))
header "${CURRENT}/${STEPS} · Verificando entorno"

if [ ! -f "functions.php" ]; then
    error "No se encontró functions.php. ¿Estás en el directorio correcto del tema?"
    exit 1
fi
success "Directorio del tema: ${THEME_DIR}"

# ─── 2. Asegurar .env de producción ─────────────────────────────
CURRENT=$((CURRENT + 1))
header "${CURRENT}/${STEPS} · Verificando .env de producción"

if [ -f ".env" ]; then
    if grep -q "WP_ENV=development" .env; then
        warn "Se detectó WP_ENV=development. Corrigiendo a producción..."
        echo "WP_ENV=production" > .env
        success "Cambiado a WP_ENV=production"
    else
        success ".env ya está en modo producción"
    fi
else
    echo "WP_ENV=production" > .env
    success "Archivo .env creado con WP_ENV=production"
fi

# ─── 3. Recompilar assets (opcional) ────────────────────────────
if [ "$RUN_ASSETS" = true ]; then
    CURRENT=$((CURRENT + 1))
    header "${CURRENT}/${STEPS} · Recompilando assets"
    
    if command -v npm &> /dev/null; then
        npm install --production=false 2>&1
        npm run build
        success "Assets recompilados en dist/"
    else
        error "npm no está instalado en este servidor. Compila localmente y usa deploy.sh"
        exit 1
    fi
fi

# ─── 4. Actualizar dependencias PHP (opcional) ──────────────────
if [ "$RUN_COMPOSER" = true ]; then
    CURRENT=$((CURRENT + 1))
    header "${CURRENT}/${STEPS} · Actualizando dependencias PHP"
    
    if command -v composer &> /dev/null; then
        composer install --no-dev --optimize-autoloader --no-interaction 2>&1
        success "Dependencias PHP actualizadas"
    else
        error "Composer no está instalado en este servidor."
        exit 1
    fi
fi

# ─── 5. Limpiar caché ───────────────────────────────────────────
CURRENT=$((CURRENT + 1))
header "${CURRENT}/${STEPS} · Limpiando cachés"

# Limpiar caché de Twig (Timber)
TWIG_CACHE="../../cache/twig"
if [ -d "$TWIG_CACHE" ]; then
    rm -rf "${TWIG_CACHE:?}"/*
    success "Caché de Twig limpiada"
else
    info "No se encontró directorio de caché Twig (puede estar desactivada)"
fi

# Limpiar OPcache si está disponible
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache limpiada\n'; } else { echo 'OPcache no disponible\n'; }" 2>/dev/null || true
fi

success "Cachés procesadas"

# ─── Resumen ────────────────────────────────────────────────────
echo -e "\n${GREEN}${BOLD}"
echo "  ╔═══════════════════════════════════════════╗"
echo "  ║   ✅  Cambios aplicados en producción      ║"
echo "  ╚═══════════════════════════════════════════╝"
echo -e "${NC}"
info "Tema:   $(basename "$THEME_DIR")"
info "Hora:   $(date '+%Y-%m-%d %H:%M:%S')"
info "Tip:    Si editaste archivos .php, los cambios ya están activos."
info "Tip:    Si editaste .twig, la caché fue limpiada."
