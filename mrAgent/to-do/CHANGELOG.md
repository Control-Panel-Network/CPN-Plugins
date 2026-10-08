# Mr Agent changelog

## 1.3.0 (08/10/2026)

- First-class **Local LLM** provider (Ollama / LM Studio / Bionic / OpenAI-compatible on the CPN server).
- Owner settings: `local_base_url`, `local_model`, `local_only_mode`, `local_allow_lan`.
- Free / auto path: CPN navigation for panel questions; general chat prefers local when configured, otherwise a clear CPN-only message (no unrelated route dumps).
- Float bubble posts to panel `/plugins/float-chat` (same origin); never the site `/mr-agent` URL.
- Docs: Local vs Cloud provider API keys vs MCP skills (`ARCHITECTURE-LOCAL-VS-CLOUD-VS-MCP.md`).
