# Changelog - CPN-Plugins

All notable changes to this repository will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2026-09-14] - Roundcube host-package redirect

### Changed
- **roundcubeWebmail** (1.0.0 → **1.1.0**): Catalog entry no longer deploys under legacy panel public paths. Description and install hooks redirect operators to **Plugins > Host packages (Email)** / `cpn app install --name roundcube` (`/opt/cpn-webmail/roundcube`, panel proxy `/roundcube/`). Removed CyberPanel path strings from `meta.xml` and path helpers.

## [2026-09-14] - Catalog dates and Featured metadata

### Added
- Per-plugin `released`, `updated`, `install_count`, and `featured` fields in `meta.xml` so CPN Plugin Store can show European dates and a Featured filter (top installs / explicit featured).

## [2026-09-13] - MTA-STS and BIMI email enablement plugins

### Added
- **mtaSts** (1.0.0): Free Email plugin that unlocks CPN **Email → MTA-STS** (policy/DNS UI already in the panel).
- **bimi** (1.0.0): Free Email plugin that unlocks CPN **Email → BIMI**.
- `catalog.json` entries for `mtaSts` and `bimi`.
- Optional `install-host.sh` / `uninstall-host.sh` write or clear `/var/lib/cpn/features/*.enabled`.

## [2026-09-13] - News Targeted Hosting Commerce (paid)

### Added
- **ntHostingBilling** (1.0.0): Paid hosting business layer for CPN (clients linked to CPN users, products/packages, orders, invoices, subscriptions, site ownership, manual + PayPal billing, client password recovery). Entitlement via `api.newstargeted.com`. Public UI under site `/nt-billing` after `install-host.sh`.
- `catalog.json` entry for `ntHostingBilling`.

## [2026-09-12] - Drop CyberPanel disclaimer boilerplate

### Changed
- Removed repeated "not CyberPanel" / "no API keys in package" disclaimer lines from root README, `clamav` README, `ntMalwareApi` README/`meta.xml`, and `fail2ban/CPN.md`. Install and entitlement docs kept.

## [2026-09-12] - ClamAV and paid malware API catalog packages

### Added
- **clamav** (1.0.0): Free host ClamAV install package (`install-host.sh` / `uninstall-host.sh`) for CPN Security → Malware scan.
- **ntMalwareApi** (1.0.0): Paid News Targeted malware API docs and `malware.json.example` (no secrets). Token path: `/var/lib/cpn/malware.json`.
- **fail2ban** (1.4.1 → **1.4.2**): CPN host install helper (`install-host.sh`), `CPN.md`, CPN-facing `meta.xml` description.
- `catalog.json` entries for `clamav` and `ntMalwareApi`.
- `to-do/HOST-SECURITY-PLUGINS.md` epic notes.

### Changed
- Root README lists security host plugins and catalog cache refresh.
- Changelog title clarified as CPN-Plugins (historical entries may still mention older hosts).

## [2026-08-05] - Fail2ban 1.4.1 Security Logs polish

### Added
- **fail2ban** (1.4.0 → **1.4.1**): Security Logs pagination (default 5 per page, go-to-page), dark mobile-friendly log cards, **Clear log** with confirmation (allowlisted sudo truncate of `/var/log/fail2ban.log`).
- `scripts/sudo/cyberpanel-safe-fail2ban-logs` and `cyberpanel-safe-fail2ban-logs-clear` helpers.

### Fixed
- Manage modal opaque backgrounds in dark mode (CPUI `cpui.css`).
- Empty log no longer falls back to unrelated journal lines after clear.

## [2026-08-05] - CyberPanel 2.5.5 sync (Fail2ban 1.4.0)

### Added
- **fail2ban** (1.3.0 → **1.4.0**): Unified Fail2ban Security Manager UI with URL tabs, server-side banned-IP pagination/search, whitelist search/pagination, firewall trusted-IP sync into fail2ban `ignoreip`, batched firewall ban import, live statistics, and per-row **Manage** modal (unban layers, whitelist/blacklist moves, labels). Shared opaque modal CSS for dark mode (CPUI `cpui.css` 1.0.8).
- **docs/cpui-assets**: Canonical `cpui.css` / `cpui_head.html` snapshot for CyberPanel `pluginHolder` when mirroring into `v2.5.5-dev`.

### Changed
- All plugin `meta.xml` files: `<min_version>2.5.5</min_version>` and `<max_version>3.0.0</max_version>` for CyberPanel 2.5.5+ store compatibility.
- Synced live working plugin trees from production CyberPanel 2.5.5 into this repo (CPUI-styled settings templates and related views across Auto Ban, Contabo Snapshot, CSP, Discord Auth/Webhooks, Email Marketing, GTM, Limited phpMyAdmin, Memcache/Redis/PM2, Panel Access, Port Manager, Premium/PayPal examples, SnappyMail Admin, Example/Test plugins).
- Patch version bumps for synced plugins (see `meta.xml` per plugin).

### Fixed
- Fail2ban Manage modal transparent in dark mode: solid card background outside CSS-variable scope; modal markup nested under `.f2b`.

## [2026-06-27] - PostgreSQL Manager 1.0.0

### Added
- **postgresManager**: Free plugin by KraoESPfan1n. Installs PostgreSQL, configures a dedicated local admin role, installs PHP PostgreSQL support for CyberPanel LSAPI, and exposes Adminer at `/postgres-adminer/` with automatic login from the plugin page.

## [2026-03-27] - Firewall UI parity (CyberPanel v2.5.5-dev)

