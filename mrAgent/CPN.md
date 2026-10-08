# Mr Agent on CPN Panel

Catalog id: `mrAgent`

Site-scoped PHP chat app published at clean URL `/mr-agent`. Complements the panel; it does not replace native CPN routes.

## Naming

| Context | Term |
|---------|------|
| UI / friendly | **Mr Agent** |
| LLM credentials | **provider API keys** |
| Tool calling | Model Context Protocol (**MCP**) style tools |

Do not call provider API keys "MCP keys".

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
- Authz: login gate + visibility ACL
- Secrets never logged (redaction helper)
- HTTP client blocks private/metadata hosts except explicit loopback local provider
- No `shell_exec` / command tools in MVP
- Plugin root `.htaccess` denies direct access to PHP/modules

## Panel sidebar

Enable **Show in sidebar** in Plugin settings. The panel dashboard for plugins is status-oriented today; day-to-day chat is at `/mr-agent`.
