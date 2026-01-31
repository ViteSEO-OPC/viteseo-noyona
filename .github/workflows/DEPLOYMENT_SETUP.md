# Multi-Environment Deployment Setup Guide

This guide walks you through setting up automatic multi-environment deployment from GitHub to your servers.

> ⚠️ **Important:** This deployment system requires a VPS or server that supports SSH commands. Managed hosting platforms that don't provide ssh access (such as EasyWP) are not compatible.

## Overview

This workflow supports **two environments** with automatic branch-based deployment:

| Push to Branch | Deploys to | Uses Secrets |
|----------------|------------|--------------|
| `PROD_BRANCH` (e.g., `main`) | Production Server | `PROD_*` |
| `STAGING_BRANCH` (e.g., `dev`) | Staging Server | `STAGING_*` |
| Other branches | Skipped | — |

**How it works:**
1. You push code to a configured branch (e.g., `main` or `dev`)
2. GitHub Actions detects which environment based on branch name
3. Connects to the appropriate server via SSH
4. Runs `./deploy/pull.sh --branch <branch-name>` on the server
5. The script pulls the latest code from GitHub

## Prerequisites

### On Each Server (Production & Staging)

1. **Git installed**
   ```bash
   git --version  # Check if installed
   sudo apt install git  # Install if needed (Ubuntu/Debian)
   ```

