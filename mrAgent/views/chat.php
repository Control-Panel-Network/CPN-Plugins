<?php
if (!defined('MRA_INIT')) {
    exit;
}
$defaultProvider = (string) ($cfg['default_provider'] ?? 'free');
?>
<section class="chat-shell">
  <div class="chat-toolbar">
    <label>
      Provider
      <select id="mra-provider">
        <option value="free" <?php echo $defaultProvider === 'free' ? 'selected' : ''; ?>>Free lightweight</option>
        <option value="openai" <?php echo $defaultProvider === 'openai' ? 'selected' : ''; ?>>OpenAI</option>
        <option value="anthropic" <?php echo $defaultProvider === 'anthropic' ? 'selected' : ''; ?>>Anthropic Claude</option>
        <option value="custom" <?php echo $defaultProvider === 'custom' ? 'selected' : ''; ?>>Custom OpenAI-compatible</option>
        <option value="local" <?php echo $defaultProvider === 'local' ? 'selected' : ''; ?>>Local (loopback)</option>
      </select>
    </label>
    <label>
      Model
      <input id="mra-model" type="text" placeholder="auto" list="mra-models">
      <datalist id="mra-models">
        <option value="cpn-help">
        <option value="gpt-4o-mini">
        <option value="gpt-4o">
        <option value="claude-3-5-haiku-latest">
        <option value="claude-3-5-sonnet-latest">
        <option value="llama3.2:1b">
      </datalist>
    </label>
  </div>
  <div id="mra-transcript" class="transcript" aria-live="polite">
    <div class="bubble assistant">
      Hi, I am <strong>Mr Agent</strong>. Ask where something is in CPN, or chat with your own provider API keys.
      The free lightweight path searches bundled CPN help (no paid key). Tool calling uses Model Context Protocol style tools for menu and docs search only.
    </div>
  </div>
  <form id="mra-chat-form" class="composer">
    <textarea id="mra-message" rows="3" maxlength="8000" placeholder="e.g. Where do I manage Fail2ban?" required></textarea>
    <button type="submit" class="btn" id="mra-send">Send</button>
  </form>
  <p class="muted small">Provider API keys are never shown in chat replies. Destructive panel actions are not exposed in this MVP.</p>
</section>
