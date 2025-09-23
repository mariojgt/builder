# Automatic Versioning and Tagging

This repository now includes automatic versioning and tagging based on commit messages. When you merge to the `main` branch, the system will automatically:

1. **Analyze commit messages** to determine the type of version bump needed
2. **Calculate the new version** based on semantic versioning
3. **Create and push a new git tag**
4. **Generate a GitHub release** with changelog

## How It Works

### Version Bump Detection

The system analyzes commit messages since the last tag to determine the version bump type:

#### Major Version Bump (x.0.0)
Triggered by commit messages containing:
- `major`
- `breaking`
- `BREAKING CHANGE`
- `feat!:` or `fix!:` (with exclamation mark)
- `!:` in the commit message

#### Minor Version Bump (x.y.0)
Triggered by commit messages containing:
- `minor`
- `feat:`
- `feature:`
- `add:`
- `new:`

#### Patch Version Bump (x.y.z)
Triggered by commit messages containing:
- `patch`
- `fix:`
- `bugfix:`
- `hotfix:`
- `docs:`
- `style:`
- `refactor:`
- `test:`
- `chore:`

### Example Commit Messages

```bash
# Patch bump (v1.0.0 → v1.0.1)
git commit -m "fix: resolve issue with table rendering"
git commit -m "hotfix: critical bug in form validation"

# Minor bump (v1.0.0 → v1.1.0)
git commit -m "feat: add new export functionality"
git commit -m "feature: implement advanced filtering"

# Major bump (v1.0.0 → v2.0.0)
git commit -m "feat!: completely redesign table API"
git commit -m "major: breaking change in form builder"
git commit -m "BREAKING CHANGE: remove deprecated methods"
```

## Workflows Available

### 1. auto-tag.yml
- **Simple workflow** for basic automatic tagging
- Runs on push to main or merged PRs
- Creates tags and GitHub releases
- Good for straightforward projects

### 2. semantic-release.yml (Recommended)
- **Advanced workflow** with better commit analysis
- Supports conventional commit formats
- Generates categorized changelogs
- Handles breaking changes appropriately
- Can optionally update composer.json version

## Setup Instructions

1. **Enable the workflows**: The workflows are already set up and will trigger automatically on merges to main.

2. **Use conventional commit messages**: Follow the commit message patterns above for automatic version detection.

3. **GitHub Permissions**: The workflows use `GITHUB_TOKEN` which should work automatically. If you encounter permission issues, ensure the repository has:
   - Actions enabled
   - Write permissions for Actions

## Manual Tagging

If you need to create a tag manually:

```bash
# Create and push a tag manually
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3
```

## Best Practices

1. **Use descriptive commit messages** that clearly indicate the type of change
2. **Group related changes** in a single commit when possible
3. **Use PR titles** that follow the same convention for better tracking
4. **Test before merging** to ensure the automatic versioning works as expected

## Troubleshooting

### No tag is created
- Check if your commit messages match the patterns above
- Ensure you're pushing to the `main` branch
- Check the Actions tab for workflow execution details

### Wrong version bump
- Review your commit messages for unintended keywords
- You can manually create a tag if the automatic one is incorrect

### Permission errors
- Check repository settings → Actions → General
- Ensure "Read and write permissions" are enabled for GITHUB_TOKEN

## Composer Installation

Once a new version is tagged, users can install specific versions:

```bash
# Install latest version
composer require mariojgt/builder

# Install specific version
composer require mariojgt/builder:^2.1.0

# Install exact version
composer require mariojgt/builder:2.1.0
```
