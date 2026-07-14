# Dot.Pulse DevSecOps Pipeline
Version: 1.0 | GitHub Actions | Classification: Internal

---

## Pipeline Philosophy

Every code change must pass through automated quality, security, and reliability gates before it reaches production. The pipeline is the last line of defence before users are affected.

**Fail fast, fail loudly.** A CI failure should take less than 10 minutes to diagnose.

---

## Branch Strategy

```
main                 ← production (protected, requires 2 approvals + all checks)
staging              ← pre-production (auto-deploy on merge)
feature/*            ← feature branches (PR → staging or main)
fix/*                ← bug fixes
security/*           ← security fixes (expedited review)
hotfix/*             ← production hotfixes (emergency deploy path)
```

### Protection Rules

**main branch:**
- Require 2 approving reviews
- Require all status checks to pass
- Require branches to be up to date
- Restrict push to `engineering-leads` team only
- No force pushes, no deletions

**staging branch:**
- Require 1 approving review
- Require CI to pass

---

## GitHub Actions Workflows

### 1. `ci.yml` — Runs on every push to every branch

```yaml
name: CI

on:
  push:
    branches: ['**']
  pull_request:
    branches: [main, staging]

jobs:
  lint:
    name: Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: ./vendor/bin/pint --test          # PHP code style
      - run: npm ci && npm run build            # JS build check

  static-analysis:
    name: Static Analysis
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: ./vendor/bin/phpstan analyse --level=9 --no-progress

  security-scan:
    name: Security Scan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with: { php-version: '8.3' }
      - run: composer install --no-interaction --prefer-dist
      - run: composer audit                     # PHP dependency CVEs
      - run: npm ci && npm audit --audit-level=high  # JS dependency CVEs
      - uses: gitleaks/gitleaks-action@v2       # Secret detection
        with:
          config-path: .gitleaks.toml

  test:
    name: Tests
    runs-on: ubuntu-latest
    env:
      DB_CONNECTION: sqlite
      DB_DATABASE: ':memory:'
      QUEUE_CONNECTION: sync
      CACHE_STORE: array
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan migrate --force
      - run: |
          ./vendor/bin/phpunit \
            --coverage-clover coverage.xml \
            --min-coverage=95
      - uses: codecov/codecov-action@v4
        with:
          file: coverage.xml
```

### 2. `security-advanced.yml` — Runs weekly + on security/* branches

```yaml
name: Advanced Security

on:
  schedule:
    - cron: '0 2 * * 1'   # Every Monday at 2am
  push:
    branches: ['security/**']

jobs:
  vulnerability-scan:
    name: Deep Vulnerability Scan
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: aquasecurity/trivy-action@master
        with:
          scan-type: 'fs'
          scan-ref: '.'
          severity: 'HIGH,CRITICAL'
          exit-code: '1'

  sbom:
    name: Generate SBOM
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: anchore/sbom-action@v0
        with:
          format: cyclonedx-json
          output-file: sbom.json
      - uses: actions/upload-artifact@v4
        with:
          name: sbom
          path: sbom.json

  owasp-dependency-check:
    name: OWASP Dependency Check
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: dependency-check/Dependency-Check_Action@main
        with:
          project: 'dot-pulse'
          path: '.'
          format: 'HTML'
      - uses: actions/upload-artifact@v4
        with:
          name: dependency-check-report
          path: reports/
```

### 3. `deploy.yml` — Runs on merge to staging/main

```yaml
name: Deploy

on:
  push:
    branches: [main, staging]

jobs:
  deploy-staging:
    if: github.ref == 'refs/heads/staging'
    runs-on: ubuntu-latest
    environment: staging
    steps:
      - uses: actions/checkout@v4
      - name: Deploy to staging
        run: |
          # Zero-downtime deploy
          php artisan down --retry=60 --secret="$MAINTENANCE_SECRET"
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan up
      - name: Smoke tests
        run: ./vendor/bin/phpunit tests/Feature/Health --no-coverage

  deploy-production:
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    environment: production
    needs: [deploy-staging]   # Staging must deploy successfully first
    steps:
      - uses: actions/checkout@v4
      - name: Production deploy (manual approval gate in GitHub Environments)
        run: |
          php artisan down --retry=60 --secret="$MAINTENANCE_SECRET"
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          php artisan up
          php artisan queue:restart
```

