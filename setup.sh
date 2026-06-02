#!/bin/bash
# Setup script for the Liberu CRM project.
#
# Supports Standalone, Docker, and Kubernetes deployments.

set -euo pipefail

RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
RESET='\e[39m'

print_message()  { echo -e "${1}${2}${RESET}"; }
print_header()   { echo ""; echo "=================================="; echo "$1"; echo "=================================="; echo ""; }
print_error()    { print_message "$RED"    "ERROR: $1"; }
print_success()  { print_message "$GREEN"  "OK: $1"; }
print_info()     { print_message "$BLUE"   "INFO: $1"; }
print_warning()  { print_message "$YELLOW" "WARN: $1"; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ── PHP version check ─────────────────────────────────────────────────────────
check_php_version() {
    if ! command_exists php; then
        print_error "PHP is not installed. Please install PHP 8.5 or higher."
        return 1
    fi

    local version
    version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    local major minor
    major=$(echo "$version" | cut -d. -f1)
    minor=$(echo "$version" | cut -d. -f2)

    if [ "$major" -lt 8 ] || { [ "$major" -eq 8 ] && [ "$minor" -lt 5 ]; }; then
        print_error "PHP $version detected. PHP 8.5+ is required."
        return 1
    fi

    print_success "PHP $version"
}

# ── Composer bootstrap ────────────────────────────────────────────────────────
ensure_composer() {
    if command_exists composer; then
        COMPOSER_CMD="composer"
        print_success "Composer $(composer --version --no-interaction 2>/dev/null | head -1)"
        return 0
    fi

    print_warning "Composer not found — downloading composer.phar..."

    command_exists curl  || { print_error "curl is required to download Composer."; return 1; }
    command_exists php   || { print_error "PHP is required."; return 1; }

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"

    if [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
        print_success "composer.phar downloaded"
    else
        print_error "Failed to download Composer."
        return 1
    fi
}

# ── Dependencies ──────────────────────────────────────────────────────────────
install_composer_dependencies() {
    print_header "COMPOSER INSTALL"

    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_info "vendor/ already exists."
        read -rp "Reinstall Composer dependencies? (y/n) " -n 1
        echo
        [[ ! $REPLY =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    ensure_composer || return 1

    print_info "Running: $COMPOSER_CMD install"
    eval "$COMPOSER_CMD install --no-interaction --prefer-dist" \
        && print_success "Composer dependencies installed" \
        || { print_error "Composer install failed"; return 1; }
}

install_npm_dependencies() {
    print_header "NPM INSTALL"

    if ! command_exists npm; then
        print_warning "npm not found — skipping."
        return 0
    fi

    if [ -d "node_modules" ]; then
        print_info "node_modules/ already exists."
        read -rp "Reinstall npm dependencies? (y/n) " -n 1
        echo
        [[ ! $REPLY =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    npm install && print_success "npm dependencies installed" \
               || { print_error "npm install failed"; return 1; }
}

build_frontend_assets() {
    print_header "NPM BUILD"

    command_exists npm || { print_warning "npm not found — skipping build."; return 0; }

    npm run build && print_success "Frontend assets built" \
                 || { print_error "npm build failed"; return 1; }
}

# ── Standalone ────────────────────────────────────────────────────────────────
install_standalone() {
    print_header "STANDALONE INSTALLATION"

    clear
    echo "=================================="
    echo "  User  : $(whoami)"
    echo "  PHP   : $(php -r 'echo phpversion();' 2>/dev/null || echo unknown)"
    echo "=================================="
    echo ""

    check_php_version || exit 1

    local copy=true
    while true; do
        read -rp "Copy .env.example to .env? (y/n) " yn
        case $yn in
            [Yy]*) cp .env.example .env; copy=true; break ;;
            [Nn]*) copy=false; break ;;
            *)     print_warning "Please answer yes or no." ;;
        esac
    done

    if [ "$copy" = true ]; then
        while true; do
            read -rp "Have you configured database credentials in .env? (y/n) " cond
            case $cond in
                [Yy]*) break ;;
                [Nn]*) print_warning "Please edit .env first, then re-run."; exit 0 ;;
                *)     print_warning "Please answer yes or no." ;;
            esac
        done
    fi

    install_composer_dependencies || exit 1
    install_npm_dependencies
    build_frontend_assets

    print_header "APP KEY"
    php artisan key:generate && print_success "Application key generated" \
        || { print_error "key:generate failed"; exit 1; }

    print_header "DATABASE MIGRATE"
    php artisan migrate:fresh && print_success "Database migrated" \
        || { print_error "migrate:fresh failed"; exit 1; }

    print_header "DATABASE SEED"
    php artisan db:seed && print_success "Database seeded" \
        || { print_error "db:seed failed"; exit 1; }

    print_header "PHPUNIT TESTS"
    if [ -f "vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit && print_success "Tests passed" \
            || print_warning "Tests failed — review errors above."
    else
        print_warning "PHPUnit not found — skipping."
    fi

    print_header "OPTIMIZE"
    php artisan optimize:clear
    php artisan route:clear

    echo ""
    print_success "=================================="
    print_success "============== DONE =============="
    print_success "=================================="
    echo ""

    while true; do
        read -rp "Start dev server? (y/n) " cond
        case $cond in
            [Yy]*) php artisan serve; break ;;
            [Nn]*) print_success "Start later with: php artisan serve"; exit 0 ;;
            *)     print_warning "Please answer yes or no." ;;
        esac
    done
}

