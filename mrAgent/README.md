# Mr Agent

AI chat assistant for **CPN Panel**. Users connect their own **provider API keys** (OpenAI, Anthropic Claude, custom OpenAI-compatible). A free lightweight helper answers simple CPN UI/help questions without a paid key.

- Plugin id: `mrAgent`
- Display name: Mr Agent
- Author: master3395
- Pricing: free
- Version: 1.2.0

## MCP vs skills (tell others this)

| Term | Meaning |
|------|---------|
| **MCP** | Panel-wide tool protocol (list/call allowlisted tools with authz) |
| **Skills** | Per CPN area: Help, Websites, Packages, Email, DNS, PHP, Plugins, Accounts |
| **Provider API keys** | LLM credentials (not "MCP keys") |
| **Mr Agent** | Chat UI + keys + ACL that uses skills through MCP |

## Install

1. In CPN: **Plugins → Store**, install **Mr Agent** on a site (Install target: Site).
2. Finish deploy as root:

```bash
sudo bash /home/<domain>/plugins/mrAgent/install.sh <domain>
```

3. Open `https://<domain>/mr-agent`
4. Sign in as `owner` (or `admin` / `cpnowner`) with the access password from:

```text
/var/lib/cpn/mr-agent/<domain>/access.password
```

## Free lightweight (MVP)

No provider API key required:

- **Help** skill: keyword search over bundled CPN routes/help (`data/help/cpn-routes.json`)
- Answers "where is X in CPN" style questions
- Optionally uses a local OpenAI-compatible endpoint on loopback if configured

Cloud models are only used when you add provider API keys. Owner inventory tools (`list_websites`, `list_packages`) also work without a paid key when you are signed in as owner/admin.

## Skills tree

```text
skills/
  help/        # search_menu, search_docs
  providers/   # list_providers
  websites/    # list_websites (read-only, owner)
  packages/    # list_packages (read-only, owner)
  email/       # stub
  dns/         # stub
  php/         # stub
  plugins/     # stub
  accounts/    # stub
```

APIs: `GET ?api=skills`, `POST ?api=mcp` (`list_tools` / `call_tool`).

## Providers

| Provider | Notes |
|----------|--------|
| Free lightweight | Built-in CPN help/search |
| OpenAI | Official OpenAI-compatible API |
| Anthropic | Claude Messages API |
| Custom | Your OpenAI-compatible base URL |
| Local | Loopback only (`local_base_url`) |

Keys live under `/var/lib/cpn/mr-agent/<domain>/keys.json` (mode 600). Never commit secrets.

## Owner ACL

Configure in CPN Plugin settings and/or Mr Agent Owner settings:

- `enabled`: site chat on/off
- `show_floating_bubble`: floating bubble in CPN Panel (default on when Active)
- `visibility`: `admins_only` | `all_authenticated` | `packages`
- `package_ids`: comma list when visibility is `packages`
- `allow_user_keys`: let users store their own provider API keys
- `default_provider`: `free` | `openai` | `anthropic` | `custom` | `local`
- `rate_limit_per_hour`: basic abuse control

## Floating bubble (CPN Panel)

When the panel supports plugin float widgets and this plugin is Active:

1. Keep **Enable Mr Agent chat** and **Show floating chat bubble** checked.
2. Set **visibility** so your signed-in panel account is allowed.
3. Refresh any CPN Panel page: a bottom-right **Mr A** bubble opens a compact chat (free helper via panel bridge). **Expand** opens the full site UI at `/mr-agent`.

**Show in sidebar** only adds an Installed plugins nav link (dashboard). It is not the floating bubble. The sidebar footer speech icon is **Feedback**, not Mr Agent.

## Uninstall

```bash
sudo bash /home/<domain>/plugins/mrAgent/uninstall.sh <domain>
sudo cpn plugin remove --domain <domain> --id mrAgent --yes
```

Add `--purge-secrets` to remove `/var/lib/cpn/mr-agent/<domain>/`.

## Docs

- `CPN.md` : operator notes
- `to-do/ARCHITECTURE-MCP-SKILLS.md` : MCP vs skills product alignment
