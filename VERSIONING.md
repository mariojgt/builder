# Automatic Versioning and Tagging

This repository includes a unified automatic versioning and tagging system. When you merge to the `main` branch, the system will automatically:

1. **Get the last tag** from the repository
2. **Analyze commit messages** since the last tag to determine the type of version bump needed
3. **Calculate the new version** based on semantic versioning
4. **Check if the tag already exists** (both locally and remotely)
5. **Create and push a new git tag** (if it doesn't exist)
6. **Generate a GitHub release** with categorized changelog

## How It Works

### Version Bump Detection

The system analyzes commit messages since the last tag to determine the version bump type with priority order:

#### Major Version Bump (x.0.0) - Highest Priority
Triggered by commit messages containing:
- `major`
- `breaking`
- `BREAKING CHANGE`
- `feat!:` or `fix!:` (with exclamation mark)
- `!:` in the commit message

#### Minor Version Bump (x.y.0) - Medium Priority
Triggered by commit messages containing:
- `minor`
- `feat:`
- `feature:`
- `add:`
- `new:`

#### Patch Version Bump (x.y.z) - Default Priority
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
- Any commit without specific keywords (defaults to patch)

### Example Commit Messages

```bash
# Patch bump (v1.0.0 → v1.0.1)
git commit -m "fix: resolve issue with table rendering"
git commit -m "hotfix: critical bug in form validation"
git commit -m "docs: update README documentation"

# Minor bump (v1.0.0 → v1.1.0)
git commit -m "feat: add new export functionality"
git commit -m "feature: implement advanced filtering"

# Major bump (v1.0.0 → v2.0.0)
git commit -m "feat!: completely redesign table API"
git commit -m "major: breaking change in form builder"
git commit -m "BREAKING CHANGE: remove deprecated methods"
```

## Unified Workflow

### release.yml
- **Comprehensive workflow** that combines the best of both previous workflows
- Gets the last tag and analyzes all commits since then
- Supports conventional commit formats
- Generates categorized changelogs with emojis
- Handles breaking changes appropriately
- Checks both local and remote for existing tags
- Can optionally update composer.json version
- Provides clear status messages for all scenarios

## Workflow Process

1. **Trigger**: Runs on push to main or merged PRs
2. **Get Last Tag**: Finds the latest semantic version tag
3. **Analyze Commits**: Reviews all commits since the last tag
4. **Determine Bump**: Calculates version bump based on commit analysis
5. **Check Existence**: Verifies tag doesn't already exist
6. **Create Tag**: Creates annotated tag with detailed message
7. **Push Tag**: Pushes tag to remote repository
8. **Generate Release**: Creates GitHub release with changelog
9. **Update Files**: Optionally updates composer.json version

## Status Messages

The workflow provides clear feedback:

- **ℹ️ No new commits since last tag** → No action needed
- **⚠️ Tag v1.4.1 already exists** → Skips creation
- **✅ Created and pushed tag: v1.4.2** → Success
- **🎉 Successfully created release** → Release created

## Features

### Smart Commit Analysis
- Prioritizes breaking changes over features over fixes
- Supports conventional commit format
- Falls back to patch bump for any unspecified commits

### Comprehensive Tag Checking
- Checks both local and remote repositories
- Prevents duplicate tag creation
- Graceful handling of existing tags

### Rich Changelog Generation
- **🚨 Breaking Changes** section
- **✨ New Features** section
- **🐛 Bug Fixes** section
- **📝 Other Changes** section
- Installation instructions included

### Optional Composer Integration
- Updates `version` field in composer.json if it exists
- Commits the change back to the repository
- Uses `[skip ci]` to prevent workflow loops

## Setup Instructions

1. **The workflow is ready**: `release.yml` will trigger automatically on merges to main
2. **Use descriptive commit messages**: Follow the commit message patterns above
3. **GitHub Permissions**: Uses `GITHUB_TOKEN` with write permissions

## Best Practices

1. **Use conventional commit messages** for automatic detection
2. **Group related changes** in single commits when possible
3. **Test thoroughly** before merging to main
4. **Review the generated changelog** in releases

## Troubleshooting

### No tag is created
- Check commit messages match the patterns above
- Ensure you're pushing to the `main` branch
- Check the Actions tab for detailed logs

### Wrong version bump
- Review commit message keywords
- Remember that major > minor > patch in priority
- Manually create a tag if the automatic one is incorrect

### Permission errors
- Check repository settings → Actions → General
- Ensure "Read and write permissions" are enabled

## Manual Override

If you need to create a tag manually:

```bash
# Create and push a tag manually
git tag -a v1.2.3 -m "Release v1.2.3"
git push origin v1.2.3
```

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
