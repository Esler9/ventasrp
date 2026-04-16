#!/usr/bin/env bash
# =============================================================================
#  VentasRP / iThink POS — Universal Installer
#  Supports: Windows (Git Bash / MSYS2), macOS, Linux (Debian/Ubuntu)
#  Usage:    bash install.sh
# =============================================================================
set -eo pipefail

# ---------------------------------------------------------------------------
# ANSI colours
# ---------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
RESET='\033[0m'

# ---------------------------------------------------------------------------
# Output helpers
# ---------------------------------------------------------------------------
info()    { echo -e "  ${CYAN}${BOLD}→${RESET} $*"; }
success() { echo -e "  ${GREEN}${BOLD}✔${RESET} $*"; }
warn()    { echo -e "  ${YELLOW}${BOLD}⚠${RESET}  $*"; }
die()     { echo -e "\n  ${RED}${BOLD}✘ FATAL:${RESET} $*\n" >&2; exit 1; }

TOTAL_STEPS=7
_step_num=0
step() {
  _step_num=$(( _step_num + 1 ))
  echo -e "\n${BOLD}${BLUE}┌─────────────────────────────────────────────────────┐${RESET}"
  echo -e "${BOLD}${BLUE}│  [${_step_num}/${TOTAL_STEPS}]  $*${RESET}"
  echo -e "${BOLD}${BLUE}└─────────────────────────────────────────────────────┘${RESET}"
}

# ---------------------------------------------------------------------------
# Logging — enabled after storage/logs/ is created in step 2
# ---------------------------------------------------------------------------
LOG_FILE=""          # set by set_paths(); directory must exist before writing

log() {
  [ -f "$LOG_FILE" ] && printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >> "$LOG_FILE"
}

# Run a command, stream output to both console and log file (if ready).
run() {
  log "RUN: $*"
  if [ -f "$LOG_FILE" ]; then
    "$@" 2>&1 | tee -a "$LOG_FILE"
  else
    "$@"
  fi
}

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------
REPO_URL="https://github.com/Esler9/ventasrp.git"
APP_PORT=8000
APP_SERVICE_NAME="ventasrp"
APP_DISPLAY_NAME="VentasRP POS"
PHP_MIN_MAJOR=8
PHP_MIN_MINOR=2

# ---------------------------------------------------------------------------
# 1. OS detection
# ---------------------------------------------------------------------------
detect_os() {
  case "$(uname -s)" in
    Linux*)          OS=Linux   ;;
    Darwin*)         OS=macOS   ;;
    CYGWIN*|MINGW*|MSYS*) OS=Windows ;;
    *) die "Unsupported OS: $(uname -s).  Supported: Linux, macOS, Windows (Git Bash)." ;;
  esac
  info "Detected OS: ${BOLD}${OS}${RESET}"
}

# ---------------------------------------------------------------------------
# 2. Install paths
# ---------------------------------------------------------------------------
set_paths() {
  case "$OS" in
    Windows) INSTALL_DIR="/c/ithink_app"        ;;
    macOS)   INSTALL_DIR="/usr/local/ithink_app" ;;
    Linux)   INSTALL_DIR="/var/www/ithink_app"   ;;
  esac
  LOG_FILE="${INSTALL_DIR}/storage/logs/service.log"
  info "Install directory: ${BOLD}${INSTALL_DIR}${RESET}"
}

# ---------------------------------------------------------------------------
# Privilege helper
# ---------------------------------------------------------------------------
SUDO=""
init_sudo() {
  if [ "$OS" != "Windows" ] && [ "$(id -u)" -ne 0 ]; then
    command -v sudo &>/dev/null || die "Not root and 'sudo' not found.  Run as root or install sudo."
    SUDO="sudo"
    info "Using sudo for privileged operations."
  fi
}

# ---------------------------------------------------------------------------
# Dependency helpers
# ---------------------------------------------------------------------------
has_cmd() { command -v "$1" &>/dev/null; }

php_version_ok() {
  has_cmd php || return 1
  local ver; ver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
  local maj; maj=$(echo "$ver" | cut -d. -f1)
  local min; min=$(echo "$ver" | cut -d. -f2)
  [ "$maj" -gt "$PHP_MIN_MAJOR" ] || { [ "$maj" -eq "$PHP_MIN_MAJOR" ] && [ "$min" -ge "$PHP_MIN_MINOR" ]; }
}

