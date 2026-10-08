# Mr Agent MVP notes

Date: 08/10/2026

## Shipped (1.0.0)

- Site plugin package `mrAgent` with chat UI, providers, ACL, free help corpus
- Safe tools: search_menu, search_docs, list_providers
- Secrets under `/var/lib/cpn/mr-agent/<domain>/`

## Shipped (1.1.0)

- Skills registry under `skills/` (MCP = panel tools; skills = per area)
- Active: Help, Providers, Websites (list), Packages (list)
- Stubs: Email, DNS, PHP, Plugins, Accounts
- APIs: `?api=skills`, `?api=mcp`
- Docs: `to-do/ARCHITECTURE-MCP-SKILLS.md`

## Later (not blocking)

- Native CPN session SSO (instead of access password gate)
- Streaming SSE replies
- Full external MCP server transport (stdio/SSE) for Cursor and others
- Panel Host Store allowlist for `mrAgent` + optional native panel MCP route
- Confirmed write tools for admin-only actions