# ── Docker ────────────────────────────────────────────────────────────────────
install_docker() {
    print_header "DOCKER INSTALLATION"

    command_exists docker || { print_error "Docker not installed. See https://docs.docker.com/get-docker/"; exit 1; }
    print_success "Docker $(docker --version)"

    if ! docker compose version >/dev/null 2>&1 && ! command_exists docker-compose; then
        print_error "Docker Compose not found. See https://docs.docker.com/compose/install/"
        exit 1
    fi
    print_success "Docker Compose available"

    if [ ! -f ".env" ]; then
        cp .env.example .env
        print_warning "Copied .env.example → .env. Edit it, then press Enter."
        read -rp "Press Enter to continue..."
    fi

    if docker compose version >/dev/null 2>&1; then
        docker compose up -d --build
    else
        docker-compose up -d --build
    fi

    print_success "Docker containers started — http://localhost:8000"
    print_info "Migrate: docker compose exec app php artisan migrate"
}

# ── Kubernetes ────────────────────────────────────────────────────────────────
install_kubernetes() {
    print_header "KUBERNETES INSTALLATION"

    command_exists kubectl || { print_error "kubectl not installed. See https://kubernetes.io/docs/tasks/tools/"; exit 1; }
    print_success "kubectl $(kubectl version --client --short 2>/dev/null | head -1 || echo 'available')"

    local K8S_DIR="k8s"
    [ ! -d "$K8S_DIR" ] && [ -d "kubernetes" ] && K8S_DIR="kubernetes"

    if [ ! -d "$K8S_DIR" ]; then
        print_error "No Kubernetes config directory found (k8s/ or kubernetes/)."
        exit 1
    fi

    print_info "Using Kubernetes configs from: $K8S_DIR/"

    if [ ! -f ".env" ]; then
        cp .env.example .env
        print_warning "Copied .env.example → .env. Edit it, then press Enter."
        read -rp "Press Enter to continue..."
    fi

    # Run validation if available
    if [ -f "$K8S_DIR/validate.sh" ]; then
        print_info "Running pre-deployment validation..."
        bash "$K8S_DIR/validate.sh" || {
            print_error "Validation failed — aborting deployment."
            exit 1
        }
    fi

    print_info "Select deployment environment:"
    echo "  1) production"
    echo "  2) development"
    read -rp "Choice (1-2, default=1): " env_choice
    local DEPLOY_ENV
    case $env_choice in
        2) DEPLOY_ENV="development" ;;
        *) DEPLOY_ENV="production" ;;
    esac

    print_info "Deploying to Kubernetes ($DEPLOY_ENV)..."
    ENVIRONMENT="$DEPLOY_ENV" bash "$K8S_DIR/deploy.sh"

    print_success "Kubernetes deployment complete"
    print_info "Status: kubectl get pods -n liberu-crm"
}

# ── Main menu ─────────────────────────────────────────────────────────────────
main() {
    clear
    print_header "LIBERU CRM - INSTALLER"

    echo "Select installation type:"
    echo ""
    echo "  1) Standalone  (local development / production)"
    echo "  2) Docker      (containerised deployment)"
    echo "  3) Kubernetes  (K8s cluster deployment)"
    echo "  4) Exit"
    echo ""

    while true; do
        read -rp "Choice (1-4): " choice
        case $choice in
            1) install_standalone; break ;;
            2) install_docker; break ;;
            3) install_kubernetes; break ;;
            4) print_info "Cancelled."; exit 0 ;;
            *) print_warning "Invalid choice — enter 1, 2, 3, or 4." ;;
        esac
    done
}

main
