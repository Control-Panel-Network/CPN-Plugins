# Mr Agent: MCP vs skills (architecture)

Date: 08/10/2026

## Short answer (for the team)

Yes: **MCP should be panel-wide**, and **Mr Agent should use one skill per CPN area**.

| Layer | Role |
|-------|------|
| **MCP** | Panel-wide tool protocol: discover and call allowlisted tools with authz. Not "API keys". |
| **Skills** | Per-area modules under `skills/<area>/skill.php` (Help, Websites, Email, DNS, Packages, PHP, Plugins, Accounts, ...). Each skill registers MCP tools. |
| **Mr Agent** | Chat UI + provider API keys + ACL. Consumes skills through the MCP tool surface. |

Free lightweight path uses the **Help** skill (menu/docs search) without paid provider keys.

## Current vs desired

| | Current (1.0.0) | Desired (1.1.0+) |
|--|-----------------|------------------|
| Scope | Site plugin UI at `/mr-agent` | UI may stay site-published; **tools see host CPN domains** |
| Tools | Flat `search_menu`, `search_docs`, `list_providers` in `tools.php` | Skills registry + MCP list/call API |
| Areas | Help-ish only | Help, Providers, Websites, Packages active; Email/DNS/PHP/Plugins/Accounts stubs |
| Install | Site Store target | Site UI for now; Host Store target when panel allowlists `mrAgent` |

## What to tell others

1. **MCP** = how agents call CPN capabilities (tools), not how you pay for ChatGPT.
2. **Provider API keys** = OpenAI / Anthropic / custom LLM credentials.
3. **Skills** = Websites, Email, DNS, Packages, etc. Drop a folder under `skills/` to add an area.
4. Free users get Help/Menu search; richer inventory tools need owner/admin Mr Agent role and readable `/var/lib/cpn/` registry files.

## Host vs Site

- **Site install** remains the supported path for publishing `/mr-agent` on a domain.
- MCP tools already read **host** state (`/var/lib/cpn/sites/`, `packages.json`) when PHP can read them.
- A later panel change can add `mrAgent` to the host-scoped plugin allowlist so Store shows **Install on Host** + per-site Activate. Not required for skill MVP.

## APIs

- `GET ?api=skills` : catalog of skills and tools
- `POST ?api=mcp` with `action=list_tools` or `call_tool` (+ CSRF): MCP-style surface for the chat UI and future external clients

## Security

- Read-only tools in MVP (no shell, no destructive panel writes)
- Owner authz for Websites / Packages / stub area status tools
- Secrets only under `/var/lib/cpn/mr-agent/<domain>/` mode 600