# Reload PATH after Chocolatey installs
reload_path_windows() {
  for d in \
    "/c/ProgramData/chocolatey/bin" \
    "/c/tools/php82" \
    "/c/ProgramData/ComposerSetup/bin" \
    "/c/Program Files/nodejs" \
    "/c/Program Files/Git/cmd"; do
    [ -d "$d" ] && export PATH="$d:$PATH"
  done
}

# ── Windows ─────────────────────────────────────────────────────────────────
install_deps_windows() {
  # Chocolatey
  if ! has_cmd choco; then
    info "Installing Chocolatey (requires Administrator)..."
    powershell -NoProfile -ExecutionPolicy Bypass -Command \
      "Set-ExecutionPolicy Bypass -Scope Process -Force; \
       [Net.ServicePointManager]::SecurityProtocol = \
         [Net.ServicePointManager]::SecurityProtocol -bor 3072; \
       iex ((New-Object Net.WebClient).DownloadString(\
         'https://community.chocolatey.org/install.ps1'))" \
      || die "Chocolatey install failed.  Run Git Bash as Administrator."
    reload_path_windows
    success "Chocolatey installed."
  else
    success "Chocolatey already present."
  fi

  # Git
  if ! has_cmd git; then
    info "Installing Git..."
    choco install -y git
    reload_path_windows
  else
    success "Git $(git --version | awk '{print $3}') already installed."
  fi

  # PHP 8.2
  if ! php_version_ok; then
    info "Installing PHP 8.2..."
    choco install -y php --version=8.2.29
    reload_path_windows
  else
    success "PHP $(php -r 'echo PHP_VERSION;') already installed."
  fi

  # Composer
  if ! has_cmd composer; then
    info "Installing Composer..."
    choco install -y composer
    reload_path_windows
  else
    success "Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}') already installed."
  fi

  # Node.js LTS
  if ! has_cmd node; then
    info "Installing Node.js LTS..."
    choco install -y nodejs-lts
    reload_path_windows
  else
    success "Node $(node --version) already installed."
  fi

  # NSSM (Windows service manager)
  if ! has_cmd nssm; then
    info "Installing NSSM..."
    choco install -y nssm
    reload_path_windows
  else
    success "NSSM already installed."
  fi
}

# ── macOS ────────────────────────────────────────────────────────────────────
install_deps_macos() {
  # Homebrew
  if ! has_cmd brew; then
    info "Installing Homebrew..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)" \
      || die "Homebrew install failed."
    # Support both Apple Silicon (/opt/homebrew) and Intel (/usr/local)
    if [ -f "/opt/homebrew/bin/brew" ]; then
      eval "$(/opt/homebrew/bin/brew shellenv)"
    else
      eval "$(/usr/local/bin/brew shellenv)"
    fi
    success "Homebrew installed."
  else
    success "Homebrew $(brew --version | head -1 | awk '{print $2}') already installed."
  fi

  # Git
  has_cmd git \
    && success "Git $(git --version | awk '{print $3}') already installed." \
    || { info "Installing Git..."; brew install git; }

  # PHP 8.2
  if ! php_version_ok; then
    info "Installing PHP 8.2..."
    brew install php@8.2
    brew link --force --overwrite php@8.2
    export PATH="$(brew --prefix php@8.2)/bin:$PATH"
  else
    success "PHP $(php -r 'echo PHP_VERSION;') already installed."
  fi

  # Composer
  has_cmd composer \
    && success "Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}') already installed." \
    || { info "Installing Composer..."; brew install composer; }

  # Node.js
  has_cmd node \
    && success "Node $(node --version) already installed." \
    || { info "Installing Node.js..."; brew install node; }
}

