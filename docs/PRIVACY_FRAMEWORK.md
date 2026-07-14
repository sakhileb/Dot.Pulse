# Dot.Pulse Privacy Framework
Version: 1.0 | GDPR + POPIA + CCPA | Classification: Internal — Confidential

---

## Applicable Regulations

| Regulation | Jurisdiction | Enforcement |
|---|---|---|
| GDPR (General Data Protection Regulation) | European Union | €20M or 4% global turnover |
| POPIA (Protection of Personal Information Act) | South Africa | R10M or imprisonment |
| CCPA (California Consumer Privacy Act) | California, USA | $7,500 per intentional violation |

---

## Data Classification

| Level | Definition | Examples |
|---|---|---|
| **Public** | Intended for public consumption | Post content, community names, public profiles |
| **Internal** | Business operations data, not user-specific | Aggregate analytics, server metrics |
| **Confidential** | User data not publicly shared | Email addresses, private messages, profile details |
| **Restricted** | High-risk personal data | Passwords, 2FA secrets, recovery codes, payment info |

All data handling must be proportionate to its classification level.

---

## Personal Information Inventory

| Data Field | Classification | Storage | Retention | Encryption |
|---|---|---|---|---|
| Name | Confidential | users table | Account lifetime | No (but hashed in logs) |
| Email address | Confidential | users table | Account lifetime + 30 days post-deletion | No |
| Password | Restricted | users table | Account lifetime | bcrypt hash |
| IP address | Confidential | sessions, audit_logs | 90 days | No |
| 2FA secret | Restricted | users table | Until removed | Laravel encrypted cast |
| Recovery codes | Restricted | users table | Until regenerated | Hashed |
| Profile bio, location | Confidential | pulse_profiles | Account lifetime | No |
| Private messages | Confidential | pulse_messages | Account lifetime | Planned: AES-256 |
| Notification content | Confidential | notifications | 90 days | No |
| Audit log entries | Confidential | audit store | 7 years | PII fields encrypted |
| File uploads | Confidential | S3 | Per content policy | At-rest S3 encryption |

---

## Legal Bases for Processing (GDPR Article 6)

| Processing Activity | Legal Basis |
|---|---|
| Account creation and authentication | Contract (Art. 6(1)(b)) |
| Posting content and community participation | Contract (Art. 6(1)(b)) |
| AI content moderation | Legitimate Interest (Art. 6(1)(f)) |
| Email notifications | Consent (Art. 6(1)(a)) — opt-in |
| Analytics and platform improvement | Legitimate Interest (Art. 6(1)(f)) |
| Security monitoring and fraud detection | Legitimate Interest (Art. 6(1)(f)) |
| Legal compliance (audit logs) | Legal Obligation (Art. 6(1)(c)) |
| Marketing communications | Consent (Art. 6(1)(a)) — explicit opt-in |

---

## Data Subject Rights

### Right of Access (GDPR Art. 15 / POPIA Sec. 23)

Users can request all data held about them. Response required within **30 days**.

Implementation required:
- [ ] `GET /api/v1/me/data-export` — returns full data package as JSON/CSV
- [ ] Email notification when export is ready
- [ ] Secure, time-limited download link
- [ ] Export includes: profile, posts, comments, reactions, follows, messages, notifications, audit events

### Right to Erasure (GDPR Art. 17 / POPIA Sec. 24)

Users can request complete account and data deletion. Response required within **30 days**.

Implementation required:
- [ ] `DELETE /api/v1/me` — initiates deletion workflow
- [ ] Queued job: `DeleteUserData`
- [ ] Soft-delete all content (30-day recovery window)
- [ ] Hard-delete after recovery window
- [ ] Anonymise audit logs (replace PII with `[DELETED]`)
- [ ] Remove from S3 (media files)
- [ ] Remove notification records
- [ ] Email confirmation to user's last known address

Content authored by the user may be **anonymised rather than deleted** to preserve community knowledge integrity:
- Post body retained as "Content by [Deleted User]"
- Author attribution removed
- Must disclose this in Privacy Policy

### Right to Rectification (GDPR Art. 16)

Users can correct their data via the profile settings page (`/profile/pulse-settings`).

### Right to Data Portability (GDPR Art. 20)

Data export (see Right of Access) must be in machine-readable format (JSON).

### Right to Object (GDPR Art. 21)

Users can object to:
- Marketing communications (unsubscribe link in every email)
- Profiling (opt-out of personalised feed ranking)

### Right to Restrict Processing (GDPR Art. 18)

Implemented via account suspension workflow — content preserved but not processed.

---

## Consent Management

### Consent Categories

