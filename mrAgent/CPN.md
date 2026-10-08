# Mr Agent on CPN Panel

Catalog id: `mrAgent`

Site-scoped PHP chat app published at clean URL `/mr-agent`. Complements the panel; it does not replace native CPN routes.

## Naming

| Context | Term |
|---------|------|
| UI / friendly | **Mr Agent** |
| Text generator on server | **Local LLM** (Ollama / LM Studio / Bionic) |
| Cloud LLM credentials | **provider API keys** |
| Tool protocol | **MCP** (Model Context Protocol style), panel-wide |
| Per-area modules | **Skills** (Help, Websites, Packages, Email, ...) |

Do not call provider API keys "MCP keys". Local LLM is not MCP.

## Architecture

1. **Provider**: who generates text (local on the CPN server, or cloud keys).
2. **MCP**: allowlisted tools the assistant (or `?api=mcp`) can call with authz.
3. **Skills**: one folder per CPN area under `skills/<id>/skill.php`.
4. **Mr Agent**: chat UI, providers, ACL; loads skills into the MCP surface.

See `to-do/ARCHITECTURE-MCP-SKILLS.md` and `to-do/ARCHITECTURE-LOCAL-VS-CLOUD-VS-MCP.md`.

### Point lab host at Ollama / LM Studio

On the Alma/Ubuntu lab (not Windows):

```bash
# Ollama example
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.2:1b
# Mr Agent default: http://127.0.0.1:11434/v1

# LM Studio / Bionic: enable local server, often http://127.0.0.1:1235/v1
```

Then set plugin/owner **Local base URL** and **Local model**, or defaults already point at Ollama.

## Skills (1.4.0)

| Skill | Status | Tools (examples) | Scope |
|-------|--------|------------------|-------|
| Help and Menu | active (free) | `search_menu`, `search_docs` | No tenant data |
| Providers | active | `list_providers` | Current user keys only |
| Websites | active | `list_websites` | Owned / site-ACL domains; admin: all |
| Packages | active | `list_packages` | Assigned package; admin: all |
| Email | active | `list_mailboxes` | Mailboxes for scoped domains |
| DNS, PHP, Plugins, Accounts | stub | `*_skill_status` | Host-wide: admin only |

Builtin: `list_skills`.

## Host install isolation (1.4.0)

When Mr Agent is installed once for the **Host** (shared float chat / host-plugins tree), every CPN login still only sees **their own** account data through chat, MCP, and skills.

| Identity source | How |
|-----------------|-----|
| Panel float chat | Trusted CLI `panel_bridge.php` stdin: `username`, `role`, `package_id` from the signed-in panel session (not a shared owner token) |
| Site `/mr-agent` UI | Mr Agent session after login (`mra_user` / `mra_role`) |

Rules:

- Deny by default; never load `/home/<other>` or another user's mail stores
- Websites/subdomains: owner match or `site-acl.json` grants (same idea as panel site ACL)
- Email: `mail-accounts.json` rows whose domain is in the actor's allowed sites (no passwords)
- Packages: assigned `package_id` only (admins see the full catalog)
- DNS/PHP/Accounts host stubs: panel owner/admin only
- Broader admin scope only when role is owner/admin (same as panel pages; no Mr Agent bypass)

Verify: `php modules/cli_scope_check.php` (two fake users + admin).

## Install paths

| Path | Role |
|------|------|
| `/var/lib/cpn/host-plugins/mrAgent/` | Host catalog install (when panel Host target is used) |
| `/home/<domain>/plugins/mrAgent/` | Site catalog install tree |
| `/home/<domain>/public_html/mr-agent` | Symlink to `public/` after `install.sh` |
| `/var/lib/cpn/mr-agent/<domain>/` | Site secrets, keys, owner settings (mode 700/600) |
| `/var/lib/cpn/mr-agent/_host/` | Host secrets when installed on Host |
| `/var/lib/cpn/mr-agent/<domain>/chats/` | Conversation JSON (pruned by retention/count/disk MB) |
| `/var/lib/cpn/mr-agent/<domain>/locks/` | Concurrent-request lock files |
| `settings.json` (plugin dir) | CPN panel plugin settings fields |

## Sideload (without Store cache)

```bash
# Copy folder into the site plugins directory, then:
sudo bash /home/<domain>/plugins/mrAgent/install.sh <domain>
sudo rm -f /var/lib/cpn/plugin-catalog-cache.json
```

Or install from Store after this plugin lands on `main` and the catalog cache refreshes.

## Security

- CSRF on POST APIs (`csrf` field or `X-CSRF-Token`)
- Authz: login gate + visibility ACL + **per-user resource scope** (`modules/scope.php`)
- Host inventory (DNS/PHP/Accounts stubs): owner/admin role only
- Secrets never logged (redaction helper); mode 600 under `/var/lib/cpn/mr-agent/`
- HTTP client blocks private/metadata hosts except explicit loopback local provider
- Storage/resource caps: retention days, max conversations, disk MB, rate limit, max tokens, message length, concurrency 1 or 2, local timeout/response bytes, upload size
- No `shell_exec` / command tools in MVP
- Plugin root `.htaccess` denies direct access to PHP/modules
- MFA material under `/var/lib/cpn/mfa/` is never read or cleared by Mr Agent

## Storage prune (1.3.0)

Opportunistic prune keeps chat logs under owner caps:

1. Delete conversations older than `chat_retention_days`
2. If count exceeds `max_stored_conversations`, delete oldest
3. If `chats/` exceeds `max_chat_disk_mb`, delete oldest until under cap

Triggers: chat send, owner settings save, `install.sh`, bridge `action=prune`, or `php modules/cli_prune.php <domain>|--all`.

## Panel sidebar vs floating bubble

| Control | Effect |
|---------|--------|
| **Show in sidebar** | Nav link under Installed plugins (plugin dashboard) |
| **Show floating chat bubble** | Bottom-right **Mr A** widget on CPN Panel pages (ACL gated) |
| Sidebar footer speech icon | CPN **Feedback**, not Mr Agent |

Day-to-day full chat remains at `/mr-agent` after `install.sh`. Compact panel chat uses `modules/panel_bridge.php` (CLI) via the panel float-chat route.

Requires a CPN Panel build that injects Active plugin float assets (`panel_float` / `public/assets/panel-float/`).


## Storage prune (1.3.0)

Opportunistic prune keeps chat logs under owner caps:

1. Delete conversations older than `chat_retention_days`
2. If count exceeds `max_stored_conversations`, delete oldest
3. If `chats/` exceeds `max_chat_disk_mb`, delete oldest until under cap

Triggers: chat send, owner settings save, `install.sh`, bridge `action=prune`, or `php modules/cli_prune.php <domain>|--all`.
