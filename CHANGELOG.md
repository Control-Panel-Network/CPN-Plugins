# Changelog - CPN-Plugins

All notable changes to this repository will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2026-10-09] - Mr Agent free helper answers date and time

### Fixed
- **mrAgent** (1.8.0 -> **1.8.1**): The free lightweight path now answers date / time / weekday questions (English and Norwegian, for example "What day is it today?", "Hva er klokka?") directly from the server clock: `Today is Friday 09/10/2026 (week 41). Server time: 23:15 (Europe/Oslo).` No provider API key or local model is needed, and the bubble no longer waits on a local LLM timeout before replying with an unrelated CPN menu list. Server time prefers the operating system zone (`/etc/localtime`, `/etc/timezone`) over PHP CLI `date.timezone` defaults (often UTC). Only short clock questions trigger this, so panel queries that merely mention "today" still go to CPN help. Free helper copy updated to say dates are handled locally. Companion panel fix: `POST /plugins/float-chat` now surfaces the bridge JSON error instead of an empty `PHP bridge exited 1:` and sends `provider=free` (older 1.4.x site installs rejected `auto`).

## [2026-10-08] - Mr Agent host policy, tabbed settings, and statistics

### Added
- **mrAgent** (1.7.0 -> **1.8.0**): Server-wide host policy at /var/lib/cpn/mr-agent/host-policy.json. llow_host_chat (default on) for panel bubble and /plugins/mr-agent. llow_site_install (default off) for Store Site Install. Modes: Off, Panel only, Panel + optional site. Visibility ACL stays separate. Existing site installs are not mass-uninstalled when site install is turned off. Owner UI: tabbed settings (General, Access, AI / Providers, Storage, Statistics); host policy read-only on Access (edit in CPN Panel Host settings / /plugins/mr-agent). Privacy-safe Statistics via ?api=stats and panel_bridge stats (counts, storage vs limit, distinct users, last activity as dd/mm/yyyy HH:mm; no message bodies).

## [2026-10-08] - Proton Mail (external / Bridge)

