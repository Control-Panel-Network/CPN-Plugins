# Host security plugins (CPN epic notes)

Date: 12/09/2026

## Shipped in catalog

- `fail2ban`: already present; CPN host path via `install-host.sh` + `CPN.md` (1.4.2 meta)
- `clamav`: free ClamAV host install package
- `ntMalwareApi`: paid News Targeted malware API docs + `malware.json.example` (no secrets)

## Deferred (panel Host packages, not catalog)

MariaDB, MySQL, PostgreSQL, RabbitMQ, OpenLiteSpeed refresh, and similar **Apps → Plugins host packages** remain panel `cpn app` / Host packages UI. They are not duplicated here unless a domain-scoped plugin drop-in is required later.
