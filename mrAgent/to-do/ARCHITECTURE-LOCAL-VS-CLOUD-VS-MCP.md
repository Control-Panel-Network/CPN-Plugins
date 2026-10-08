# Mr Agent: Local model vs Cloud API keys vs MCP skills

Date: 08/10/2026

## Short answer

| Layer | Role | Needs |
|-------|------|--------|
| **Provider (Local LLM)** | Generates text on the CPN **server** via OpenAI-compatible HTTP (`/v1/chat/completions`) | Ollama / LM Studio / Bionic listening on loopback (or owner-allowed LAN) |
| **Provider (Cloud keys)** | Generates text via OpenAI / Anthropic / custom remote URL | Provider API keys under `/var/lib/cpn/mr-agent/` |
| **MCP** | Panel-wide **tool** protocol (list/call allowlisted skills) | Not a substitute for a model |
| **Skills** | Per CPN area modules (Help, Websites, Packages, …) | Loaded into MCP for the model (or free help search) |
| **Free helper** | Keyword CPN navigation help only | No model; no cloud key |

## Server-side local (important)

Mr Agent PHP and the panel float-chat bridge run on the **lab/server host**. They call:

- `http://127.0.0.1:11434/v1` (Ollama default)
- `http://127.0.0.1:1235/v1` (LM Studio / Bionic often)

A browser on Windows **cannot** reach `127.0.0.1` on the user's PC from the Alma/Ubuntu guest. Install the model on the CPN server, or expose a LAN endpoint and enable **Local allow LAN**.

## Float chat

Bubble chat must POST to panel `/plugins/float-chat?domain=…&id=mrAgent` (same origin, session cookies). Never call `https://<site>/mr-agent` from the panel (CORS / Failed to fetch).

## Routing

1. `local_only_mode` → always `local`
2. Provider `auto` / `free` → try local for general chat; CPN corpus help only when the question looks like panel navigation
3. Explicit `local` / `openai` / `anthropic` / `custom` → that provider