### Added
- **protonMail** (**1.0.0**): Host-scoped Email plugin. Unlocks CPN Email -> Proton Mail (/email/proton): Open Proton Mail (https://mail.proton.me), Bridge IMAP/SMTP guidance, and operator settings. Honest external integration (not a self-hosted Proton stack). Bridge automation is follow-up only (protonMail/to-do/BRIDGE-FOLLOWUP.md).

## [2026-10-08] - Mr Agent panel setup (no Operator notes)

### Changed
- **mrAgent** (1.6.0 -> **1.7.0**): Removed Operator notes settings field (no `install.sh` / prune CLI dump in Plugin settings). Store Install / Activate / Enable runs setup via the panel. Settings actions: Run setup / Publish folder and Prune chat logs. Optional SSH scripts stay in README only.

## [2026-10-08] - Mr Agent Host/Site install modes

### Added
- **mrAgent** (1.5.0 -> **1.6.0**): Dual catalog scope (Host + Site). Host install via `install-host.sh` / Store Host target serves chat from the panel (`/plugins/mr-agent`) with no site takeover. Site default `INSTALL_MODE=folder` publishes `/mr-agent/` under the docroot without wiping the site index. `INSTALL_MODE=vhost` requires `CONFIRM=yes` (refuses silent docroot takeover). Settings: `install_mode`, `expand_via`. Uninstall restores previous docroot after vhost mode.


## [2026-10-08] - Mr Agent local LLM + smarter free path

### Added
- **mrAgent** (1.4.0 -> **1.5.0**): Local LLM is a first-class provider (Ollama / LM Studio / Bionic / any OpenAI-compatible on the CPN server). Owner settings: `local_base_url`, `local_model`, `local_only_mode`, `local_allow_lan`. Free/auto routing prefers local for general chat; CPN help only for panel navigation (no more unrelated websites dumps for "what day is it"). Float bubble always uses panel `/plugins/float-chat` proxy with `provider=auto`. Documents that the browser cannot reach Windows Ollama unless exposed to the server.

## [2026-10-08] - Mr Agent per-user Host isolation

### Added
- **mrAgent** (1.3.0 -> **1.4.0**): Per-user data isolation for Host (and Site) installs. Float-chat / session identity drives every list/read skill: websites and mailboxes only for owned or site-ACL domains; packages only the assigned package; host-wide DNS/PHP/Accounts stubs require panel owner/admin (same as panel pages). Deny-by-default path checks block `/home/<other>`. New `modules/scope.php`, bridge `call_tool`, and `php modules/cli_scope_check.php` (two fake users). No panel float-chat change required (username/role/package_id already passed securely).

## [2026-10-08] - Mr Agent storage and resource limits

### Added
- **mrAgent** (1.2.0 -> **1.3.0**): Owner storage/resource restrictions so chat cannot clog host disk or abuse concurrency. New settings (Store fields + Owner UI): `max_history_messages` (100), `max_stored_conversations` (200), `chat_retention_days` (30), `max_chat_disk_mb` (50), `max_tokens_per_reply` (1024), `max_message_length` (4000), `concurrent_requests` (1 or 2, default 2), `local_timeout_seconds` (45), `local_max_response_bytes` (1 MiB), `max_upload_bytes` (256 KiB); keeps `rate_limit_per_hour` (60). Conversations under `/var/lib/cpn/mr-agent/<domain>/chats/`; opportunistic prune on chat send, settings save, `install.sh`, bridge `prune`, and `modules/cli_prune.php`. HTTP client caps request/response bytes; local provider uses timeout + max response bytes.

## [2026-10-08] - Mr Agent floating bubble

### Added
- **mrAgent** (1.1.0 -> **1.2.0**): Floating chat bubble in CPN Panel (bottom-right) when **Show floating chat bubble** is on, the plugin is Active/enabled, and the signed-in user passes visibility ACL (`admins_only` / `all_authenticated` / `packages`). Declares `panel_float` + `show_floating_bubble` settings fields. Ships `public/assets/panel-float/` and CLI `modules/panel_bridge.php` for panel-proxied free chat. Expand opens `https://<domain>/mr-agent`. Requires a panel build that injects Active plugin float widgets (CPN-Control-Panel-Network).

## [2026-10-08] - Mr Agent MCP skills

### Changed
- **mrAgent** (1.0.0 -> **1.1.0**): Aligns product language: **MCP** is the panel-wide tool protocol; **skills** are per CPN area. Adds `skills/` registry (Help, Providers, Websites list, Packages list active; Email/DNS/PHP/Plugins/Accounts stubs), `list_skills`, `GET ?api=skills`, `POST ?api=mcp`, and `to-do/ARCHITECTURE-MCP-SKILLS.md`. Chat UI remains site-published at `/mr-agent`; inventory tools read host `/var/lib/cpn` with owner authz.

## [2026-10-08] - Mr Agent AI chat

### Added
- **mrAgent** (1.0.0): Free site plugin **Mr Agent**, an AI chat assistant for CPN. Supports OpenAI-compatible, Anthropic, custom base URL, and local loopback providers via **provider API keys**. Includes a free lightweight CPN help/menu search path (no paid key). Owner ACL (admins only / all authenticated / package ids), CSRF, rate limits, and safe read-oriented tools (`search_menu`, `search_docs`, `list_providers`). Secrets under `/var/lib/cpn/mr-agent/<domain>/` (mode 600). Run `install.sh` after Store install to publish `/mr-agent`.
- `catalog.json` entry for `mrAgent`.

## [2026-10-03] - FileGator site file manager

### Added
- **fileGator** (1.0.0): Free site plugin that deploys pinned FileGator **7.16.5** under `/home/<domain>/plugins/fileGator/`, jails the repository to the site home (or docroot), publishes clean `/filegator`, and stores a generated admin password under `/var/lib/cpn/filegator/<domain>/` (mode 600). Includes `install.sh`, `heal.sh`, `uninstall.sh`, and `CPN.md`.
- `catalog.json` entry for `fileGator`.

## [2026-10-01] - Remove legacy panel identifiers

### Changed
- Rebranded every plugin, doc, script, template and helper to CPN only: legacy product names, install paths (now under /usr/local/cpn, /etc/cpn, /var/log/cpn, /home/cpn), env and helper names, UI strings and upstream links were replaced with CPN equivalents. Helper scripts under scripts/sudo are now named cpn-safe-fail2ban-logs and cpn-safe-fail2ban-logs-clear.

## [2026-09-14] - Roundcube host-package redirect

### Changed
- **roundcubeWebmail** (1.0.0 -> **1.1.0**): Catalog entry no longer deploys under legacy panel public paths. Description and install hooks redirect operators to **Plugins > Host packages (Email)** / `cpn app install --name roundcube` (`/opt/cpn-webmail/roundcube`, panel proxy `/roundcube/`). Removed CPN path strings from `meta.xml` and path helpers.

## [2026-09-14] - Catalog dates and Featured metadata

### Added
- Per-plugin `released`, `updated`, `install_count`, and `featured` fields in `meta.xml` so CPN Plugin Store can show European dates and a Featured filter (top installs / explicit featured).

## [2026-09-13] - MTA-STS and BIMI email enablement plugins

### Added
- **mtaSts** (1.0.0): Free Email plugin that unlocks CPN **Email -> MTA-STS** (policy/DNS UI already in the panel).
- **bimi** (1.0.0): Free Email plugin that unlocks CPN **Email -> BIMI**.
- `catalog.json` entries for `mtaSts` and `bimi`.
- Optional `install-host.sh` / `uninstall-host.sh` write or clear `/var/lib/cpn/features/*.enabled`.

## [2026-09-13] - News Targeted Hosting Commerce (paid)

### Added
- **ntHostingBilling** (1.0.0): Paid hosting business layer for CPN (clients linked to CPN users, products/packages, orders, invoices, subscriptions, site ownership, manual + PayPal billing, client password recovery). Entitlement via `api.newstargeted.com`. Public UI under site `/nt-billing` after `install-host.sh`.
- `catalog.json` entry for `ntHostingBilling`.

## [2026-09-12] - Drop CPN disclaimer boilerplate

### Changed
- Removed repeated "not CPN" / "no API keys in package" disclaimer lines from root README, `clamav` README, `ntMalwareApi` README/`meta.xml`, and `fail2ban/CPN.md`. Install and entitlement docs kept.

## [2026-09-12] - ClamAV and paid malware API catalog packages

### Added
- **clamav** (1.0.0): Free host ClamAV install package (`install-host.sh` / `uninstall-host.sh`) for CPN Security -> Malware scan.
- **ntMalwareApi** (1.0.0): Paid News Targeted malware API docs and `malware.json.example` (no secrets). Token path: `/var/lib/cpn/malware.json`.
- **fail2ban** (1.4.1 -> **1.4.2**): CPN host install helper (`install-host.sh`), `CPN.md`, CPN-facing `meta.xml` description.
- `catalog.json` entries for `clamav` and `ntMalwareApi`.
- `to-do/HOST-SECURITY-PLUGINS.md` epic notes.

### Changed
- Root README lists security host plugins and catalog cache refresh.
- Changelog title clarified as CPN-Plugins (historical entries may still mention older hosts).

## [2026-08-05] - Fail2ban 1.4.1 Security Logs polish

### Added
- **fail2ban** (1.4.0 -> **1.4.1**): Security Logs pagination (default 5 per page, go-to-page), dark mobile-friendly log cards, **Clear log** with confirmation (allowlisted sudo truncate of `/var/log/fail2ban.log`).
- `scripts/sudo/cpn-safe-fail2ban-logs` and `cpn-safe-fail2ban-logs-clear` helpers.

### Fixed
- Manage modal opaque backgrounds in dark mode (CPUI `cpui.css`).
- Empty log no longer falls back to unrelated journal lines after clear.

## [2026-08-05] - CPN 2.5.5 sync (Fail2ban 1.4.0)

### Added
- **fail2ban** (1.3.0 -> **1.4.0**): Unified Fail2ban Security Manager UI with URL tabs, server-side banned-IP pagination/search, whitelist search/pagination, firewall trusted-IP sync into fail2ban `ignoreip`, batched firewall ban import, live statistics, and per-row **Manage** modal (unban layers, whitelist/blacklist moves, labels). Shared opaque modal CSS for dark mode (CPUI `cpui.css` 1.0.8).
- **docs/cpui-assets**: Canonical `cpui.css` / `cpui_head.html` snapshot for CPN `pluginHolder` when mirroring into `v2.5.5-dev`.

### Changed
- All plugin `meta.xml` files: `<min_version>2.5.5</min_version>` and `<max_version>3.0.0</max_version>` for CPN 2.5.5+ store compatibility.
- Synced live working plugin trees from production CPN 2.5.5 into this repo (CPUI-styled settings templates and related views across Auto Ban, Contabo Snapshot, CSP, Discord Auth/Webhooks, Email Marketing, GTM, Limited phpMyAdmin, Memcache/Redis/PM2, Panel Access, Port Manager, Premium/PayPal examples, SnappyMail Admin, Example/Test plugins).
- Patch version bumps for synced plugins (see `meta.xml` per plugin).

### Fixed
- Fail2ban Manage modal transparent in dark mode: solid card background outside CSS-variable scope; modal markup nested under `.f2b`.

## [2026-06-27] - PostgreSQL Manager 1.0.0

### Added
- **postgresManager**: Free plugin by KraoESPfan1n. Installs PostgreSQL, configures a dedicated local admin role, installs PHP PostgreSQL support for CPN LSAPI, and exposes Adminer at `/postgres-adminer/` with automatic login from the plugin page.

## [2026-03-27] - Firewall UI parity (CPN v2.5.5-dev)

### Note (core panel, not this repoÔÇÖs plugin code)
- **CPN `firewallManager.getBannedIPs`**: Merges **Auto Ban Security Alerts** `AutoBanLog` rows (latest event per IP) when an IP is not already listed from the firewall DB or `banned_ips.json`, so **Security -> Firewall -> Banned IPs** matches bans shown under `/plugins/autoBanSecurityAlerts/settings/`. Synthetic row ids use the form `ablog-<log_pk>`; unban/delete routes through the same IP unban flow and removes the log row.

## [2026-03-07] - PM2 Manager 1.2.0

### Fixed
- **pm2Manager** (1.1.1 -> 1.2.0): Dashboard table column alignment and data placement. Table rows are now built with DOM (`insertRow`/`insertCell`) so ID, App Name, Namespace, Version, Mode, Status, CPU, Memory, Uptime, Restarts, User, Watching, and Actions align correctly with headers. ID column shows only numeric PM2 id (or ÔÇô). Fixed static file serving: after plugin updates, copy `pm2Manager/static/**` to CPN `STATIC_ROOT` (e.g. `/usr/local/cpn/static/pm2Manager/`) or run `collectstatic` so the panel serves the updated JS/CSS.

### Changed
- **pm2Manager**: Sortable column headers; explicit table and column widths; cache-bust script tag (`dashboard.js?v=15`).

## [2026-02-15] - Settings routes and resilience

### Changed
- **panelAccess** (1.0.0 -> 1.0.1): Added `settings/` route so `/plugins/panelAccess/settings/` works in Plugin Store grid.
- **cspManager** (1.0.1 -> 1.0.2): Settings view handles missing DB table gracefully; prompts user to run `migrate cspManager` instead of 500.
- **examplePlugin** (1.0.1 -> 1.0.2): Template directory layout and compatibility with panel plugin URL routing; ensure template dirs are readable (755) when deployed.

### Fixed
- **emailMarketing**: Added `settings/` route (version already 1.0.2). All plugins with a settings page now expose `/plugins/<name>/settings/` for the store.

## [2026-02-02] - Redis Manager & Memcache Manager 1.1.0 (CPN 2.5.5-dev)

### Added
- **Redis Manager** (1.0.0 -> 1.1.0): Confirmations on all Actions (Start, Stop, Restart, Flush All) and Save Settings; Load Default button to restore Redis config defaults; Fix permissions button and API when config file is unreadable; auto-detect config path (Redis INFO, process, systemd, find); deploy script and fix-permissions script.
- **Memcache Manager** (1.0.0 -> 1.1.0): Version bump for CPN 2.5.5-dev compatibility.
- **README**: Added Redis Manager and Memcache Manager to Available Plugins table.

### Changed
- **Redis Manager**: Settings form and Load Default always visible (including when config path not yet set); editable config defaults passed to template for reset.

## [2026-02-02] - Repository v1.2.0

### Changed
- **Repository version**: 1.1.0 -> 1.2.0
- **README**: Added contaboAutoSnapshot and cspManager to Available Plugins table

### Fixed
- **cspManager**: Migration creates `cspManager_cspconfig` table; run `python3 manage.py migrate cspManager` if missing

## [2026-02-02] - Unified verification for all premium plugins

### Changed (premiumPlugin, paypalPremiumPlugin)
- **premiumPlugin** (1.0.1 -> 1.0.2): Unified verification - Plugin Grants, activation key, Patreon, PayPal, AES-256-CBC encryption. Same flow as contaboAutoSnapshot.
- **paypalPremiumPlugin** (1.0.1 -> 1.0.2): Unified verification - Plugin Grants, activation key, Patreon, PayPal, AES-256-CBC encryption. Same flow as contaboAutoSnapshot.

## [2026-02-02] - contaboAutoSnapshot 1.0.2

### Changed (contaboAutoSnapshot)
- **contaboAutoSnapshot** (1.0.1 -> 1.0.2): Contabo API x-request-id fix (UUID4), max snapshots from plan, unified settings form, API credentials save once, activation key persistence, optional AES-256-CBC encryption for verification API, Plugin Grants auto-unlock

## [2026-02-01] - New categories added

### Added
- **Monitoring** - Health checks, metrics, alerts
- **Integration** - Webhooks, Discord, third-party APIs
- **Email** - Email marketing, deliverability
- **Development** - Dev tools, PM2, staging
- **Analytics** - Stats, GTM, reporting

### Changed (category reassignments)
- **discordWebhooks** (1.0.1 -> 1.0.2): Utility -> Integration
- **emailMarketing** (1.0.1 -> 1.0.2): Utility -> Email
- **googleTagManager** (1.0.1 -> 1.0.2): Utility -> Analytics
- **pm2Manager** (1.1.0 -> 1.1.1): Utility -> Development

## [2026-02-01] - Category updates and Plugin removal

### Changed
- **Plugin categories**: Removed the generic "Plugin" category. Valid categories are now: **Utility**, **Security**, **Backup**, **Performance**.
- **emailMarketing** (1.0.0 -> 1.0.1): Updated `<type>` from `plugin` to `Utility`.
- **examplePlugin** (1.0.0 -> 1.0.1): Updated `<type>` from `plugin` to `Utility`.
- **fail2ban** (1.0.1 -> 1.0.2): Normalized `<type>` from `security` to `Security`.

### Migration
Plugins using `<type>plugin</type>` or `<type>Plugin</type>` will no longer appear in the Plugin Store. Update your meta.xml to use one of: Utility, Security, Backup, or Performance.