# ── Linux (Debian / Ubuntu) ──────────────────────────────────────────────────
install_deps_linux() {
  has_cmd apt-get || die "apt-get not found.  Only Debian/Ubuntu-based distros are supported."

  $SUDO apt-get update -qq

  # Git
  has_cmd git \
    && success "Git $(git --version | awk '{print $3}') already installed." \
    || { info "Installing Git..."; $SUDO apt-get install -y git; }

  # PHP 8.2 via ondrej/php PPA
  if ! php_version_ok; then
    info "Adding ondrej/php PPA and installing PHP 8.2..."
    $SUDO apt-get install -y software-properties-common ca-certificates apt-transport-https lsb-release
    $SUDO add-apt-repository -y ppa:ondrej/php
    $SUDO apt-get update -qq
    $SUDO apt-get install -y \
      php8.2 php8.2-cli \
      php8.2-mbstring php8.2-xml php8.2-curl \
      php8.2-zip php8.2-mysql php8.2-sqlite3 \
      php8.2-bcmath php8.2-intl php8.2-tokenizer
    $SUDO update-alternatives --set php /usr/bin/php8.2 2>/dev/null || true
  else
    success "PHP $(php -r 'echo PHP_VERSION;') already installed."
  fi

  # Composer
  if ! has_cmd composer; then
    info "Installing Composer..."
    local TMP; TMP=$(mktemp)
    curl -sS https://getcomposer.org/installer -o "$TMP"
    # Verify checksum
    local EXPECTED; EXPECTED=$(curl -sS https://composer.github.io/installer.sig)
    local ACTUAL;   ACTUAL=$(php -r "echo hash_file('sha384','${TMP}');")
    [ "$EXPECTED" = "$ACTUAL" ] || { rm -f "$TMP"; die "Composer installer checksum mismatch."; }
    php "$TMP" --quiet
    rm -f "$TMP"
    $SUDO mv composer.phar /usr/local/bin/composer
    $SUDO chmod +x /usr/local/bin/composer
  else
    success "Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}') already installed."
  fi

  # Node.js LTS (NodeSource)
  if ! has_cmd node; then
    info "Installing Node.js LTS via NodeSource..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | $SUDO bash -
    $SUDO apt-get install -y nodejs
  else
    success "Node $(node --version) already installed."
  fi
}

# ---------------------------------------------------------------------------
# STEP 1 — System dependencies
# ---------------------------------------------------------------------------
step1_deps() {
  step "Install system dependencies"

  case "$OS" in
    Windows) install_deps_windows ;;
    macOS)   install_deps_macos   ;;
    Linux)   install_deps_linux   ;;
  esac

  # Final sanity check
  php_version_ok || die "PHP ${PHP_MIN_MAJOR}.${PHP_MIN_MINOR}+ not available after installation."
  has_cmd composer || die "composer not available after installation."
  has_cmd node     || die "node not available after installation."
  has_cmd npm      || die "npm not available after installation."
  has_cmd git      || die "git not available after installation."

  success "All system dependencies satisfied."
}

# ---------------------------------------------------------------------------
# STEP 2 — Clone or update repository
# ---------------------------------------------------------------------------
step2_repo() {
  step "Clone or update repository"

  if [ -d "${INSTALL_DIR}/.git" ]; then
    warn "Repository already exists at ${INSTALL_DIR} — pulling latest changes..."
    cd "$INSTALL_DIR"
    git fetch --prune origin
    git pull origin main
    success "Repository updated."
  else
    info "Cloning ${REPO_URL} → ${INSTALL_DIR}..."
    case "$OS" in
      Windows)
        mkdir -p "$INSTALL_DIR"
        ;;
      macOS|Linux)
        $SUDO mkdir -p "$INSTALL_DIR"
        $SUDO chown "$(id -u):$(id -g)" "$INSTALL_DIR"
        ;;
    esac
    git clone "$REPO_URL" "$INSTALL_DIR"
    cd "$INSTALL_DIR"
    success "Repository cloned."
  fi

  cd "$INSTALL_DIR"

  # Create log directory and file now that the repo root exists
  mkdir -p storage/logs bootstrap/cache
  touch "$LOG_FILE"

  # Ensure writable on Unix
  if [ "$OS" != "Windows" ]; then
    chmod -R 775 storage bootstrap/cache 2>/dev/null || true
  fi

  log "=== Install started (OS=${OS}, dir=${INSTALL_DIR}) ==="
  success "Working directory: ${INSTALL_DIR}"
}

# ---------------------------------------------------------------------------
# STEP 3 — PHP & JS dependencies, frontend build
# ---------------------------------------------------------------------------
step3_build() {
  step "Install PHP & JS dependencies, build frontend"
  cd "$INSTALL_DIR"

  info "composer install --no-dev --optimize-autoloader..."
  run composer install --no-dev --optimize-autoloader --no-interaction

  info "npm install..."
  run npm install

  info "npm run build..."
  run npm run build

  success "Dependencies installed and frontend built."
}

