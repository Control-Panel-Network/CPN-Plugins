/**
 * Mr Agent floating chat bubble for CPN Panel chrome.
 * Chat MUST go through panel /plugins/float-chat (same-origin). Never hit site /mr-agent.
 */
(function () {
  "use strict";

  var cfg = window.CPN_PLUGIN_FLOAT;
  if (!cfg || !Array.isArray(cfg.widgets)) {
    return;
  }

  var widget = null;
  for (var i = 0; i < cfg.widgets.length; i++) {
    if (cfg.widgets[i] && String(cfg.widgets[i].id).toLowerCase() === "mragent") {
      widget = cfg.widgets[i];
      break;
    }
  }
  if (!widget || document.getElementById("mra-float-root")) {
    return;
  }

  function panelChatUrl() {
    var domain = encodeURIComponent(widget.domain || "");
    var id = encodeURIComponent(widget.id || "mrAgent");
    var fromCfg = (widget.chatUrl || "").trim();
    if (fromCfg.indexOf("/plugins/float-chat") === 0) {
      return fromCfg;
    }
    if (fromCfg.indexOf("plugins/float-chat") === 0) {
      return "/" + fromCfg;
    }
    return "/plugins/float-chat?domain=" + domain + "&id=" + id;
  }

  function el(tag, attrs, children) {
    var node = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === "text") {
          node.textContent = attrs[k];
        } else if (k === "className") {
          node.className = attrs[k];
        } else if (attrs[k] !== undefined && attrs[k] !== null) {
          node.setAttribute(k, attrs[k]);
        }
      });
    }
    (children || []).forEach(function (c) {
      if (c) node.appendChild(c);
    });
    return node;
  }

  var root = el("div", { id: "mra-float-root", role: "region", "aria-label": "Mr Agent chat" });
  var btn = el("button", {
    id: "mra-float-btn",
    type: "button",
    "aria-expanded": "false",
    "aria-controls": "mra-float-panel",
    title: "Open Mr Agent",
    text: "Mr A",
  });
  var panel = el("div", { id: "mra-float-panel", role: "dialog", "aria-label": "Mr Agent" });
  var headTitle = el("div", {}, [
    el("strong", { text: "Mr Agent" }),
    el("span", { className: "mra-meta", text: widget.domain || "" }),
  ]);
  var expand = el("a", {
    href: widget.expandUrl || "#",
    target: "_blank",
    rel: "noopener noreferrer",
    text: "Expand",
    title: "Open full Mr Agent",
  });
  var closeBtn = el("button", { type: "button", text: "Close", title: "Close" });
  var head = el("div", { className: "mra-float-head" }, [
    headTitle,
    el("div", { className: "mra-float-actions" }, [expand, closeBtn]),
  ]);
  var log = el("div", { id: "mra-float-log" });
  log.appendChild(
    el("div", {
      className: "mra-float-msg assistant",
      text:
        "Ask about CPN menus, or general chat if a local model is configured on this server. " +
        "Free helper = CPN navigation. Local LLM / cloud keys = text generation. MCP skills = panel tools.",
    })
  );
  var input = el("input", {
    type: "text",
    id: "mra-float-input",
    placeholder: "Ask Mr Agent…",
    autocomplete: "off",
    maxlength: "8000",
  });
  var send = el("button", { type: "submit", text: "Send" });
  var form = el("form", { className: "mra-float-form" }, [input, send]);

  panel.appendChild(head);
  panel.appendChild(log);
  panel.appendChild(form);
  root.appendChild(panel);
  root.appendChild(btn);
  document.body.appendChild(root);

  function setOpen(open) {
    root.classList.toggle("is-open", open);
    btn.setAttribute("aria-expanded", open ? "true" : "false");
    if (open) input.focus();
  }

  btn.addEventListener("click", function () {
    setOpen(!root.classList.contains("is-open"));
  });
  closeBtn.addEventListener("click", function () {
    setOpen(false);
  });

  function addMsg(role, text) {
    log.appendChild(el("div", { className: "mra-float-msg " + role, text: text || "" }));
    log.scrollTop = log.scrollHeight;
  }

  form.addEventListener("submit", function (ev) {
    ev.preventDefault();
    var message = (input.value || "").trim();
    if (!message) return;
    addMsg("user", message);
    input.value = "";
    send.disabled = true;
    fetch(panelChatUrl(), {
      method: "POST",
      credentials: "same-origin",
      headers: { Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify({ message: message, provider: "auto", model: "" }),
    })
      .then(function (res) {
        return res.text().then(function (raw) {
          var data = {};
          try {
            data = raw ? JSON.parse(raw) : {};
          } catch (e) {
            throw new Error(
              res.ok
                ? "Invalid JSON from float-chat"
                : "Chat proxy HTTP " + res.status + " (update panel float-chat route)"
            );
          }
          if (!res.ok || data.ok === false) {
            throw new Error((data && data.error) || "Chat failed (HTTP " + res.status + ")");
          }
          return data;
        });
      })
      .then(function (data) {
        addMsg("assistant", data.reply || "(empty reply)");
      })
      .catch(function (err) {
        var msg = (err && err.message) || "Chat failed";
        if (msg === "Failed to fetch" || /NetworkError|Load failed/i.test(msg)) {
          msg =
            "Failed to reach panel float-chat proxy. Use relative /plugins/float-chat " +
            "(not the site /mr-agent URL). Refresh after updating the panel build.";
        }
        addMsg("err", msg);
      })
      .then(function () {
        send.disabled = false;
        input.focus();
      });
  });
})();
