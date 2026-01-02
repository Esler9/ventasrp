#!/usr/bin/env bash
# deploy.sh - Realiza migraciones, semillas y optimización de artisan.
# Uso:
#   ./deploy.sh            -> Ejecuta migrate, db:seed y optimize (por defecto)
#   ./deploy.sh --migrate  -> Solo migrate
#   ./deploy.sh --seed     -> Solo seed
#   ./deploy.sh --optimize -> Solo optimize (clear + optimize)
#   ./deploy.sh --fresh    -> migrate:fresh --seed
#   ./deploy.sh --help

set -euo pipefail

# Determina la raíz del proyecto (directorio donde está este script)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ARTISAN="$SCRIPT_DIR/artisan"

if [[ ! -f "$ARTISAN" ]]; then
    echo "Error: no se encontró artisan en $SCRIPT_DIR"
    exit 2
fi

# Detecta el entorno de la aplicación para decidir tareas opcionales (ej. npm run build)
APP_ENV_VALUE="${APP_ENV:-}"
if [[ -z "$APP_ENV_VALUE" && -f .env ]]; then
    env_line=$(grep -E '^APP_ENV=' .env | tail -n 1 || true)
    if [[ -n "$env_line" ]]; then
        APP_ENV_VALUE=${env_line#APP_ENV=}
        APP_ENV_VALUE="${APP_ENV_VALUE%\"}"
        APP_ENV_VALUE="${APP_ENV_VALUE#\"}"
    fi
fi

APP_ENV_NORMALIZED=$(printf '%s' "$APP_ENV_VALUE" | tr '[:upper:]' '[:lower:]')

PHP_CMD="${PHP_CMD:-php}"

if ! command -v "$PHP_CMD" >/dev/null 2>&1; then
    echo "Error: no se encontró php en PATH. Exporta PHP_CMD si usas un binario distinto."
    exit 2
fi

# Opciones por defecto
DO_MIGRATE=false
DO_SEED=false
DO_OPTIMIZE=false
DO_FRESH=false

if [[ $# -eq 0 ]]; then
    DO_MIGRATE=true
    DO_SEED=true
    DO_OPTIMIZE=true
fi

while [[ $# -gt 0 ]]; do
    case "$1" in
        -m|--migrate) DO_MIGRATE=true; shift ;;
        -s|--seed) DO_SEED=true; shift ;;
        -o|--optimize|--opt) DO_OPTIMIZE=true; shift ;;
        --fresh) DO_FRESH=true; shift ;;
        -a|--all) DO_MIGRATE=true; DO_SEED=true; DO_OPTIMIZE=true; shift ;;
        -h|--help) sed -n '1,80p' "$0"; exit 0 ;;
        *) echo "Opción desconocida: $1"; echo "Usa --help"; exit 1 ;;
    esac
done

# En producción es recomendable pasar --force para no solicitar confirmación
ARTISAN_FORCE="--force"

run() {
    echo
    echo "==> $*"
    if ! "$PHP_CMD" "$ARTISAN" $*; then
        echo "Comando falló: $*"
        exit 1
    fi
}

# Opcional: instalar dependencias antes (comentar si no se desea)
if command -v composer >/dev/null 2>&1; then
    echo "Comprobando dependencias (composer install --no-dev --optimize-autoloader)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Compilación frontend: por defecto sólo en entornos locales. Anula con RUN_NPM_BUILD=always|never
RUN_NPM_BUILD_MODE="${RUN_NPM_BUILD:-auto}"
SHOULD_BUILD_FRONTEND=false
RUN_NPM_BUILD_MODE_LOWER=$(printf '%s' "$RUN_NPM_BUILD_MODE" | tr '[:upper:]' '[:lower:]')

case "$RUN_NPM_BUILD_MODE_LOWER" in
    always)
        SHOULD_BUILD_FRONTEND=true
        ;;
    never)
        SHOULD_BUILD_FRONTEND=false
        ;;
    *)
        if [[ "$APP_ENV_NORMALIZED" == "local" ]]; then
            SHOULD_BUILD_FRONTEND=true
        fi
        ;;
esac

if [[ "$DO_FRESH" == true ]]; then
    if [[ "${ALLOW_DB_RESET:-0}" != "1" ]]; then
        echo "Error: --fresh requiere ALLOW_DB_RESET=1 para evitar borrados accidentales."
        exit 3
    fi

    if [[ "$APP_ENV_NORMALIZED" != "local" && "$APP_ENV_NORMALIZED" != "testing" ]]; then
        echo "Error: migrate:fresh sólo está permitido en entornos local/testing (APP_ENV=$APP_ENV_VALUE)."
        exit 3
    fi
fi

if [[ "$SHOULD_BUILD_FRONTEND" == true && -f package.json ]]; then
    if command -v npm >/dev/null 2>&1; then
        echo "Construyendo assets con npm run build (APP_ENV=${APP_ENV_VALUE:-desconocido})..."
        if ! npm run build; then
            echo "Error al ejecutar npm run build"
            exit 1
        fi
    else
        echo "Advertencia: npm no está disponible; se omite npm run build" >&2
    fi
else
    echo "Omitiendo npm run build (modo=${RUN_NPM_BUILD_MODE}, APP_ENV=${APP_ENV_VALUE:-desconocido})."
fi

if [[ "$DO_OPTIMIZE" == true ]]; then
    run optimize:clear
fi

if [[ "$DO_FRESH" == true ]]; then
    # migrate:fresh y luego seed (si se pidió)
    if [[ "$DO_SEED" == true ]]; then
        run migrate:fresh $ARTISAN_FORCE --seed
        DO_SEED=false
    else
        run migrate:fresh $ARTISAN_FORCE
    fi
else
    if [[ "$DO_MIGRATE" == true ]]; then
        run migrate $ARTISAN_FORCE
    fi
    if [[ "$DO_SEED" == true ]]; then
        run db:seed $ARTISAN_FORCE
    fi
fi

if [[ "$DO_OPTIMIZE" == true ]]; then
    run optimize
fi

echo
echo "Despliegue finalizado correctamente."