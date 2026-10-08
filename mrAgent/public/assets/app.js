(function () {
  "use strict";

  function csrf() {
    return (window.MRA && window.MRA.csrf) || "";
  }

  function api(path, options) {
    options = options || {};
    var headers = options.headers || {};
    headers["Accept"] = "application/json";
    if (options.body && typeof options.body === "object") {
      headers["Content-Type"] = "application/json";
      if (!options.body.csrf) {
        options.body.csrf = csrf();
      }
      options.body = JSON.stringify(options.body);
    }
    if (options.method && options.method !== "GET") {
      headers["X-CSRF-Token"] = csrf();
    }
    return fetch(path, {
      method: options.method || "GET",
      headers: headers,
      body: options.body || undefined,
      credentials: "same-origin",
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok || data.ok === false) {
          var err = new Error((data && data.error) || "Request failed");
          err.data = data;
          throw err;
        }
        if (data.csrf) {
          window.MRA = window.MRA || {};
          window.MRA.csrf = data.csrf;
        }
        return data;
      });
    });
  }

  function addBubble(role, text) {
    var box = document.getElementById("mra-transcript");
    if (!box) return;
    var el = document.createElement("div");
    el.className = "bubble " + role;
    el.textContent = text;
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
  }

  var chatForm = document.getElementById("mra-chat-form");
  if (chatForm) {
    chatForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var msgEl = document.getElementById("mra-message");
      var sendBtn = document.getElementById("mra-send");
      var provider = document.getElementById("mra-provider").value;
      var model = document.getElementById("mra-model").value.trim();
      var message = (msgEl.value || "").trim();
      if (!message) return;
      addBubble("user", message);
      msgEl.value = "";
      sendBtn.disabled = true;
      api("?api=chat", {
        method: "POST",
        body: { message: message, provider: provider, model: model, csrf: csrf() },
      })
        .then(function (data) {
          addBubble("assistant", data.reply || "(empty reply)");
        })
        .catch(function (err) {
          addBubble("err", (err && err.message) || "Chat failed");
        })
        .then(function () {
          sendBtn.disabled = false;
          msgEl.focus();
        });
    });
  }

  var keysStatus = document.getElementById("mra-keys-status");
  var keysForm = document.getElementById("mra-keys-form");
  function refreshKeys() {
    if (!keysStatus) return;
    api("?api=keys")
      .then(function (data) {
        var lines = ["Your keys:"];
        Object.keys(data.user_keys || {}).forEach(function (k) {
          var row = data.user_keys[k];
          lines.push(" - " + k + ": " + (row.set ? row.mask : "not set"));
        });
        if (data.host_keys && Object.keys(data.host_keys).length) {
          lines.push("Host defaults:");
          Object.keys(data.host_keys).forEach(function (k) {
            var row = data.host_keys[k];
            lines.push(" - " + k + ": " + (row.set ? row.mask || "(set)" : "not set"));
          });
        }
        keysStatus.textContent = lines.join("\n");
        keysStatus.style.whiteSpace = "pre-wrap";
      })
      .catch(function (err) {
        keysStatus.textContent = (err && err.message) || "Could not load keys";
      });
  }
  if (keysForm) {
    refreshKeys();
    keysForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      api("?api=keys", {
        method: "POST",
        body: {
          scope: document.getElementById("mra-key-scope").value,
          provider: document.getElementById("mra-key-provider").value,
          value: document.getElementById("mra-key-value").value,
          csrf: csrf(),
        },
      })
        .then(function () {
          document.getElementById("mra-key-value").value = "";
          refreshKeys();
        })
        .catch(function (err) {
          alert((err && err.message) || "Save failed");
        });
    });
    var clearBtn = document.getElementById("mra-key-clear");
    if (clearBtn) {
      clearBtn.addEventListener("click", function () {
        api("?api=keys", {
          method: "POST",
          body: {
            scope: document.getElementById("mra-key-scope").value,
            provider: document.getElementById("mra-key-provider").value,
            clear: true,
            csrf: csrf(),
          },
        })
          .then(refreshKeys)
          .catch(function (err) {
            alert((err && err.message) || "Clear failed");
          });
      });
    }
  }

  var ownerForm = document.getElementById("mra-owner-form");
  if (ownerForm) {
    function setNum(id, val) {
      var el = document.getElementById(id);
      if (el && val !== undefined && val !== null) {
        el.value = String(val);
      }
    }
    api("?api=owner-settings")
      .then(function (res) {
        var s = res.settings || {};
        var disk = res.storage || {};
        var hp = res.host_policy || {};
        if (document.getElementById("mra-allow-host-chat")) {
          document.getElementById("mra-allow-host-chat").checked =
            hp.allow_host_chat !== false;
        }
        if (document.getElementById("mra-allow-site-install")) {
          document.getElementById("mra-allow-site-install").checked =
            !!hp.allow_site_install;
        }
        var modeEl = document.getElementById("mra-host-policy-mode");
        if (modeEl) {
          var chatOn = hp.allow_host_chat !== false;
          var siteOn = !!hp.allow_site_install;
          var mode = !chatOn && !siteOn
            ? "Off"
            : chatOn && !siteOn
              ? "Panel only"
              : chatOn && siteOn
                ? "Panel + optional site"
                : "Site install only (unusual)";
          modeEl.textContent = "Current mode: " + mode;
        }
        if (document.getElementById("mra-enabled")) {
          document.getElementById("mra-enabled").checked = !!s.plugin_enabled;
        }
        if (s.visibility) {
          document.getElementById("mra-visibility").value = s.visibility;
        }
        if (document.getElementById("mra-packages")) {
          document.getElementById("mra-packages").value = s.package_ids || "";
        }
        if (document.getElementById("mra-allow-user-keys")) {
          document.getElementById("mra-allow-user-keys").checked = !!s.allow_user_keys;
        }
        if (s.default_provider) {
          document.getElementById("mra-default-provider").value = s.default_provider;
        }
        setNum("mra-rate", s.rate_limit_per_hour);
        setNum("mra-max-msg", s.max_message_length);
        setNum("mra-max-tokens", s.max_tokens_per_reply);
        setNum("mra-concurrent", s.concurrent_requests);
        setNum("mra-max-upload", s.max_upload_bytes);
        setNum("mra-max-history", s.max_history_messages);
        setNum("mra-max-convs", s.max_stored_conversations);
        setNum("mra-retention", s.chat_retention_days);
        setNum("mra-disk-mb", s.max_chat_disk_mb);
        setNum("mra-local-timeout", s.local_timeout_seconds);
        setNum("mra-local-max-bytes", s.local_max_response_bytes);
        if (document.getElementById("mra-custom-base")) {
          document.getElementById("mra-custom-base").value = s.custom_base_url || "";
        }
        if (document.getElementById("mra-local-base")) {
          document.getElementById("mra-local-base").value =
            s.local_base_url || "http://127.0.0.1:11434/v1";
        }
        if (document.getElementById("mra-local-model")) {
          document.getElementById("mra-local-model").value = s.local_model || "llama3.2:1b";
        }
        var diskEl = document.getElementById("mra-disk-usage");
        if (diskEl) {
          diskEl.textContent =
            "Chat log disk usage: " +
            (disk.chat_disk_mb != null ? disk.chat_disk_mb : 0) +
            " MB (cap " +
            (s.max_chat_disk_mb || 50) +
            " MB)";
        }
        if (me.local_base_url && document.getElementById("mra-local-base")) {
          document.getElementById("mra-local-base").value = me.local_base_url;
        }
        if (me.local_model && document.getElementById("mra-local-model")) {
          document.getElementById("mra-local-model").value = me.local_model;
        }
        if (document.getElementById("mra-local-only")) {
          document.getElementById("mra-local-only").checked = !!me.local_only_mode;
        }
        if (document.getElementById("mra-local-lan")) {
          document.getElementById("mra-local-lan").checked = !!me.local_allow_lan;
        }
      })
      .catch(function () {
        api("?api=me")
          .then(function (me) {
            if (me.default_provider) {
              document.getElementById("mra-default-provider").value = me.default_provider;
            }
          })
          .catch(function () {});
      });
    ownerForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      api("?api=owner-settings", {
        method: "POST",
        body: {
          allow_host_chat: !!(
            document.getElementById("mra-allow-host-chat") &&
            document.getElementById("mra-allow-host-chat").checked
          ),
          allow_site_install: !!(
            document.getElementById("mra-allow-site-install") &&
            document.getElementById("mra-allow-site-install").checked
          ),
          plugin_enabled: document.getElementById("mra-enabled").checked,
          visibility: document.getElementById("mra-visibility").value,
          package_ids: document.getElementById("mra-packages").value,
          allow_user_keys: document.getElementById("mra-allow-user-keys").checked,
          default_provider: document.getElementById("mra-default-provider").value,
          rate_limit_per_hour: parseInt(document.getElementById("mra-rate").value, 10) || 60,
          max_message_length: parseInt(document.getElementById("mra-max-msg").value, 10) || 4000,
          max_tokens_per_reply: parseInt(document.getElementById("mra-max-tokens").value, 10) || 1024,
          concurrent_requests: parseInt(document.getElementById("mra-concurrent").value, 10) || 2,
          max_upload_bytes: parseInt(document.getElementById("mra-max-upload").value, 10) || 262144,
          max_history_messages: parseInt(document.getElementById("mra-max-history").value, 10) || 100,
          max_stored_conversations: parseInt(document.getElementById("mra-max-convs").value, 10) || 200,
          chat_retention_days: parseInt(document.getElementById("mra-retention").value, 10) || 30,
          max_chat_disk_mb: parseInt(document.getElementById("mra-disk-mb").value, 10) || 50,
          local_timeout_seconds: parseInt(document.getElementById("mra-local-timeout").value, 10) || 45,
          local_max_response_bytes:
            parseInt(document.getElementById("mra-local-max-bytes").value, 10) || 1048576,
          custom_base_url: document.getElementById("mra-custom-base").value,
          local_base_url: document.getElementById("mra-local-base").value,
          local_model: document.getElementById("mra-local-model").value,
          local_only_mode: !!(document.getElementById("mra-local-only") &&
            document.getElementById("mra-local-only").checked),
          local_allow_lan: !!(document.getElementById("mra-local-lan") &&
            document.getElementById("mra-local-lan").checked),
          access_password: document.getElementById("mra-access-password").value,
          csrf: csrf(),
        },
      })
        .then(function () {
          alert("Owner settings saved");
          location.reload();
        })
        .catch(function (err) {
          alert((err && err.message) || "Save failed");
        });
    });
  }
})();
