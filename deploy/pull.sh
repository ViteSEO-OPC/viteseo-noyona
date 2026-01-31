#!/bin/bash
# 
# ═══════════════════════════════════════════════════════════════════════════════
# Pull Script for WordPress Theme Deployment
# ═══════════════════════════════════════════════════════════════════════════════
#
# This script pulls the latest changes from the git repository to the server.
# It is called by the GitHub Actions deployment workflow (deploy.yml) after
# SSHing into the target server.
#
# ═══════════════════════════════════════════════════════════════════════════════
# USAGE
# ═══════════════════════════════════════════════════════════════════════════════
#
#   ./deploy/pull.sh --branch <name>    Pull from specified branch (switches if needed)
#   ./deploy/pull.sh --auto             Pull from current branch (no switch)
#   ./deploy/pull.sh                    Same as --auto
#   ./deploy/pull.sh --help             Show help message
#
# Examples:
#   ./deploy/pull.sh --branch main      # Pull from 'main' branch
#   ./deploy/pull.sh --branch dev       # Pull from 'dev' branch
#   ./deploy/pull.sh --branch feature/x # Pull from any feature branch
#
# ═══════════════════════════════════════════════════════════════════════════════
# PREREQUISITES
# ═══════════════════════════════════════════════════════════════════════════════
#
#   1. Git installed on the server
#   2. Repository cloned at the script's location
#   3. Git authentication configured (see GIT AUTHENTICATION below)
#   4. User has write permissions to the theme directory
#   5. Script has execute permissions: chmod +x deploy/pull.sh
#
# ═══════════════════════════════════════════════════════════════════════════════
# GIT AUTHENTICATION
# ═══════════════════════════════════════════════════════════════════════════════
#
# The server needs to pull from GitHub. Configure ONE of these options:
#
# OPTION A: SSH Key (Recommended)
#   - Clone via SSH: git clone git@github.com:user/repo.git
#   - Add server's public key to GitHub (deploy key or user SSH key)
#   - No additional configuration needed for this script
#
# OPTION B: HTTPS with PAT in URL
#   - Clone with PAT: git clone https://TOKEN@github.com/user/repo.git
#   - Or update existing: git remote set-url origin https://TOKEN@github.com/user/repo.git
#   - No additional configuration needed for this script
#
# OPTION C: HTTPS with GH_PAT Environment Variable
#   - Clone via HTTPS: git clone https://github.com/user/repo.git
#   - Set GH_PAT environment variable on the server:
#       export GH_PAT="ghp_xxxxxxxxxxxxxxxxxxxx"
#   - Or add to ~/.bashrc for persistence:
#       echo 'export GH_PAT="ghp_xxxxxxxxxxxxxxxxxxxx"' >> ~/.bashrc
#   - This script will automatically use GH_PAT for git authentication
#
# To check your current remote URL:
#   git remote -v
#
# ═══════════════════════════════════════════════════════════════════════════════
# ENVIRONMENT VARIABLES
# ═══════════════════════════════════════════════════════════════════════════════
#
# GH_PAT (optional)
#   GitHub Personal Access Token for HTTPS authentication.
#   Only required if using OPTION C above (plain HTTPS clone).
#   
#   Create a PAT at: https://github.com/settings/tokens
#   Required scopes: repo (Full control of private repositories)
#   
#   Example:
#     export GH_PAT="ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
#
# ═══════════════════════════════════════════════════════════════════════════════
# INTEGRATION WITH GITHUB ACTIONS
# ═══════════════════════════════════════════════════════════════════════════════
#
# The deploy.yml workflow calls this script with --branch parameter:
#   - Push to PROD_BRANCH    → ./deploy/pull.sh --branch <PROD_BRANCH value>
#   - Push to STAGING_BRANCH → ./deploy/pull.sh --branch <STAGING_BRANCH value>
#
# Branch names are configured via GitHub Secrets (PROD_BRANCH, STAGING_BRANCH),
# not hardcoded in this script.
#
# ═══════════════════════════════════════════════════════════════════════════════
# LOGGING
# ═══════════════════════════════════════════════════════════════════════════════
#
# Logs are written to: <theme-dir>/logs/pull-YYYYMMDD-HHMMSS.log
# Add 'logs/' to .gitignore to prevent committing log files.
#
#

