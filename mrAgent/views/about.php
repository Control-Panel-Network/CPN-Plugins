<?php
if (!defined('MRA_INIT')) {
    exit;
}
?>
<section class="card">
  <h1>About Mr Agent</h1>
  <p>Friendly AI chat for CPN Panel. <strong>MCP</strong> is the panel-wide tool protocol. <strong>Skills</strong> are per-area modules (Help, Websites, Packages, and more). User-facing copy always says <strong>provider API keys</strong> for LLM credentials (never "MCP keys").</p>
  <h2>Free lightweight</h2>
  <ul>
    <li>Help skill: bundled CPN route/help search (no GPU, no paid key)</li>
    <li>Optional local OpenAI-compatible endpoint on loopback (Ollama / LM Studio) if present</li>
    <li>Does not call cloud providers unless you configure keys</li>
  </ul>
  <h2>Skills</h2>
  <ul>
    <li>Active: Help and Menu, Providers, Websites (list), Packages (list)</li>
    <li>Stub: Email, DNS, PHP, Plugins, Accounts</li>
    <li>Discover via chat tool <code>list_skills</code> or API <code>?api=skills</code></li>
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
    <li>Authz and owner ACL on chat/settings; owner role for host inventory skills</li>
    <li>Keys mode 600 under <code>/var/lib/cpn/mr-agent/</code></li>
    <li>No shell execution from prompts; read-only tools only in MVP</li>
  </ul>
  <p class="muted">Author: master3395 · Plugin id: mrAgent · Version <?php echo mra_h(MRA_VERSION); ?></p>
</section>