2. **Repository cloned at the theme directory** (see [Git Authentication](#step-4-configure-git-authentication-on-servers) for URL options)
   ```bash
   cd /path/to/wp-content/themes
   git clone <your-repository-url> your-theme-name
   cd your-theme-name
   git config user.name "Deploy Bot"
   git config user.email "deploy@example.com"
   ```

3. **Pull script executable**
   ```bash
   chmod +x deploy/pull.sh
   ```

4. **Git authentication configured** (see [Step 4](#step-4-configure-git-authentication-on-servers))

### On GitHub

- Repository secrets configured (see below)
- GitHub Actions enabled

---

## Step 1: Generate SSH Keys

Generate **separate SSH keys** for each environment.

### Option 1: Use the Setup Script (Recommended)

```bash
./deploy/setup-deployment.sh
```

This interactive script will generate keys, collect server info, and show you exactly what to paste into GitHub Secrets.

### Option 2: Manual Generation

```bash
# For Production
ssh-keygen -t ed25519 -C "github-deploy-prod" -f ~/.ssh/deploy_prod_key

# For Staging
ssh-keygen -t ed25519 -C "github-deploy-staging" -f ~/.ssh/deploy_staging_key
```

## Step 2: Install Public Keys on Servers

**On Production Server:**
```bash
ssh-copy-id -i ~/.ssh/deploy_prod_key.pub -p 22 user@production-server.com
```

**On Staging Server:**
```bash
ssh-copy-id -i ~/.ssh/deploy_staging_key.pub -p 22 user@staging-server.com
```

## Step 3: Configure GitHub Secrets

Go to: **GitHub Repository → Settings → Secrets and variables → Actions**

### Branch Configuration (Required)

| Secret | Value | Example |
|--------|-------|---------|
| `PROD_BRANCH` | Branch name for production | `main` |
| `STAGING_BRANCH` | Branch name for staging | `dev` |

### Production Environment Secrets

| Secret | Value | Example |
|--------|-------|---------|
| `PROD_SSH_PRIVATE_KEY` | Contents of `~/.ssh/deploy_prod_key` | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `PROD_SSH_HOST` | Production server hostname or IP | `prod.example.com` |
| `PROD_SSH_PORT` | SSH port number | `22` |
| `PROD_SSH_USER` | Username on production server | `deploy` |
| `PROD_THEME_DIR` | Theme directory path | `/var/www/html/wp-content/themes/mytheme` |

### Staging Environment Secrets

| Secret | Value | Example |
|--------|-------|---------|
| `STAGING_SSH_PRIVATE_KEY` | Contents of `~/.ssh/deploy_staging_key` | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `STAGING_SSH_HOST` | Staging server hostname or IP | `staging.example.com` |
| `STAGING_SSH_PORT` | SSH port number | `22` |
| `STAGING_SSH_USER` | Username on staging server | `deploy` |
| `STAGING_THEME_DIR` | Theme directory path | `/var/www/staging/wp-content/themes/mytheme` |

**To get private key contents:**
```bash
cat ~/.ssh/deploy_prod_key
# Copy the ENTIRE output including BEGIN and END lines
```

## Step 4: Configure Git Authentication on Servers

The server needs to pull from GitHub. Choose **ONE** of these options:

### Option A: SSH Keys (Recommended) ✅

Most secure option. The server uses SSH keys to authenticate with GitHub.

**On each server:**
```bash
# Generate SSH key on server
ssh-keygen -t ed25519 -C "server-git-pull" -f ~/.ssh/git_key

# Display public key
cat ~/.ssh/git_key.pub
```

**On GitHub:**
1. Go to **Repository → Settings → Deploy keys** (for single repo) or **GitHub Settings → SSH keys** (for all repos)
2. Add the public key from above

**Update remote URL to use SSH:**
```bash
cd /path/to/theme
git remote set-url origin git@github.com:username/repo.git
git remote -v  # Verify: should show git@github.com:...
```

### Option B: HTTPS with PAT in URL ✅

Simpler setup. The PAT is embedded in the remote URL.

**Create a Personal Access Token:**
1. Go to https://github.com/settings/tokens
2. Generate new token (classic)
3. Select scope: `repo` (Full control of private repositories)
4. Copy the token

**Clone or update remote URL with PAT:**
```bash
# For new clone:
git clone https://YOUR_PAT@github.com/username/repo.git

# For existing repo:
cd /path/to/theme
git remote set-url origin https://YOUR_PAT@github.com/username/repo.git
git remote -v  # Verify: should show https://***@github.com:...
```

> ⚠️ **Note:** The PAT will be visible in `git remote -v` output. This is secure as long as only trusted users have server access.

### Option C: HTTPS with GH_PAT Environment Variable

The PAT is stored as an environment variable and used by `deploy/pull.sh` at runtime.

**Create a Personal Access Token:** (same as Option B)

**Set the environment variable on the server:**
```bash
# Add to ~/.bashrc for persistence
echo 'export GH_PAT="ghp_xxxxxxxxxxxxxxxxxxxx"' >> ~/.bashrc
source ~/.bashrc

# Verify
echo $GH_PAT
```

**Clone via plain HTTPS:**
```bash
git clone https://github.com/username/repo.git
```

The `deploy/pull.sh` script will automatically use the `GH_PAT` environment variable for authentication.

### Which Option Should I Use?

| Option | Security | Setup Complexity | Best For |
|--------|----------|------------------|----------|
| **A: SSH Keys** | ⭐⭐⭐ Highest | Medium | Production servers |
| **B: PAT in URL** | ⭐⭐ Good | Easy | Quick setup, trusted servers |
| **C: GH_PAT Env** | ⭐⭐ Good | Easy | Multiple repos, same PAT |

## Step 5: Test Manually

**On each server**, test the pull script:

```bash
# SSH into server
ssh user@your-server.com

# Navigate to theme directory
cd /path/to/theme

# Test pull script
./deploy/pull.sh --branch main      # For production
./deploy/pull.sh --branch dev       # For staging
./deploy/pull.sh                    # Auto-detect current branch

# Expected output:
# ✓ Fetching latest changes...
# ✓ Pull completed successfully
# ✓ Deployment completed successfully!
```

## Step 6: Test GitHub Actions

**Test Staging:**
```bash
git checkout dev
echo "/* Test staging */" >> style.css
git add style.css
git commit -m "Test staging deployment"
git push origin dev
```

**Test Production:**
```bash
git checkout main
git merge dev
git push origin main
```

Check the workflow in **GitHub → Actions tab**.

---

## Manual Deployment

### From GitHub UI

1. Go to **Actions → Run remote script over SSH**
2. Click **Run workflow**
3. Select environment: `staging` or `production`
4. Click **Run workflow**

### From Server

```bash
ssh user@your-server.com
cd /path/to/theme
./deploy/pull.sh --branch main    # or --branch dev
```

---

## Troubleshooting

### "Missing required secrets: PROD_BRANCH / STAGING_BRANCH"
- Configure `PROD_BRANCH` and `STAGING_BRANCH` secrets in GitHub

### "Missing required secrets for production/staging environment"
- Configure all required `PROD_*` or `STAGING_*` secrets
- The error message lists which secrets are missing

### "Permission denied (publickey)"
- Verify the SSH private key is correct in secrets
- Verify the public key is in `~/.ssh/authorized_keys` on the server
- Check permissions: `chmod 600 ~/.ssh/authorized_keys`
- Make sure deploy/pull.sh is executable: `chmod +x deploy/pull.sh`

### "Branch 'xyz' is not configured for deployment"
- This is normal for branches other than PROD_BRANCH and STAGING_BRANCH
- The workflow skips gracefully (not an error)

### "Permission denied" or "Authentication failed" (git pull on server)
- **If using Option A (SSH):** Verify deploy key is added to GitHub, check `git remote -v` shows `git@github.com:...`
- **If using Option B (PAT in URL):** Verify PAT is valid and has `repo` scope
- **If using Option C (GH_PAT):** Verify environment variable is set: `echo $GH_PAT`

### "Already up to date" but changes should be there
- Verify the correct branch is checked out on server
- Check git remote: `git remote -v`
- Ensure commits are pushed to GitHub

---

## Deployment Flow Diagram

```
┌─────────────────┐
│  Push to dev    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  GitHub Actions Workflow Triggers   │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  Validate PROD_BRANCH &             │
│  STAGING_BRANCH secrets exist       │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  Detect: dev == STAGING_BRANCH?     │──No──▶ Skip (not configured)
└────────┬────────────────────────────┘
         │ Yes
         ▼
┌─────────────────────────────────────┐
│  Validate STAGING_* secrets         │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  SSH into STAGING_SSH_HOST          │
│  using STAGING_SSH_USER             │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  cd STAGING_THEME_DIR               │
│  ./deploy/pull.sh --branch dev      │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│  ✓ Deployment Complete              │
└─────────────────────────────────────┘
```

---

## Security Best Practices

- ✅ Use **separate SSH keys** for each environment
- ✅ Use **dedicated deployment users** (not personal accounts)
- ✅ Use **GitHub Personal Access Tokens** with minimal scope
- ✅ Set **token expiration dates**
- ✅ **Never use root** for deployments
- ✅ Add `logs/` to `.gitignore`
- ✅ Review deployment logs regularly

---

## Quick Reference: All Secrets & Variables

### Required GitHub Secrets

| Secret | Description | Example |
|--------|-------------|---------|
| `PROD_BRANCH` | Branch name for production | `main` |
| `STAGING_BRANCH` | Branch name for staging | `dev` |
| `PROD_SSH_PRIVATE_KEY` | SSH private key for production server | `-----BEGIN OPENSSH...` |
| `PROD_SSH_HOST` | Production server hostname/IP | `prod.example.com` |
| `PROD_SSH_PORT` | Production SSH port | `22` |
| `PROD_SSH_USER` | Production SSH username | `deploy` |
| `PROD_THEME_DIR` | Theme path on production server | `/var/www/html/wp-content/themes/mytheme` |
| `STAGING_SSH_PRIVATE_KEY` | SSH private key for staging server | `-----BEGIN OPENSSH...` |
| `STAGING_SSH_HOST` | Staging server hostname/IP | `staging.example.com` |
| `STAGING_SSH_PORT` | Staging SSH port | `22` |
| `STAGING_SSH_USER` | Staging SSH username | `deploy` |
| `STAGING_THEME_DIR` | Theme path on staging server | `/var/www/staging/wp-content/themes/mytheme` |

### Optional Server Environment Variables

| Variable | Description | When Needed |
|----------|-------------|-------------|
| `GH_PAT` | GitHub Personal Access Token | Only if using Option C (HTTPS with env var) |

---

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [SSH Key Generation Guide](https://docs.github.com/en/authentication/connecting-to-github-with-ssh)
- [Creating Personal Access Tokens](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/creating-a-personal-access-token)
- [Deploy Keys](https://docs.github.com/en/developers/overview/managing-deploy-keys)
- [Git Credential Storage](https://git-scm.com/book/en/v2/Git-Tools-Credential-Storage)