# Strict mode for better error handling
set -o errexit   # Exit on any error (same as set -e)
set -o nounset   # Exit on undefined variable (same as set -u)
set -o pipefail  # Exit on pipe failures

# Exit codes
readonly EXIT_SUCCESS=0
readonly EXIT_GENERAL_ERROR=1
readonly EXIT_MISSING_PREREQ=2
readonly EXIT_GIT_ERROR=3
readonly EXIT_NETWORK_ERROR=4
readonly EXIT_CONFLICT=5

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
# Get the directory where this script is located (deploy folder)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Theme directory is the parent of the deploy folder
THEME_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$THEME_DIR/logs"
LOG_FILE="$LOG_DIR/pull-$(date +%Y%m%d-%H%M%S).log"

# Maximum log files to keep (for rotation)
readonly MAX_LOG_FILES=50

# Parse command line arguments
BRANCH=""
AUTO_DETECT=false

# Track if we stashed changes (for cleanup)
DID_STASH=false

# Track askpass file for cleanup
ASKPASS_FILE=""
USE_PAT_AUTH=false

# ═══════════════════════════════════════════════════════════════════════════════
# FUNCTION DEFINITIONS (must be defined before traps reference them)
# ═══════════════════════════════════════════════════════════════════════════════

# Cleanup function for askpass file
cleanup_askpass() {
    if [ -n "${ASKPASS_FILE:-}" ] && [ -f "$ASKPASS_FILE" ]; then
        rm -f "$ASKPASS_FILE" 2>/dev/null || true
    fi
}

# Logging function
log() {
    local level=$1
    shift
    local message="$*"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    # Use printf for safer output (handles special chars)
    printf '%s [%s] %s\n' "$timestamp" "$level" "$message" >> "$LOG_FILE" 2>/dev/null || true
    echo -e "${timestamp} [${level}] ${message}"
}

log_info() {
    echo -e "${BLUE}ℹ${NC} $*"
    log "INFO" "$*"
}

log_success() {
    echo -e "${GREEN}✓${NC} $*"
    log "SUCCESS" "$*"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $*"
    log "WARNING" "$*"
}

log_error() {
    echo -e "${RED}✗${NC} $*" >&2
    log "ERROR" "$*"
}

# Error handler function
handle_error() {
    local exit_code=$?
    local line_number=${1:-unknown}
    
    # Disable further error trapping to prevent recursion
    trap - ERR
    
    log_error "Script failed at line $line_number with exit code $exit_code"
    log_error "Check log file for details: $LOG_FILE"
    
    # Attempt to restore stashed changes if we stashed them
    if [ "${DID_STASH:-false}" = true ]; then
        log_warning "Attempting to restore stashed changes..."
        if git stash pop 2>/dev/null; then
            log_info "Stashed changes restored"
        else
            log_warning "Could not restore stashed changes. Run 'git stash pop' manually if needed."
        fi
    fi
    
    cleanup_askpass
    exit "$exit_code"
}

# Cleanup handler for normal exit
cleanup_on_exit() {
    local exit_code=$?
    cleanup_askpass
    exit "$exit_code"
}

# Log rotation - keep only the last N log files
rotate_logs() {
    local log_count
    log_count=$(find "$LOG_DIR" -maxdepth 1 -name 'pull-*.log' -type f 2>/dev/null | wc -l)
    
    if [ "$log_count" -gt "$MAX_LOG_FILES" ]; then
        local files_to_delete=$((log_count - MAX_LOG_FILES))
        log_info "Rotating logs: removing $files_to_delete old log files..."
        find "$LOG_DIR" -maxdepth 1 -name 'pull-*.log' -type f -printf '%T+ %p\n' 2>/dev/null | \
            sort | head -n "$files_to_delete" | cut -d' ' -f2- | xargs -r rm -f 2>/dev/null || true
    fi
}

