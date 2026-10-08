<?php
if (!defined('MRA_INIT')) {
    exit;
}
if (!mra_is_owner()) {
    echo '<p class="notice err">Owner access required.</p>';
    return;
}
?>
<section class="card">
  <h1>Owner settings</h1>
  <p class="muted">These settings are stored under <code>/var/lib/cpn/mr-agent/&lt;domain&gt;/settings.json</code>. CPN Plugin Store settings (visibility, rate limit) are also read from <code>settings.json</code> next to the plugin when present.</p>
  <form id="mra-owner-form" class="stack">
    <label class="check"><input type="checkbox" id="mra-enabled" checked> Enable Mr Agent</label>
    <label>
      Visibility
      <select id="mra-visibility">
        <option value="admins_only">Administrators only</option>
        <option value="all_authenticated">All authenticated users</option>
        <option value="packages">Specific package ids</option>
      </select>
    </label>
    <label>
      Package ids (comma list)
      <input id="mra-packages" type="text" placeholder="Default,Pro">
    </label>
    <label class="check"><input type="checkbox" id="mra-allow-user-keys" checked> Allow users to add their own provider API keys</label>
    <label>
      Default provider (full /mr-agent UI)
      <select id="mra-default-provider">
        <option value="free">Free / auto (CPN help + local if configured)</option>
        <option value="local">Local LLM</option>
        <option value="openai">OpenAI</option>
        <option value="anthropic">Anthropic</option>
        <option value="custom">Custom</option>
      </select>
    </label>
    <p class="muted">Provider generates text (local model or cloud keys). MCP skills are panel tools, not a model. Float bubble always uses auto/free routing.</p>
    <label>
      Rate limit (messages / hour / user)
      <input id="mra-rate" type="number" min="1" max="1000" value="60">
    </label>
    <label>
      Custom OpenAI-compatible base URL
      <input id="mra-custom-base" type="url" placeholder="https://example.com/v1">
    </label>
    <label class="check"><input type="checkbox" id="mra-local-only"> Local-only mode (never use cloud providers)</label>
    <label class="check"><input type="checkbox" id="mra-local-lan"> Local allow LAN (private RFC1918 hosts)</label>
    <label>
      Local base URL (on this CPN server; Ollama 11434, LM Studio / Bionic often 1235)
      <input id="mra-local-base" type="url" value="http://127.0.0.1:11434/v1">
    </label>
    <label>
      Local model
      <input id="mra-local-model" type="text" value="llama3.2:1b">
    </label>
    <p class="muted">Install Ollama/LM Studio on the lab host (not only Windows). The panel calls 127.0.0.1 on the server.</p>
    <label>
      Rotate access password (leave blank to keep)
      <input id="mra-access-password" type="password" autocomplete="new-password">
    </label>
    <button type="submit" class="btn">Save owner settings</button>
  </form>
</section>
