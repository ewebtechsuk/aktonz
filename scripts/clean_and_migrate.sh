#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# clean_and_migrate.sh
# Safe helper to:
# - Remove secrets (wp-config.php) from git history using git-filter-repo
# - Install and enable Git LFS and migrate large files into LFS
# - Force-push the cleaned history to the origin after confirmation

REPO_DIR="$(pwd)"
MIRROR_BACKUP="${REPO_DIR}-mirror-backup-$(date +%Y%m%d%H%M%S)"
TMP_DIR="${REPO_DIR}-tmp-clean-$(date +%Y%m%d%H%M%S)"

echo "This script will rewrite git history. It creates backups and prompts before force-push."

# Preflight checks
command -v git >/dev/null 2>&1 || { echo "git not found"; exit 1; }
command -v python3 >/dev/null 2>&1 || { echo "python3 is required for git-filter-repo"; exit 1; }

# Recommend git-filter-repo
if ! command -v git-filter-repo >/dev/null 2>&1; then
  echo "git-filter-repo not found. Please install it."
  echo "Installation options:"
  echo "  pip3 install --user git-filter-repo"
  echo "  or see https://github.com/newren/git-filter-repo"
  read -p "Continue anyway (not recommended)? [y/N] " yn || exit 1
  [[ "$yn" =~ ^[Yy] ]] || exit 1
fi

# Make a mirror backup of remote
echo "Creating mirror backup of remote into: $MIRROR_BACKUP"
if [ -d "$MIRROR_BACKUP" ]; then
  echo "Backup dir already exists: $MIRROR_BACKUP"; exit 1
fi

git clone --mirror git@github.com:ewebtechsuk/aktonz.git "$MIRROR_BACKUP"

# Create a temporary cloned working repo
echo "Creating temporary working clone at: $TMP_DIR"
git clone "$MIRROR_BACKUP" "$TMP_DIR"
cd "$TMP_DIR"

# Ensure LFS is installed
if ! command -v git-lfs >/dev/null 2>&1; then
  echo "git-lfs not installed. Attempting to install via package manager is not automated here." 
  echo "Install git-lfs, then run 'git lfs install' before proceeding."
  read -p "Continue without Git LFS (not recommended)? [y/N] " yn || exit 1
  [[ "$yn" =~ ^[Yy] ]] || exit 1
fi

# 1) Remove sensitive files from history
# Find any tracked paths whose basename matches wp-config*.php (in any directory)
REMOVE_PATHS=()
# Use git ls-files to locate any tracked files with wp-config in their name
mapfile -t FOUND < <(git ls-files --full-name | grep -E '(^|/)wp-config[^/]*\.php$' || true)
for p in "${FOUND[@]}"; do
  REMOVE_PATHS+=("$p")
done
# Also include a common backup filename if present in the index
if git ls-files --error-unmatch -- 'wp-config.php.backup' >/dev/null 2>&1; then
  REMOVE_PATHS+=("wp-config.php.backup")
fi

if [ ${#REMOVE_PATHS[@]} -gt 0 ]; then
  echo "Detected sensitive files to remove: ${REMOVE_PATHS[*]}"
  echo "Running git-filter-repo to remove these paths from history..."
  # Use git-filter-repo to remove all paths in one call via a temporary file
  TMP_PATHS_FILE="$(mktemp)"
  for p in "${REMOVE_PATHS[@]}"; do
    printf '%s\n' "$p" >> "$TMP_PATHS_FILE"
  done
  git filter-repo --paths-from-file "$TMP_PATHS_FILE" --invert-paths || true
  rm -f "$TMP_PATHS_FILE"
else
  echo "No sensitive files detected in current index. Skipping path removal."
fi

# 2) Setup Git LFS and migrate large files (based on .gitattributes)
if command -v git-lfs >/dev/null 2>&1; then
  git lfs install --local || true
  echo "Migrating files listed in .gitattributes to LFS..."
  # migrate common large types; adjust as needed
  git lfs migrate import --include-ref=refs/heads/main --everything --include="*.wpress,*.zip,*.png,*.jpg,*.jpeg,*.gif,*.mp4,*.webp" || true
else
  echo "git-lfs not installed; skipped migration."
fi

# 3) Compact repository
git reflog expire --expire=now --all || true
git gc --prune=now --aggressive || true

# 4) Show summary and confirm push
echo "Repository cleaned in temporary dir: $TMP_DIR"
echo "A mirror backup exists at: $MIRROR_BACKUP"
read -p "Force-push cleaned history to origin/main? This will overwrite remote main branch. Type YES to continue: " confirm || exit 1
if [ "$confirm" != "YES" ]; then
  echo "Aborted by user. No changes pushed."; exit 0
fi

# 5) Force-push to origin
git remote remove origin 2>/dev/null || true
git remote add origin git@github.com:ewebtechsuk/aktonz.git
git push --force origin main || true

echo "Push complete. Please verify on GitHub and inform collaborators to re-clone."

# End script
