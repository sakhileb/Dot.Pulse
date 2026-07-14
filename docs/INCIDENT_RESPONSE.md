# Dot.Pulse Incident Response Playbook
Version: 1.0 | Classification: Internal — Confidential

---

## Severity Definitions

| Severity | Definition | Response Time | Example |
|---|---|---|---|
| **P0 — Critical** | Platform down, data breach, security incident | < 5 min | All endpoints 500, database unreachable, tokens compromised |
| **P1 — High** | Major feature broken, significant performance degradation | < 15 min | Post creation failing, AI moderation offline, queue backed up |
| **P2 — Medium** | Non-critical feature broken, elevated error rates | < 1 hour | Search broken, notifications delayed, file upload failing |
| **P3 — Low** | Minor issue, cosmetic, workaround available | Next business day | UI styling issue, non-critical performance |

---

## Incident Roles

| Role | Responsibility |
|---|---|
| **Incident Commander (IC)** | Coordinates response, owns the call, makes deployment decisions |
| **Tech Lead** | Investigates root cause, implements fix |
| **Communications Lead** | Updates status page, drafts user communications |
| **Scribe** | Documents timeline, actions, and decisions in incident record |

On small teams, one person may hold multiple roles. Always designate an IC before starting investigation.

---

## Incident Response Process

```
DETECT
  └─ Alert fires / User report / Team member notices
        ↓
ACKNOWLEDGE
  └─ On-call engineer acknowledges within SLA
  └─ Open incident channel in Slack: #incident-YYYY-MM-DD-description
  └─ Designate Incident Commander
        ↓
ASSESS
  └─ Determine severity (P0/P1/P2/P3)
  └─ Determine blast radius (how many users affected?)
  └─ Determine data exposure risk
        ↓
COMMUNICATE
  └─ Post initial status update (even if nothing is known yet)
  └─ Update status page
  └─ Notify stakeholders if P0/P1
        ↓
INVESTIGATE
  └─ Check error logs (Sentry, Loki)
  └─ Check metrics dashboards (Grafana)
  └─ Check recent deployments (git log)
  └─ Check recent config changes
        ↓
MITIGATE
  └─ Apply temporary fix (feature flag off, rollback, restart)
  └─ Communicate mitigation
        ↓
RESOLVE
  └─ Root cause identified and fixed
  └─ Fix deployed and verified
  └─ Status page updated to resolved
        ↓
POST-INCIDENT
  └─ Post-Incident Review (PIR) within 48 hours
  └─ Document in incident register
  └─ Action items assigned and tracked
```

---

## Runbook: Platform Down (P0)

**Symptoms:** All API endpoints returning 500 or no response, users unable to load the app.

**Step 1 — Confirm scope**
```bash
# Check from external location
curl -I https://pulse.infodot.app/health

# Check server status
ssh deploy@pulse.infodot.app "systemctl status php-fpm nginx"
```

**Step 2 — Check recent changes**
```bash
git log --oneline -10
# Was there a recent deployment?
php artisan migrate:status
```

**Step 3 — Check application logs**
```bash
tail -100 storage/logs/laravel.log
# Or via Sentry dashboard
```

**Step 4 — Quick recovery options**
```bash
# Restart PHP-FPM
sudo systemctl restart php-fpm

# Restart queue workers
php artisan queue:restart

# Clear all caches if config change caused it
php artisan config:clear && php artisan cache:clear

# Enable maintenance mode while fixing
php artisan down --retry=60
```

**Step 5 — If recent deployment caused it**
```bash
# Rollback to previous release
git revert HEAD --no-edit
git push origin main
# Wait for CI/CD to deploy revert
```

---

## Runbook: Database Unreachable (P0)

**Symptoms:** `SQLSTATE[HY000] [2002] Connection refused` in logs, health check `database: unhealthy`.

**Step 1 — Verify connectivity**
```bash
php artisan db:monitor
# or
psql -h DB_HOST -U DB_USER -d DB_NAME -c "SELECT 1;"
```

**Step 2 — Check if primary is down, replica available**
```bash
# Switch to replica (read-only mode) temporarily
# Update DB_HOST in .env to replica host
# Note: writes will fail — enable maintenance mode
php artisan down --retry=60
```

**Step 3 — Engage database team / cloud provider**
- Check AWS RDS console / DigitalOcean managed database status
- Check for scheduled maintenance window
- Open support ticket with provider if infra issue

**Step 4 — Communicate**
- Post P0 to #incident channel immediately
- Update status page: "We are investigating database connectivity issues"

---

## Runbook: Security Breach Detected (P0)