# ---------------------------------------------------------------------------
# .env helpers
# ---------------------------------------------------------------------------

# set_env_var KEY VALUE [FILE]
# Handles: existing key, commented-out key, or append if absent.
set_env_var() {
  local key="$1" val="$2" file="${3:-.env}"

  # Escape characters that are special in the sed replacement string
  local esc
  esc=$(printf '%s' "$val" | sed 's/[&\]/\\&/g; s|/|\\/|g')

  if grep -qE "^${key}=" "$file" 2>/dev/null; then
    sed -i.bak "s|^${key}=.*|${key}=${esc}|" "$file" && rm -f "${file}.bak"
  elif grep -qE "^# *${key}=" "$file" 2>/dev/null; then
    # Uncomment and set
    sed -i.bak "s|^# *${key}=.*|${key}=${esc}|" "$file" && rm -f "${file}.bak"
  else
    printf '%s=%s\n' "$key" "$val" >> "$file"
  fi
}

get_env_var() {
  local key="$1" file="${2:-.env}"
  grep -E "^${key}=" "$file" 2>/dev/null | head -1 | cut -d= -f2- | tr -d '"' || true
}

# ---------------------------------------------------------------------------
# STEP 4 — Configure .env + MySQL credentials
# ---------------------------------------------------------------------------
step4_env() {
  step "Configure environment (.env)"
  cd "$INSTALL_DIR"

  # Copy only when .env does not yet exist
  if [ ! -f ".env" ]; then
    info "Copying .env.example → .env"
    cp .env.example .env
  else
    warn ".env already exists — keeping existing values; only DB credentials will be updated."
  fi

  # Application baseline
  set_env_var "APP_NAME"  "VentasRP"
  set_env_var "APP_ENV"   "production"
  set_env_var "APP_DEBUG" "false"
  set_env_var "APP_URL"   "http://localhost:${APP_PORT}"

  # ── MySQL credentials ────────────────────────────────────────────────────
  echo ""
  echo -e "  ${BOLD}${YELLOW}MySQL Credentials${RESET}  ${DIM}(press Enter to keep the value in brackets)${RESET}"
  echo ""

  _prompt_db() {
    local label="$1" key="$2" default="$3" secret="${4:-}"
    local cur; cur=$(get_env_var "$key")
    cur="${cur:-$default}"
    if [ -n "$secret" ]; then
      read -rsp "    ${label} [${cur//?/*}]: " input
      echo ""
    else
      read -rp  "    ${label} [${cur}]: " input
    fi
    # Keep current value if user pressed Enter without typing
    printf '%s' "${input:-$cur}"
  }

  DB_HOST=$(_prompt_db "DB Host"     DB_HOST     "127.0.0.1")
  DB_PORT=$(_prompt_db "DB Port"     DB_PORT     "3306")
  DB_NAME=$(_prompt_db "DB Name"     DB_DATABASE "ventasrp")
  DB_USER=$(_prompt_db "DB Username" DB_USERNAME "root")
  DB_PASS=$(_prompt_db "DB Password" DB_PASSWORD ""  secret)

  # Apply to .env
  set_env_var "DB_CONNECTION" "mysql"
  set_env_var "DB_HOST"       "$DB_HOST"
  set_env_var "DB_PORT"       "$DB_PORT"
  set_env_var "DB_DATABASE"   "$DB_NAME"
  set_env_var "DB_USERNAME"   "$DB_USER"
  set_env_var "DB_PASSWORD"   "$DB_PASS"

  log "DB configured: host=${DB_HOST} port=${DB_PORT} database=${DB_NAME} user=${DB_USER}"

  # Generate APP_KEY only when not already set
  local cur_key; cur_key=$(get_env_var "APP_KEY")
  if [ -z "$cur_key" ] || [ "$cur_key" = "base64:" ]; then
    info "Generating APP_KEY..."
    php artisan key:generate --force
    success "APP_KEY generated."
  else
    success "APP_KEY already set — skipping key:generate."
  fi

  success ".env configured."
}