# Check and remove stale git lock files
cleanup_git_locks() {
    local lock_file="$THEME_DIR/.git/index.lock"
    
    if [ -f "$lock_file" ]; then
        # Check if the lock is stale (older than 10 minutes)
        local lock_age
        lock_age=$(find "$lock_file" -mmin +10 2>/dev/null | wc -l)
        
        if [ "$lock_age" -gt 0 ]; then
            log_warning "Found stale git lock file (>10 min old). Removing..."
            rm -f "$lock_file" 2>/dev/null || {
                log_error "Cannot remove stale lock file: $lock_file"
                log_error "Another git process may be running, or remove it manually"
                return 1
            }
            log_success "Stale lock file removed"
        else
            log_error "Git lock file exists: $lock_file"
            log_error "Another git operation may be in progress."
            log_error "If no other git process is running, remove it manually:"
            log_error "  rm -f $lock_file"
            return 1
        fi
    fi
    return 0
}

# Validate branch name for safety (prevent command injection)
validate_branch_name() {
    local branch="$1"
    
    # Check for empty
    if [ -z "$branch" ]; then
        log_error "Branch name cannot be empty"
        return 1
    fi
    
    # Check for dangerous characters (allow only safe chars)
    if [[ ! "$branch" =~ ^[a-zA-Z0-9/_.-]+$ ]]; then
        log_error "Branch name contains invalid characters: $branch"
        log_error "Allowed characters: letters, numbers, /, _, ., -"
        return 1
    fi
    
    # Check for path traversal attempts
    if [[ "$branch" =~ \.\. ]]; then
        log_error "Branch name cannot contain '..': $branch"
        return 1
    fi
    
    return 0
}

# Check for merge conflicts
check_merge_conflicts() {
    if git ls-files -u 2>/dev/null | grep -q .; then
        log_error "Merge conflicts detected!"
        log_error "Conflicting files:"
        git ls-files -u 2>&1 | tee -a "$LOG_FILE"
        log_error ""
        log_error "To resolve:"
        log_error "  1. SSH to server and cd to $THEME_DIR"
        log_error "  2. Resolve conflicts manually"
        log_error "  3. Run: git add . && git commit -m 'Resolve merge conflicts'"
        log_error "  4. Re-run deployment"
        return 1
    fi
    return 0
}

# ═══════════════════════════════════════════════════════════════════════════════
# SET UP TRAPS (after functions are defined)
# ═══════════════════════════════════════════════════════════════════════════════

# Set up error trap - will call handle_error on any error
trap 'handle_error $LINENO' ERR

# Trap EXIT for cleanup
trap cleanup_on_exit EXIT

# Trap common signals for cleanup
trap 'log_warning "Received interrupt signal"; cleanup_askpass; exit 130' INT
trap 'log_warning "Received termination signal"; cleanup_askpass; exit 143' TERM

# ═══════════════════════════════════════════════════════════════════════════════
# INITIALIZATION
# ═══════════════════════════════════════════════════════════════════════════════

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR" || {
    echo "ERROR: Failed to create log directory: $LOG_DIR" >&2
    exit $EXIT_GENERAL_ERROR
}

# Rotate old logs
rotate_logs

# ═══════════════════════════════════════════════════════════════════════════════
# ARGUMENT PARSING
# ═══════════════════════════════════════════════════════════════════════════════

while [[ $# -gt 0 ]]; do
    case $1 in
        --branch)
            if [ -z "${2:-}" ]; then
                log_error "Option --branch requires an argument"
                exit $EXIT_GENERAL_ERROR
            fi
            BRANCH="$2"
            shift 2
            ;;
        --auto)
            AUTO_DETECT=true
            shift
            ;;
        -h|--help)
            echo "Usage: $0 [--branch <branch-name>] [--auto]"
            echo ""
            echo "Options:"
            echo "  --branch <name>  Specify the branch to pull from"
            echo "  --auto           Auto-detect branch from current checkout"
            echo "  -h, --help       Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --branch main      # Pull from 'main' branch"
            echo "  $0 --branch dev       # Pull from 'dev' branch"
            echo "  $0 --auto             # Pull from current branch"
            echo "  $0                    # Same as --auto"
            echo ""
            echo "Exit codes:"
            echo "  0 - Success"
            echo "  1 - General error"
            echo "  2 - Missing prerequisites"
            echo "  3 - Git error"
            echo "  4 - Network error"
            echo "  5 - Merge conflict"
            exit $EXIT_SUCCESS
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Usage: $0 [--branch <branch-name>] [--auto]"
            echo "Use -h or --help for more information"
            exit $EXIT_GENERAL_ERROR
            ;;
    esac
