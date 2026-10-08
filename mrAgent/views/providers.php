<?php
if (!defined('MRA_INIT')) {
    exit;
}
?>
<section class="card">
  <h1>Provider API keys</h1>
  <p class="muted">Add keys for OpenAI, Anthropic, or a custom OpenAI-compatible base URL. Keys are stored under <code>/var/lib/cpn/mr-agent/</code> with mode 600 and are never committed to git.</p>
  <div id="mra-keys-status" class="muted">Loading…</div>
  <form id="mra-keys-form" class="stack">
    <label>
      Scope
      <select id="mra-key-scope">
        <option value="user">My keys</option>
        <?php if (mra_is_owner()): ?>
          <option value="host">Host defaults (owner)</option>
        <?php endif; ?>
      </select>
    </label>
    <label>
      Provider
      <select id="mra-key-provider">
        <option value="openai">OpenAI</option>
        <option value="anthropic">Anthropic</option>
        <option value="custom">Custom OpenAI-compatible key</option>
        <option value="local">Local endpoint key (optional)</option>
        <?php if (mra_is_owner()): ?>
          <option value="custom_base_url">Custom base URL (host)</option>
        <?php endif; ?>
      </select>
    </label>
    <label>
      Value
      <input id="mra-key-value" type="password" autocomplete="off" placeholder="Paste provider API key or base URL">
    </label>
    <div class="row">
      <button type="submit" class="btn">Save</button>
      <button type="button" class="btn secondary" id="mra-key-clear">Clear</button>
    </div>
  </form>
</section>