---

## Pre-commit Hooks

Install local hooks to catch issues before they hit CI:

```bash
# .git/hooks/pre-commit (or use husky)
#!/bin/bash

echo "Running pre-commit checks..."

# PHP Pint
./vendor/bin/pint --test
if [ $? -ne 0 ]; then
    echo "❌ PHP style issues found. Run: ./vendor/bin/pint"
    exit 1
fi

# Quick PHPStan
./vendor/bin/phpstan analyse --level=9 --no-progress --memory-limit=1G
if [ $? -ne 0 ]; then
    echo "❌ Static analysis failed."
    exit 1
fi

# Secret detection (fast local scan)
grep -r "sk-ant-\|sk-\|AKIA\|ghp_\|glpat-" --include="*.php" --include="*.env" .
if [ $? -eq 0 ]; then
    echo "❌ Possible secret detected. Review before committing."
    exit 1
fi

echo "✅ Pre-commit checks passed."
```

---

## Code Quality Gates

| Check | Tool | Threshold | Blocks Merge |
|---|---|---|---|
| PHP code style | Pint | Zero violations | Yes |
| Static analysis | PHPStan Level 9 | Zero errors | Yes |
| Test coverage | PHPUnit | ≥ 95% | Yes |
| PHP dependency CVEs | `composer audit` | No HIGH/CRITICAL | Yes |
| JS dependency CVEs | `npm audit` | No HIGH/CRITICAL | Yes |
| Secret detection | Gitleaks | No secrets | Yes |
| Container scan | Trivy | No CRITICAL | Yes (on main) |
| OWASP dependency | Dependency-Check | No CRITICAL | Yes (weekly) |

---

## Secrets Management in CI

**Never hardcode secrets in workflows.** Use GitHub Actions Secrets:

```yaml
# Reference secrets safely
env:
  ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
  APP_KEY: ${{ secrets.APP_KEY_STAGING }}
  DB_PASSWORD: ${{ secrets.DB_PASSWORD_STAGING }}
```

Secrets required per environment:

| Environment | Required Secrets |
|---|---|
| CI (testing) | `APP_KEY` only (generated in workflow) |
| Staging | `APP_KEY`, `DB_*`, `AWS_*`, `REVERB_*`, `ANTHROPIC_API_KEY` |
| Production | All of staging + `SENTRY_DSN`, `MAIL_*`, `VITE_*` |

Rotate all secrets quarterly or immediately on suspected compromise.

---

## Dependency Update Policy

| Type | Update Frequency | Review Required |
|---|---|---|
| Security patch (patch version) | Immediately on CVE publication | Security review |
| Bug fix (patch version) | Weekly batch | Standard review |
| Minor version | Monthly | Test + review |
| Major version | Quarterly | Full regression test + review |

Use Dependabot or Renovate for automated PRs. All dependency update PRs must pass full CI before merge.

---

## Release Process

```
1. Feature complete on feature/* branch
2. PR opened against staging
3. CI must pass (lint, analysis, tests, security)
4. 1 peer review
5. Merge to staging → auto-deploy to staging environment
6. QA verification in staging (manual + smoke tests)
7. PR opened against main
8. CI must pass again (fresh run against main)
9. 2 approvals including security champion
10. Merge to main → deployment pipeline triggers
11. Manual approval gate in GitHub Environments
12. Zero-downtime deploy to production
13. Post-deploy smoke tests
14. Monitor dashboards for 30 minutes
15. Tag release: git tag v1.2.0 -m "Release notes"
```

---

## Rollback Plan

If a deployment causes issues:

```bash
# Immediate rollback
git revert HEAD --no-edit
git push origin main
# CI triggers, deploys the revert

# If migration needs rollback
php artisan migrate:rollback --step=1

# If complete rollback needed
git checkout v1.1.0  # Last known good tag
git push --force origin main  # Emergency only — requires senior approval
```

All rollback actions must be logged in the incident tracker.