done

# If no branch specified, auto-detect from current checkout
if [ -z "$BRANCH" ]; then
    AUTO_DETECT=true
fi

# Validate branch name if provided
if [ -n "$BRANCH" ]; then
    validate_branch_name "$BRANCH" || exit $EXIT_GENERAL_ERROR
fi

# Configure GitHub PAT for non-interactive git commands (optional)
# If GH_PAT environment variable is set, configure git to use it for authentication
setup_git_askpass() {
    if [ -n "${GH_PAT:-}" ]; then
        USE_PAT_AUTH=true
        ASKPASS_FILE="$(mktemp /tmp/git-askpass.XXXXXX)" || {
            log_error "Failed to create temporary askpass file"
            return 1
        }
        cat > "$ASKPASS_FILE" <<'EOF'
#!/usr/bin/env bash
echo "$GH_PAT"
EOF
        chmod 700 "$ASKPASS_FILE" || {
            log_error "Failed to set permissions on askpass file"
            rm -f "$ASKPASS_FILE"
            return 1
        }
        export GIT_ASKPASS="$ASKPASS_FILE"
        export GIT_TERMINAL_PROMPT=0
        export GIT_USERNAME="x-access-token"
        log_info "PAT authentication configured"
    fi
}

# Helper function to run git commands with optional PAT authentication and error handling
run_git() {
    local result exit_code=0
    if [ "$USE_PAT_AUTH" = true ]; then
        result=$(git -c "credential.username=$GIT_USERNAME" "$@" 2>&1) || exit_code=$?
    else
        result=$(git "$@" 2>&1) || exit_code=$?
    fi
    
    if [ $exit_code -ne 0 ]; then
        log_error "Git command failed: git $*"
        log_error "Output: $result"
        return $EXIT_GIT_ERROR
    fi
    
    echo "$result"
    return 0
}

# Verify prerequisites
verify_prerequisites() {
    # Check git is installed
    if ! command -v git &> /dev/null; then
        log_error "Git is not installed or not in PATH"
        exit $EXIT_MISSING_PREREQ
    fi
    log_info "Git version: $(git --version)"
    
    # Check we can write to log directory
    if [ ! -w "$LOG_DIR" ]; then
        log_error "Cannot write to log directory: $LOG_DIR"
        exit $EXIT_MISSING_PREREQ
    fi
}

verify_prerequisites
setup_git_askpass

# ═══════════════════════════════════════════════════════════════════════════════
# START DEPLOYMENT
# ═══════════════════════════════════════════════════════════════════════════════

log_info "========================================"
log_info "Starting deployment"
log_info "Timestamp: $(date '+%Y-%m-%d %H:%M:%S %Z')"
log_info "========================================"

# Check if theme directory exists
if [ ! -d "$THEME_DIR" ]; then
    log_error "Theme directory does not exist: $THEME_DIR"
    log_error "Please clone the repository first:"
    log_error "  git clone <repository-url> $THEME_DIR"
    exit $EXIT_MISSING_PREREQ
fi

# Check if it's a git repository
if [ ! -d "$THEME_DIR/.git" ]; then
    log_error "Directory is not a git repository: $THEME_DIR"
    log_error "Please initialize git or clone the repository"
    exit $EXIT_MISSING_PREREQ
fi

# Change to theme directory
if ! cd "$THEME_DIR"; then
    log_error "Failed to change to theme directory: $THEME_DIR"
    exit $EXIT_GENERAL_ERROR
fi
log_info "Changed to directory: $THEME_DIR"

# Check for and clean up stale git lock files
cleanup_git_locks || exit $EXIT_GIT_ERROR

# Check for existing merge conflicts before proceeding
check_merge_conflicts || exit $EXIT_CONFLICT

