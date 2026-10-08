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
    api("?api=me")
      .then(function (me) {
        if (me.default_provider) {
          document.getElementById("mra-default-provider").value = me.default_provider;
        }
      })
      .catch(function () {});
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
          custom_base_url: document.getElementById("mra-custom-base").value,
          local_base_url: document.getElementById("mra-local-base").value,
          local_model: document.getElementById("mra-local-model").value,
          access_password: document.getElementById("mra-access-password").value,
          csrf: csrf(),
        },
      })
        .then(function () {
          alert("Owner settings saved");
        })
        .catch(function (err) {
          alert((err && err.message) || "Save failed");
        });
    });
  }
})();
