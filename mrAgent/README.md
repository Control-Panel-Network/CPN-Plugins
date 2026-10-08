# Mr Agent

AI chat assistant for **CPN Panel**. Users connect their own **provider API keys** (OpenAI, Anthropic Claude, custom OpenAI-compatible). A free lightweight helper answers simple CPN UI/help questions without a paid key.

- Plugin id: `mrAgent`
- Display name: Mr Agent
- Author: master3395
- Pricing: free

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

- Keyword search over bundled CPN routes/help (`data/help/cpn-routes.json`)
- Answers "where is X in CPN" style questions
- Optionally uses a local OpenAI-compatible endpoint on loopback (`127.0.0.1` / `localhost`) if configured (for example Ollama)

Cloud models are only used when you add provider API keys.

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

- `visibility`: `admins_only` | `all_authenticated` | `packages`
- `package_ids`: comma list when visibility is `packages`
- `allow_user_keys`: let users store their own provider API keys
- `default_provider`: `free` | `openai` | `anthropic` | `custom` | `local`
- `rate_limit_per_hour`: basic abuse control

## Tools (MCP-style)

MVP exposes safe read-oriented tools for tool calling:

- `search_menu`
- `search_docs`
- `list_providers` (never returns secret values)

Destructive panel actions are not exposed. Full MCP server expansion can build on `modules/tools.php`.

## Uninstall

```bash
sudo bash /home/<domain>/plugins/mrAgent/uninstall.sh <domain>
sudo cpn plugin remove --domain <domain> --id mrAgent --yes
```

Add `--purge-secrets` to remove `/var/lib/cpn/mr-agent/<domain>/`.

## Docs

See `CPN.md` for operator notes.
