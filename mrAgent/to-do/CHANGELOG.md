# Mr Agent changelog (plugin-local)

## 1.8.0 (08/10/2026)

- Host policy file `/var/lib/cpn/mr-agent/host-policy.json`: `allow_host_chat` (default on), `allow_site_install` (default off)
- Owner settings UI + API for the two switches; prefer panel `/plugins/mr-agent` for server owner
- Modes: Off, Panel only, Panel + optional site (visibility ACL remains separate)
- Turning off site install does not uninstall existing site copies

## 1.6.0 (08/10/2026)

- Host/Site install modes: dual catalog scope, `install-host.sh`, folder publish under `/mr-agent/`
- Vhost takeover only with `CONFIRM=yes`; settings `install_mode`, `expand_via`
- Uninstall restores previous docroot after vhost mode

## 1.5.0 (08/10/2026)

- First-class **Local LLM** provider (Ollama / LM Studio / Bionic / OpenAI-compatible on the CPN server)
- Owner settings: `local_base_url`, `local_model`, `local_only_mode`, `local_allow_lan`
- Free / auto path: CPN navigation for panel questions; general chat prefers local when configured
- Float bubble posts to panel `/plugins/float-chat` (same origin); never the site `/mr-agent` URL
- Docs: Local vs Cloud provider API keys vs MCP skills (`ARCHITECTURE-LOCAL-VS-CLOUD-VS-MCP.md`)

## 1.4.0 (08/10/2026)

- Per-user Host/Site isolation for MCP skills (`modules/scope.php`)
- Email `list_mailboxes`; websites/packages scoped; host stubs admin-gated
- `php modules/cli_scope_check.php` two-user check
- Bridge `call_tool` + ping isolation fields

## 1.3.0 (08/10/2026)

- Storage and resource limits (retention, disk MB, rate/token/concurrency)

## 1.2.0 (08/10/2026)

- Floating chat bubble + `panel_bridge.php`

