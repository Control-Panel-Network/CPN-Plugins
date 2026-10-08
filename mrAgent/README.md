# Mr Agent

AI chat assistant for **CPN Panel**. Users connect their own **provider API keys** (OpenAI, Anthropic Claude, custom OpenAI-compatible). A free lightweight helper answers simple CPN UI/help questions without a paid key.

- Plugin id: `mrAgent`
- Display name: Mr Agent
- Author: master3395
- Pricing: free
- Version: 1.7.0
- Catalog scope: **dual** (Host + Site)

## MCP vs skills (tell others this)

| Term | Meaning |
|------|---------|
| **MCP** | Panel-wide tool protocol (list/call allowlisted tools with authz) |
| **Skills** | Per CPN area: Help, Websites, Packages, Email, DNS, PHP, Plugins, Accounts |
| **Provider API keys** | LLM credentials (not "MCP keys") |
| **Mr Agent** | Chat UI + keys + ACL that uses skills through MCP |

## Install choices

| Choice | Who | Effect | Site takeover? |
|--------|-----|--------|----------------|
| **Host** | Panel admin | Panel chat at `/plugins/mr-agent` | No |
| **Site folder** (default) | Site managers | `/mr-agent/` under docroot; index kept | No |
| **Site vhost** | Confirmed only | Docroot points at Mr Agent `public/` | Yes |

**Preferred:** Install or Activate from the Plugin Store. CPN Panel runs setup automatically (secrets, folder publish, host finalize). On Plugin settings, use **Run setup** / **Publish folder** and **Prune chat logs**. No SSH is required for normal use.

If Host is not installed, site users can still Install on their site.

### Host

Plugins → Store → Install target **Host** → **Install on Host**.

### Site folder

Plugins → Store → Install target **Site** → choose domain → Install (folder mode is default). Preferred full chat: CPN `/plugins/mr-agent?domain=<domain>`. Optional site URL: `https://<domain>/mr-agent`.

### Site vhost (never silent)

In the Store Install form, choose vhost and tick **Confirm vhost takeover**. The panel never switches docroot without that confirm.

Sign in as `owner` (or `admin` / `cpnowner`) with `/var/lib/cpn/mr-agent/<domain>/access.password` (Host: `_host`).

### Optional operator CLI

Advanced operators may still run scripts from SSH (documented only; not shown in Plugin settings):

```bash
INSTALL_MODE=folder sudo bash /home/<domain>/plugins/mrAgent/install.sh <domain>
INSTALL_MODE=vhost CONFIRM=yes sudo bash /home/<domain>/plugins/mrAgent/install.sh <domain>
sudo bash /var/lib/cpn/host-plugins/mrAgent/install-host.sh
php /home/<domain>/plugins/mrAgent/modules/cli_prune.php <domain>
```

## Free lightweight (MVP)

No provider API key required:

- **Help** skill: keyword search over bundled CPN routes/help (`data/help/cpn-routes.json`)
- Answers "where is X in CPN" style questions
- Optionally uses a local OpenAI-compatible endpoint on loopback if configured

Cloud models are only used when you add provider API keys. Inventory tools (`list_websites`, `list_packages`, `list_mailboxes`) work without a paid key and are **scoped to the signed-in CPN user**.

## Per-user isolation (Host and Site)

On a Host install, user2 must never see user1 websites, mailboxes, or packages. Identity comes from the panel float-chat bridge (`username` / `role` / `package_id`) or the Mr Agent session. See **Host install isolation** in `CPN.md`.

Check: `php modules/cli_scope_check.php`

## Skills tree

```text
skills/
  help/        # search_menu, search_docs
  providers/   # list_providers
  websites/    # list_websites (scoped)
  packages/    # list_packages (scoped)
  email/       # list_mailboxes (scoped)
  dns/         # stub (admin for host-wide)
  php/         # stub (admin for host-wide)
  plugins/     # stub (scoped note)
  accounts/    # stub (admin)
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

Chat logs are written under `/var/lib/cpn/mr-agent/<domain>/chats/` (mode 600). Prune runs on each chat send, on owner settings save, during panel setup / `install.sh`, via bridge action `prune`, the Plugin settings **Prune chat logs** button, or optional CLI:

```bash
php /home/<domain>/plugins/mrAgent/modules/cli_prune.php <domain>
# or all domains:
php /home/<domain>/plugins/mrAgent/modules/cli_prune.php --all
```

## Floating bubble (CPN Panel)

When the panel supports plugin float widgets and this plugin is Active:

1. Keep **Enable Mr Agent chat** and **Show floating chat bubble** checked.
2. Set **visibility** so your signed-in panel account is allowed.
3. Refresh any CPN Panel page: a bottom-right **Mr A** bubble opens a compact chat via `/plugins/float-chat`. **Expand** opens panel `/plugins/mr-agent` by default (`expand_via=panel`).

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
