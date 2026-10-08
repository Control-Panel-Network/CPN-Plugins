<?php
if (!defined('MRA_INIT')) {
    exit;
}
?>
<section class="card">
  <h1>About Mr Agent</h1>
  <p>Friendly AI chat for CPN Panel. <strong>Provider</strong> generates text (Local LLM on this server, or cloud provider API keys). <strong>MCP</strong> is the panel-wide tool protocol. <strong>Skills</strong> are per-area modules (Help, Websites, Packages, and more). Never call provider API keys "MCP keys".</p>
  <h2>Local LLM (first-class)</h2>
  <ul>
    <li>Ollama, LM Studio, Bionic, or any OpenAI-compatible API on the CPN server</li>
    <li>Defaults: <code>http://127.0.0.1:11434/v1</code> (Ollama) or <code>http://127.0.0.1:1235/v1</code> (LM Studio)</li>
    <li>Windows browser Ollama is not reachable from the guest; install the model on the lab host</li>
  </ul>
  <h2>Free lightweight</h2>
  <ul>
    <li>CPN navigation only (menu/route search) when the question is about the panel</li>
    <li>General chat (dates, small talk) prefers a configured local model; otherwise explains that free helper is CPN-only</li>
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
