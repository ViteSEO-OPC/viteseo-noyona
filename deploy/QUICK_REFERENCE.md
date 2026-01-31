# Quick Reference: Auto-Deploy Setup

> ⚠️ **Important:** This deployment system requires a VPS or server that supports SSH commands. Managed hosting platforms (like EasyWP) that don't provide ssh access (such as EasyWP) are not compatible.

## What Was Set Up

✅ **GitHub Action** (`.github/workflows/deploy.sh`)
- Triggers on push to `main` branch
- SSHs into your server
- Runs the pull script

✅ **Pull Script** (`deploy/pull.sh`)
- Lives in your repository under the deploy folder
- Pulls latest changes from GitHub
- Auto-detects its own directory
- Logs all deployments

## How It Works

```
You push to GitHub (main branch)
        ↓
GitHub Action triggers
        ↓
SSH into your server
        ↓
cd to theme directory
        ↓
Run ./deploy/pull.sh
        ↓
Git pull latest changes
        ↓
Theme updated! ✓
```

## One-Time Server Setup

1. **Clone the repo on your server:**
   ```bash
   cd /var/www/html/wp-content/themes
   git clone <your-repo-url> your-theme-name
   ```

2. **Make pull.sh executable:**
   ```bash
   cd your-theme-name
   chmod +x deploy/pull.sh
   ```

3. **Configure git authentication** (choose one):
   
   **Option A - HTTPS with token:**
   ```bash
   git config credential.helper store
   git pull  # Enter username and GitHub token
   ```
   
   **Option B - SSH keys:**
   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/git_deploy_key
   cat ~/.ssh/git_deploy_key.pub  # Add to GitHub
   git remote set-url origin git@github.com:username/your-theme-name.git
   ```

4. **Update GitHub Action:**
   - Edit `.github/workflows/deploy.yml`
   - Update the path to match your server's theme directory:
     ```bash
     'bash -lc "cd /var/www/html/wp-content/themes/your-theme-name && ./deploy/pull.sh --env prod"'
     ```

5. **Add GitHub Secrets:**
   - Go to: Repository → Settings → Secrets → Actions
   - Add:
     - `SSH_PRIVATE_KEY` - Your SSH private key
     - `SSH_HOST` - Your server hostname/IP
     - `SSH_PORT` - SSH port (usually 22)
     - `SSH_USER` - Your server username

## Testing

**Manual test on server:**
```bash
ssh user@server
cd /var/www/html/wp-content/themes/your-theme-name
./deploy/pull.sh --env prod
```

**Trigger GitHub Action:**
```bash
git commit -m "Test deploy" --allow-empty
git push origin main
```

Then check: GitHub → Actions tab

## Logs

Logs are saved in: `<theme-directory>/logs/pull-TIMESTAMP.log`

```bash
# View latest log
cd /var/www/html/wp-content/themes/your-theme-name
ls -lt logs/
tail -f logs/pull-*.log
```

## Important Notes

⚠️ **Before first use:**
- Update the theme directory path in `.github/workflows/deploy.sh`
- Configure git authentication on server
- Add all 4 GitHub Secrets

✅ **The deploy/pull.sh script:**
- Is part of your repository (no separate copy needed)
- Auto-detects its directory (no path configuration)
- Creates logs in `logs/` (already in .gitignore)

## Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Permission denied (SSH) | Check SSH_PRIVATE_KEY secret and authorized_keys on server |
| Script not found | Make sure path in deploy.yml matches server, run `chmod +x deploy/pull.sh` |
| Git authentication failed | Configure git credentials (see step 3 above) |
| Already up to date | That's fine! Means no changes since last pull |

## Files Created

- `.github/workflows/deploy.sh` - GitHub Action workflow
- `.github/workflows/DEPLOYMENT_SETUP.md` - Detailed setup guide
- `deploy/pull.sh` - Pull script (deployed as part of theme)
- `deploy/setup-deployment.sh` - Setup script for generating deployment keys
- `QUICK_REFERENCE.md` - This file

---

For full setup instructions, see: `.github/workflows/DEPLOYMENT_SETUP.md`
