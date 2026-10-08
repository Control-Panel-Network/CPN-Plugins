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

  var ownerTabs = document.getElementById("mra-owner-tabs");
  if (ownerTabs) {
    function activateOwnerTab(id, push) {
      var tabs = [].slice.call(ownerTabs.querySelectorAll("[data-mra-tab]"));
      var panels = [].slice.call(ownerTabs.querySelectorAll("[data-mra-panel]"));
      var known = tabs.some(function (t) {
        return t.getAttribute("data-mra-tab") === id;
      });
      if (!known) id = "general";
      tabs.forEach(function (btn) {
        var on = btn.getAttribute("data-mra-tab") === id;
        btn.setAttribute("aria-selected", on ? "true" : "false");
      });
      panels.forEach(function (p) {
        var on = p.getAttribute("data-mra-panel") === id;
        if (on) p.removeAttribute("hidden");
        else p.setAttribute("hidden", "hidden");
      });
      if (push) {
        try {
          var u = new URL(window.location.href);
          u.searchParams.set("tab", id);
          history.replaceState(null, "", u.toString());
        } catch (e) {}
      }
      if (id === "statistics") loadOwnerStats();
    }
    function loadOwnerStats() {
      var box = document.getElementById("mra-stats-body");
      if (!box || box.getAttribute("data-loaded") === "1") return;
      box.textContent = "Loading statistics…";
      api("?api=stats")
        .then(function (s) {
          if (s.empty) {
            box.innerHTML =
              '<p class="muted">No chat activity yet. Statistics appear after the first conversation.</p>';
          } else {
            function card(label, value) {
              return (
                '<div class="mra-stat"><div class="label">' +
                label +
                '</div><div class="value">' +
                value +
                "</div></div>"
              );
            }
            var html = '<div class="mra-stats-grid">';
            html += card("Conversations", String(s.conversations_total || 0));
            html += card("Messages", String(s.messages_total || 0));
            html += card("Conversations (7d)", String(s.conversations_7d || 0));
            html += card("Messages (7d)", String(s.messages_7d || 0));
            html += card("Conversations (30d)", String(s.conversations_30d || 0));
            html += card("Messages (30d)", String(s.messages_30d || 0));
            html += card("Distinct users", String(s.distinct_users || 0));
            html += card(
              "Storage",
              (s.storage_mb != null ? s.storage_mb : 0) + " / " + (s.storage_limit_mb || 50) + " MB"
            );
            html += card("Last activity", s.last_activity || "n/a");
            html += "</div>";
            html +=
              '<p class="muted" style="margin-top:12px;">Privacy-safe counts only. Message bodies and secrets are never shown.</p>';
            box.innerHTML = html;
          }
          box.setAttribute("data-loaded", "1");
        })
        .catch(function (err) {
          box.textContent = (err && err.message) || "Could not load statistics";
        });
    }
    ownerTabs.addEventListener("click", function (ev) {
      var btn = ev.target.closest("[data-mra-tab]");
      if (!btn || !ownerTabs.contains(btn)) return;
      activateOwnerTab(btn.getAttribute("data-mra-tab"), true);
    });
    var initial = ownerTabs.getAttribute("data-initial-tab") || "general";
    activateOwnerTab(initial, false);
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
        var hpEl = document.getElementById("mra-host-policy");
        if (hpEl) {
          hpEl.textContent =
            "Allow host chat: " +
            (hp.allow_host_chat ? "On" : "Off") +
            " · Allow site install: " +
            (hp.allow_site_install ? "On" : "Off");
        }
        if (document.getElementById("mra-local-only")) {
          document.getElementById("mra-local-only").checked = !!s.local_only_mode;
        }
        if (document.getElementById("mra-local-lan")) {
          document.getElementById("mra-local-lan").checked = !!s.local_allow_lan;
        }
      })
      .catch(function () {
        api("?api=me")
          .then(function (meRes) {
            if (meRes.default_provider) {
              document.getElementById("mra-default-provider").value = meRes.default_provider;
            }
          })
          .catch(function () {});
      });
    ownerForm.addEventListener("submit", function (ev) {
      ev.preventDefault();
      api("?api=owner-settings", {
        method: "POST",
        body: {
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
