# BIMI (Brand Indicators for Message Identification)

Free CPN Panel catalog plugin. Installing it unlocks **Email → BIMI** (sidebar and hub tile). Logo URL, recommended DNS, and optional Cloudflare push stay in the panel; this package is the enablement gate.

## Install

```bash
# Plugin Store, or:
sudo cpn plugin install --domain example.com --id bimi

# Optional host flag (also written automatically by newer panel installs):
sudo /home/example.com/plugins/bimi/install-host.sh
```

## Verify

- Sidebar: Email → BIMI appears
- Open `https://<panel>/email/bimi` and save logo URL / DNS for a domain

## Uninstall

```bash
sudo /home/example.com/plugins/bimi/uninstall-host.sh
sudo cpn plugin remove --domain example.com --id bimi --yes
```
