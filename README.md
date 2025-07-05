# Aktonz WordPress Project

This repository contains the WordPress files for the Aktonz project. Use Git for version control and push changes to the shared staging branch when ready.

Future development should occur on the `staging` branch created from `work`. Push the branch with upstream tracking using:

```bash
git checkout -b staging    # from the work branch
git push -u origin staging # set upstream tracking
```

## Initializing the repository

1. Clone the repository and navigate into the directory.
2. Run `git init` if the repository is not already initialized.
3. Configure your user name and email to prevent auto-generated author info:
   ```bash
   git config --global user.name "Your Name"
   git config --global user.email "you@example.com"
   ```
4. Add the repository as the `origin` remote so you can push and pull changes:
   ```bash
   git remote add origin git@github.com:ewebtechsuk/aktonz.git
   ```
   All pushes and pulls require this remote.

## Committing WordPress files

1. Add or update your WordPress files in the repository directory.
2. Stage the changes with `git add .`.
3. Commit with a descriptive message:
   ```bash
   git commit -m "Add theme updates"
   ```

## Pushing to the `staging` branch

1. Ensure your local branch is named `staging` or create it:
   ```bash
   git checkout -b staging         # if it doesn't exist
   ```
2. Push the branch to the remote and set it as upstream:
   ```bash
   git push -u origin staging      # set upstream
   ```

All future development occurs on this `staging` branch.

When pushing to GitHub, authenticate either via SSH using a repository URL like
`git@github.com:<user>/<repo>.git`, or over HTTPS using a Personal Access Token
in place of your password. GitHub no longer accepts account passwords for push
operations.

## Handling large files

Avoid committing large files such as `.wpress` archives directly to the repository. Use Git LFS to manage them or add the file patterns to `.gitignore` to keep the repository lightweight.

## Using Git LFS

To store large `.wpress` files in the repo, initialize Git LFS and track the pattern:

```bash
git lfs install
git lfs track "*.wpress"
git add .gitattributes
```

GitHub blocks files larger than 100 MB unless they are handled with LFS.

## Removing a committed backup

If a `.wpress` archive or other backup was accidentally committed, untrack it
while keeping the local copy:

```bash
git rm --cached path/to/file.wpress
git commit -m "Remove large backup"
```

After removing the file from the repository, add the pattern to `.gitignore` or
track it with Git LFS to prevent future commits.

## License

This project is released under the [MIT License](LICENSE).