| Category | Required? | Default | Mechanism |
|---|---|---|---|
| Essential (auth, security) | Yes | Always on | No consent needed |
| Functional (preferences, settings) | No | On | Consent banner toggle |
| Analytics (aggregated usage) | No | Off | Explicit opt-in |
| Marketing emails | No | Off | Explicit opt-in at registration |
| AI personalisation | No | On | Opt-out via privacy dashboard |
| Third-party integrations | No | Off | Per-integration consent |

### Consent Storage

```
pulse_consent_records (to be created)
├── user_id
├── category
├── granted (boolean)
├── granted_at
├── withdrawn_at (nullable)
├── ip_address
├── user_agent
└── version (consent text version)
```

Every consent change is immutable — append-only.

---

## Cookie Policy

| Cookie | Purpose | Duration | Consent Required |
|---|---|---|---|
| `laravel_session` | Authentication session | 2 hours | No (essential) |
| `XSRF-TOKEN` | CSRF protection | Session | No (essential) |
| `remember_me` | Persistent login | 30 days | No (essential) |
| `cookie_consent` | Records consent choice | 1 year | No (essential) |
| Analytics cookies | Usage tracking | 90 days | Yes (opt-in) |

---

## Data Retention Policy

| Data Category | Retention Period | Deletion Method |
|---|---|---|
| Active user account | Until deletion request | Soft delete → hard delete after 30 days |
| Published posts/comments | Account lifetime | Anonymised on account deletion |
| Private messages | Account lifetime (both parties) | Hard delete when both parties delete accounts |
| Notifications | 90 days | Automated purge job |
| Session data | 2 hours (active) / 30 days (remember me) | Automated expiry |
| IP addresses (in logs) | 90 days | Automated purge from session logs |
| Audit logs | 7 years | Never deleted — PII anonymised after 2 years |
| AI moderation logs | 2 years | Anonymised after 2 years |
| Deleted account data | 30-day recovery window | Hard delete at window close |
| File uploads | Until deleted by user | S3 lifecycle rule |

---

## Data Minimisation

Collect only what is necessary for the stated purpose:

| Context | Collect | Do Not Collect |
|---|---|---|
| Registration | Name, email, password | Date of birth, phone (unless MFA) |
| Profile | Bio, location, website, skills | National ID, passport |
| Post creation | Content, type, community | Device fingerprint, precise GPS |
| Event RSVP | Status (going/maybe/not) | Physical address |

---

## Cross-Border Data Transfer

For data transferred outside South Africa (POPIA) or EEA (GDPR):

| Destination | Mechanism | Status |
|---|---|---|
| Anthropic (Claude AI) | SCCs (Standard Contractual Clauses) | Required before AI features go live |
| AWS S3 | AWS DPA + SCCs | Verify region configuration |
| GitHub Codespaces | Microsoft DPA | Development only — no production data |

All vendor DPAs (Data Processing Agreements) must be signed before processing personal data.

---

## Privacy by Design Checklist

Run this checklist when adding any new feature:

- [ ] What personal data does this feature collect?
- [ ] Is collection necessary for the stated purpose?
- [ ] What is the legal basis for processing?
- [ ] What is the retention period?
- [ ] Where is the data stored and is it encrypted?
- [ ] Can users export or delete this data?
- [ ] Does this feature involve cross-border transfer?
- [ ] Has consent been obtained if required?
- [ ] Is the Privacy Policy updated to reflect this collection?
- [ ] Is there an audit trail for access to this data?

---

## Incident Response (Data Breach)

Under GDPR, data breaches must be reported to the supervisory authority within **72 hours** and to affected users **without undue delay** if high risk.

Under POPIA, the Information Regulator must be notified as soon as reasonably possible.

### Breach Response Steps

```
1. Detect breach (monitoring alert, user report, security scan)
2. Contain (revoke tokens, disable affected endpoints, isolate systems)
3. Assess (what data was exposed? how many users? what risk?)
4. Internal notification within 1 hour
5. Legal counsel notification within 2 hours
6. Regulator notification within 72 hours (if required)
7. Affected user notification (if high risk)
8. Root cause analysis
9. Remediation + post-incident review
10. Update incident register
```

---

## Privacy Dashboard (Planned Feature)

Every user must have access to a Privacy Dashboard at `/settings/privacy`:

- [ ] Download all my data (JSON export)
- [ ] Delete my account
- [ ] View what data we hold about you
- [ ] Manage notification preferences
- [ ] Manage AI personalisation (opt-out of feed ranking)
- [ ] View active sessions
- [ ] Revoke API tokens
- [ ] View consent history
- [ ] Request human review of an AI moderation decision