# ---------------------------------------------------------------------------
# STEP 5 — Migrate and seed
# ---------------------------------------------------------------------------
step5_db() {
  step "Run database migrations and seeders"
  cd "$INSTALL_DIR"

  info "php artisan migrate --force..."
  run php artisan migrate --force

  info "php artisan db:seed --force..."
  run php artisan db:seed --force

  # Warm up caches
  info "Warming up config / route / view caches..."
  php artisan config:cache 2>&1 | tee -a "$LOG_FILE" || warn "config:cache failed — continuing."
  php artisan route:cache  2>&1 | tee -a "$LOG_FILE" || warn "route:cache failed — continuing."
  php artisan view:cache   2>&1 | tee -a "$LOG_FILE" || warn "view:cache failed — continuing."

  success "Database ready.  Default credentials: admin@example.com / 1234"
}

# ---------------------------------------------------------------------------
# STEP 6 — Auto-start service
# ---------------------------------------------------------------------------

# ── Windows (NSSM) ──────────────────────────────────────────────────────────
_register_nssm_svc() {
  # Usage: _register_nssm_svc <service-name> <display-name> <php-args...>
  local svc="$1" disp="$2"; shift 2
  local php_win; php_win=$(to_win_path "$(command -v php)")
  local dir_win; dir_win=$(to_win_path "$INSTALL_DIR")
  local log_win; log_win=$(to_win_path "$LOG_FILE")
  local artisan_win; artisan_win=$(to_win_path "${INSTALL_DIR}/artisan")

  # Build quoted argument string
  local args="\"${artisan_win}\""
  for a in "$@"; do args="${args} ${a}"; done

  if nssm status "$svc" &>/dev/null 2>&1; then
    warn "NSSM service '${svc}' exists — stopping and re-installing..."
    nssm stop   "$svc" 2>/dev/null || true
    nssm remove "$svc" confirm 2>/dev/null || true
  fi

  nssm install "$svc" "$php_win"
  nssm set "$svc" AppParameters  "$args"
  nssm set "$svc" AppDirectory   "$dir_win"
  nssm set "$svc" DisplayName    "$disp"
  nssm set "$svc" Description    "VentasRP POS Application"
  nssm set "$svc" Start          "SERVICE_AUTO_START"
  nssm set "$svc" AppStdout      "$log_win"
  nssm set "$svc" AppStderr      "$log_win"
  nssm set "$svc" AppRotateFiles 1
  nssm start "$svc"
  success "NSSM service '${svc}' started."
}

to_win_path() {
  if has_cmd cygpath; then cygpath -w "$1"
  else echo "$1" | sed 's|/c/|C:\\|; s|/|\\|g'; fi
}

register_service_windows() {
  has_cmd nssm || die "NSSM not found.  Ensure step 1 completed as Administrator."
  _register_nssm_svc \
    "$APP_SERVICE_NAME" "$APP_DISPLAY_NAME" \
    serve --host=0.0.0.0 "--port=${APP_PORT}"
  _register_nssm_svc \
    "${APP_SERVICE_NAME}-queue" "${APP_DISPLAY_NAME} Queue" \
    queue:listen --tries=1
}

# ── macOS (launchctl) ────────────────────────────────────────────────────────
_write_plist() {
  local label="$1" plist="$2" php="$3"; shift 3
  # Build <string> entries for each argument
  local args_xml=""
  for a in "$@"; do
    args_xml="${args_xml}        <string>${a}</string>\n"
  done

  cat > "$plist" << PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
  "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>${label}</string>
    <key>ProgramArguments</key>
    <array>
        <string>${php}</string>
$(printf '%b' "$args_xml")    </array>
    <key>WorkingDirectory</key>
    <string>${INSTALL_DIR}</string>
    <key>StandardOutPath</key>
    <string>${LOG_FILE}</string>
    <key>StandardErrorPath</key>
    <string>${LOG_FILE}</string>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>ThrottleInterval</key>
    <integer>5</integer>
</dict>
</plist>
PLIST
}

