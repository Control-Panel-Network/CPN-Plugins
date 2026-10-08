<?php
if (!defined('MRA_INIT')) {
    exit;
}
if (!mra_is_owner()) {
    echo '<p class="notice err">Owner access required.</p>';
    return;
}
$tab = isset($_GET['tab']) ? strtolower(trim((string) $_GET['tab'])) : 'general';
$allowedTabs = ['general', 'access', 'ai', 'storage', 'statistics'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'general';
}
?>
<section class="card">
  <h1>Owner settings</h1>
  <p class="muted">Stored under <code>/var/lib/cpn/mr-agent/&lt;domain&gt;/settings.json</code>. Panel Plugin Store settings are also read when present.</p>

  <div class="mra-tabs" id="mra-owner-tabs" data-initial-tab="<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="mra-tablist" role="tablist" aria-label="Owner settings">
      <button type="button" class="mra-tab" role="tab" data-mra-tab="general" aria-selected="<?php echo $tab === 'general' ? 'true' : 'false'; ?>">General</button>
      <button type="button" class="mra-tab" role="tab" data-mra-tab="access" aria-selected="<?php echo $tab === 'access' ? 'true' : 'false'; ?>">Access</button>
      <button type="button" class="mra-tab" role="tab" data-mra-tab="ai" aria-selected="<?php echo $tab === 'ai' ? 'true' : 'false'; ?>">AI / Providers</button>
      <button type="button" class="mra-tab" role="tab" data-mra-tab="storage" aria-selected="<?php echo $tab === 'storage' ? 'true' : 'false'; ?>">Storage</button>
      <button type="button" class="mra-tab" role="tab" data-mra-tab="statistics" aria-selected="<?php echo $tab === 'statistics' ? 'true' : 'false'; ?>">Statistics</button>
    </div>

    <form id="mra-owner-form" class="stack">
      <div class="mra-tabpanel" data-mra-panel="general" <?php echo $tab === 'general' ? '' : 'hidden'; ?>>
        <label class="check"><input type="checkbox" id="mra-enabled" checked> Enable Mr Agent</label>
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
        <p class="muted">Provider generates text. MCP skills are panel tools, not a model. Float bubble uses auto/free routing.</p>
        <label>
          Rotate access password (leave blank to keep)
          <input id="mra-access-password" type="password" autocomplete="new-password">
        </label>
      </div>

      <div class="mra-tabpanel" data-mra-panel="access" <?php echo $tab === 'access' ? '' : 'hidden'; ?>>
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
        <h2>Host policy (read-only)</h2>
        <p class="muted" id="mra-host-policy">Loading host policy…</p>
        <p class="muted">Edit Allow host chat / Allow site install in CPN Panel: Host Mr Agent settings, Access tab.</p>
      </div>

      <div class="mra-tabpanel" data-mra-panel="ai" <?php echo $tab === 'ai' ? '' : 'hidden'; ?>>
        <h2>Request limits</h2>
        <label>Rate limit (messages / hour / user)<input id="mra-rate" type="number" min="1" max="10000" value="60"></label>
        <label>Max message length (characters)<input id="mra-max-msg" type="number" min="256" max="32000" value="4000"></label>
        <label>Max tokens per reply<input id="mra-max-tokens" type="number" min="64" max="8192" value="1024"></label>
        <label>Concurrent requests per user (1 or 2)<input id="mra-concurrent" type="number" min="1" max="2" value="2"></label>
        <label>Max upload body (bytes)<input id="mra-max-upload" type="number" min="4096" max="2097152" value="262144"></label>
        <h2>Local provider</h2>
        <label>Local request timeout (seconds)<input id="mra-local-timeout" type="number" min="5" max="300" value="45"></label>
        <label>Local max response bytes<input id="mra-local-max-bytes" type="number" min="65536" max="16777216" value="1048576"></label>
        <label>Custom OpenAI-compatible base URL<input id="mra-custom-base" type="url" placeholder="https://example.com/v1"></label>
        <label class="check"><input type="checkbox" id="mra-local-only"> Local-only mode (never use cloud providers)</label>
        <label class="check"><input type="checkbox" id="mra-local-lan"> Local allow LAN (private RFC1918 hosts)</label>
        <label>Local base URL (on this CPN server)<input id="mra-local-base" type="url" value="http://127.0.0.1:11434/v1"></label>
        <label>Local model<input id="mra-local-model" type="text" value="llama3.2:1b"></label>
        <p class="muted">Install Ollama/LM Studio on the lab host. The panel calls 127.0.0.1 on the server.</p>
      </div>

      <div class="mra-tabpanel" data-mra-panel="storage" <?php echo $tab === 'storage' ? '' : 'hidden'; ?>>
        <p class="muted" id="mra-disk-usage">Chat log disk usage: loading…</p>
        <label>Max history messages per conversation<input id="mra-max-history" type="number" min="10" max="500" value="100"></label>
        <label>Max stored conversations<input id="mra-max-convs" type="number" min="10" max="5000" value="200"></label>
        <label>Auto-prune older than (days)<input id="mra-retention" type="number" min="1" max="3650" value="30"></label>
        <label>Max chat log disk (MB)<input id="mra-disk-mb" type="number" min="1" max="10240" value="50"></label>
        <p class="muted">Panel Host/Site settings also offer Run setup and Prune without SSH.</p>
      </div>

      <div class="mra-tabpanel" data-mra-panel="statistics" <?php echo $tab === 'statistics' ? '' : 'hidden'; ?>>
        <div id="mra-stats-body"><p class="muted">Loading statistics…</p></div>
      </div>

      <button type="submit" class="btn" id="mra-save-btn">Save owner settings</button>
    </form>
  </div>
</section>
