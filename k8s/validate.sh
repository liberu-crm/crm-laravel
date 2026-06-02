#!/bin/bash
# Pre-deployment validation for Liberu CRM Kubernetes manifests.
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

NAMESPACE="${NAMESPACE:-liberu-crm}"
ENVIRONMENT="${ENVIRONMENT:-production}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
K8S_DIR="$SCRIPT_DIR"
ERRORS=0
WARNINGS=0

info()    { echo -e "${GREEN}[INFO]${NC}  $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; WARNINGS=$((WARNINGS+1)); }
error()   { echo -e "${RED}[ERROR]${NC} $1"; ERRORS=$((ERRORS+1)); }
section() { echo -e "\n${BLUE}=== $1 ===${NC}"; }

# ── Tool checks ──────────────────────────────────────────────────────────────
section "Tool Checks"

for tool in kubectl kustomize; do
    if command -v "$tool" &>/dev/null; then
        info "$tool found: $(command -v "$tool")"
    else
        if [ "$tool" = "kustomize" ]; then
            warn "kustomize not found — will fall back to kubectl apply -k"
        else
            error "$tool is required but not installed"
        fi
    fi
done

# ── Environment variable checks ───────────────────────────────────────────────
section "Environment Variable Checks"

[ -z "${APP_KEY:-}" ]          && error "APP_KEY is not set" || info "APP_KEY is set"
[ -z "${DB_PASSWORD:-}" ]      && error "DB_PASSWORD is not set" || info "DB_PASSWORD is set"
[ -z "${DB_ROOT_PASSWORD:-}" ] && error "DB_ROOT_PASSWORD is not set" || info "DB_ROOT_PASSWORD is set"
[ -z "${DOMAIN:-}" ]           && warn  "DOMAIN is not set (will default to crm.example.com)" || info "DOMAIN=$DOMAIN"

# ── Manifest file existence ───────────────────────────────────────────────────
section "Manifest File Checks"

BASE_DIR="$K8S_DIR/base"
OVERLAY_DIR="$K8S_DIR/overlays/$ENVIRONMENT"

required_base=(
    namespace.yaml configmap.yaml secret.yaml pvc.yaml
    resource-quota.yaml mysql-statefulset.yaml redis.yaml
    deployment.yaml service.yaml ingress.yaml network-policy.yaml
    kustomization.yaml
)

for f in "${required_base[@]}"; do
    if [ -f "$BASE_DIR/$f" ]; then
        info "base/$f ✓"
    else
        error "base/$f is missing"
    fi
done

for f in kustomization.yaml; do
    if [ -f "$OVERLAY_DIR/$f" ]; then
        info "overlays/$ENVIRONMENT/$f ✓"
    else
        error "overlays/$ENVIRONMENT/$f is missing"
    fi
done

# ── Kustomize dry-run ─────────────────────────────────────────────────────────
section "Kustomize Dry-Run"

if command -v kustomize &>/dev/null; then
    if kustomize build "$OVERLAY_DIR" >/dev/null 2>&1; then
        info "kustomize build succeeded"
    else
        error "kustomize build failed for overlays/$ENVIRONMENT"
        kustomize build "$OVERLAY_DIR" 2>&1 | sed 's/^/    /'
    fi
elif command -v kubectl &>/dev/null; then
    if kubectl kustomize "$OVERLAY_DIR" >/dev/null 2>&1; then
        info "kubectl kustomize succeeded"
    else
        error "kubectl kustomize failed for overlays/$ENVIRONMENT"
    fi
fi

# ── kubectl connectivity (optional) ──────────────────────────────────────────
section "Cluster Connectivity"

if command -v kubectl &>/dev/null; then
    if kubectl cluster-info &>/dev/null 2>&1; then
        info "kubectl can reach the cluster"
        if kubectl get namespace "$NAMESPACE" &>/dev/null 2>&1; then
            info "Namespace '$NAMESPACE' already exists"
        else
            info "Namespace '$NAMESPACE' does not exist yet (will be created)"
        fi
    else
        warn "kubectl cannot reach a cluster — skipping live checks"
    fi
fi

# ── Summary ───────────────────────────────────────────────────────────────────
section "Validation Summary"

echo ""
echo "  Warnings : $WARNINGS"
echo "  Errors   : $ERRORS"
echo ""

if [ "$ERRORS" -gt 0 ]; then
    echo -e "${RED}Validation FAILED — fix errors before deploying.${NC}"
    exit 1
else
    echo -e "${GREEN}Validation PASSED${NC}${YELLOW}$([ "$WARNINGS" -gt 0 ] && echo " (with $WARNINGS warning(s))")${NC}"
fi
