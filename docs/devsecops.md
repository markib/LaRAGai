# DevSecOps Process for LaRAGai

This project uses free tooling and GitHub Actions to implement a modern DevSecOps workflow for Laravel + React.

## Goals

- enforce secure development practices
- automate dependency and vulnerability checks
- ensure tests and build succeed before merge
- keep secrets out of source control
- document the process for the team

## Recommended workflow

1. Use GitHub branches for all work.
2. Create a pull request for feature changes, bug fixes, and config updates.
3. Require passing CI before merging.
4. Use `main` as the protected production branch.

## CI pipeline

The new workflow is defined in `.github/workflows/ci.yml` and includes:

- PHP and Node setup
- `composer install` and `npm install`
- `composer validate`
- `vendor/bin/phpunit`
- `composer audit`
- `npm audit --audit-level=moderate`
- `npm run build`
- Trivy filesystem vulnerability scan

## Free tooling recommendations

### PHP
- `composer audit` for dependency vulnerability scanning
- `phpunit` for automated tests
- add `phpstan` or `psalm` later for static analysis
- add `phpcs` for code style and secure coding checks

### JavaScript / frontend
- `npm audit` for dependency scanning
- `vite build` to verify frontend packaging
- add `eslint` / `prettier` for linting and formatting

### DevSecOps controls
- protect `main` with branch protection rules
- require status checks before merge
- store secrets in GitHub Secrets or another vault, never in `.env`
- enable Dependabot or GitHub Dependabot alerts for dependency updates
- use Trivy or similar open-source scanners for container/file scans

## Secrets and environment

- keep `.env` out of repository
- use `.env.example` for sample values only
- never commit API keys, tokens, or credentials

## Next improvements

After this baseline, add:

- static security analysis for PHP (`phpstan`, `psalm`, `phpcs`)
- React linting with `eslint`
- automated secret scanning with `gitleaks`
- infrastructure-as-code or Docker-based dev environments if desired

## Notes

This repo currently has no existing GitHub Actions workflows, so the new `.github/workflows/ci.yml` file is the starting point for a modern free DevSecOps process.