# Get current branch/state
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null) || {
    log_error "Failed to determine current branch. Is this a valid git repository?"
    exit $EXIT_GIT_ERROR
}

# Handle detached HEAD state
if [ "$CURRENT_BRANCH" = "HEAD" ]; then
    CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null) || CURRENT_COMMIT="unknown"
    log_warning "Repository is in detached HEAD state (at commit: $CURRENT_COMMIT)"
    
    if [ "$AUTO_DETECT" = true ]; then
        log_error "Cannot auto-detect branch in detached HEAD state"
        log_error "Please specify branch with: --branch <branch-name>"
        log_error "Or checkout a branch: git checkout <branch-name>"
        exit $EXIT_GIT_ERROR
    fi
    
    log_info "Will checkout specified branch: $BRANCH"
else
    log_info "Current branch: $CURRENT_BRANCH"
fi

# Handle branch selection
if [ "$AUTO_DETECT" = true ]; then
    BRANCH="$CURRENT_BRANCH"
    log_info "Auto-detected branch: $BRANCH"
    
    # Validate auto-detected branch name
    validate_branch_name "$BRANCH" || {
        log_error "Auto-detected branch name is invalid"
        exit $EXIT_GIT_ERROR
    }
else
    log_info "Target branch: $BRANCH"
    
    # Verify the target branch exists (locally or remotely)
    if ! git show-ref --verify --quiet "refs/heads/$BRANCH" && \
       ! git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
        log_error "Branch '$BRANCH' does not exist locally or on remote"
        log_error "Available local branches:"
        git branch 2>&1 | tee -a "$LOG_FILE"
        log_error "Available remote branches:"
        git branch -r 2>&1 | tee -a "$LOG_FILE"
        exit $EXIT_GIT_ERROR
    fi
    
    # Checkout target branch if not already on it
    if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
        log_info "Switching to branch: $BRANCH"
        
        # First, try to checkout if it exists locally
        if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
            if ! git checkout "$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
                log_error "Failed to checkout local branch: $BRANCH"
                exit $EXIT_GIT_ERROR
            fi
        else
            # Branch exists only on remote, create tracking branch
            log_info "Creating local tracking branch for origin/$BRANCH"
            if ! git checkout -b "$BRANCH" "origin/$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
                log_error "Failed to checkout remote branch: origin/$BRANCH"
                exit $EXIT_GIT_ERROR
            fi
        fi
        log_success "Switched to branch: $BRANCH"
    fi
fi

# ═══════════════════════════════════════════════════════════════════════════════
# FETCH AND PULL
# ═══════════════════════════════════════════════════════════════════════════════

# Fetch latest changes with timeout and retry
log_info "Fetching latest changes from remote..."
FETCH_ATTEMPTS=0
MAX_FETCH_ATTEMPTS=3
FETCH_SUCCESS=false

while [ $FETCH_ATTEMPTS -lt $MAX_FETCH_ATTEMPTS ]; do
    FETCH_ATTEMPTS=$((FETCH_ATTEMPTS + 1))
    log_info "Fetch attempt $FETCH_ATTEMPTS of $MAX_FETCH_ATTEMPTS..."
    
    # Disable pipefail temporarily for this command to handle tee properly
    set +o pipefail
    FETCH_OUTPUT=$(run_git fetch origin 2>&1) && FETCH_SUCCESS=true
    FETCH_EXIT=$?
    set -o pipefail
    
    # Log the output
    echo "$FETCH_OUTPUT" >> "$LOG_FILE"
    echo "$FETCH_OUTPUT"
    
    if [ "$FETCH_SUCCESS" = true ]; then
        log_success "Fetch completed"
        break
    else
        if [ $FETCH_ATTEMPTS -lt $MAX_FETCH_ATTEMPTS ]; then
            log_warning "Fetch attempt $FETCH_ATTEMPTS failed. Retrying in 5 seconds..."
            sleep 5
        else
            log_error "Failed to fetch from remote after $MAX_FETCH_ATTEMPTS attempts"
            log_error "Possible causes:"
            log_error "  - Network connectivity issues"
            log_error "  - Remote repository unavailable"
            log_error "  - Authentication failure"
            exit $EXIT_NETWORK_ERROR
        fi
    fi
