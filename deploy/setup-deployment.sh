#!/bin/bash
#
# ═══════════════════════════════════════════════════════════════════════════════
# Deployment Setup Script
# ═══════════════════════════════════════════════════════════════════════════════
#
# This script helps you set up multi-environment deployment by:
#   1. Generating SSH keys for GitHub Actions to connect to your servers
#   2. Providing the exact values to paste into GitHub Secrets
#   3. Optionally installing the public key on your server
#
# USAGE:
#   ./deploy/setup-deployment.sh              # Interactive mode
#   ./deploy/setup-deployment.sh --env prod   # Generate production keys
#   ./deploy/setup-deployment.sh --env staging # Generate staging keys
#
# ═══════════════════════════════════════════════════════════════════════════════

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

print_header() {
    echo ""
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}  $1${NC}"
    echo -e "${CYAN}═══════════════════════════════════════════════════════════════════════════════${NC}"
    echo ""
}

print_step() { echo -e "${BLUE}▶${NC} ${BOLD}$1${NC}"; }
print_success() { echo -e "${GREEN}✓${NC} $1"; }
print_warning() { echo -e "${YELLOW}⚠${NC} $1"; }
print_error() { echo -e "${RED}✗${NC} $1"; }
print_info() { echo -e "${BLUE}ℹ${NC} $1"; }

ENV=""
while [[ $# -gt 0 ]]; do
    case $1 in
        --env) ENV="$2"; shift 2 ;;
        -h|--help)
            echo "Usage: $0 [--env prod|staging]"
            echo "  --env prod      Generate production keys"
            echo "  --env staging   Generate staging keys"
            exit 0 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

KEYS_DIR="$HOME/.ssh/github-deploy-keys"
mkdir -p "$KEYS_DIR"
chmod 700 "$KEYS_DIR"

generate_keys() {
    local env_name="$1"
    local env_upper=$(echo "$env_name" | tr '[:lower:]' '[:upper:]')
    local key_file="$KEYS_DIR/deploy_${env_name}_key"
    
    print_header "Generating ${env_upper} Environment Keys"
    
    if [ -f "$key_file" ]; then
        print_warning "Key exists: $key_file"
        read -p "Overwrite? (y/N): " overwrite
        [[ "$overwrite" =~ ^[Yy]$ ]] && rm -f "$key_file" "$key_file.pub"
    fi
    
    if [ ! -f "$key_file" ]; then
        print_step "Generating SSH key pair..."
        ssh-keygen -t ed25519 -C "github-deploy-${env_name}" -f "$key_file" -N "" -q
        chmod 600 "$key_file"
        print_success "SSH key pair generated"
    fi
    
    echo ""
    print_step "Collecting ${env_upper} server info..."
    read -p "  SSH Host: " ssh_host
    read -p "  SSH Port [22]: " ssh_port; ssh_port=${ssh_port:-22}
    read -p "  SSH Username: " ssh_user
    read -p "  Theme Directory: " theme_dir
    read -p "  Git Branch: " branch_name
    
    print_header "GitHub Secrets for ${env_upper}"
    
    local prefix="STAGING_"
    [ "$env_name" == "prod" ] && prefix="PROD_"
    
    echo -e "${YELLOW}Add these to GitHub → Settings → Secrets:${NC}"
    echo ""
    echo "  ${prefix}BRANCH         = $branch_name"
    echo "  ${prefix}SSH_HOST       = $ssh_host"
    echo "  ${prefix}SSH_PORT       = $ssh_port"
    echo "  ${prefix}SSH_USER       = $ssh_user"
    echo "  ${prefix}THEME_DIR      = $theme_dir"
    echo ""
    echo -e "${YELLOW}${prefix}SSH_PRIVATE_KEY (copy all lines):${NC}"
    echo ""
    cat "$key_file"
    echo ""
    
    print_header "Public Key for ${env_upper} Server"
    echo -e "Add to ${CYAN}~/.ssh/authorized_keys${NC} on server:"
    echo ""
    cat "$key_file.pub"
    echo ""
    
    read -p "Install key on server now? (y/N): " install_key
    if [[ "$install_key" =~ ^[Yy]$ ]]; then
        ssh-copy-id -i "$key_file.pub" -p "$ssh_port" "$ssh_user@$ssh_host" || \
            print_error "Auto-install failed. Copy public key manually."
    fi
}

show_summary() {
    print_header "All Required Secrets"
    echo "  PROD_BRANCH, STAGING_BRANCH"
    echo "  PROD_SSH_PRIVATE_KEY, PROD_SSH_HOST, PROD_SSH_PORT, PROD_SSH_USER, PROD_THEME_DIR"
    echo "  STAGING_SSH_PRIVATE_KEY, STAGING_SSH_HOST, STAGING_SSH_PORT, STAGING_SSH_USER, STAGING_THEME_DIR"
    echo ""
    print_info "Keys saved in: $KEYS_DIR"
}

print_header "Multi-Environment Deployment Setup"

if [ -n "$ENV" ]; then
    [[ "$ENV" =~ ^(prod|production)$ ]] && generate_keys "prod"
    [[ "$ENV" =~ ^(staging|stage)$ ]] && generate_keys "staging"
else
    echo "Configure: 1) Prod  2) Staging  3) Both"
    read -p "Select [3]: " opt; opt=${opt:-3}
    case $opt in
        1) generate_keys "prod" ;;
        2) generate_keys "staging" ;;
        3) generate_keys "staging"; generate_keys "prod" ;;
    esac
fi

show_summary
print_success "Setup complete!"
