# Mr Agent

AI chat assistant for **CPN Panel**. Users connect their own **provider API keys** (OpenAI, Anthropic Claude, custom OpenAI-compatible). A free lightweight helper answers simple CPN UI/help questions without a paid key.

- Plugin id: `mrAgent`
- Display name: Mr Agent
- Author: master3395
- Pricing: free
- Version: 1.3.0

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

## Free lightweight + local

No cloud provider API key required:

- **Help** skill: keyword search over bundled CPN routes/help (`data/help/cpn-routes.json`) for panel navigation
- General chat (dates, small talk) prefers a **local** model when `local_base_url` is reachable on the server
- If no local model: free helper clearly says it only answers CPN navigation (does not dump unrelated website links)

Cloud models are only used when you add provider API keys (unless Local-only mode is on). Owner inventory tools (`list_websites`, `list_packages`) also work without a paid key when you are signed in as owner/admin.

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
| Free lightweight | Built-in CPN navigation help |
| Local | Server OpenAI-compatible (`local_base_url`; Ollama / LM Studio / Bionic) |
| OpenAI | Official OpenAI-compatible API |
| Anthropic | Claude Messages API |
| Custom | Your remote OpenAI-compatible base URL |

Keys live under `/var/lib/cpn/mr-agent/<domain>/keys.json` (mode 600). Never commit secrets.

## Owner ACL

Configure in CPN Plugin settings and/or Mr Agent Owner settings:

- `enabled`: site chat on/off
- `show_floating_bubble`: floating bubble in CPN Panel (default on when Active)
- `visibility`: `admins_only` | `all_authenticated` | `packages`
- `package_ids`: comma list when visibility is `packages`
- `allow_user_keys`: let users store their own provider API keys
- `default_provider`: `free` | `openai` | `anthropic` | `custom` | `local`
- `rate_limit_per_hour`: messages per user per hour (default **60**)
- `max_message_length`: characters per user message (default **4000**)
- `max_tokens_per_reply`: model completion cap (default **1024**)
- `concurrent_requests`: in-flight chats per user, **1** or **2** (default **2**)
- `max_history_messages`: messages kept per conversation file (default **100**)
- `max_stored_conversations`: conversation files kept (default **200**)
- `chat_retention_days`: delete conversations older than N days (default **30**)
- `max_chat_disk_mb`: hard cap for `/var/lib/cpn/mr-agent/<domain>/chats/` (default **50**)
- `local_timeout_seconds`: local provider HTTP timeout (default **45**)
- `local_max_response_bytes`: refuse oversized local replies (default **1048576**)
- `max_upload_bytes`: refuse huge POST bodies (default **262144**)

Chat logs are written under `/var/lib/cpn/mr-agent/<domain>/chats/` (mode 600). Prune runs on each chat send, on owner settings save, during `install.sh`, via bridge action `prune`, or:

```bash
php /home/<domain>/plugins/mrAgent/modules/cli_prune.php <domain>
# or all domains:
php /home/<domain>/plugins/mrAgent/modules/cli_prune.php --all
```

## Floating bubble (CPN Panel)

When the panel supports plugin float widgets and this plugin is Active:

1. Keep **Enable Mr Agent chat** and **Show floating chat bubble** checked.
2. Set **visibility** so your signed-in panel account is allowed.
3. Refresh any CPN Panel page: a bottom-right **Mr A** bubble opens a compact chat via panel `/plugins/float-chat` (not the site origin). **Expand** opens the full site UI at `/mr-agent`.

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