### Note (core panel, not this repo’s plugin code)
- **CyberPanel `firewallManager.getBannedIPs`**: Merges **Auto Ban Security Alerts** `AutoBanLog` rows (latest event per IP) when an IP is not already listed from the firewall DB or `banned_ips.json`, so **Security → Firewall → Banned IPs** matches bans shown under `/plugins/autoBanSecurityAlerts/settings/`. Synthetic row ids use the form `ablog-<log_pk>`; unban/delete routes through the same IP unban flow and removes the log row.

## [2026-03-07] - PM2 Manager 1.2.0

### Fixed
- **pm2Manager** (1.1.1 → 1.2.0): Dashboard table column alignment and data placement. Table rows are now built with DOM (`insertRow`/`insertCell`) so ID, App Name, Namespace, Version, Mode, Status, CPU, Memory, Uptime, Restarts, User, Watching, and Actions align correctly with headers. ID column shows only numeric PM2 id (or –). Fixed static file serving: after plugin updates, copy `pm2Manager/static/**` to CyberPanel `STATIC_ROOT` (e.g. `/usr/local/CyberCP/static/pm2Manager/`) or run `collectstatic` so the panel serves the updated JS/CSS.

### Changed
- **pm2Manager**: Sortable column headers; explicit table and column widths; cache-bust script tag (`dashboard.js?v=15`).

## [2026-02-15] - Settings routes and resilience

### Changed
- **panelAccess** (1.0.0 → 1.0.1): Added `settings/` route so `/plugins/panelAccess/settings/` works in Plugin Store grid.
- **cspManager** (1.0.1 → 1.0.2): Settings view handles missing DB table gracefully; prompts user to run `migrate cspManager` instead of 500.
- **examplePlugin** (1.0.1 → 1.0.2): Template directory layout and compatibility with panel plugin URL routing; ensure template dirs are readable (755) when deployed.

### Fixed
- **emailMarketing**: Added `settings/` route (version already 1.0.2). All plugins with a settings page now expose `/plugins/<name>/settings/` for the store.

## [2026-02-02] - Redis Manager & Memcache Manager 1.1.0 (CyberPanel 2.5.5-dev)

### Added
- **Redis Manager** (1.0.0 → 1.1.0): Confirmations on all Actions (Start, Stop, Restart, Flush All) and Save Settings; Load Default button to restore Redis config defaults; Fix permissions button and API when config file is unreadable; auto-detect config path (Redis INFO, process, systemd, find); deploy script and fix-permissions script.
- **Memcache Manager** (1.0.0 → 1.1.0): Version bump for CyberPanel 2.5.5-dev compatibility.
- **README**: Added Redis Manager and Memcache Manager to Available Plugins table.

### Changed
- **Redis Manager**: Settings form and Load Default always visible (including when config path not yet set); editable config defaults passed to template for reset.

## [2026-02-02] - Repository v1.2.0

### Changed
- **Repository version**: 1.1.0 → 1.2.0
- **README**: Added contaboAutoSnapshot and cspManager to Available Plugins table

### Fixed
- **cspManager**: Migration creates `cspManager_cspconfig` table; run `python3 manage.py migrate cspManager` if missing

## [2026-02-02] - Unified verification for all premium plugins

### Changed (premiumPlugin, paypalPremiumPlugin)
- **premiumPlugin** (1.0.1 → 1.0.2): Unified verification - Plugin Grants, activation key, Patreon, PayPal, AES-256-CBC encryption. Same flow as contaboAutoSnapshot.
- **paypalPremiumPlugin** (1.0.1 → 1.0.2): Unified verification - Plugin Grants, activation key, Patreon, PayPal, AES-256-CBC encryption. Same flow as contaboAutoSnapshot.

## [2026-02-02] - contaboAutoSnapshot 1.0.2

### Changed (contaboAutoSnapshot)
- **contaboAutoSnapshot** (1.0.1 → 1.0.2): Contabo API x-request-id fix (UUID4), max snapshots from plan, unified settings form, API credentials save once, activation key persistence, optional AES-256-CBC encryption for verification API, Plugin Grants auto-unlock

## [2026-02-01] - New categories added

### Added
- **Monitoring** - Health checks, metrics, alerts
- **Integration** - Webhooks, Discord, third-party APIs
- **Email** - Email marketing, deliverability
- **Development** - Dev tools, PM2, staging
- **Analytics** - Stats, GTM, reporting

### Changed (category reassignments)
- **discordWebhooks** (1.0.1 → 1.0.2): Utility → Integration
- **emailMarketing** (1.0.1 → 1.0.2): Utility → Email
- **googleTagManager** (1.0.1 → 1.0.2): Utility → Analytics
- **pm2Manager** (1.1.0 → 1.1.1): Utility → Development

## [2026-02-01] - Category updates and Plugin removal

### Changed
- **Plugin categories**: Removed the generic "Plugin" category. Valid categories are now: **Utility**, **Security**, **Backup**, **Performance**.
- **emailMarketing** (1.0.0 → 1.0.1): Updated `<type>` from `plugin` to `Utility`.
- **examplePlugin** (1.0.0 → 1.0.1): Updated `<type>` from `plugin` to `Utility`.
- **fail2ban** (1.0.1 → 1.0.2): Normalized `<type>` from `security` to `Security`.

### Migration
Plugins using `<type>plugin</type>` or `<type>Plugin</type>` will no longer appear in the Plugin Store. Update your meta.xml to use one of: Utility, Security, Backup, or Performance.