done

# Check if upstream is configured
if ! git rev-parse --abbrev-ref "@{u}" &>/dev/null; then
    log_warning "No upstream configured for branch '$BRANCH'"
    log_info "Setting upstream to origin/$BRANCH..."
    
    # First verify origin/$BRANCH exists
    if ! git show-ref --verify --quiet "refs/remotes/origin/$BRANCH"; then
        log_error "Remote branch origin/$BRANCH does not exist"
        log_error "Available remote branches:"
        git branch -r 2>&1 | tee -a "$LOG_FILE"
        exit $EXIT_GIT_ERROR
    fi
    
    if ! git branch --set-upstream-to="origin/$BRANCH" "$BRANCH" 2>&1 | tee -a "$LOG_FILE"; then
        log_error "Failed to set upstream. Make sure origin/$BRANCH exists."
        exit $EXIT_GIT_ERROR
    fi
    log_success "Upstream configured"
fi

# Check if there are changes
LOCAL=$(git rev-parse @ 2>/dev/null) || {
    log_error "Failed to get local commit hash"
    exit $EXIT_GIT_ERROR
}

REMOTE=$(git rev-parse "@{u}" 2>/dev/null) || {
    log_error "Failed to get remote commit hash. Is the upstream configured?"
    exit $EXIT_GIT_ERROR
}

# Check for diverged history
BASE=$(git merge-base @ "@{u}" 2>/dev/null) || BASE=""

if [ "$LOCAL" = "$REMOTE" ]; then
    log_info "Already up to date. No changes to pull."
elif [ -n "$BASE" ] && [ "$LOCAL" = "$BASE" ]; then
    # Normal case: local is behind remote
    log_info "Changes detected. Pulling latest code..."
    log_info "Local:  ${LOCAL:0:8}"
    log_info "Remote: ${REMOTE:0:8}"
    log_info "Commits to pull: $(git rev-list --count "$LOCAL".."$REMOTE" 2>/dev/null || echo "unknown")"
    
    # Stash any local changes (just in case)
    if ! git diff-index --quiet HEAD -- 2>/dev/null; then
        log_warning "Local changes detected. Stashing..."
        STASH_MSG="Auto-stash before deployment $(date +%Y%m%d-%H%M%S)"
        if git stash push -m "$STASH_MSG" 2>&1 | tee -a "$LOG_FILE"; then
            DID_STASH=true
            log_success "Changes stashed: $STASH_MSG"
        else
            log_error "Failed to stash local changes"
            log_error "You may need to commit or discard local changes manually"
            exit $EXIT_GIT_ERROR
        fi
    fi
    
    # Also check for untracked files that might conflict
    UNTRACKED_COUNT=$(git ls-files --others --exclude-standard 2>/dev/null | wc -l)
    if [ "$UNTRACKED_COUNT" -gt 0 ]; then
        log_warning "Found $UNTRACKED_COUNT untracked files (these won't be affected by pull)"
    fi
    
    # Pull latest changes with retry
    PULL_ATTEMPTS=0
    MAX_PULL_ATTEMPTS=3
    PULL_SUCCESS=false
    
    while [ $PULL_ATTEMPTS -lt $MAX_PULL_ATTEMPTS ]; do
        PULL_ATTEMPTS=$((PULL_ATTEMPTS + 1))
        log_info "Pull attempt $PULL_ATTEMPTS of $MAX_PULL_ATTEMPTS..."
        
        # Disable pipefail temporarily for this command
        set +o pipefail
        PULL_OUTPUT=$(run_git pull --ff-only origin "$BRANCH" 2>&1) && PULL_SUCCESS=true
        PULL_EXIT=$?
        set -o pipefail
        
        # Log the output
        echo "$PULL_OUTPUT" >> "$LOG_FILE"
        echo "$PULL_OUTPUT"
        
        if [ "$PULL_SUCCESS" = true ]; then
            log_success "Pull completed successfully"
            break
        else
            # Check for merge conflict
            if echo "$PULL_OUTPUT" | grep -qi "conflict\|merge\|CONFLICT"; then
                log_error "Merge conflict detected during pull!"
                check_merge_conflicts || true  # Log conflict details
                exit $EXIT_CONFLICT
            fi
            
            if [ $PULL_ATTEMPTS -lt $MAX_PULL_ATTEMPTS ]; then
                log_warning "Pull attempt $PULL_ATTEMPTS failed. Retrying in 5 seconds..."
                sleep 5
            else
                log_error "Failed to pull from remote after $MAX_PULL_ATTEMPTS attempts"
                exit $EXIT_NETWORK_ERROR
            fi
        fi
    done
    
    # Verify no merge conflicts after pull
    check_merge_conflicts || exit $EXIT_CONFLICT
    
    # Show what changed
    log_info "Latest commit:"
    git log -1 --pretty=format:"  %h - %an, %ar : %s" 2>&1 | tee -a "$LOG_FILE" || true
    echo ""
    
    # Show files changed
    CHANGED_FILES=$(git diff --name-only "$LOCAL".."$REMOTE" 2>/dev/null | wc -l)
    log_info "Files changed: $CHANGED_FILES"
    
    # Optionally restore stashed changes
    if [ "$DID_STASH" = true ]; then
        log_info "Note: Local changes were stashed. Run 'git stash pop' to restore if needed."
        log_info "Stash list: $(git stash list 2>/dev/null | head -1)"
    fi
    
