# Mr Agent (`mrAgent`)

AI chat for CPN. **MCP** = panel-wide tool protocol. **Skills** = per-area modules under `mrAgent/skills/`. **Provider API keys** = LLM credentials.

Version **1.2.0** adds an optional floating chat bubble in CPN Panel (settings: `show_floating_bubble`; ACL via `visibility`). Full UI remains at `/mr-agent`.

Version **1.3.0** adds owner storage and resource limits so chat logs cannot fill the host: retention days, max conversations, disk MB under `/var/lib/cpn/mr-agent/<domain>/chats/`, rate/token/message/concurrency caps, and local provider timeout/response-byte guards. Configure via Plugin Store `settings_fields` or Mr Agent Owner settings. Prune with `php modules/cli_prune.php <domain>`.

Version **1.4.0** enforces **per-user data isolation** on Host (and Site) installs. Each CPN login only sees websites, mailboxes, and packages in their panel scope. Owner/admin broader access matches panel role, not a Mr Agent bypass. Verify with `php modules/cli_scope_check.php`.

See `mrAgent/README.md`, `mrAgent/CPN.md`, and `mrAgent/to-do/ARCHITECTURE-MCP-SKILLS.md`.
