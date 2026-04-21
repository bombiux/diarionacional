#!/usr/bin/env bash
#
# deploy.sh — Script de despliegue para DBarricada
#
# Uso:
#   ./scripts/deploy.sh          → Build + deploy completo
#   ./scripts/deploy.sh --dry    → Simula el rsync sin copiar nada
#   ./scripts/deploy.sh --skip-build → Solo sincroniza (sin rebuild)
#

set -euo pipefail

# ─── Configuración ──────────────────────────────────────────────
SSH_HOST="172.16.12.102"
SSH_USER="root"
REMOTE_PATH="/usr/local/lsws/Example/html/wordpress/wp-content/themes/DBarricada"
THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Funciones auxiliares ────────────────────────────────────────
info()    { echo -e "${CYAN}▸${NC} $*"; }
success() { echo -e "${GREEN}✔${NC} $*"; }
warn()    { echo -e "${YELLOW}⚠${NC} $*"; }
error()   { echo -e "${RED}✘${NC} $*" >&2; }
header()  { echo -e "\n${BOLD}${CYAN}━━━ $* ━━━${NC}\n"; }

# Helper: ejecutar comando remoto via sshpass
remote() {
    sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$@"
}

# ─── Flags ───────────────────────────────────────────────────────
DRY_RUN=false
SKIP_BUILD=false

for arg in "$@"; do
    case "$arg" in
        --dry)        DRY_RUN=true ;;
        --skip-build) SKIP_BUILD=true ;;
        --help|-h)
            echo "Uso: ./scripts/deploy.sh [--dry] [--skip-build]"
            echo ""
            echo "  --dry          Simula el rsync sin copiar archivos"
            echo "  --skip-build   Salta el build de assets (solo sincroniza)"
            exit 0
            ;;
    esac
done

cd "$THEME_DIR"

# ─── 0. Verificar sshpass ───────────────────────────────────────
if ! command -v sshpass &> /dev/null; then
    error "Se requiere 'sshpass'. Instalalo con: sudo apt install sshpass"
    exit 1
fi

# ─── 1. Pedir contraseña del servidor ───────────────────────────
header "1/8 · Autenticación"
echo -n "Contraseña de ${SSH_USER}@${SSH_HOST}: "
read -s SSH_PASS
echo ""

# Verificar conexión
if ! remote "echo ok" > /dev/null 2>&1; then
    error "No se pudo conectar. Verifica la contraseña."
    exit 1
fi
success "Conexión a '${SSH_USER}@${SSH_HOST}' establecida"

# ─── 2. Build de assets ─────────────────────────────────────────
if [ "$SKIP_BUILD" = true ]; then
    warn "Saltando build de assets (--skip-build)"
else
    header "2/8 · Compilando assets de producción"
    npm run build
    success "Assets compilados en dist/"
fi



# ─── 3. Verificar que dist/ existe ──────────────────────────────
if [ ! -d "dist" ]; then
    error "El directorio dist/ no existe. Ejecuta 'npm run build' primero."
    exit 1
fi

# ─── 4. Crear directorio remoto si no existe ────────────────────
header "3/8 · Preparando directorio remoto"

if [ "$DRY_RUN" = true ]; then
    warn "Simulación: mkdir -p ${REMOTE_PATH}"
else
    remote "mkdir -p ${REMOTE_PATH}"
fi
success "Directorio remoto listo: ${REMOTE_PATH}"

# ─── 5. Sincronizar archivos ────────────────────────────────────
header "4/8 · Sincronizando archivos al servidor"

RSYNC_FLAGS="-avz --delete --checksum"

if [ "$DRY_RUN" = true ]; then
    RSYNC_FLAGS="$RSYNC_FLAGS --dry-run"
    warn "Modo simulación activado (--dry-run)"
fi

sshpass -p "$SSH_PASS" rsync $RSYNC_FLAGS \
    -e "ssh -o StrictHostKeyChecking=no" \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.env' \
    --exclude='.env.example' \
    --exclude='.editorconfig' \
    --exclude='.dockerrc' \
    --exclude='.vscode' \
    --exclude='.engram' \
    --exclude='src/' \
    --exclude='vendor/' \
    --exclude='vite.config.js' \
    --exclude='tailwind.config.js' \
    --exclude='postcss.config.js' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='AGENTS.md' \
    --exclude='walkthrough.md' \
    --exclude='*.md' \
    --exclude='scripts/' \
    ./ "${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/"

success "Archivos sincronizados"

# ─── 6. Crear .env de producción ────────────────────────────────
header "5/8 · Configurando entorno de producción"

if [ "$DRY_RUN" = true ]; then
    warn "Simulación: Crear .env con WP_ENV=production en el servidor"
else
    remote "echo 'WP_ENV=production' > ${REMOTE_PATH}/.env"
fi
success "Archivo .env de producción creado"

# ─── 7. Instalar dependencias PHP en producción ─────────────────
header "6/8 · Instalando dependencias PHP (producción)"

if [ "$DRY_RUN" = true ]; then
    warn "Simulación: rm -rf vendor && composer install --no-dev --optimize-autoloader --ignore-platform-reqs"
else
    remote "export PATH=\$PATH:/usr/local/bin:/usr/bin:/bin; source /etc/profile 2>/dev/null; cd ${REMOTE_PATH} && rm -rf vendor && composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction 2>&1"
fi
success "Dependencias PHP instaladas (sin dev)"

# ─── 8. Ajustar permisos ────────────────────────────────────────
header "7/8 · Ajustando permisos de la carpeta del tema"

if [ "$DRY_RUN" = true ]; then
    warn "Simulación: chown -R nobody:nogroup ${REMOTE_PATH}"
else
    remote "chown -R nobody:nogroup ${REMOTE_PATH}"
fi
success "Permisos ajustados a nobody:nogroup"

# ─── 9. Resumen final ───────────────────────────────────────────
header "8/8 · Despliegue completado"

if [ "$DRY_RUN" = true ]; then
    warn "Este fue un ensayo. Ejecuta sin --dry para desplegar de verdad."
else
    echo -e "${GREEN}${BOLD}"
    echo "  ╔═══════════════════════════════════════╗"
    echo "  ║   🚀  DBarricada desplegado con éxito  ║"
    echo "  ╚═══════════════════════════════════════╝"
    echo -e "${NC}"
    info "Servidor:  ${SSH_USER}@${SSH_HOST}"
    info "Ruta:      ${REMOTE_PATH}"
    info "Hora:      $(date '+%Y-%m-%d %H:%M:%S')"
fi

# Limpiar contraseña de la memoria
unset SSH_PASS
