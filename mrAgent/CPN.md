# Mr Agent on CPN Panel

Catalog id: `mrAgent`

Site-scoped PHP chat app published at clean URL `/mr-agent`. Complements the panel; it does not replace native CPN routes.

## Naming

| Context | Term |
|---------|------|
| UI / friendly | **Mr Agent** |
| LLM credentials | **provider API keys** |
| Tool protocol | **MCP** (Model Context Protocol style), panel-wide |
| Per-area modules | **Skills** (Help, Websites, Packages, Email, ...) |

Do not call provider API keys "MCP keys".

## Architecture

1. **MCP**: allowlisted tools the assistant (or `?api=mcp`) can call with authz.
2. **Skills**: one folder per CPN area under `skills/<id>/skill.php`.
3. **Mr Agent**: chat UI, providers, ACL; loads skills into the MCP surface.

See `to-do/ARCHITECTURE-MCP-SKILLS.md`.

## Skills (1.1.0)

| Skill | Status | Tools (examples) |
|-------|--------|------------------|
| Help and Menu | active (free) | `search_menu`, `search_docs` |
| Providers | active | `list_providers` |
| Websites | active (owner) | `list_websites` |
| Packages | active (owner) | `list_packages` |
| Email, DNS, PHP, Plugins, Accounts | stub | `*_skill_status` |

Builtin: `list_skills`.

## Install paths

| Path | Role |
|------|------|
| `/home/<domain>/plugins/mrAgent/` | Catalog install tree |
| `/home/<domain>/public_html/mr-agent` | Symlink to `public/` after `install.sh` |
| `/var/lib/cpn/mr-agent/<domain>/` | Secrets, keys, owner settings (mode 700/600) |
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
- Authz: login gate + visibility ACL; owner role for host inventory skills
- Secrets never logged (redaction helper)
- HTTP client blocks private/metadata hosts except explicit loopback local provider
- No `shell_exec` / command tools in MVP
- Plugin root `.htaccess` denies direct access to PHP/modules

## Panel sidebar

Enable **Show in sidebar** in Plugin settings. The panel dashboard for plugins is status-oriented today; day-to-day chat is at `/mr-agent`.
