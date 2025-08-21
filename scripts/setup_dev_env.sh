#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# setup_dev_env.sh
# Idempotent developer environment bootstrap for Ubuntu (Codespaces/devcontainer) and macOS.
# Installs: git-lfs, pip/pipx, git-filter-repo (via pipx), python3, git
# Usage: sudo ./scripts/setup_dev_env.sh   (on Codespaces/Ubuntu run as root or with sudo)

RECOMMENDED_PYTHON=3.11

echo "== dev environment setup script =="

OS_NAME="$(uname -s)"
echo "Detected OS: $OS_NAME"

install_apt_pkgs() {
  echo "Updating apt and installing packages..."
  apt-get update -y
  DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    git curl ca-certificates gnupg python3 python3-venv python3-pip build-essential \
    ca-certificates lsb-release
}

install_brew_pkgs() {
  echo "Using Homebrew to install packages..."
  if ! command -v brew >/dev/null 2>&1; then
    echo "Homebrew not found. Please install Homebrew first: https://brew.sh/"
    exit 1
  fi
  brew update
  brew install git python git-lfs
}

install_git_lfs() {
  if command -v git-lfs >/dev/null 2>&1; then
    echo "git-lfs already installed"
  else
    echo "Installing git-lfs..."
    if [ "$OS_NAME" = "Linux" ]; then
      # On devcontainers/ubuntu we prefer apt
      curl -s https://packagecloud.io/install/repositories/github/git-lfs/script.deb.sh | bash
      apt-get install -y git-lfs || true
    else
      brew install git-lfs || true
    fi
  fi
  git lfs install --local || true
}

install_pipx_and_filterrepo() {
  if ! command -v pipx >/dev/null 2>&1; then
    echo "Installing pipx..."
    if [ "$OS_NAME" = "Linux" ]; then
      python3 -m pip install --upgrade --user pipx
      python3 -m pipx ensurepath || true
    else
      brew install pipx || python3 -m pip install --user pipx
      python3 -m pipx ensurepath || true
    fi
    # ensure pipx is on PATH for this shell
    if command -v pipx >/dev/null 2>&1; then
      echo "pipx installed"
    else
      echo "pipx appears not on PATH. You may need to restart your shell."
    fi
  else
    echo "pipx already installed"
  fi

  if ! command -v git-filter-repo >/dev/null 2>&1; then
    echo "Installing git-filter-repo via pipx..."
    pipx install git-filter-repo || python3 -m pip install --user git-filter-repo
  else
    echo "git-filter-repo already available"
  fi
}

post_install_notes() {
  echo "\n== Summary =="
  echo "git:       " "$(git --version 2>/dev/null || echo 'missing')"
  echo "git-lfs:   " "$(git lfs version 2>/dev/null || echo 'missing')"
  echo "git-filter-repo: " "$(git-filter-repo --version 2>/dev/null || echo 'missing')"
  echo "python3:   " "$(python3 --version 2>/dev/null || echo 'missing')"

  cat <<'EOF'

Next steps (recommended):
 - Ensure your SSH key is added to GitHub (for pushes): https://github.com/settings/keys
 - If you plan to rewrite history (git-filter-repo / git lfs migrate), create a mirror backup first:
     git clone --mirror git@github.com:<owner>/<repo>.git ~/repo-mirror-backup.git

 - To use this project in Codespaces, add a devcontainer if you want reproducible environments.

EOF
}

main() {
  if [ "$(id -u)" -ne 0 ] && [ "$OS_NAME" = "Linux" ]; then
    echo "This script should be run as root (sudo) on Linux to install packages. Re-run with sudo." >&2
    exit 1
  fi

  if [ "$OS_NAME" = "Linux" ]; then
    install_apt_pkgs
  else
    install_brew_pkgs
  fi

  install_git_lfs
  install_pipx_and_filterrepo
  post_install_notes
  echo "Done."
}

main "$@"