elif [ -n "$BASE" ] && [ "$REMOTE" = "$BASE" ]; then
    # Local is ahead of remote
    log_warning "Local branch is ahead of remote by $(git rev-list --count "$REMOTE".."$LOCAL" 2>/dev/null || echo "unknown") commits"
    log_warning "No pull needed. Consider pushing your changes."
    log_info "Local:  ${LOCAL:0:8}"
    log_info "Remote: ${REMOTE:0:8}"
    
else
    # Branches have diverged
    LOCAL_AHEAD=$(git rev-list --count "$BASE".."$LOCAL" 2>/dev/null || echo "?")
    REMOTE_AHEAD=$(git rev-list --count "$BASE".."$REMOTE" 2>/dev/null || echo "?")
    
    log_error "Branches have diverged!"
    log_error "Local is $LOCAL_AHEAD commits ahead, remote is $REMOTE_AHEAD commits ahead"
    log_error "Local:  ${LOCAL:0:8}"
    log_error "Remote: ${REMOTE:0:8}"
    log_error "Base:   ${BASE:0:8}"
    log_error ""
    log_error "To resolve, SSH to server and either:"
    log_error "  1. Reset to remote: git reset --hard origin/$BRANCH"
    log_error "  2. Merge manually:  git merge origin/$BRANCH"
    log_error "  3. Rebase:          git rebase origin/$BRANCH"
    exit $EXIT_CONFLICT
fi

# Optional: Run additional deployment tasks
# Uncomment and customize as needed:

# Clear WordPress cache (if using a caching plugin)
# log_info "Clearing WordPress cache..."
# if command -v wp &> /dev/null; then
#     wp cache flush --path=/var/www/html 2>&1 | tee -a "$LOG_FILE" || log_warning "Cache flush failed"
# fi

# Set proper file permissions
# log_info "Setting file permissions..."
# find "$THEME_DIR" -type f -exec chmod 644 {} \; 2>/dev/null || log_warning "Failed to set file permissions"
# find "$THEME_DIR" -type d -exec chmod 755 {} \; 2>/dev/null || log_warning "Failed to set directory permissions"

# Restart PHP-FPM (if needed)
# log_info "Restarting PHP-FPM..."
# sudo systemctl restart php8.2-fpm 2>&1 | tee -a "$LOG_FILE" || log_warning "Failed to restart PHP-FPM"

log_success "========================================"
log_success "Deployment completed successfully!"
log_success "Branch: $BRANCH"
log_success "Directory: $THEME_DIR"
log_success "Log file: $LOG_FILE"
log_success "========================================"

# Disable error trap for clean exit
trap - ERR
exit $EXIT_SUCCESS