**Never investigate a security breach alone.** Engage security counsel immediately.

**Step 1 — Contain first**
```bash
# Immediately revoke all active sessions
php artisan session:flush  # if implemented

# Rotate the application key (invalidates all encrypted data/sessions)
php artisan key:generate   # WARNING: this breaks active sessions

# Revoke all Sanctum tokens
# php artisan tokens:revoke-all  # implement this command
```

**Step 2 — Preserve evidence**
```bash
# Take snapshots before any cleanup
# Preserve logs — do NOT clear them
# Take database snapshot for forensics
```

**Step 3 — Assess**
- What data was accessed?
- How was access obtained?
- Is the attacker still active?
- Are other systems in the Dot ecosystem affected?

**Step 4 — Notify (time-sensitive)**
- POPIA: Notify Information Regulator as soon as reasonably possible
- GDPR: Notify supervisory authority within 72 hours
- Affected users: if high risk to their rights and freedoms
- Legal counsel: immediately
- Dot ecosystem security team: immediately

**Step 5 — Remediate**
- Patch the vulnerability
- Rotate all secrets and credentials
- Force password reset for affected accounts
- Audit for further compromise

---

## Runbook: Queue Worker Down (P1)

**Symptoms:** Moderation queue growing, notifications not delivered, posts stuck in `pending`.

**Step 1 — Verify**
```bash
php artisan queue:monitor  # or
php artisan tinker --execute="echo DB::table('jobs')->count();"
```

**Step 2 — Restart workers**
```bash
php artisan queue:restart    # Graceful restart
# Wait 30 seconds, then:
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

**Step 3 — Check for failed jobs**
```bash
php artisan queue:failed
php artisan queue:retry all  # Retry all failed jobs
```

**Step 4 — If AI moderation is backed up**
- Users will still see posts (they're in `pending` status)
- Temporarily enable auto-approve: `QUEUE_SKIP_AI=true` env flag (implement this)
- Communicate that moderation may be delayed

---

## Runbook: AI Moderation Offline (P1)

**Symptoms:** All posts stuck in `pending`, AI error rate alert fired, `EnrichPost` jobs failing.

**Step 1 — Verify**
```bash
curl -H "x-api-key: $ANTHROPIC_API_KEY" \
     -H "anthropic-version: 2023-06-01" \
     https://api.anthropic.com/v1/messages \
     -d '{"model":"claude-haiku-20240307","max_tokens":10,"messages":[{"role":"user","content":"ping"}]}'
```

**Step 2 — Check Anthropic status**
- https://status.anthropic.com

**Step 3 — Enable fallback mode**
The `EnrichPost` job already uses `mockEnrichment()` when the API fails, so jobs will auto-resolve. Verify this is working by checking that `pending` posts are being published via the mock path.

**Step 4 — If mock fallback is not triggering**
```bash
# Check failed jobs
php artisan queue:failed | grep EnrichPost

# Retry with force-mock flag
ANTHROPIC_API_KEY="" php artisan queue:work --once
```

---

## Post-Incident Review Template

Complete within 48 hours of incident resolution.

```markdown
# Post-Incident Review: [Title]
Date: 
Severity: P0/P1/P2
Duration: [start time] → [end time] = [total duration]
Incident Commander: 
Scribe:

## Summary
[2-3 sentence description of what happened and impact]

## Timeline
- HH:MM — Alert fired / first detection
- HH:MM — IC designated
- HH:MM — [Action taken]
- HH:MM — Root cause identified
- HH:MM — Fix deployed
- HH:MM — Incident resolved

## Root Cause
[Technical explanation of what caused the incident]

## Impact
- Users affected: [estimate]
- Features affected: [list]
- Data affected: [yes/no — detail if yes]
- Revenue impact: [if applicable]

## What Went Well
- [Item]

## What Needs Improvement
- [Item]

## Action Items
| Action | Owner | Due Date | Priority |
|--------|-------|----------|----------|
| [Fix] | [Name] | [Date] | P1 |
```

---

## Incident Register

Maintain a running log of all P0/P1 incidents:

| Date | Severity | Title | Duration | Root Cause | Resolved |
|---|---|---|---|---|---|
| YYYY-MM-DD | P0 | Example: DB failover | 23 min | Primary DB disk full | ✅ |

Review register in monthly engineering retrospectives.

---

## On-Call Schedule

To be defined when team grows beyond 3 engineers.

Requirements:
- 24/7 coverage for P0 alerts
- Maximum 1-week on-call rotation per engineer
- Handoff checklist at start of each rotation
- Compensation policy for out-of-hours incidents
- Explicit escalation path when primary on-call is unreachable
