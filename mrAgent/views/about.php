<?php
if (!defined('MRA_INIT')) {
    exit;
}
?>
<section class="card">
  <h1>About Mr Agent</h1>
  <p>Friendly AI chat for CPN Panel. Technical tool calling uses Model Context Protocol (MCP) style tools for safe menu and docs search. User-facing copy always says <strong>provider API keys</strong> for LLM credentials.</p>
  <h2>Free lightweight (MVP)</h2>
  <ul>
    <li>Bundled CPN route/help search (no GPU, no paid key)</li>
    <li>Optional local OpenAI-compatible endpoint on loopback (Ollama / LM Studio) if present</li>
    <li>Does not call cloud providers unless you configure keys</li>
  </ul>
  <h2>Providers</h2>
  <ul>
    <li>OpenAI (OpenAI-compatible API)</li>
    <li>Anthropic Claude</li>
    <li>Custom OpenAI-compatible base URL</li>
    <li>Local loopback endpoint</li>
  </ul>
  <h2>Security</h2>
  <ul>
    <li>CSRF on state-changing APIs</li>
    <li>Authz and owner ACL on chat/settings</li>
    <li>Keys mode 600 under <code>/var/lib/cpn/mr-agent/</code></li>
    <li>No shell execution from prompts; read-only tools only in MVP</li>
  </ul>
  <p class="muted">Author: master3395 · Plugin id: mrAgent · Version <?php echo mra_h(MRA_VERSION); ?></p>
</section>