register_service_macos() {
  local PHP_BIN; PHP_BIN=$(command -v php)
  local AGENTS="$HOME/Library/LaunchAgents"
  mkdir -p "$AGENTS"

  local PLIST_HTTP="${AGENTS}/com.ithink.${APP_SERVICE_NAME}.plist"
  local PLIST_QUEUE="${AGENTS}/com.ithink.${APP_SERVICE_NAME}-queue.plist"

  launchctl unload "$PLIST_HTTP"  2>/dev/null || true
  launchctl unload "$PLIST_QUEUE" 2>/dev/null || true

  _write_plist \
    "com.ithink.${APP_SERVICE_NAME}" "$PLIST_HTTP" "$PHP_BIN" \
    "${INSTALL_DIR}/artisan" serve --host=0.0.0.0 "--port=${APP_PORT}"

  _write_plist \
    "com.ithink.${APP_SERVICE_NAME}-queue" "$PLIST_QUEUE" "$PHP_BIN" \
    "${INSTALL_DIR}/artisan" queue:listen --tries=1

  launchctl load -w "$PLIST_HTTP"
  launchctl load -w "$PLIST_QUEUE"
  success "launchd services loaded (auto-start at login)."
}

# ── Linux (systemd) ──────────────────────────────────────────────────────────
_write_systemd_unit() {
  local unit_file="$1" desc="$2" exec="$3"
  local run_user; run_user=$(id -un)

  $SUDO tee "$unit_file" > /dev/null << UNIT
[Unit]
Description=${desc}
After=network.target mysql.service
Wants=mysql.service

[Service]
Type=simple
User=${run_user}
WorkingDirectory=${INSTALL_DIR}
ExecStart=${exec}
Restart=on-failure
RestartSec=5
StandardOutput=append:${LOG_FILE}
StandardError=append:${LOG_FILE}
Environment="APP_ENV=production"

[Install]
WantedBy=multi-user.target
UNIT
}

register_service_linux() {
  local PHP_BIN; PHP_BIN=$(command -v php)
  local HTTP_UNIT="/etc/systemd/system/${APP_SERVICE_NAME}.service"
  local QUEUE_UNIT="/etc/systemd/system/${APP_SERVICE_NAME}-queue.service"

  info "Writing systemd units..."
  _write_systemd_unit "$HTTP_UNIT"  "VentasRP POS HTTP server" \
    "${PHP_BIN} ${INSTALL_DIR}/artisan serve --host=0.0.0.0 --port=${APP_PORT}"
  _write_systemd_unit "$QUEUE_UNIT" "VentasRP POS Queue worker" \
    "${PHP_BIN} ${INSTALL_DIR}/artisan queue:listen --tries=1"

  $SUDO systemctl daemon-reload
  $SUDO systemctl enable  "${APP_SERVICE_NAME}" "${APP_SERVICE_NAME}-queue"
  $SUDO systemctl restart "${APP_SERVICE_NAME}" "${APP_SERVICE_NAME}-queue"
  success "systemd services enabled and started."
}

step6_service() {
  step "Register auto-start service"
  case "$OS" in
    Windows) register_service_windows ;;
    macOS)   register_service_macos   ;;
    Linux)   register_service_linux   ;;
  esac
  log "Service registered."
}

# ---------------------------------------------------------------------------
# STEP 7 — Desktop shortcuts
# ---------------------------------------------------------------------------
create_shortcut_windows() {
  # In Git Bash, $USERPROFILE is the Windows-style path; convert to POSIX.
  local DESK
  if has_cmd cygpath; then
    DESK=$(cygpath "$USERPROFILE/Desktop")
  else
    DESK="${USERPROFILE}/Desktop"
  fi

  # .url Internet Shortcut (double-clickable in Explorer)
  cat > "${DESK}/VentasRP POS.url" << 'URL'
[InternetShortcut]
URL=http://localhost:8000
IconFile=C:\Windows\System32\shell32.dll
IconIndex=13
URL

  # .bat launcher (opens browser from cmd)
  cat > "${DESK}/Abrir VentasRP.bat" << 'BAT'
@echo off
start "" "http://localhost:8000"
BAT

  success "Shortcut created: ${DESK}/VentasRP POS.url"
}

create_shortcut_macos() {
  local DESK="$HOME/Desktop"

  cat > "${DESK}/VentasRP POS.webloc" << 'WEBLOC'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
  "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>URL</key>
    <string>http://localhost:8000</string>
</dict>
</plist>
WEBLOC

  success "Shortcut created: ${DESK}/VentasRP POS.webloc"
}

