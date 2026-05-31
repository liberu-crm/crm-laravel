#!/bin/bash
# Kubernetes Deployment Script for Liberu CRM
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

NAMESPACE="${NAMESPACE:-liberu-crm}"
ENVIRONMENT="${ENVIRONMENT:-production}"
DOMAIN="${DOMAIN:-crm.example.com}"
APP_KEY="${APP_KEY:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"

print_info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
print_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${GREEN}=== Liberu CRM Kubernetes Deployment ===${NC}"
echo ""

if ! command -v kubectl &> /dev/null; then
    print_error "kubectl is not installed."
    exit 1
fi

[ -z "$APP_KEY" ] && { print_error "APP_KEY is required. Generate: php artisan key:generate --show"; exit 1; }
[ -z "$DB_PASSWORD" ] && { print_error "DB_PASSWORD is required."; exit 1; }
[ -z "$DB_ROOT_PASSWORD" ] && { print_error "DB_ROOT_PASSWORD is required."; exit 1; }

print_info "Creating namespace: $NAMESPACE"
kubectl create namespace "$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -

print_info "Updating secrets..."
kubectl create secret generic liberu-crm-secrets \
    --from-literal=APP_KEY="$APP_KEY" \
    --from-literal=DB_USERNAME="liberu_crm" \
    --from-literal=DB_PASSWORD="$DB_PASSWORD" \
    --from-literal=DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
    --from-literal=REDIS_PASSWORD="" \
    --namespace="$NAMESPACE" \
    --dry-run=client -o yaml | kubectl apply -f -

print_info "Configuring ingress domain: $DOMAIN"
cat > "k8s/overlays/$ENVIRONMENT/ingress-patch.yaml" <<EOF
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: liberu-crm
spec:
  tls:
  - hosts:
    - $DOMAIN
    secretName: liberu-crm-tls
  rules:
  - host: $DOMAIN
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: liberu-crm
            port:
              number: 80
EOF

print_info "Deploying to Kubernetes (env: $ENVIRONMENT)..."
if command -v kustomize &> /dev/null; then
    kustomize build "k8s/overlays/$ENVIRONMENT" | kubectl apply -f -
else
    kubectl apply -k "k8s/overlays/$ENVIRONMENT"
fi

print_info "Waiting for deployment..."
kubectl wait --for=condition=available --timeout=300s \
    deployment/liberu-crm -n "$NAMESPACE" || true

print_info "Deployment complete!"
echo ""
echo "Status:  kubectl get pods -n $NAMESPACE"
echo "Logs:    kubectl logs -n $NAMESPACE -l app=liberu-crm -c app"
echo "App URL: https://$DOMAIN"
