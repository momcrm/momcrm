# Contributing to MomCRM

Thanks for helping out! Everyone taking part in MomCRM is expected to follow our [Code of Conduct](CODE_OF_CONDUCT.md).

1. Fork the repo and create a branch from `main`.
2. Follow the setup steps in the [README](README.md).
3. Add tests for your change and make sure `composer test` passes.
4. Fix formatting and lint issues with `composer lint` (PHP) and `npm run check:fix` (frontend).
5. Add a line describing your change under `## [Unreleased]` in [CHANGELOG.md](CHANGELOG.md).
6. Open a pull request describing what you changed and why.

Please keep pull requests focused on one change.

## AI coding assistants

MomCRM uses [Laravel Boost](https://laravel.com/docs/boost) to give AI coding assistants (Claude Code, Cursor, Codex, Copilot and others) project-specific guidelines, skills and an MCP server. The generated files are ignored by git, so each contributor sets them up for their own tools:

```bash
php artisan boost:install
```

Run `php artisan boost:update` after updating dependencies to refresh the guidelines and skills.

## Releasing

Maintainers release new versions by tagging `main`:

1. In a pull request, move the entries under `## [Unreleased]` in [CHANGELOG.md](CHANGELOG.md) into a new section such as `## [0.1.0] - 2026-11-20`, and add a matching compare link at the bottom. Merge it.
2. Tag the merge commit on `main` and push the tag:

    ```bash
    git checkout main && git pull
    git tag v0.1.0
    git push origin v0.1.0
    ```

The [release workflow](.github/workflows/release.yml) then creates a GitHub release using that version's changelog section as the release notes. Tags with a suffix, such as `v0.2.0-beta.1`, are published as pre-releases. The workflow fails if the tag isn't on `main` or the changelog has no section for that version.