create_shortcut_linux() {
  local DESK="$HOME/Desktop"
  mkdir -p "$DESK"

  cat > "${DESK}/ventasrp.desktop" << 'DESKFILE'
[Desktop Entry]
Version=1.0
Type=Application
Name=VentasRP POS
GenericName=Punto de Venta
Comment=Abrir VentasRP Point of Sale
Exec=xdg-open http://localhost:8000
Icon=applications-internet
Terminal=false
StartupNotify=false
Categories=Office;Finance;
DESKFILE

  chmod +x "${DESK}/ventasrp.desktop"
  # Mark as trusted for GNOME (silently ignore if gio is absent)
  gio set "${DESK}/ventasrp.desktop" metadata::trusted true 2>/dev/null || true

  success "Shortcut created: ${DESK}/ventasrp.desktop"
}

step7_shortcut() {
  step "Create desktop shortcut"
  case "$OS" in
    Windows) create_shortcut_windows ;;
    macOS)   create_shortcut_macos   ;;
    Linux)   create_shortcut_linux   ;;
  esac
}

# ---------------------------------------------------------------------------
# Summary banner
# ---------------------------------------------------------------------------
print_summary() {
  echo ""
  echo -e "${BOLD}${GREEN}╔═══════════════════════════════════════════════════════╗${RESET}"
  echo -e "${BOLD}${GREEN}║           ✔  Installation complete!                   ║${RESET}"
  echo -e "${BOLD}${GREEN}╚═══════════════════════════════════════════════════════╝${RESET}"
  echo ""
  echo -e "  ${BOLD}URL:${RESET}         http://localhost:${APP_PORT}"
  echo -e "  ${BOLD}Install dir:${RESET} ${INSTALL_DIR}"
  echo -e "  ${BOLD}Log file:${RESET}    ${LOG_FILE}"
  echo ""
  echo -e "  ${BOLD}Default credentials${RESET}"
  echo -e "    Admin:    admin@example.com    / 1234"
  echo -e "    Vendedor: vendedor@example.com / 1234"
  echo ""

  case "$OS" in
    Windows)
      echo -e "  ${BOLD}Service commands (run as Administrator)${RESET}"
      echo -e "    nssm start|stop|restart ${APP_SERVICE_NAME}"
      echo -e "    nssm start|stop|restart ${APP_SERVICE_NAME}-queue"
      ;;
    macOS)
      echo -e "  ${BOLD}Service commands${RESET}"
      echo -e "    launchctl start|stop com.ithink.${APP_SERVICE_NAME}"
      echo -e "    launchctl start|stop com.ithink.${APP_SERVICE_NAME}-queue"
      ;;
    Linux)
      echo -e "  ${BOLD}Service commands${RESET}"
      echo -e "    sudo systemctl start|stop|restart ${APP_SERVICE_NAME}"
      echo -e "    sudo systemctl start|stop|restart ${APP_SERVICE_NAME}-queue"
      echo -e "    sudo journalctl -u ${APP_SERVICE_NAME} -f"
      ;;
  esac
  echo ""
  log "=== Installation finished ==="
}

# ---------------------------------------------------------------------------
# Trap — show where we failed
# ---------------------------------------------------------------------------
on_error() {
  local line=$1
  echo -e "\n  ${RED}${BOLD}✘  Script failed at line ${line}.${RESET}"
  echo -e "  Check the log for details: ${LOG_FILE:-<not yet created>}\n"
  log "FAILED at line ${line}"
  exit 1
}
trap 'on_error $LINENO' ERR

# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------
main() {
  echo ""
  echo -e "${BOLD}${CYAN}  ╦  ╔╦╗╦ ╦╦╔╗╔╦╔═  ╔═╗╔═╗╔═╗${RESET}"
  echo -e "${BOLD}${CYAN}  ║   ║ ╠═╣║║║║╠╩╗  ╠═╝║ ║╚═╗${RESET}"
  echo -e "${BOLD}${CYAN}  ╩═╝ ╩ ╩ ╩╩╝╚╝╩ ╩  ╩  ╚═╝╚═╝${RESET}"
  echo -e "  ${BOLD}VentasRP — Universal Installer${RESET}  ${DIM}(${OS:-detecting...})${RESET}"
  echo ""

  detect_os
  set_paths
  init_sudo

  step1_deps
  step2_repo
  step3_build
  step4_env
  step5_db
  step6_service
  step7_shortcut

  print_summary
}

main "$@"
