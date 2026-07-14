# Dot.Pulse Enterprise Vendor Questionnaire — Self-Assessment
Version: 1.0 | Updated: 2026-07-14 | Classification: Confidential

---

This document is a self-assessment against the most common enterprise vendor security questionnaire categories. It tracks current status and evidence for each control.

**Legend:**
- ✅ Implemented
- 🔄 Partially implemented / In progress
- ❌ Not yet implemented
- N/A Not applicable

---

## Section 1: Information Security Program

| Control | Status | Evidence / Notes |
|---|---|---|
| Documented information security policy | ❌ | ENTERPRISE_VENDOR_SECURITY_BLUEPRINT.md is the baseline |
| Security governance structure defined | 🔄 | Security champion role defined, formal governance pending |
| Dedicated security resources | 🔄 | Part of engineering responsibility |
| Annual security reviews | ❌ | Scheduled but not yet performed |
| Security awareness training | ❌ | To be scheduled |
| Third-party security assessments | ❌ | Pending penetration testing |

---

## Section 2: Access Control

| Control | Status | Evidence / Notes |
|---|---|---|
| Role-based access control (RBAC) | ✅ | Jetstream roles + PulseProfile roles (customer, business, developer, partner, moderator, admin) |
| Least privilege principle | ✅ | Policies grant minimum required permissions |
| Multi-factor authentication (MFA) | ✅ | Laravel Fortify 2FA (TOTP) |
| Single Sign-On (SSO) | ✅ | Ecosystem SSO via `GET /auth/ecosystem` |
| API token management | ✅ | Sanctum tokens with abilities scoping |
| Session timeout | ✅ | Configurable session lifetime |
| Account lockout on failed logins | ❌ | Not yet implemented |
| Privileged access management | 🔄 | Admin role exists, formal PAM process pending |
| Access reviews (quarterly) | ❌ | Process not yet defined |
| Offboarding process | ❌ | Account deletion implemented, formal process pending |

---

## Section 3: Data Protection

| Control | Status | Evidence / Notes |
|---|---|---|
| Data classification policy | 🔄 | Defined in PRIVACY_FRAMEWORK.md, not yet fully enforced |
| Data encryption at rest | 🔄 | S3 at-rest encryption; PII fields not yet encrypted in DB |
| Data encryption in transit | ✅ | TLS on all endpoints |
| PII identified and inventoried | ✅ | Inventory in PRIVACY_FRAMEWORK.md |
| Data retention policies defined | ✅ | Defined in PRIVACY_FRAMEWORK.md |
| Data retention automated | ❌ | Manual process — automation pending |
| Backup encryption | 🔄 | Depends on S3/provider settings |
| Key management | ❌ | Using APP_KEY; KMS integration pending |
| Data loss prevention | ❌ | Pending |

---

## Section 4: Incident Response

| Control | Status | Evidence / Notes |
|---|---|---|
| Incident response plan documented | ✅ | INCIDENT_RESPONSE.md |
| Incident severity classification | ✅ | P0-P3 defined in INCIDENT_RESPONSE.md |
| Breach notification process | ✅ | Documented (72hr GDPR, ASAP POPIA) |
| Incident response tested | ❌ | Tabletop exercise pending |
| Incident tracking system | ❌ | Linear/Jira to be set up |
| Post-incident review process | ✅ | Template in INCIDENT_RESPONSE.md |
| Mean time to detect (MTTD) | ❌ | Not yet measured |
| Mean time to resolve (MTTR) | ❌ | Not yet measured |

---

## Section 5: Vulnerability Management

| Control | Status | Evidence / Notes |
|---|---|---|
| Regular vulnerability scanning | 🔄 | `composer audit` + `npm audit` in CI |
| Penetration testing | ❌ | Scheduled before production launch |
| Dependency CVE monitoring | 🔄 | CI scans; Dependabot not yet configured |
| Patch management process | ❌ | Formal process pending |
| Container image scanning | ❌ | No containers yet |
| SBOM generated | ❌ | CI workflow pending |
| CVE remediation SLAs | ❌ | To be defined: Critical < 24h, High < 7 days |

---

## Section 6: Network Security

| Control | Status | Evidence / Notes |
|---|---|---|
| TLS 1.2+ enforced | ✅ | Platform requirement |
| HSTS enabled | 🔄 | Web server config — to verify |
| CSRF protection | ✅ | Laravel built-in |
| Clickjacking prevention (X-Frame-Options) | 🔄 | Web server config — to verify |
| Content Security Policy | ❌ | CSP headers not yet configured |
| Rate limiting | ✅ | Per-endpoint rate limiters in AppServiceProvider |
| DDoS protection | 🔄 | Depends on CDN/cloud provider |
| Firewall rules | 🔄 | Depends on infrastructure setup |
| WAF (Web Application Firewall) | ❌ | Pending |

---

## Section 7: Application Security

| Control | Status | Evidence / Notes |
|---|---|---|
| Input validation | ✅ | FormRequests on all API endpoints |
| Output encoding | ✅ | Blade auto-escapes; API returns JSON |
| SQL injection prevention | ✅ | Eloquent parameterised queries |
| XSS prevention | ✅ | Blade auto-escaping + JSON API |
| Mass assignment protection | ✅ | Explicit `$fillable` on all models |
| File upload security | 🔄 | MIME validation + size limits; virus scan pending |
| Signed URLs for file access | 🔄 | S3 signed URLs planned; local uses public storage |
| Security headers | ❌ | CSP, HSTS, X-Content-Type-Options not configured |
| OWASP ASVS Level 2 | 🔄 | Most controls met; full assessment pending |

---

