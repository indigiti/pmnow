# Production Checklist — M7

Before switching traffic to a production host:

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] unique 32+ character `APP_KEY`
- [ ] `ADMIN_DEV_BYPASS=false`
- [ ] bootstrap admin password changed
- [ ] HTTPS certificate active
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `HSTS_ENABLED=true`
- [ ] `ALLOW_PRIVATE_SOURCE_URLS=false`
- [ ] Redis enabled for runtime state when deploying more than a single PHP process
- [ ] FastAPI bound to loopback/private interface only
- [ ] `storage/` excluded from public web root and backups encrypted
- [ ] source credentials moved to vault or protected process environment
- [ ] `php tools/security-audit.php --strict` passes
- [ ] `php tools/healthcheck.php` passes with engine running
- [ ] M1–M7 smoke tests pass
- [ ] systemd/supervisor worker and scheduler configured
- [ ] Nginx/Apache front controller blocks private directories
- [ ] log rotation and backup/restore procedure configured
- [ ] source soft quotas set to appropriate provider/account limits
- [ ] Error Center reviewed after first live sync

M7 soft quota counters are operational guardrails, not substitutes for provider-reported quota balances.