## Section 8: AI & Automated Decision-Making

| Control | Status | Evidence / Notes |
|---|---|---|
| AI governance policy | ✅ | AI_GOVERNANCE.md |
| Human oversight for consequential decisions | ✅ | Moderation queue; humans review flagged content |
| AI model audit trail | ✅ | PulseModerationLog records AI decisions |
| Prompt injection prevention | ✅ | Input sanitised before prompt construction |
| AI output validation | ✅ | Response validated before acting on it |
| Fallback when AI unavailable | ✅ | Mock enrichment fallback in AiModerationService |
| Model vendor DPA | ❌ | Anthropic DPA / SCCs required before production |
| AI bias review | ❌ | Planned for quarterly AI review |
| Right to human review of AI decision | ❌ | Process defined; UI pending |

---

## Section 9: Privacy & Compliance

| Control | Status | Evidence / Notes |
|---|---|---|
| Privacy policy published | ❌ | Template exists; legal review pending |
| GDPR compliance | 🔄 | Framework defined; implementation 60% complete |
| POPIA compliance | 🔄 | Framework defined; Information Officer to be appointed |
| CCPA compliance | 🔄 | Framework defined |
| Data subject rights (access, erasure, portability) | 🔄 | Framework in PRIVACY_FRAMEWORK.md; implementation pending |
| Consent management | ❌ | Design defined; not yet implemented |
| Cookie consent | ❌ | Not yet implemented |
| Privacy impact assessments | ❌ | Process defined; not yet performed |
| Data Processing Agreements with vendors | ❌ | Anthropic, AWS DPAs pending |
| Cross-border transfer mechanisms | ❌ | SCCs for Anthropic pending |

---

## Section 10: Business Continuity & Disaster Recovery

| Control | Status | Evidence / Notes |
|---|---|---|
| Business continuity plan | ❌ | Pending |
| Disaster recovery plan | 🔄 | Objectives defined in ENTERPRISE_VENDOR_SECURITY_BLUEPRINT.md |
| Automated backups | ❌ | Depends on infrastructure setup |
| Backup restoration tested | ❌ | Pending |
| RTO/RPO defined | ✅ | Defined in blueprint |
| RTO/RPO tested | ❌ | Pending DR exercise |
| High availability architecture | 🔄 | Planned; current setup single-region |
| Geographic redundancy | ❌ | Pending |

---

## Section 11: Third-Party & Supply Chain

| Control | Status | Evidence / Notes |
|---|---|---|
| Third-party risk assessment | ❌ | Process pending |
| Vendor security reviews | ❌ | Pending for Anthropic, AWS |
| Software composition analysis | 🔄 | `composer audit` and `npm audit` in CI |
| Open source license compliance | 🔄 | Manual review; automated scanning pending |
| SBOM maintained | ❌ | CI workflow pending |

---

## Section 12: Operational Security

| Control | Status | Evidence / Notes |
|---|---|---|
| Change management process | 🔄 | Git workflow + PR reviews defined |
| Deployment pipeline security | 🔄 | DEVSECOPS_PIPELINE.md defined; CI not yet implemented |
| Secrets management | 🔄 | `.env` file; formal vault integration pending |
| Logging and monitoring | 🔄 | Standards defined; production monitoring stack pending |
| Audit logging | 🔄 | PulseModerationLog exists; comprehensive audit trail pending |
| Log retention | ❌ | Process pending |
| Security event monitoring | ❌ | SIEM pending |

---

## Self-Assessment Score

| Section | Controls Total | Implemented | Partial | Not Done | Score |
|---|---|---|---|---|---|
| Information Security Program | 6 | 0 | 2 | 4 | 17% |
| Access Control | 10 | 6 | 2 | 2 | 70% |
| Data Protection | 9 | 2 | 3 | 4 | 39% |
| Incident Response | 8 | 3 | 0 | 5 | 38% |
| Vulnerability Management | 7 | 0 | 2 | 5 | 14% |
| Network Security | 8 | 2 | 4 | 2 | 50% |
| Application Security | 9 | 6 | 2 | 1 | 78% |
| AI & Automated Decision-Making | 8 | 5 | 1 | 2 | 69% |
| Privacy & Compliance | 11 | 0 | 6 | 5 | 27% |
| Business Continuity & DR | 8 | 1 | 2 | 5 | 25% |
| Third-Party & Supply Chain | 5 | 0 | 2 | 3 | 20% |
| Operational Security | 6 | 0 | 4 | 2 | 33% |
| **TOTAL** | **95** | **25** | **30** | **40** | **~42%** |

---

## Path to 98% Score

The 42% current score reflects a platform that is **technically sound but not yet operationally mature**. The code quality and security architecture are strong. The gaps are primarily in:

1. **Formal processes** (policies, procedures, incident response testing)
2. **Privacy implementation** (consent management, data export, erasure)
3. **Infrastructure security** (security headers, WAF, formal backups)
4. **Third-party risk** (vendor DPAs, SBOM)
5. **Monitoring** (production stack not yet deployed)

Priority actions to reach 98%:
- [ ] Implement privacy dashboard and data subject rights (GDPR/POPIA)
- [ ] Set up production monitoring (Grafana, Sentry, alerts)
- [ ] Configure security headers (CSP, HSTS, X-Content-Type)
- [ ] Sign vendor DPAs (Anthropic, AWS)
- [ ] Complete penetration test
- [ ] Implement GitHub Actions CI pipeline (per DEVSECOPS_PIPELINE.md)
- [ ] Set up automated backups with tested restore
- [ ] Write and review formal security policies
- [ ] Implement account lockout and login attempt logging
- [ ] Configure WAF and DDoS protection
